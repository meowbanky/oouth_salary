<?php require_once('Connections/paymaster.php');
include_once('classes/model.php');
include_once('classes/function_salary.php');
session_start();
if (!function_exists("GetSQLValueString")) {
    function GetSQLValueString($theValue, $theType, $theDefinedValue = "", $theNotDefinedValue = "")
    {

        global $salary;

        $theValue = function_exists("mysql_real_escape_string") ? mysqli_real_escape_string($salary, $theValue) : mysqli_escape_string($salary, $theValue);

        switch ($theType) {
            case "text":
                $theValue = ($theValue != "") ? "'" . $theValue . "'" : "NULL";
                break;
            case "long":
            case "int":
                $theValue = ($theValue != "") ? intval($theValue) : "NULL";
                break;
            case "double":
                $theValue = ($theValue != "") ? doubleval($theValue) : "NULL";
                break;
            case "date":
                $theValue = ($theValue != "") ? "'" . $theValue . "'" : "NULL";
                break;
            case "defined":
                $theValue = ($theValue != "") ? $theDefinedValue : $theNotDefinedValue;
                break;
        }
        return $theValue;
    }
}


if ((isset($_POST['groupUnion'])) and (!isset($_POST['saveForm']))) {
    $groupUnion = intval($_POST['groupUnion']);


    mysqli_select_db($salary, $database_salary);
    $selectSql = sprintf(
        "SELECT employee.`NAME`,employee.staff_id FROM allow_deduc INNER JOIN employee ON allow_deduc.staff_id = employee.staff_id WHERE allow_id = %s and STATUSCD = %s",
        GetSQLValueString($groupUnion, "int"),
        GetSQLValueString('A', "text")
    );
    $result = mysqli_query($salary, $selectSql) or die(mysqli_error($salary));
    $row = mysqli_fetch_assoc($result);
    $total_result = mysqli_num_rows($result);
} elseif ((isset($_POST['groupDept'])) and (!isset($_POST['saveForm']))) {
    $groupDept = intval($_POST['groupDept']);

    mysqli_select_db($salary, $database_salary);
    $selectSql = sprintf(
        "SELECT employee.`NAME`,employee.staff_id FROM employee WHERE DEPTCD = %s and STATUSCD = %s",
        GetSQLValueString($groupDept, "int"),
        GetSQLValueString('A', "text")
    );
    $result = mysqli_query($salary, $selectSql) or die(mysqli_error($salary));
    $row = mysqli_fetch_assoc($result);
    $total_result = mysqli_num_rows($result);
}



if (isset($_POST['criteria']) and $_POST['criteria'] == 1) {
    if ($_POST['stop_allow'] == 1) {
        $deleteAllow_Deduct =  sprintf(
            'Delete from allow_deduc where staff_id IN (SELECT employee.staff_id FROM employee WHERE DEPTCD = %s AND STATUSCD = %s) and allow_id = %s ',
            GetSQLValueString($_POST['groupDept'], "text"),
            GetSQLValueString('A', "text"),
            GetSQLValueString($_POST['deduction'], "int")
        );
        $ResultDelete = mysqli_query($salary, $deleteAllow_Deduct) or die(mysqli_error($salary));
    } else {
        $sql_dept = sprintf(
            "SELECT employee.staff_id FROM employee WHERE DEPTCD = %s AND STATUSCD = %s",
            GetSQLValueString($_POST['groupDept'], "text"),
            GetSQLValueString('A', "text")
        );
        $result_dept = mysqli_query($salary, $sql_dept) or die(mysqli_error($salary));
        $row_dept = mysqli_fetch_assoc($result_dept);
        $total_result = mysqli_num_rows($result_dept);
        do {
            $sqlcheck = "SELECT allow_deduc.staff_id, allow_deduc.allow_id FROM allow_deduc WHERE staff_id = {$row_dept['staff_id']} and allow_id = {$_POST['deduction']}";
            $result_check = mysqli_query($salary, $sqlcheck);
            $rowcheck_check = mysqli_fetch_assoc($result_check);
            $total_check = mysqli_num_rows($result_check);
            if ($total_check > 0) {
                $output = getSalary($_POST['code'], $row_dept['staff_id'], $_POST['deduction'], $_POST['amount']);
                if (strpos($output, ',') == true) {
                    $output = str_replace(',', '', $output);
                } else {
                    $output = $output;
                }
                $updateSQL = sprintf(
                    "UPDATE allow_deduc SET allow_deduc.`value` = %s,allow_deduc.counter = %s,inserted_by = %s,date_insert = now() where allow_deduc.staff_id = %s AND allow_deduc.allow_id = %s",
                    GetSQLValueString($output, "float"),
                    GetSQLValueString($_POST['runningPeriod'], "int"),
                    GetSQLValueString($_SESSION['SESS_MEMBER_ID'], "text"),
                    GetSQLValueString($row_dept['staff_id'], "text"),
                    GetSQLValueString($_POST['deduction'], "int")
                );
                $Result_dept = mysqli_query($salary, $updateSQL) or die(mysqli_error($salary));
            } else {
                $output = getSalary($_POST['code'], $row_dept['staff_id'], $_POST['deduction'], $_POST['amount']);
                if (strpos($output, ',') == true) {
                    $output = str_replace(',', '', $output);
                } else {
                    $output = $output;
                }
                $getCodeSql =  sprintf(
                    "select code from tbl_earning_deduction where ed_id = %s",
                    GetSQLValueString($_POST['deduction'], "int")
                );
                $query_getCode = mysqli_query($salary, $getCodeSql) or die(mysqli_error($salary));
                $row_getCode = mysqli_fetch_assoc($query_getCode);
                $totalRows_getCode = mysqli_num_rows($query_getCode);
                if ($totalRows_getCode > 0) {
                    $code = $row_getCode['code'];
                }

                $insertSQL = sprintf(
                    "INSERT INTO allow_deduc (allow_deduc.staff_id,allow_deduc.allow_id,allow_deduc.`value`,allow_deduc.transcode,allow_deduc.counter,inserted_by,date_insert) VALUES (%s,%s,%s,%s,%s,%s,now())",
                    GetSQLValueString($row_dept['staff_id'], "text"),
                    GetSQLValueString($_POST['deduction'], "int"),
                    GetSQLValueString($output, "float"),
                    GetSQLValueString($code, "int"),
                    GetSQLValueString($_POST['runningPeriod'], "int"),
                    GetSQLValueString($_SESSION['SESS_MEMBER_ID'], "text")
                );


                $Result_insert = mysqli_query($salary, $insertSQL) or die(mysqli_error($salary));
            }
        } while ($row_dept = mysqli_fetch_assoc($result_dept));
    }
} elseif (isset($_POST['criteria']) and $_POST['criteria'] == 0) {

    if ($_POST['stop_allow'] == 1) {
        $selectSql = sprintf(
            "SELECT employee.`NAME`,employee.staff_id FROM allow_deduc INNER JOIN employee ON allow_deduc.staff_id = employee.staff_id WHERE allow_id = %s and STATUSCD = %s",
            GetSQLValueString($_POST['groupUnion'], "int"),
            GetSQLValueString('A', "text")
        );
        $result = mysqli_query($salary, $selectSql) or die(mysqli_error($salary));
        $row = mysqli_fetch_assoc($result);
        $total_result = mysqli_num_rows($result);
        do {
            $deleteAllow_Deduct =  sprintf(
                'Delete from allow_deduc where staff_id IN (SELECT employee.staff_id FROM employee WHERE staff_id = %s) and allow_id = %s ',
                GetSQLValueString($row['staff_id'], "text"),
                GetSQLValueString($_POST['deduction'], "int")
            );

            $Result1 = mysqli_query($salary, $deleteAllow_Deduct) or die(mysqli_error($salary));
        } while ($row = mysqli_fetch_assoc($result));
    } else {
        $i = 0;
        $selectSql10 = sprintf(
            "SELECT employee.`NAME`,employee.staff_id FROM allow_deduc INNER JOIN employee ON allow_deduc.staff_id = employee.staff_id WHERE allow_id = %s and STATUSCD = %s",
            GetSQLValueString($_POST['groupUnion'], "int"),
            GetSQLValueString('A', "text")
        );
        $result10 = mysqli_query($salary, $selectSql10) or die(mysqli_error($salary));
        $row10 = mysqli_fetch_assoc($result10);
        $total_result10 = mysqli_num_rows($result10);
        if ($total_result10 > 0) {
            do {

                $sqlcheck = "SELECT allow_deduc.staff_id, allow_deduc.allow_id FROM allow_deduc WHERE staff_id = {$row10['staff_id']} and allow_id = {$_POST['deduction']}";
                $result = mysqli_query($salary, $sqlcheck);
                $rowcheck = mysqli_fetch_assoc($result);
                $totalcheck = mysqli_num_rows($result);
                if ($totalcheck > 0) {
                    $output = getSalary($_POST['code'], $row10['staff_id'], $_POST['deduction'], $_POST['amount']);
                    if (strpos($output, ',') == true) {
                        $output = str_replace(',', '', $output);
                    } else {
                        $output = $output;
                    }
                    $updateSQL = sprintf(
                        "UPDATE allow_deduc SET allow_deduc.`value` = %s,allow_deduc.counter = %s,inserted_by = %s,date_insert = now() where allow_deduc.staff_id = %s AND allow_deduc.allow_id = %s",
                        GetSQLValueString($output, "float"),
                        GetSQLValueString($_POST['runningPeriod'], "int"),
                        GetSQLValueString($_SESSION['SESS_MEMBER_ID'], "text"),
                        GetSQLValueString($row10['staff_id'], "text"),
                        GetSQLValueString($_POST['deduction'], "int")
                    );

                    $Result1 = mysqli_query($salary, $updateSQL) or die(mysqli_error($salary));
                } else {

                    $output = getSalary($_POST['code'], $row10['staff_id'], $_POST['deduction'], $_POST['amount']);

                    if (strpos($output, ',') == true) {
                        $output = str_replace(',', '', $output);
                    } else {
                        $output = $output;
                    }


                    $getCodeSql =  sprintf(
                        "select code from tbl_earning_deduction where ed_id = %s",
                        GetSQLValueString($_POST['deduction'], "int")
                    );
                    $query_getCode = mysqli_query($salary, $getCodeSql) or die(mysqli_error($salary));
                    $row_getCode = mysqli_fetch_assoc($query_getCode);
                    $totalRows_getCode = mysqli_num_rows($query_getCode);
                    if ($totalRows_getCode > 0) {
                        $code = $row_getCode['code'];
                    }

                    $insertSQL = sprintf(
                        "INSERT INTO allow_deduc (allow_deduc.staff_id,allow_deduc.allow_id,allow_deduc.`value`,allow_deduc.transcode,allow_deduc.counter,inserted_by,date_insert) VALUES (%s,%s,%s,%s,%s,%s,now())",
                        GetSQLValueString($row10['staff_id'], "text"),
                        GetSQLValueString($_POST['deduction'], "int"),
                        GetSQLValueString($output, "float"),
                        GetSQLValueString($code, "int"),
                        GetSQLValueString($_POST['runningPeriod'], "int"),
                        GetSQLValueString($_SESSION['SESS_MEMBER_ID'], "text")
                    );


                    $Result1 = mysqli_query($salary, $insertSQL) or die(mysqli_error($salary));
                }
                $i++;
            } while ($row10 = mysqli_fetch_assoc($result10));
        }
    }
}


// if ((isset($_POST['groupUnion'])) and (!isset($_POST['saveForm']))) {
//     $groupUnion = intval($_POST['groupUnion']);


//     mysqli_select_db($salary, $database_salary);
//     $selectSql = sprintf(
//         "SELECT employee.`NAME`,employee.staff_id FROM allow_deduc INNER JOIN employee ON allow_deduc.staff_id = employee.staff_id WHERE allow_id = %s and STATUSCD = %s",
//         GetSQLValueString($groupUnion, "int"),
//         GetSQLValueString('A', "text")
//     );
//     $result = mysqli_query($salary, $selectSql) or die(mysqli_error($salary));
//     $row = mysqli_fetch_assoc($result);
//     $total_result = mysqli_num_rows($result);
// } elseif ((isset($_POST['groupDept'])) and (!isset($_POST['saveForm']))) {
//     $groupDept = intval($_POST['groupDept']);

//     mysqli_select_db($salary, $database_salary);
//     $selectSql = sprintf(
//         "SELECT employee.`NAME`,employee.staff_id FROM employee WHERE DEPTCD = %s and STATUSCD = %s",
//         GetSQLValueString($groupDept, "int"),
//         GetSQLValueString('A', "text")
//     );
//     $result = mysqli_query($salary, $selectSql) or die(mysqli_error($salary));
//     $row = mysqli_fetch_assoc($result);
//     $total_result = mysqli_num_rows($result);
// }

// if (isset($_POST['saveForm'])) {
//     mysqli_select_db($salary, $database_salary);
//     if ($_POST['criteria'] == 1) {
//         $selectSql = sprintf(
//             "SELECT employee.NAME,employee.staff_id FROM  employee WHERE DEPTCD = %s and STATUSCD = %s",
//             GetSQLValueString($_POST['groupDept'], "int"),
//             GetSQLValueString('A', "text")
//         );
//     } else {
//         $selectSql = sprintf(
//             "SELECT employee.NAME,employee.`NAME`,employee.staff_id FROM allow_deduc INNER JOIN employee ON allow_deduc.staff_id = employee.staff_id WHERE allow_id = %s and STATUSCD = %s",
//             GetSQLValueString($_POST['groupUnion'], "int"),
//             GetSQLValueString('A', "text")
//         );
//     }
//     $result = mysqli_query($salary, $selectSql) or die(mysqli_error($salary));
//     $row = mysqli_fetch_assoc($result);
//     $total_result = mysqli_num_rows($result);


//     if ($total_result > 0) {
//         do {
//             if ($_POST['stop_allow'] == 1) {
//                 if ($_POST['criteria'] == 1) {
//                     $deleteAllow_Deduct =  sprintf(
//                         'Delete from allow_deduc where staff_id IN (SELECT employee.staff_id FROM employee WHERE staff_id = %s) and allow_id = %s ',
//                         GetSQLValueString($row['staff_id'], "text"),
//                         GetSQLValueString($_POST['deduction'], "int")
//                     );
//                    
//                 } else {
//                     $deleteAllow_Deduct =  sprintf(
//                         'Delete from allow_deduc where staff_id = %s and allow_id = %s',
//                         GetSQLValueString($row['staff_id'], "text"),
//                         GetSQLValueString($_POST['groupUnion'], "int")
//                     );
//                 }
//                 $Result1 = mysqli_query($salary, $deleteAllow_Deduct) or die(mysqli_error($salary));
//             } else {
//                 $checkSQL =  sprintf(
//                     "select * from allow_deduc where allow_id = %s and staff_id = %s",
//                     GetSQLValueString($_POST['deduction'], "int"),
//                     GetSQLValueString($row['staff_id'], "text")
//                 );
//                 $query_check = mysqli_query($salary, $checkSQL) or die(mysqli_error($salary));
//                 $row_check = mysqli_fetch_assoc($query_check);
//                 $totalRows_check = mysqli_num_rows($query_check);

//                 if ($totalRows_check > 0) {


//                     $updateSQL = sprintf(
//                         "UPDATE allow_deduc SET allow_deduc.`value` = %s,allow_deduc.transcode = %s,allow_deduc.counter = %s,inserted_by = %s,date_insert = now() where allow_deduc.staff_id = %s AND allow_deduc.allow_id = %s",
//                         GetSQLValueString($_POST['amount'], "float"),
//                         GetSQLValueString(2, "int"),
//                         GetSQLValueString($_POST['runningPeriod'], "int"),
//                         GetSQLValueString($_SESSION['SESS_MEMBER_ID'], "text"),
//                         GetSQLValueString($row['staff_id'], "text"),
//                         GetSQLValueString($_POST['deduction'], "int")
//                     );
//                     $Result1 = mysqli_query($salary, $updateSQL) or die(mysqli_error($salary));
//                 } else {

//                     $getCodeSql =  sprintf(
//                         "select code from tbl_earning_deduction where ed_id = %s",
//                         GetSQLValueString($_POST['deduction'], "int")
//                     );
//                     $query_getCode = mysqli_query($salary, $getCodeSql) or die(mysqli_error($salary));
//                     $row_getCode = mysqli_fetch_assoc($query_getCode);
//                     $totalRows_getCode = mysqli_num_rows($query_getCode);
//                     if ($totalRows_getCode > 0) {
//                         $code = $row_getCode['code'];
//                     }

//                     $insertSQL = sprintf(
//                         "INSERT INTO allow_deduc (allow_deduc.staff_id,allow_deduc.allow_id,allow_deduc.`value`,allow_deduc.transcode,allow_deduc.counter,inserted_by,date_insert) VALUES (%s,%s,%s,%s,%s,%s,now())",
//                         GetSQLValueString($row['staff_id'], "text"),
//                         GetSQLValueString($_POST['deduction'], "int"),
//                         GetSQLValueString($_POST['amount'], "text"),
//                         GetSQLValueString($code, "int"),
//                         GetSQLValueString($_POST['runningPeriod'], "int"),
//                         GetSQLValueString($_SESSION['SESS_MEMBER_ID'], "text")
//                     );


//                     $Result1 = mysqli_query($salary, $insertSQL) or die(mysqli_error($salary));
//                 }
//             }
//         } while ($row = mysqli_fetch_assoc($result));
//         // code to execute endwhile;
//     }
// }

if ((isset($_POST['groupUnion']))) {
    $groupUnion = intval($_POST['groupUnion']);


    mysqli_select_db($salary, $database_salary);
    $selectSql = sprintf(
        "SELECT employee.`NAME`,employee.staff_id FROM allow_deduc INNER JOIN employee ON allow_deduc.staff_id = employee.staff_id WHERE allow_id = %s and STATUSCD = %s",
        GetSQLValueString($groupUnion, "int"),
        GetSQLValueString('A', "text")
    );
    $result = mysqli_query($salary, $selectSql) or die(mysqli_error($salary));
    $row = mysqli_fetch_assoc($result);
    $total_result = mysqli_num_rows($result);
} elseif ((isset($_POST['groupDept']))) {
    $groupDept = intval($_POST['groupDept']);

    mysqli_select_db($salary, $database_salary);
    $selectSql = sprintf(
        "SELECT employee.`NAME`,employee.staff_id FROM employee WHERE DEPTCD = %s and STATUSCD = %s",
        GetSQLValueString($groupDept, "int"),
        GetSQLValueString('A', "text")
    );
    $result = mysqli_query($salary, $selectSql) or die(mysqli_error($salary));
    $row = mysqli_fetch_assoc($result);
    $total_result = mysqli_num_rows($result);
}

?>

<div class="table-responsive">
    <table id="register" class="table table-bordered">

        <thead>
            <tr>
                <th class="item_name_heading">S/N</th>
                <th class="item_name_heading">Staff No</th>
                <th class="item_name_heading">Name Total <?php echo $total_result; ?></th>
            </tr>
        </thead>
        <tbody id="cart_contents" class="sa">

            <?php $i = 1;
            if ($total_result > 0) {    ?> <?php do { ?>
                    <tr id="reg_item_top" bgcolor="#eeeeee">
                        <td class="text text-success"><?php echo $i; ?></td>
                        <td class="text text-success"><?php echo $row['staff_id']; ?></td>
                        <td class="text text-success"><?php echo $row['NAME']; ?></td>

                        <?php $i++;
                                            } while ($row = mysqli_fetch_assoc($result)); ?><?php } // Show if recordset not empty 
                                                                                            ?>
                    </tr>