<?php ini_set('max_execution_time', '300');
require_once('Connections/paymaster.php');
include_once('classes/model.php'); ?>
<?php

//Start session
session_start();

//Check whether the session variable SESS_MEMBER_ID is present or not
if (!isset($_SESSION['SESS_MEMBER_ID']) || (trim($_SESSION['SESS_MEMBER_ID']) == '') || $_SESSION['role'] != 'Admin') {
    header("location: index.php");
    exit();
}

if (!function_exists("GetSQLValueString")) {
    function GetSQLValueString($theValue, $theType, $theDefinedValue = "", $theNotDefinedValue = "")
    {

        global $con;
        $theValue = function_exists("mysql_real_escape_string") ? mysqli_real_escape_string($con, $theValue) : mysqli_escape_string($con, $theValue);

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

$currentPage = $_SERVER["PHP_SELF"];






$today = '';
$today = date('Y-m-d');
?>
<!DOCTYPE html>

<html>
<?php include('header1.php'); ?>

<body data-color="grey" class="flat" style="zoom: 1;">
<div class="modal fade hidden-print" id="myModal"></div>
<div id="wrapper">
    <div id="header" class="hidden-print">
        <h1><a href="index.php"><img src="img/header_logo.png" class="hidden-print header-log" id="header-logo" alt=""></a></h1>
        <a id="menu-trigger" href="#"><i class="fa fa-bars fa fa-2x"></i></a>
        <div class="clear"></div>
    </div>

    <?php include('header.php'); ?>


    <?php include('sidebar.php'); ?>



    <div id="content" class="clearfix sales_content_minibar">

        <script type="text/javascript">
            $(document).ready(function() {


            });
        </script>
        <div id="content-header" class="hidden-print">
            <h1>Unauthorized Page</h1>


        </div>


        <div id="breadcrumb" class="hidden-print">
            <a href="home.php"><i class="fa fa-home"></i> Dashboard</a><a class="current" href="#">Unauthorized Page</a>
        </div>
        <div class="clear"></div>
        <div id="datatable_wrapper"></div>


           <main>

                <div class="text-center p-6 bg-red-500">
                    <h1 class="text-2xl font-bold mb-4">Unauthorized Access</h1>
                    <p class="mb-4">You do not have permission to access this page.</p>
                    <a href="index.php" class="text-blue-500 hover:underline">Login</a>
                </div>


        </main>

        <div id="footer" class="col-md-12 hidden-print">
            Please visit our
            <a href="#" target="_blank">
                website		</a>
            to learn the latest information about the project.
            <span class="text-info"> <span class="label label-info"> 14.1</span></span>
        </div>

    </div><!--end #content-->
</div><!--end #wrapper-->


<ul class="ui-autocomplete ui-front ui-menu ui-widget ui-widget-content ui-corner-all" id="ui-id-1" tabindex="0" style="display: none;"></ul><ul class="ui-autocomplete ui-front ui-menu ui-widget ui-widget-content ui-corner-all" id="ui-id-2" tabindex="0" style="display: none;"></ul>
</body>
</html>