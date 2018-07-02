<?php
// PUGS Database CLASS
namespace Pugs;

use PDO;
{
  class Database
  {
      public $conn;
      public $db;
      private $user;
      private $pass;
      private $host;

      public function __construct($ENV)
      {
          if (file_exists(__dir__.'/main.php')) {
            include_once __dir__.'/main.php';
          } else {
            include_once '/opt/includes/main.php';
          }
          $this->user = JAYNE_DB_USER;
          $this->pass = JAYNE_DB_PASS;
          $this->host = JAYNE_DB_HOST;

          switch ($ENV) {
              case "dev":
              $this->db = JAYNE_DB_DEV;
              break;

              case "live":
              $this->db = JAYNE_DB_LIVE;
              break;
          }

          try {
              $this->conn = new PDO(
                  $this->host . $this->db,
                  $this->user,
                  $this->pass,
                  [   PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                      PDO::ATTR_EMULATE_PREPARES => false
                  ]
              );
          } catch (PDOException $e) {
              die('Connection failed: '.$e->getMessage());
          }
      }

      /**
      * Returns leaderboard under spesificed conditions
      *
      * @param string  $mode  Returns the leaderboard under spesific scenarios
      * @return string  Return leaderboard
      */
      public function getLeaderboard($mode)
      {
      	$this->mode = $mode;
          $col = "@curRank := @curRank + 1 AS rank, name, rating, wins, losses, draws";
          switch ($this->mode) {
              case "top10":
              $do = $this->conn->prepare(
                  "SELECT $col FROM `Main` m, (SELECT @curRank := 0) r ".
                  "WHERE (wins+losses+draws) > 0 ORDER by `rating` DESC LIMIT 10"
              );
              break;
              case "most-played":
              $do = $this->conn->prepare(
                  "SELECT $col FROM `Main` m, (SELECT @curRank := 0) r ".
                  "WHERE (wins+losses+draws) > 0 ORDER by (wins+losses+draws)"
              );
              break;
              default:
              $do = $this->conn->prepare(
                  "SELECT $col FROM `Main` m, (SELECT @curRank := 0) r ".
                  "WHERE (wins+losses+draws) > 0 ORDER by `rating` DESC"
              );
          }

          /*
          if ($this->top10) {
          $col = "@curRank := @curRank + 1 AS rank, name, rating, wins, losses, draws";
          $do = $this->conn->prepare(
          "SELECT $col FROM `Main` m, (SELECT @curRank := 0) r ".
          "WHERE (wins+losses+draws) > 0 ORDER by `rating` DESC LIMIT 10"
          );
          } else {
          $col = "@curRank := @curRank + 1 AS rank, name, rating, wins, losses, draws";
          $do = $this->conn->prepare(
          "SELECT $col FROM `Main` m, (SELECT @curRank := 0) r ".
          "WHERE (wins+losses+draws) > 0 ORDER by `rating` DESC"
          );
          */

          $do->execute();
          $rank = 0;
          $this->resp = "";
          while ($row = $do->fetch(PDO::FETCH_ASSOC)) {
              $rank += 1;
              $total   = $row["wins"] + $row["losses"] + $row["draws"];
              $winrate = round(100 * $row["wins"] / ($total), 1);
              $winrate = (is_nan($winrate) ? 0 : $winrate);
              $this->resp .= "<tr>";
              $this->resp .= "\t<th scope=\"row\">#".$row["rank"]."</th>";
              $this->resp .= "\t<td>".$row["name"]."</td>";
              $this->resp .= "\t<td>".$row["rating"]."</td>";
              $this->resp .= "\t<td>".$row["wins"]."</td>";
              $this->resp .= "\t<td>".$row["losses"]."</td>";
              $this->resp .= "\t<td>".$row["draws"]."</td>";
              $this->resp .= "\t<td>$winrate%</td>";
              $this->resp .= "\t<td>".$total."</td>";
              $this->resp .= "</tr>";
          }
          return $this->resp;
      }
  }
}
