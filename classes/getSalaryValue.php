<?php
//load_data.php  
$output = '';
require_once('../Connections/paymaster.php');

mysqli_select_db($salary, $database_salary);
$code = $_POST['code'];
if ($code == 1) {
     if (isset($_POST["grade_level"])) {



          if ($_POST['newearningcode'] == 21) {
               $sql = "SELECT ifnull(allowancetable.`value`,0) as `value`  FROM allowancetable WHERE allowancetable.grade = '" . $_POST['grade_level'] . "' AND allowancetable.step = '" . $_POST['step'] . "' AND allowcode = " . $_POST['newearningcode'] . " AND category = '" . $_POST['callType'] . "'";
          } elseif ($_POST['newearningcode'] == 5) {
               $sql = "SELECT ifnull(allowancetable.`value`,0) as `value`  FROM allowancetable WHERE allowancetable.grade = '" . $_POST['grade_level'] . "' AND allowancetable.step = '" . $_POST['step'] . "' AND allowcode = " . $_POST['newearningcode'] . " AND category = '" . $_POST['HARZAD_TYPE'] . "'";
          } else {
               $sql = "SELECT ifnull(allowancetable.`value`,0) as `value` FROM allowancetable WHERE allowancetable.grade = '" . $_POST['grade_level'] . "' AND allowancetable.step = '" . $_POST['step'] . "' AND allowcode = " . $_POST['newearningcode'] . "";
          }



          $result = mysqli_query($salary, $sql);
          $row = mysqli_fetch_assoc($result);
          $total_row = mysqli_num_rows($result);


          if ($total_row > 0) {
               echo  $output = number_format($row['value']);
          } else {
               echo "manual";
          }
     }
} elseif ($code == 2) {
     if ($_POST['newearningcode'] == 50) {

          $sql_consolidated = "SELECT allowancetable.`value` FROM allowancetable WHERE allowancetable.allowcode = 1 and grade = '" . $_POST['grade_level'] . "' and step = '" . $_POST['step'] . "'";
          $result_consolidated = mysqli_query($salary, $sql_consolidated);
          $row_consolidated = mysqli_fetch_assoc($result_consolidated);
          $total_rowsConsolidated = mysqli_num_rows($result_consolidated);

          $sql_pensionRate = "SELECT rate as rate FROM pension";
          $result_pensionRate = mysqli_query($salary, $sql_pensionRate);
          $row_pensionRate = mysqli_fetch_assoc($result_pensionRate);
          $total_pensionRate = mysqli_num_rows($result_pensionRate);
          if ($total_pensionRate > 0) {
               $output = ceil($row_consolidated['value'] * $row_pensionRate['rate']);
               echo $output;
          } else {
               echo "manual";
          }
     } else {
          echo "manual";
     }
} elseif ($code == 3) {
     if (isset($_POST['grade_level'])) {


          $sql_numberOfRows = "SELECT deductiontable.ded_id, deductiontable.allowcode, deductiontable.grade, deductiontable.step, deductiontable.`value`, deductiontable.category, deductiontable.ratetype, deductiontable.percentage FROM deductiontable WHERE allowcode = '" . $_POST['newearningcode'] . "'";
          $result_numberOfRows = mysqli_query($salary, $sql_numberOfRows);
          $row_numberOfRows = mysqli_fetch_assoc($result_numberOfRows);
          $total_rows = mysqli_num_rows($result_numberOfRows);

          if ($total_rows == 1) {
               if ($row_numberOfRows['ratetype'] == 1) {
                    $output = $row_numberOfRows['value'];
                    echo $output;
               } else {
                    $sql_consolidated = "SELECT allowancetable.allow_id, allowancetable.allowcode, allowancetable.grade, allowancetable.step, allowancetable.`value`, allowancetable.category, allowancetable.ratetype, allowancetable.percentage FROM allowancetable WHERE allowancetable.allowcode = 1 and grade = '" . $_POST['grade_level'] . "' and step = '" . $_POST['step'] . "'";
                    $result_consolidated = mysqli_query($salary, $sql_consolidated);
                    $row_consolidated = mysqli_fetch_assoc($result_consolidated);
                    $total_rowsConsolidated = mysqli_num_rows($result_consolidated);
                    $output = ($row_numberOfRows['percentage'] * $row_consolidated['value']) / 100;
                    echo $output;
               }
          } else if ($total_rows > 1) {
               $sql_mulitple = "SELECT deductiontable.ded_id, deductiontable.allowcode, deductiontable.grade, deductiontable.step, deductiontable.`value`, deductiontable.category, deductiontable.ratetype, deductiontable.percentage FROM deductiontable WHERE allowcode = '" . $_POST['newearningcode'] . "' and grade = '" . $_POST['grade_level'] . "'";
               $result_mulitple = mysqli_query($salary, $sql_mulitple);
               $row_mulitple = mysqli_fetch_assoc($result_mulitple);
               $total_mulitple = mysqli_num_rows($result_mulitple);
               if ($total_mulitple > 0) {
                    if ($row_numberOfRows['ratetype'] == 1) {
                         $output = $row_mulitple['value'];
                         //echo $sql_mulitple ; 
                    } else {
                         $sql_consolidated = "SELECT allowancetable.allow_id, allowancetable.allowcode, allowancetable.grade, allowancetable.step, allowancetable.`value`, allowancetable.category, allowancetable.ratetype, allowancetable.percentage FROM allowancetable WHERE allowancetable.allowcode = 1 and grade = '" . $_POST['grade_level'] . "' and step = '" . $_POST['step'] . "'";
                         $result_consolidated = mysqli_query($salary, $sql_consolidated);
                         $row_consolidated = mysqli_fetch_assoc($result_consolidated);
                         $total_rowsConsolidated = mysqli_num_rows($result_consolidated);
                         if ($total_rowsConsolidated  > 0) {
                              $output = ceil(($row_mulitple['percentage'] * $row_consolidated['value']) / 100);
                              echo $output;
                         } else {
                              echo "manual";
                         }
                    }
               } else {
                    echo 'manual';
               }
          } else if ($total_rows == 0) {

               echo 'manual';
          }
     }
}