<?php require_once('Connections/paymaster.php');
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

      global $paymaster;
      $theValue = function_exists("mysql_real_escape_string") ? mysqli_real_escape_string($paymaster, $theValue) : mysqli_escape_string($paymaster, $theValue);

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


?>
<!DOCTYPE html>
<html>
<?php include('header1.php'); ?>

<body data-color="grey" class="flat" style="zoom: 1;">
   <div class="modal fade hidden-print" id="myModal"></div>
   <div id="wrapper">
      <div id="header" class="hidden-print">
         <h1>
            <a href="index.php">
               <img src="img/header_logo.png" class="hidden-print header-log" id="header-logo" alt="">
            </a>
         </h1>
         <a id="menu-trigger" href="#">
            <i class="fa fa-bars fa fa-2x"></i>
         </a>
         <div class="clear"></div>
      </div>
      <?php include('header.php'); ?>
      <?php include('sidebar.php'); ?>
      <div id="content" class="clearfix sales_content_minibar">

         <div id="content-header" class="hidden-print">
            <h1>
               <i class="icon fa fa-user"></i>
               Employee Earnings
            </h1>
         </div>
         <div id="breadcrumb" class="hidden-print">
            <a href="home.php">
               <i class="fa fa-home"></i> Dashboard
            </a>
            <a class="current" href="permissions.php">Permissions</a>
         </div>
         <div class="clear"></div>
         <div id="datatable_wrapper"></div>
         <div class=" pull-right">
            <div class="row">
               <div id="datatable_wrapper"></div>
               <div class="col-md-12 center" style="text-align: center;">
                  <div class="btn-group  "></div>
               </div>
            </div>
         </div>
         <div class="row"></div>
         <div class="row">
             <div id="loadContent">
                 <div class="flex animate-pulse">
                     <div class="flex-shrink-0">
                         <span class="w-12 h-12 block bg-gray-200 rounded-full dark:bg-gray-700"></span>
                     </div>

                     <div class="ms-4 mt-2 w-full">
                         <h3 class="h-4 bg-gray-200 rounded-md dark:bg-gray-700" style="width: 40%;"></h3>
                         <ul class="mt-5 space-y-3">
                             <li class="w-full h-4 bg-gray-200 rounded-md dark:bg-gray-700"></li>
                             <li class="w-full h-4 bg-gray-200 rounded-md dark:bg-gray-700"></li>
                             <li class="w-full h-4 bg-gray-200 rounded-md dark:bg-gray-700"></li>
                             <li class="w-full h-4 bg-gray-200 rounded-md dark:bg-gray-700"></li>
                         </ul>
                     </div>
                 </div>
             </div>
         </div>

   <div id="footer" class="col-md-12 hidden-print">
      Please visit our
      <a href="#" target="_blank">
         website </a>
      to learn the latest information about the project.
      <span class="text-info">
         <span class="label label-info"> 14.1</span>
      </span>
   </div>

   <ul class="ui-autocomplete ui-front ui-menu ui-widget ui-widget-content ui-corner-all" id="ui-id-1" tabindex="0" style="display: none;"></ul>
          <script>
              $(document).ready(function() {

                  $('#loadContent').load('view/view_permissions.php');
                  // $("#search").focus();
                  // $("#search").select();
                  // $("#search").autocomplete({
                  //     source: 'libs/searchstaff.php',
                  //     type: 'POST',
                  //     delay: 10,
                  //     autoFocus: false,
                  //     minLength: 3,
                  //     select: function (event, ui) {
                  //         event.preventDefault();
                  //         $("#staff_id").val(ui.item.value);
                  //         $('#employee_name').val(ui.item.label);
                  //         $('#email').val(ui.item.EMAIL);
                  //
                  //         $('#searchform').ajaxSubmit({
                  //             url: 'view/view_users.php', // URL for form submission
                  //             type: 'POST', // Method for form submission
                  //             success: function(response) {
                  //                 $('#loadContent').html(response);
                  //             },
                  //             error: function(xhr, status, error) {
                  //                 // Handle the error response here
                  //                 console.log(error);
                  //             }
                  //         });
                  //
                  //     }
                  // });


              })
          </script>
</body>
</html>

