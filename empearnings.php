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

$currentPage = $_SERVER["PHP_SELF"];
$empID = -1;

if (isset($_GET['empearnings'])) {
   $empID = $_GET['empearnings'];
}
if (isset($_GET['empID'])) {
   $empID = $_GET['empID'];
}
mysqli_select_db($salary, $database_salary);
$query_employee = "SELECT
employee.staff_id,
employee.`NAME`,
employee.EMPDATE,
tbl_dept.dept,
employee.POST,
employee.GRADE,
employee.STEP,
employee.ACCTNO,
tbl_bank.BNAME,
tbl_pfa.PFANAME,
employee.PFAACCTNO,
employee.TAXPD,
employee.PFACODE
FROM
employee
LEFT JOIN tbl_dept ON tbl_dept.dept_id = employee.DEPTCD
LEFT JOIN tbl_bank ON tbl_bank.BCODE = employee.BCODE
LEFT JOIN tbl_pfa ON tbl_pfa.PFACODE = employee.PFACODE";
$employee = mysqli_query($salary, $query_employee) or die(mysqli_error($salary,));
$row_employee = mysqli_fetch_assoc($employee);
$totalRows_employee = mysqli_num_rows($employee);

$queryString_employee = "";
if (!empty($_SERVER['QUERY_STRING'])) {
   $params = explode("&", $_SERVER['QUERY_STRING']);
   $newParams = array();
   foreach ($params as $param) {
      if (
         stristr($param, "pageNum_employee") == false &&
         stristr($param, "totalRows_employee") == false
      ) {
         array_push($newParams, $param);
      }
   }
   if (count($newParams) != 0) {
      $queryString_employee = "&" . htmlentities(implode("&", $newParams));
   }
}
$queryString_employee = sprintf("&totalRows_employee=%d%s", $totalRows_employee, $queryString_employee);


?>
<!DOCTYPE html>
<!-- saved from url=(0055)http://www.optimumlinkup.com.ng/pos/index.php/customers -->
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
         <script type="text/javascript">
            $(document).ready(function() {


            });
         </script>
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
            <a class="current" href="empearnings.php">Employee Earnings</a>
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
            <!-- BEGIN PAGE BAR -->
            <div class="page-bar">
               <div class="row payperiod">
                  <div class="col-md-8">
                     <div class="form-group">
                        <label class="col-md-4 control-label"><b>Current Payroll Period: </b></label>
                        <div class="col-md-8">
                           <?php echo $_SESSION['activeperiodDescription']; ?> &nbsp; <span class="label label-inverse label-sm label-success"> Open </span>
                        </div>
                     </div>
                  </div>

                  <div class="col-md-4">

                  </div>
               </div>
            </div>
            <!-- END PAGE BAR -->
            <div class="row bottom-spacer-20">
               <?php
               if ($_SESSION['empDataTrack'] == 'next') {
                  $query = $conn->prepare("SELECT employee.staff_id, employee.`NAME` FROM employee WHERE STATUSCD = 'A'  ORDER BY staff_id desc");
                  $query->execute();
                  $ftres = $query->fetchAll(PDO::FETCH_COLUMN);
                  $count = $query->rowCount();
                  //print($count . "<br />");
                  //print_r($ftres);
                  $counter = 0;
                  if ($_SESSION['emptrack'] >= $count) {
                     $_SESSION['emptrack'] = 0;
                  }
                  $currentemp = $ftres['' . $_SESSION['emptrack'] . ''];
               } elseif ($_SESSION['empDataTrack'] == 'option') {
                  $currentemp = $_SESSION['emptNumTack'];
               }

               ?>
               <div class="col-md-6 top-spacer-20">

                  <?php
                  $query = $conn->prepare('SELECT
															employee.staff_id,
															employee.`NAME`,
															employee.EMPDATE,
															tbl_dept.dept,
															employee.POST,
															employee.GRADE,
															employee.STEP,
															employee.ACCTNO,
															tbl_bank.BNAME,
															tbl_pfa.PFANAME,
															employee.PFAACCTNO,
															employee.TAXPD,
                                             IFNULL(employee.HARZAD_TYPE,-1) AS HARZAD_TYPE,
															employee.PFACODE,
															employee.CALLTYPE,employee.STATUSCD
															FROM
															employee
															LEFT JOIN tbl_dept ON tbl_dept.dept_id = employee.DEPTCD
															LEFT JOIN tbl_bank ON tbl_bank.BCODE = employee.BCODE
															LEFT JOIN tbl_pfa ON tbl_pfa.PFACODE = employee.PFACODE WHERE staff_id = ?');
                  $query->execute(array($currentemp));
                  if ($row = $query->fetch()) {
                     $empfname = $row['NAME'];
                     $empGrade = $row['GRADE'];
                     $empStep = $row['STEP'];
                     $staffID   = $row['staff_id'];
                     $dept = $row['dept'];
                     $callType =  $row['CALLTYPE'];
                     $HARZAD_TYPE = $row['HARZAD_TYPE'];
                     $status = $row['STATUSCD'];
                  } else {
                     $empfname = '';
                     $empGrade = '';
                     $empStep = '';
                     $staffID   = '';
                     $dept = '';
                     $callType =  '';
                     $HARZAD_TYPE = '';
                     $status = '';
                  }
                  ?>
                  <div class="empname"> <span class="empnumbersize"> Emp # <?php echo $staffID . ' : '; ?></span> <span class="empnamesize"> <?php echo $empfname; ?> </span></div>
                  <span class="empnumbersize">Grade Level:</span> <span class="empnamesize"> <?php echo $empGrade; ?>/<?php echo $empStep; ?></span>
                  <br><span class="empnumbersize">Dept:</span><span class="empnamesize"> <?php echo $dept; ?></span>
                  <br><span class="empnumbersize">Status:</span><span class="empnamesize"> <?php echo $status;  ?></span>

               </div>

               <div class="col-md-3 top-spacer-20">
                  <div class="row">
                     <div class="col-md-12">
                        <div class="input-group">
                           <div class="input-icon">
                              <form action="classes/controller.php?act=retrieveSingleEmployeeData" method="post" accept-charset="utf-8" id="add_item_form" class="form-inline" autocomplete="off">
                                 <i class="fa fa-user fa-fw"></i>
                                 <input type="text" name="item" value="" id="item" class="form-control" required="required" accesskey="i" placeholder="Enter Staff Name or Staff No" /><span id="ajax-loader"><img src="img/ajax-loader.gif" alt="" /></span>
                                 <input name="code" type="hidden" id="code" value="<?php if (isset($error)) {
                                                                                       echo $error;
                                                                                    } else {
                                                                                       echo -1;
                                                                                    } ?>" />
                              </form>
                           </div>

                        </div>

                     </div>
                  </div>
               </div>



               <div class="col-md-3 top-spacer-20">
                  <div class="pull-right">
                     <!--<a href="# ?>" class="btn red"><i class="fa fa-angle-double-left fa-lg" aria-hidden="true"></i> Previous Employee  </a>-->
                     <a href="classes/controller.php?act=getNextEmployee&track=<?php echo $_SESSION['emptrack'] + 1; ?>" class="earnings">Next Employee <i class="fa fa-angle-double-right fa-lg" aria-hidden="true"></i> </a>
                     <?php
                     //print($_SESSION['emptrack']);
                     ?>
                  </div>
               </div>
            </div>
            <div class="portlet light bordered">
               <div class="portlet-body">
                  <!-- Start Modal -->
                  <!--Printer-->
                  <script type="text/javascript">
                     //<![CDATA[
                     window.onload = function() {
                        jQuery.fn.extend({
                           printElem: function() {
                              var cloned = this.clone();
                              var printSection = $('#printSection');
                              if (printSection.length == 0) {
                                 printSection = $('<div id="printSection"></div>')
                                 $('body').append(printSection);
                              }
                              printSection.append(cloned);
                              var toggleBody = $('body *:visible');
                              toggleBody.hide();
                              $('#printSection, #printSection *').show();
                              window.print();
                              printSection.remove();
                              toggleBody.show();
                           }
                        });

                        $(document).ready(function() {
                           $(document).on('click', '#btnPrint', function() {
                              $('.printMe').printElem();
                           });
                        });
                     } //]]> 
                  </script>
                  <!--Printer-->
                  <div id="viewemployeepayslip" class="modal fade" tabindex="-1" data-width="660">
                     <div class="modal-header modal-title" style="background: #6e7dc7;">
                        <button type=" button" class="close" data-dismiss="modal" aria-hidden="true"></button>
                        <h4 class="modal-title">Employee Payslip</h4>
                     </div>
                     <div class="modal-body">
                        <!-- START ROLL-->
                        <?php
                        $staff_id = $staffID;
                        $period = $_SESSION['currentactiveperiod'];

                        global $conn;

                        try {
                           $query = $conn->prepare('SELECT payperiods.description, payperiods.periodYear, payperiods.periodId FROM payperiods WHERE periodId = ?');
                           $res = $query->execute(array($period));
                           $out = $query->fetchAll(PDO::FETCH_ASSOC);

                           while ($row = array_shift($out)) {
                              $fullPeriod =  $row['description'] . '-' . $row['periodYear'];
                              echo ($fullPeriod);
                           }
                        } catch (PDOException $e) {
                           $e->getMessage();
                        }

                        $query = $conn->prepare('SELECT staff_id FROM master_staff WHERE staff_id=? and period = ?');
                        $query->execute(array($staff_id, $period));
                        $ftres = $query->fetchAll(PDO::FETCH_COLUMN);
                        $count = $query->rowCount();
                        $counter = 1;
                        //print($count . "<br />");
                        //print_r($ftres);
                        $counter = 0;
                        if ($_SESSION['emptrack'] >= $count) {
                           $_SESSION['emptrack'] = 0;
                        }
                        // $currentemp = $ftres[''.$_SESSION['emptrack'].''];
                        ?>
                        <div class="col-md-12">
                           <!-- BEGIN EXAMPLE TABLE PORTLET-->
                           <div class="portlet light bordered">

                              <div class="portlet-body">
                                 <div class="table-toolbar hidden-print">
                                    <div class="row">
                                       <div class="col-md-6">
                                          <button class="btn btn-sm btn-primary" type="button">
                                             Payroll Period <span class="badge"><?php print $_SESSION['activeperiodDescription'] ?></span>
                                          </button>
                                          <button class="btn btn-sm purple" type="button">
                                             Number of Employees <span class="badge"><?php print $count ?></span>
                                          </button>
                                       </div>
                                       <div class="col-md-6">
                                          <div class="btn-group pull-right">
                                             <button class="btn btn-sm red" id="btnPrint">Print <i class="fa fa-print" aria-hidden="true"></i></button>
                                             <!--<button class="btn blue  btn-outline dropdown-toggle" data-toggle="dropdown">Tools
                                                            <i class="fa fa-angle-down"></i>
                                                        </button>
                                                        <ul class="dropdown-menu pull-right">
                                                            <li>
                                                                <a href="javascript:;">
                                                                    <i class="fa fa-print"></i> Print </a>
                                                            </li>
                                                            <li>
                                                                <a href="javascript:;">
                                                                    <i class="fa fa-file-pdf-o"></i> Save as PDF </a>
                                                            </li>
                                                            <li>
                                                                <a href="javascript:;">
                                                                    <i class="fa fa-file-excel-o"></i> Export to Excel </a>
                                                            </li>
                                                        </ul>-->
                                          </div>
                                       </div>
                                    </div>
                                 </div>

                                 <!--Printer-->
                                 <script type='text/javascript'>
                                    //<![CDATA[
                                    window.onload = function() {
                                       jQuery.fn.extend({
                                          printElem: function() {
                                             var cloned = this.clone();
                                             var printSection = $('#printSection');
                                             if (printSection.length == 0) {
                                                printSection = $('<div id="printSection"></div>')
                                                $('body').append(printSection);
                                             }
                                             printSection.append(cloned);
                                             var toggleBody = $('body *:visible');
                                             toggleBody.hide();
                                             $('#printSection, #printSection *').show();
                                             window.print();
                                             printSection.remove();
                                             toggleBody.show();
                                          }
                                       });

                                       $(document).ready(function() {
                                          $(document).on('click', '#btnPrint', function() {
                                             $('.printMe').printElem();
                                          });
                                       });
                                    } //]]> 
                                 </script>
                                 <!--Printer-->
                                 <!--Printer-->
                                 <table border="1" class="wrap_trs">
                                    <tr>
                                       <?php
                                       while ($counter < $count) {
                                          echo '<td>';
                                          //Print employee payslips
                                          $thisemployee = $ftres['' . $counter . ''];
                                          //print_r($thisemployee);
                                       ?>

                                          <!-- START ROLL-->
                                          <?php
                                          global $conn;

                                          try {
                                             $query = $conn->prepare('SELECT tbl_bank.BNAME, tbl_dept.dept, master_staff.STEP, master_staff.GRADE, master_staff.staff_id, master_staff.`NAME`, master_staff.ACCTNO FROM master_staff INNER JOIN tbl_dept ON tbl_dept.dept_id = master_staff.DEPTCD INNER JOIN tbl_bank ON tbl_bank.BCODE = master_staff.BCODE WHERE staff_id = ? and period = ?');
                                             $res = $query->execute(array($thisemployee, $period));
                                             $out = $query->fetch();
                                          ?>
                                             <div class="row bottom-spacer-40">
                                                <div class="col-md-3"></div>

                                                <div class="col-md-6">

                                                   <div id="printThis" class="printMe payslip-wrapper">
                                                      <div class="payslip-header">
                                                         <div class="row header-label">
                                                            <div class="col-md-12 txt-ctr text-uppercase"><b>
                                                                  OOUTH, SAGAMU
                                                               </b></div>
                                                            <div class="col-md-12 txt-ctr text-uppercase">
                                                               <b> PAYSLIP FOR <b> <?php echo $fullPeriod; ?> </b></b>
                                                            </div>

                                                         </div>
                                                         <div class="row header-label">
                                                            <div class="col-md-6 col-xs-6">
                                                               <span class="pay-header-item" style="white-space:nowrap;">Name:
                                                                  <?php
                                                                  echo $out['NAME'];
                                                                  ?>

                                                               </span>
                                                            </div>
                                                            <div class="col-md-6 col-xs-6 txt-left" style="white-space:nowrap;"><?php
                                                                                                                                 //  echo $out['NAME'];
                                                                                                                                 ?></div>
                                                         </div>
                                                         <div class="row header-label">
                                                            <div class="col-md-6 col-xs-6" style="white-space:nowrap;">Staff No.: <?php print_r($thisemployee); ?> </div>

                                                         </div>
                                                         <div class="row header-label">

                                                            <div class="col-md-6 col-xs-6" style="white-space:nowrap;">
                                                               Dept:
                                                               <?php
                                                               echo $out['dept'];
                                                               ?>
                                                            </div>
                                                         </div>
                                                         <div class="row header-label">
                                                            <div class="col-md-6 col-xs-6" style="white-space:nowrap;">Bank:
                                                               <?php
                                                               echo $out['BNAME'];
                                                               ?>
                                                            </div>

                                                         </div>
                                                         <div class="row header-label">
                                                            <div class="col-md-6 col-xs-6" style="white-space:nowrap;">Acct No.:
                                                               <?php
                                                               echo $out['ACCTNO'];
                                                               ?>
                                                            </div>

                                                         </div>
                                                         <div class="row header-label">

                                                            <div class="col-md-6 col-xs-6" style="white-space:nowrap;">CONSOLIDATED:
                                                               <?php
                                                               echo $out['GRADE'] . '/' . $out['STEP'];
                                                               ?>
                                                            </div>
                                                         </div>
                                                      </div>
                                                   <?php
                                                } catch (PDOException $e) {
                                                   echo $e->getMessage();
                                                }

                                                   ?>

                                                   <div class="payslip-body">
                                                      <div class="row header-label">
                                                         <div class="col-md-12 col-xs-12"><b>CONSOLIDATED SALARY</b></div>
                                                      </div>

                                                      <div class="row header-label">
                                                         <div class="col-md-6 col-xs-6" style="white-space:nowrap;">CONSOLIDATED SALARY: </div>
                                                         <div class="col-md-6 col-xs-6 txt-right">
                                                            <?php
                                                            $consolidated = 0;
                                                            try {
                                                               $query = $conn->prepare('SELECT tbl_master.staff_id,tbl_master.allow FROM tbl_master WHERE allow_id = ? and staff_id = ? and period = ?');
                                                               $fin = $query->execute(array('1', $thisemployee, $period));
                                                               //$res = $query->fetchAll(PDO::FETCH_ASSOC);
                                                               $res = $query->fetch();
                                                               //print_r($res);
                                                               if ($query->rowCount() > 0) {
                                                                  $consolidated = $res['allow'];
                                                               } else {
                                                                  $consolidated = 0;
                                                               }


                                                               echo number_format($consolidated);
                                                            } catch (PDOException $e) {
                                                               echo $e->getMessage();
                                                            }
                                                            ?>



                                                         </div>

                                                      </div>
                                                      <div class="row header-label">
                                                         <div class="col-md-12 col-xs-12"><b><u>ALLOWANCES</u></b></div>
                                                      </div>
                                                      <div class="row payslip-data">
                                                         <?php
                                                         $totalAllow = 0;
                                                         try {
                                                            $query = $conn->prepare('SELECT tbl_master.staff_id, tbl_master.allow, tbl_earning_deduction.ed FROM tbl_master INNER JOIN tbl_earning_deduction ON tbl_earning_deduction.ed_id = tbl_master.allow_id WHERE allow_id <> ? and staff_id = ? and period = ? and type = ?');
                                                            $fin = $query->execute(array('1', $thisemployee, $period, '1'));
                                                            $res = $query->fetchAll(PDO::FETCH_ASSOC);
                                                            //print_r($res);

                                                            foreach ($res as $row => $link) {

                                                               $totalAllow = $totalAllow + floatval($link['allow']);

                                                               echo '<div class="col-md-8 col-xs-8" style="white-space:nowrap;">' . $link['ed'];

                                                               echo '</div><div class="col-md-4 col-xs-4 payslip-amount">' . number_format($link['allow']) . '</div>';
                                                            }
                                                         } catch (PDOException $e) {
                                                            echo $e->getMessage();
                                                         }
                                                         ?>
                                                      </div>

                                                      <div class="row payslip-total">
                                                         <div class="col-md-8 col-xs-8"><b>Total Allowance</b></div>
                                                         <div class="col-md-4 col-xs-4 payslip-amount"><b>
                                                               <?php
                                                               echo number_format($totalAllow);
                                                               ?>
                                                            </b></div>
                                                         <div class="col-md-8 col-xs-8"><b>Gross Salary</b></div>
                                                         <div class="col-md-4 col-xs-4 payslip-amount"><b>
                                                               <?php
                                                               echo number_format(floatval($totalAllow) + floatval($consolidated));

                                                               ?>
                                                            </b></div>
                                                      </div>
                                                   </div>



                                                   <div class="payslip-body">
                                                      <div class="row header-label">
                                                         <div class="col-md-12 col-xs-12"><b><u>Deductions</u></b></div>
                                                      </div>
                                                      <div class="row payslip-data">
                                                         <?php
                                                         $totalDeduction = 0;
                                                         try {
                                                            $query = $conn->prepare('SELECT tbl_master.staff_id, tbl_master.deduc, tbl_earning_deduction.ed FROM tbl_master INNER JOIN tbl_earning_deduction ON tbl_earning_deduction.ed_id = tbl_master.allow_id WHERE staff_id = ? and period = ? and type = ?');
                                                            $fin = $query->execute(array($thisemployee, $period, '2'));
                                                            $res = $query->fetchAll(PDO::FETCH_ASSOC);


                                                            foreach ($res as $row => $link) {

                                                               //Get ED description
                                                               $totalDeduction = $totalDeduction + floatval($link['deduc']);


                                                               echo '<div class="col-md-8 col-xs-8" style="white-space:nowrap;">' . $link['ed'];

                                                               echo '</div><div class="col-md-4 col-xs-4 payslip-amount">' . number_format($link['deduc']) . '</div>';
                                                            }
                                                         } catch (PDOException $e) {
                                                            echo $e->getMessage();
                                                         }
                                                         ?>


                                                      </div>



                                                      <div class="row payslip-total">
                                                         <div class="col-md-8 col-xs-8"><b>Total Deductions</b></div>
                                                         <div class="col-md-4 col-xs-4 payslip-amount"><b>
                                                               <?php
                                                               echo number_format($totalDeduction);
                                                               ?>
                                                            </b></div>
                                                      </div>
                                                   </div>


                                                   <div class="payslip-body">


                                                      <div class="row payslip-total">
                                                         <div class="col-md-8 col-xs-8"><b>Net Pay</b></div>
                                                         <div class="col-md-4 col-xs-4 payslip-amount"><b>
                                                               <?php
                                                               echo number_format((floatval($totalAllow) + floatval($consolidated)) - floatval($totalDeduction));
                                                               ?>
                                                            </b></div>
                                                      </div>
                                                   </div>

                                                   </div>

                                                </div>

                                                <div class="col-md-3"></div>
                                             </div>
                                             <!-- END ROLL-->

                                          <?php
                                          $counter++;
                                          //end employee payslips
                                       }
                                       echo '</td>';
                                       echo '<p style = "page-break-after:always;"></p>';
                                          ?>
                                    </tr>
                                 </table>
                              </div>

                           </div>

                           <!-- END EXAMPLE TABLE PORTLET-->
                        </div>
                        <!-- END ROLL-->
                        <div class="modal-footer">
                           <button type="button" data-dismiss="modal" class="btn btn-outline dark">Close</button>
                           <button class="btn red" id="btnPrint">Print <i class="fa fa-print" aria-hidden="true"></i></button>
                        </div>
                     </div>
                  </div>
                  <script>
                     // tell the embed parent frame the height of the content
                     if (window.parent && window.parent.parent) {
                        window.parent.parent.postMessage("resultsFrame", {
                           height: document.body.getBoundingClientRect().height,
                           slug: "95ezN"
                        }, "*")
                     }
                  </script>
                  <!--End Modal-->
                  <div class="row">
                     <div class="col-md-9">
                        <?php
                        if (isset($_SESSION['msg'])) {
                           echo '<div class="alert alert-' . $_SESSION['alertcolor'] . ' alert-dismissable role="alert"> <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>' . $_SESSION['msg'] . '</div>';
                           unset($_SESSION['msg']);
                           unset($_SESSION['alertcolor']);
                        }
                        ?>
                        <!--Begin Earnings/Ded table-->
                        <div class="table-responsive" id="reloadtable">
                           <table class="table table-bordered table-hover">
                              <thead>
                                 <tr class="earnings-ded-header">
                                    <th> Code </th>
                                    <th> Description </th>
                                    <th> Amount </th>
                                    <th width="110"> </th>
                                 </tr>
                              </thead>
                              <tbody>
                                 <tr class="earnings-row">
                                    <td colspan="4"> <strong>Earnings</strong></td>
                                 </tr>
                                 <!--New Earning-->
                                 <?php
                                 try {
                                    $query = $conn->prepare('SELECT ifnull(allow_deduc.`value`,0) as `value`,allow_deduc.allow_id,allow_deduc.temp_id,tbl_earning_deduction.edDesc FROM
																						tbl_earning_deduction right JOIN allow_deduc ON tbl_earning_deduction.ed_id = allow_deduc.allow_id
																						WHERE transcode = ? and staff_id = ? order by allow_id asc');
                                    $fin = $query->execute(array(01, $staffID));
                                    $res = $query->fetchAll(PDO::FETCH_ASSOC);
                                    //print_r($res);

                                    $gross = 0;

                                    $totalRows = (int)$count = $query->rowCount();

                                    if ($totalRows > 0) {

                                       foreach ($res as $row => $link) {

                                 ?> <tr class="odd gradeX">
                                             <?php echo '<td>' . $link['allow_id'];
                                             echo '</td><td>' .    $link['edDesc'];

                                             echo '</td><td class="align-right">' . number_format($link['value']) . '</td>';
                                             echo '<td> <button type="button" title="Delete Allow/Deduction" data-target="#deleteed' . $link['temp_id'] . '" data-toggle="modal" class="btn btn-zs red fa fa-minus-square"></button></td>';

                                             $gross = $gross + $link['value'];

                                             ?>
                                             <!--Delete ED-->
                                             <div id="deleteed<?php echo $link['temp_id']; ?>" class="modal fade" tabindex="-1" data-width="560">
                                                <div class="modal-dialog" role="document">
                                                   <form class="form-horizontal" method="post" action="classes/controller.php?act=deactivateEd">
                                                      <div class="modal-content">
                                                         <div class="modal-header modal-title" style="background: #6e7dc7;">
                                                            <h4 class="modal-title" style="text-transform: uppercase;">Delete Employee Earning / Deduction</h4>
                                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                               <span aria-hidden="true">&times;</span>
                                                            </button>
                                                         </div>
                                                         <div class="modal-body">

                                                            <div class="row">
                                                               <div class="col-md-12">
                                                                  <div class="form-body">
                                                                     <input type="hidden" value="<?php echo $link['temp_id']; ?>" name="empeditnum">
                                                                     <input type="hidden" value="" name="edited">
                                                                     <div class="form-group">
                                                                        <label class="col-md-4 control-label">Description</label>
                                                                        <div class="col-md-7">
                                                                           <input type="text" required class="form-control" name="editname" disabled="" value="<?php echo $link['edDesc']; ?>">
                                                                        </div>
                                                                     </div>
                                                                     <div class="form-group">
                                                                        <label class="col-md-4 control-label">Amount </label>
                                                                        <div class="col-md-7">
                                                                           <input type="text" required class="form-control" name="editvalue" disabled="" value="<?php echo number_format($link['value']); ?>">
                                                                        </div>
                                                                     </div>
                                                                  </div>
                                                               </div>
                                                            </div>
                                                         </div>
                                                         <div class="modal-footer">
                                                            <button type="button" data-dismiss="modal" class="btn btn-outline dark">Close</button>
                                                            <button type="submit" class="btn red deletedItem">Delete E/D</button>
                                                   </form>
                                                </div>
                                             </div>
                        </div>
                     </div>





            <?php
                                       }
                                    }
                                 } catch (PDOException $e) {
                                    echo $e->getMessage();
                                 }
            ?>
            <!-- Begining of Temp Allowanc-->

            <tr class="lighter-row">
               <th> </th>
               <th> Gross Salary </th>
               <th class="align-right">
                  <?php echo number_format($gross); ?>
               </th>
               <th></th>
               <!--Write Function to run query and output based on one record-->
            </tr>
            <!--Computing Taxable income-->
            <tr class="earnings-row">
               <td> <strong>Deductions</strong></td>
               <td> </td>
               <td class="align-right"> </td>
               <td></td>
            </tr>
            <?php
            try {
               $query = $conn->prepare('SELECT ifnull(allow_deduc.`value`,0) as `value`, allow_deduc.allow_id,allow_deduc.temp_id,tbl_earning_deduction.edDesc FROM
																						tbl_earning_deduction RIGHT JOIN allow_deduc ON tbl_earning_deduction.ed_id = allow_deduc.allow_id
																						WHERE transcode = ? and staff_id = ? order by allow_id asc');
               $fin = $query->execute(array('02', $staffID));;
               $res = $query->fetchAll(PDO::FETCH_ASSOC);
               //print_r($res);

               $totalDeduction = 0;
               foreach ($res as $row => $link2) {




            ?>
                  <tr class="odd gradeX">
                     <?php echo '<td>' . $link2['allow_id'];
                     echo '</td><td>' .    $link2['edDesc'];
                     echo '</td><td class="align-right">' . number_format($link2['value']) . '</td>';
                     echo '<td> <button type="button" title="Delete Allow/Deduction" data-target="#deleteed' . $link2['temp_id'] . '" data-toggle="modal" class="btn btn-zs red fa fa-minus-square"></button></td>';

                     $totalDeduction = $totalDeduction + $link2['value'];

                     ?>
                     <!--Delete ED-->
                     <div id="deleteed<?php echo $link2['temp_id']; ?>" class="modal fade" tabindex="-1" data-width="560">
                        <div class="modal-dialog" role="document">
                           <div class="modal-content">
                              <div class="modal-header modal-title" style="background: #6e7dc7;">
                                 <h4 class="modal-title">Delete Employee Earning / Deduction</h4>
                                 <button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
                              </div>
                              <div class="modal-body">
                                 <form class="form-horizontal" method="post" action="classes/controller.php?act=deactivateEd">
                                    <div class="row">
                                       <div class="col-md-12">
                                          <div class="form-body">
                                             <input type="hidden" value="<?php echo $link2['temp_id']; ?>" name="empeditnum">
                                             <input type="hidden" value="" name="edited">
                                             <div class="form-group">
                                                <label class="col-md-4 control-label">Description</label>
                                                <div class="col-md-7">
                                                   <input type="text" required class="form-control" name="editname" disabled="" value="<?php echo $link2['edDesc']; ?>">
                                                </div>
                                             </div>
                                             <div class="form-group">
                                                <label class="col-md-4 control-label">Amount </label>
                                                <div class="col-md-7">
                                                   <input type="text" required class="form-control" name="editvalue" disabled="" value="<?php echo number_format($link2['value']); ?>">
                                                </div>
                                             </div>
                                          </div>
                                       </div>
                                    </div>
                              </div>
                              <div class="modal-footer">
                                 <button type="button" data-dismiss="modal" class="btn btn-outline dark">Close</button>
                                 <button type="submit" class="btn red">Delete E/D</button>
                              </div>
                           </div>
                        </div>
                        </form>
                     </div>
               <?php
               }
            } catch (PDOException $e) {
               echo $e->getMessage();
            }
               ?>
               <!--  Being Temp Deduction-->

               <!-- End of Temp Deduction-->
                  <tr class="lighter-row">
                     <th> </th>
                     <th> Total Deduction </th>
                     <th class="align-right">
                        <?php echo number_format($totalDeduction, 2); ?>
                     </th>
                     <th></th>
                     <!--Write Function to run query and output based on one record-->
                  </tr>
                  <tr class="earnings-row">
                     <th> Net Pay </th>
                     </th>
                     <th align-right colspan="3"><?php echo number_format(($gross - $totalDeduction)); ?></th>
                  </tr>
                  </tbody>
                  </table>
                  </div>
                  <!--End Earnings/Ded table-->
               </div>
               <div class="col-md-3">
                  <button type="button" title="Add Earning/Deduction" class="btn green btn-block fa fa-plus-square" data-toggle="modal" data-target="#newemployeeearning">
                     Add Earning/Deduction</button>
                  <!-- <button type="button" title="Add Deduction" class="btn green btn-block fa fa-minus-square" data-toggle="modal" data-target="#newemployeededuction"> Add Deduction </button> -->
                  <!-- <button type="button" title="Add UnionDeduction" class="btn red btn-block fa fa-minus-square" data-toggle="modal" data-target="#newemployeededuction_union"> Add Union Deduction </button> -->
                  <button type="button" title="Add Temporary Deduction/Allowance" class="btn red btn-block fa fa-minus-square" data-toggle="modal" data-target="#newtempemployeededuction"> Add Temp. Deduction/Allowance </button>
                  <button type="button" title="Add Loan/Corporate" class="btn red btn-block fa fa-minus-square" data-toggle="modal" data-target="#loan_corporate"> Add Loan/Corporate </button>
                  <form method="post" id="form_runPayslip" action="classes/runPayrollindividual.php">
                     <input type="hidden" id="thisemployeePayslip" name="thisemployeePayslip" value="<?php echo $staffID; ?>">
                     <button type="button" class="btn btn-block black top-spacer-5" id="runPayslip" <?php $j =  retrievePayrollRunStatus($staffID, $_SESSION['currentactiveperiod']);
                                                                                                      if ($j == '1') {
                                                                                                         echo 'disabled="disabled"';
                                                                                                      } ?>>Run this Employee's Payroll <i class="fa fa-refresh" aria-hidden="true"></i></button>
                  </form>
                  <form method="post" id="form_deletePayslip" action="assets/classes/controller.php?act=deletecurrentstaffPayslip">
                     <input type="hidden" id="thisemployee" name="thisemployee" value="<?php echo $staffID; ?>">
                     <button type="button" class="btn btn-block black top-spacer-5" id="deletePayslip" <?php $j =  retrievePayrollRunStatus($staffID, $_SESSION['currentactiveperiod']);
                                                                                                         if ($j == '0') {
                                                                                                            echo 'disabled="disabled"';
                                                                                                         } ?>>Delete this Emp Payslip <i class="fa fa-refresh" aria-hidden="true"></i></button>
                  </form>
                  <button type="button" title="View employee Payslip" class="btn blue btn-block top-spacer-5" data-toggle="modal" data-target="#viewemployeepayslip" <?php $j =  retrievePayrollRunStatus($staffID, $_SESSION['currentactiveperiod']);
                                                                                                                                                                     if ($j == '0') {
                                                                                                                                                                        echo 'disabled="disabled"';
                                                                                                                                                                     } ?>> View Employee Payslip <i class="fa fa-file-text" aria-hidden="true"></i></button>
                  <button type="button" title="Pro-rate" class="btn red btn-block fa fa-minus-square" data-toggle="modal" data-target="#prorate"> Pro-rate Allow </button>
                  <button type="button" title="Update Grade/Step" class="btn red btn-block fa fa-minus-square" data-toggle="modal" data-target="#gradeStep"> Update Grade/Step </button>
               </div>
            </div>
            <!--<div class="row top-spacer-40">
                        <div class="col-md-12 profile-info">
                            <div class="col-md-12 top-spacer-20">
                                <form method="post" action="assets/classes/controller.php?act=runCurrentEmployeePayroll">
                                    <div class="btn-group pull-right">
                                        <input type="hidden" name="thisemployee" value="">
                                        <button type="submit" class="btn black" > Run Current Employee's Payroll <i class="fa fa-refresh" aria-hidden="true"></i></button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        </div>-->
            <!--end primary data row-->
            <div id="newemployeeearning" class="modal fade" tabindex="-1" data-width="560">

               <div class="modal-dialog" role="document">
                  <div class="modal-content">
                     <div class="modal-header modal-title" style="background: #6e7dc7;">
                        <h4 class=" modal-title" style="text-transform: uppercase;">Add New Earning/Deduction for this Employee</h4>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                           <span aria-hidden="true">&times;</span>
                        </button>
                     </div>
                     <div class="modal-body">
                        <form class="form-horizontal" name="form_newearningcode" id="form_newearningcode" method="post" action="classes/controller.php?act=addemployeeearning">
                           <div class="row">
                              <div class="col-md-12">
                                 <div class="form-body">
                                    <input type="hidden" name="curremployee" value="<?php echo $staffID; ?>">
                                    <input type="hidden" name="grade_level" value="<?php echo $empGrade; ?>">
                                    <input type="hidden" name="step" value="<?php echo $empStep; ?>">
                                    <input type="hidden" name="callType" value="<?php echo $callType; ?>">
                                    <input type="hidden" name="HARZAD_TYPE" value="<?php echo $HARZAD_TYPE; ?>">
                                    <div class="form-group">
                                       <label class="col-md-4 control-label">Description</label>
                                       <div class="col-md-7">
                                          <select required="" class="form-control" id="newearningcode" name="newearningcode">
                                             <option>- - Select Earning - -</option>
                                             <?php // retrieveSelect('tbl_earning_deduction', '*', 'edType', '1', 'ed_id');


                                             try {
                                                $query = $conn->prepare(
                                                   'SELECT * FROM tbl_earning_deduction'
                                                );
                                                $res = $query->execute(array());
                                                $out = $query->fetchAll(PDO::FETCH_ASSOC);

                                                while ($row = array_shift($out)) {

                                                   echo ('<option value="' . $row['ed_id'] . '" data-code="' . $row['edType'] . '">' . $row['ed'] . ' - ' . $row['ed_id'] . '</option>');
                                                }
                                             } catch (PDOException $e) {
                                                echo $e->getMessage();
                                             }

                                             ?>
                                          </select>
                                       </div>
                                    </div>
                                    <div class="form-group">
                                       <label class="col-md-4 control-label">Amount</label>
                                       <div class="col-md-7">
                                          <input type="text" readonly="readonly" required="required" class="form-control" id="earningamount" name="earningamount" placeholder="Amount">
                                       </div>
                                    </div>
                                 </div>
                              </div>
                           </div>
                     </div>
                     <div class="modal-footer">
                        <button type="button" data-dismiss="modal" class="btn btn-outline dark">Close</button>
                        <button type="button" class="btn red" id="addearningsButton">Add</button>
                        </form>
                     </div>
                  </div>
               </div>
            </div>
            <div class="modal fade" id="newemployeededuction" tabindex="-1" role="dialog" aria-labelledby="newemployeededuction" aria-hidden="true">
               <div class="modal-dialog" role="document">
                  <div class="modal-content">
                     <div class="modal-header modal-title" style="background: #6e7dc7;">
                        <h5 class=" modal-title" id="newemployeeearning">Add New Deduction for this Employee</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                           <span aria-hidden="true">&times;</span>
                        </button>
                     </div>
                     <div class="modal-body">
                        <form class="form-horizontal" name="form_newedeductioncode" id="form_newedeductioncode" method="post" action="classes/controller.php?act=addemployeededuction">
                           <div class="row">
                              <div class="col-md-12">
                                 <div class="form-body">
                                    <input type="hidden" name="curremployee" value="<?php echo $staffID; ?>">
                                    <input type="hidden" name="grade_level" value="<?php echo $empGrade; ?>">
                                    <input type="hidden" name="step" value="<?php echo $empStep; ?>">
                                    <div class="form-group">
                                       <label class="col-md-4 control-label">Description</label>
                                       <div class="col-md-7">
                                          <select required="required" class="form-control" id="newdeductioncode" name="newdeductioncode">
                                             <option>- - Select Deduction - -</option>
                                             <?php retrieveSelect('tbl_earning_deduction', '*', 'edType', '2', 'ed_id'); ?>
                                          </select>
                                       </div>
                                    </div>
                                    <div class="form-group">
                                       <label class="col-md-4 control-label">Amount</label>
                                       <div class="col-md-7">
                                          <input type="number" required="required" class="form-control" id="deductionamount" name="deductionamount" placeholder="Amount">
                                       </div>
                                    </div>
                                 </div>
                              </div>
                           </div>
                     </div>
                     <div class="modal-footer">
                        <button type="button" data-dismiss="modal" class="btn btn-outline dark">Close</button>
                        <button type="button" id="addDeductionButton" class="btn red">Add</button>
                        </form>
                     </div>
                  </div>
               </div>
            </div>
            <!-- Add new Union Deduction-->
            <div class="modal fade" id="newemployeededuction_union" tabindex="-1" role="dialog" aria-labelledby="newemployeededuction_union" aria-hidden="true">
               <div class="modal-dialog" role="document">
                  <div class="modal-content">
                     <div class="modal-header modal-title" style="background: #6e7dc7;">
                        <h5 class=" modal-title" id="newemployeeearning">Add Union Deduction for this Employee</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                           <span aria-hidden="true" style="background:'white'">&times;</span>
                        </button>
                     </div>
                     <div class="modal-body">
                        <form class="form-horizontal" name="form_newedeductioncodeunion" id="form_newedeductioncodeunion" method="post" action="classes/controller.php?act=addemployeedeductionunion">
                           <div class="row">
                              <div class="col-md-12">
                                 <div class="form-body">
                                    <input type="hidden" name="curremployee" value="<?php echo $staffID; ?>">
                                    <input type="hidden" name="grade_level" value="<?php echo $empGrade; ?>">
                                    <input type="hidden" name="step" value="<?php echo $empStep; ?>">
                                    <div class="form-group">
                                       <label class="col-md-4 control-label">Description</label>
                                       <div class="col-md-7">
                                          <select required="required" class="form-control" id="newdeductioncodeunion" name="newdeductioncodeunion">
                                             <option>- - Select Union Deduction - -</option>
                                             <?php retrieveSelect('tbl_earning_deduction', '*', 'edType', '3', 'ed_id'); ?>
                                          </select>
                                       </div>
                                    </div>
                                    <div class="form-group">
                                       <label class="col-md-4 control-label">Amount</label>
                                       <div class="col-md-7">
                                          <input type="number" required="required" class="form-control" id="deductionamountunion" name="deductionamountunion" placeholder="Amount">
                                       </div>
                                    </div>
                                 </div>
                              </div>
                           </div>
                     </div>
                     <div class="modal-footer">
                        <button type="button" data-dismiss="modal" class="btn btn-outline dark">Close</button>
                        <button type="button" id="addDeductionButtonUnion" class="btn red">Add</button>
                        </form>
                     </div>
                  </div>
               </div>
            </div>
            <!-- Bening Temp Deduction/Allowance-->
            <div class="modal fade" id="newtempemployeededuction" tabindex="-1" role="dialog" aria-labelledby="newtempemployeededuction" aria-hidden="true">
               <div class="modal-dialog" role="document">
                  <div class="modal-content">
                     <div class="modal-header modal-title" style="background: #6e7dc7;">
                        <h5 class=" modal-title" id="newemployeeearning">Add New Deduction for this Employee</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                           <span aria-hidden="true">&times;</span>
                        </button>
                     </div>
                     <div class="modal-body">
                        <form class="form-horizontal" name="form_temp" id="form_temp" method="post" action="classes/controller.php?act=newtempemployeededuction">
                           <div class="row">
                              <div class="col-md-12">
                                 <div class="form-body">
                                    <input type="hidden" name="curremployee" value="<?php echo $staffID; ?>">
                                    <input type="hidden" name="grade_level" value="<?php echo $empGrade; ?>">
                                    <input type="hidden" name="step" value="<?php echo $empStep; ?>">
                                    <div class="form-group">
                                       <label class="col-md-4 control-label">Description</label>
                                       <div class="col-md-7">
                                          <select required="required" class="form-control" id="newdeductioncodetemp" name="newdeductioncodetemp">
                                             <option>- - Select Deduction/Allowance - -</option>
                                             <?php
                                             try {
                                                global $conn;
                                                $query = $conn->prepare('SELECT tbl_earning_deduction.ed_id,tbl_earning_deduction.ed,tbl_earning_deduction.edDesc FROM tbl_earning_deduction WHERE status = ?');
                                                $res = $query->execute(array('Active'));
                                                $out = $query->fetchAll(PDO::FETCH_ASSOC);

                                                while ($row = array_shift($out)) {
                                                   echo ('<option value="' . $row['ed_id'] . '">' . $row['ed_id'] . ' - ' . $row['edDesc'] . '</option>');
                                                }
                                             } catch (PDOException $e) {
                                                echo $e->getMessage();
                                             }
                                             ?>
                                          </select>
                                       </div>
                                    </div>
                                    <div class="form-group">
                                       <label class="col-md-4 control-label">No of Time to Run(In Month)</label>
                                       <div class="col-md-7">
                                          <input type="number" min="0" required="required" class="form-control" id="no_times" name="no_times" placeholder="Run Times">
                                       </div>
                                    </div>
                                    <div class="form-group">
                                       <label class="col-md-4 control-label">Amount</label>
                                       <div class="col-md-7">
                                          <input type="number" required="required" class="form-control" id="deductionamount" name="deductionamount" placeholder="Amount">
                                       </div>
                                    </div>
                                 </div>
                              </div>
                           </div>
                     </div>
                     <div class="modal-footer">
                        <button type="button" data-dismiss="modal" class="btn btn-outline dark">Close</button>
                        <button type="button" id="addTempButton" class="btn red">Add</button>
                        </form>
                     </div>
                  </div>
               </div>
            </div>

            <!-- Begining of Upgrade Grade/Step Thing-->
            <div class="modal fade" id="gradeStep" tabindex="-1" role="dialog" aria-labelledby="gradeStep" aria-hidden="true">
               <div class="modal-dialog" role="document">
                  <div class="modal-content">
                     <div class="modal-header modal-title" style="background: #6e7dc7;">
                        <h5 class=" modal-title" id="newprorate">Update Grade/Step for this Employee</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                           <span aria-hidden="true">&times;</span>
                        </button>
                     </div>
                     <div class="modal-body">
                        <form class="form-horizontal" name="form_newgrade_stepemployee" id="form_newgrade_stepemployee" method="post" action="classes/runUpdateGrade1.php">
                           <div class="row">
                              <div class="col-md-12">
                                 <div class="form-body">
                                    <input type="hidden" name="curremployee" value="<?php echo $staffID; ?>">
                                    <input type="hidden" name="grade_level" value="<?php echo $empGrade; ?>">
                                    <input type="hidden" name="step" value="<?php echo $empStep; ?>">

                                    <div class="form-group">
                                       <label class="col-md-4 control-label">Grade:</label>
                                       <div class="col-md-7">
                                          <input type="text" class="form-control" id="grade" name="grade" value="<?php echo $empGrade; ?>" readonly>
                                       </div>
                                    </div>
                                    <div class="form-group">
                                       <label class="col-md-4 control-label">Step:</label>
                                       <div class="col-md-7">
                                          <input type="text" class="form-control" id="step" name="step" value="<?php echo $empStep; ?>" readonly>
                                       </div>
                                    </div>
                                    <div class="form-group">
                                       <label class="col-md-4 control-label">Grade:</label>
                                       <div class="col-md-7">
                                          <input type="text" class="form-control" id="new_grade" name="new_grade" value="" required>
                                       </div>
                                    </div>
                                    <div class="form-group">
                                       <label class="col-md-4 control-label">Step:</label>
                                       <div class="col-md-7">
                                          <input type="text" class="form-control" id="new_step" name="new_step" value="" required>
                                       </div>
                                    </div>

                                 </div>
                              </div>
                           </div>
                     </div>
                     <div class="modal-footer">
                        <button type="button" data-dismiss="modal" class="btn btn-outline dark">Close</button>
                        <button type="button" id="updateGradeStepButton" class="btn red">Save</button>
                        </form>
                     </div>
                  </div>
               </div>
            </div>

            <!-- Begining of Pr Thing-->
            <div class="modal fade" id="prorate" tabindex="-1" role="dialog" aria-labelledby="prorate" aria-hidden="true">
               <div class="modal-dialog" role="document">
                  <div class="modal-content">
                     <div class="modal-header modal-title" style="background: #6e7dc7;">
                        <h5 class=" modal-title" id="newprorate">Pro-rate Allowances for this Employee</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                           <span aria-hidden="true">&times;</span>
                        </button>
                     </div>
                     <div class="modal-body">
                        <form class="form-horizontal" name="form_newprotateemployee" id="form_newprotateemployee" method="post" action="classes/getProrate.php">
                           <div class="row">
                              <div class="col-md-12">
                                 <div class="form-body">
                                    <input type="hidden" name="curremployee" value="<?php echo $staffID; ?>">
                                    <input type="hidden" name="grade_level" value="<?php echo $empGrade; ?>">
                                    <input type="hidden" name="step" value="<?php echo $empStep; ?>">
                                    <?php
                                    $split = explode(' ', $_SESSION['activeperiodDescription'], 2);
                                    $mon = date('m', strtotime($split[0]));
                                    $yr =  $split[1];
                                    $dayss = cal_days_in_month(CAL_GREGORIAN, $mon, $yr);
                                    ?>
                                    <div class="form-group">
                                       <label class="col-md-4 control-label">No. of Day in Current Period:</label>
                                       <div class="col-md-7">
                                          <input type="text" class="form-control" id="no_days" name="no_days" value="<?php echo $dayss; ?>" readonly>
                                       </div>
                                    </div>
                                    <div class="form-group">
                                       <label class="col-md-4 control-label">No of Days to Calculate</label>
                                       <div class="col-md-7">
                                          <input type="number" min="0" max="<?php echo $dayss; ?>" value="0" required="required" class="form-control" id="daysToCal" name="daysToCal" placeholder="No of Days">
                                       </div>
                                    </div>


                                    <div class="form-group">
                                       <label class="col-md-4 control-label">Allowance</label>
                                       <div class="col-md-7">
                                          <table class="table table-bordered table-hover">
                                             <thead>
                                                <tr class="earnings-ded-header">
                                                   <th> Code </th>
                                                   <th> Description </th>
                                                   <th> Amount </th>

                                                </tr>
                                             </thead>
                                             <tbody>
                                                <?php
                                                try {
                                                   $query = $conn->prepare('SELECT allow_deduc.`value`,allow_deduc.allow_id,allow_deduc.temp_id,tbl_earning_deduction.edDesc FROM
																						tbl_earning_deduction INNER JOIN allow_deduc ON tbl_earning_deduction.ed_id = allow_deduc.allow_id
																						WHERE transcode = ? and staff_id = ? order by allow_id asc');
                                                   $fin = $query->execute(array('01', $staffID));;
                                                   $res = $query->fetchAll(PDO::FETCH_ASSOC);
                                                   //print_r($res);


                                                   foreach ($res as $row => $link2) {




                                                ?>
                                                      <tr class="odd gradeX">
                                                   <?php echo '<td>' . $link2['allow_id'];
                                                      echo '</td><td>' .    $link2['edDesc'];
                                                      echo '</td><td class="align-right">' . number_format($link2['value']) . '</td>';
                                                      echo '</td>';
                                                   }
                                                } catch (PDOException $e) {
                                                   echo $e->getMessage();
                                                }
                                                   ?>
                                                      </tr>
                                             </tbody>
                                          </table>
                                       </div>
                                    </div>
                                    <div class="form-group">
                                       <label class="col-md-4 control-label">Calculated Value</label>
                                       <div class="col-md-7">
                                          <div id="getProrateValue"></div>
                                       </div>
                                    </div>

                                 </div>
                              </div>
                           </div>
                     </div>
                     <div class="modal-footer">
                        <button type="button" data-dismiss="modal" class="btn btn-outline dark">Close</button>
                        <button type="button" id="addProButton" class="btn red">Calculate</button>
                        </form>
                     </div>
                  </div>
               </div>
            </div>
            <!-- Begining of Loan/Corporate Thing-->
            <div class="modal fade" id="loan_corporate" tabindex="-1" role="dialog" aria-labelledby="loan_corporate" aria-hidden="true">
               <div class="modal-dialog" role="document">
                  <div class="modal-content">
                     <div class="modal-header modal-title" style="background: #6e7dc7;">
                        <h5 class=" modal-title" id="newemployeeearning">Add New Loan/Corporate for this Employee</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                           <span aria-hidden="true">&times;</span>
                        </button>
                     </div>
                     <div class="modal-body">
                        <form class="form-horizontal" name="form_newloanemployeededuction" id="form_newloanemployeededuction" method="post" action="classes/controller.php?act=loan_corporate">
                           <div class="row">
                              <div class="col-md-12">
                                 <div class="form-body">
                                    <input type="hidden" name="curremployee" value="<?php echo $staffID; ?>">
                                    <input type="hidden" name="grade_level" value="<?php echo $empGrade; ?>">
                                    <input type="hidden" name="step" value="<?php echo $empStep; ?>">
                                    <div class="form-group">
                                       <label class="col-md-4 control-label">Description</label>
                                       <div class="col-md-7">
                                          <select required="required" class="form-control" id="newdeductioncodeloan" name="newdeductioncodeloan">
                                             <option>- - Select Deduction/Allowance - -</option>

                                             //
                                             <?php retrieveSelect('tbl_earning_deduction', '*', 'edType', '4', 'ed_id'); ?>


                                          </select>
                                       </div>
                                    </div>
                                    <div class="form-group">
                                       <label class="col-md-4 control-label">Principal</label>
                                       <div class="col-md-7">
                                          <input type="number" required="required" min="0" class="form-control" id="Principal" name="Principal" placeholder="Principal">
                                       </div>
                                    </div>
                                    <div class="form-group">
                                       <label class="col-md-4 control-label">Interest</label>
                                       <div class="col-md-7">
                                          <input type="number" min="0" value="0" required="required" class="form-control" id="interest" name="interest" placeholder="Interest">
                                       </div>
                                    </div>
                                    <div class="form-group">
                                       <label class="col-md-4 control-label">Repayment Period</label>
                                       <div class="col-md-7">
                                          <input type="number" min="0" value="1" required="required" class="form-control" id="no_times_repayment" name="no_times_repayment" placeholder="Run Times">
                                       </div>
                                    </div>
                                    <div class="form-group">
                                       <label class="col-md-4 control-label">Monthly Repayment</label>
                                       <div class="col-md-7">
                                          <input type="number" class="form-control" id="monthlyRepayment" name="monthlyRepayment" placeholder="Monthly Repayment">
                                       </div>
                                    </div>
                                    <div class="form-group">
                                       <label class="col-md-4 control-label">Existing Balance</label>
                                       <div class="col-md-7">
                                          <input type="number" class="form-control" id="Balance" name="Balance" placeholder="Balance" readonly>
                                       </div>
                                    </div>
                                 </div>
                              </div>
                           </div>
                     </div>
                     <div class="modal-footer">
                        <button type="button" data-dismiss="modal" class="btn btn-outline dark">Close</button>
                        <button type="button" id="addLoanButton" class="btn red">Add</button>
                        </form>
                     </div>
                  </div>
               </div>


            </div>
         </div>
      </div>
   </div>
   </div>
   </div>
   </div>
   <!-- Button trigger modal -->
   <!-- Modal -->
   <div id="footer" class="col-md-12 hidden-print">
      Please visit our
      <a href="#" target="_blank">
         website </a>
      to learn the latest information about the project.
      <span class="text-info">
         <span class="label label-info"> 14.1</span>
      </span>
   </div>
   <script type="text/javascript">
      $(document).ready(function() {
         //$("#ajax-loader").show();
         //$("#pickEmployee").select2();
         //$("#newdeductioncodeunion").select2();
         //$("Input[type=Select]").select2();
         $('#item').focus();
         var last_focused_id = null;
         var submitting = false;

         function salesBeforeSubmit(formData, jqForm, options) {
            if (submitting) {
               return false;
            }
            submitting = true;
            $("#ajax-loader").show();

         }

         function itemScannedSuccess(responseText, statusText, xhr, $form) {

            if (($('#code').val()) == 1) {
               gritter("Error", 'Item not Found', 'gritter-item-error', false, true);

            } else {
               gritter("Success", "Staff No Found Successfully", 'gritter-item-success', false, true);
               window.location.reload(true);
               $("#ajax-loader").hide();

            }
            setTimeout(function() {
               $('#item').focus();
            }, 10);

            setTimeout(function() {

               $.gritter.removeAll();
               return false;

            }, 1000);

         }

         $("#item").autocomplete({
            source: 'searchStaff.php',
            type: 'POST',
            delay: 10,
            autoFocus: false,
            minLength: 1,
            select: function(event, ui) {
               event.preventDefault();
               $("#item").val(ui.item.value);
               $('#add_item_form').ajaxSubmit({
                  beforeSubmit: salesBeforeSubmit,
                  success: itemScannedSuccess
               });

            }
         });

         $('#item').click(function() {
            $(this).attr('placeholder', '');
         });

         $("#no_times_repayment").blur(function() {
            // alert(parseFloat($("#principal").val().trim()));
            var monthlyPayment = ((parseFloat($("#Principal").val()) + parseFloat($("#interest").val())) / parseFloat($("#no_times_repayment").val()));

            $("#monthlyRepayment").val(monthlyPayment);
         });

         $("#monthlyRepayment").blur(function() {
            // alert(parseFloat($("#principal").val().trim()));
            var monthlyPayment = ((parseFloat($("#Principal").val()) + parseFloat($("#interest").val())) / parseFloat($(this).val()));

            $("#no_times_repayment").val(monthlyPayment);
         });


         //Ajax submit current location

         $("#addearningsButton").click(function() {

            $("#form_newearningcode").ajaxSubmit({
               url: 'classes/controller.php?act=addemployeeearning',
               success: function(response, message) {

                  $("#form_newearningcode").unmask();
                  submitting = false;

                  if (message == 'success') {
                     $("#reloadtable").load(location.href + " #reloadtable");

                  } else {
                     gritter("Error", message, 'gritter-item-error', false, false);

                  }


               }
            });

         })

         $("#addTempButton").click(function() {

            $("#form_temp").ajaxSubmit({
               url: 'classes/controller.php?act=tempearnings',
               success: function(response, message) {

                  $("#form_temp").unmask();
                  submitting = false;

                  if (message == 'success') {
                     $("#reloadtable").load(location.href + " #reloadtable");

                  } else {
                     gritter("Error", message, 'gritter-item-error', false, false);

                  }


               }
            });

         })


         $("#addDeductionButtonUnion").click(function() {

            $("#form_newedeductioncodeunion").ajaxSubmit({
               url: 'classes/controller.php?act=addemployeedeductionunion',
               success: function(response, message) {

                  $("#form_newedeductioncode").unmask();
                  submitting = false;

                  if (message == 'success') {

                     $("#reloadtable").load(location.href + " #reloadtable");


                  } else {
                     gritter("Error", message, 'gritter-item-error', false, false);

                  }


               }
            });

         })

         $("#addDeductionButton").click(function() {

            $("#form_newedeductioncode").ajaxSubmit({
               url: 'classes/controller.php?act=addemployeededuction',
               success: function(response, message) {

                  $("#form_newedeductioncode").unmask();
                  submitting = false;

                  if (message == 'success') {

                     $("#reloadtable").load(location.href + " #reloadtable");


                  } else {
                     gritter("Error", message, 'gritter-item-error', false, false);

                  }


               }
            });

         })

         $("#addLoanButton").click(function() {

            $("#form_newloanemployeededuction").ajaxSubmit({
               url: 'classes/controller.php?act=loan_corporate',
               success: function(response, message) {

                  $("#form_newedeductioncode").unmask();
                  submitting = false;

                  if (message == 'success') {
                     $("#reloadtable").load(location.href + " #reloadtable");


                  } else {
                     gritter("Error", message, 'gritter-item-error', false, false);

                  }


               }
            });

         })

         $("#addProButton").click(function() {
            if ($("#daysToCal").val() == '0' || ($("#daysToCal").val() == '')) {
               alert("No of Days to Calculate can not be 0");
               return false;
            } else if ($("#daysToCal").val() > $("#no_days").val()) {
               alert("No of Days to Calculate can not be Greater than No of Days in the month");
               return false;
            }
            $("#form_newprotateemployee").ajaxSubmit({
               url: 'classes/getProrate.php',
               success: function(response, message) {

                  $("#form_newedeductioncode").unmask();
                  submitting = false;

                  if (message == 'success') {
                     $("#getProrateValue").html(response);

                     location.reload(true);
                  } else {
                     gritter("Error", message, 'gritter-item-error', false, false);

                  }


               }
            });

         })

         $("#updateGradeStepButton").click(function() {
            if ($("#new_grade").val() == '0' || ($("#new_step").val() == '0') || ($("#new_step").val() == '') || ($("#new_grade").val() == '')) {
               alert("Please fill new Grade or Step fields");
               return false;
            }
            $("#form_newgrade_stepemployee").ajaxSubmit({
               url: 'classes/runUpdateGrade.php',
               success: function(response, message) {

                  $("#form_newedeductioncode").unmask();
                  submitting = false;

                  if (message == 'success') {
                     //$("#getProrateValue").html(response);

                     location.reload(true);
                  } else {
                     gritter("Error", message, 'gritter-item-error', false, false);

                  }


               }
            });

         })
         $(".btn btn-outline dark").click(function() {

            alert('ok');
            // location.reload(true);


         });

         $("#deletePayslip").click(function() {
            event.preventDefault();
            if (confirm('Are you sure you want to delete Payslip info for this employee?')) {
               $('#deletePayslip').attr('disabled', true);
               $('#deletePayslip').html("Transaction is processing");
               var staff_id = $("#thisemployee").val();
               $("#form_deletePayslip").ajaxSubmit({
                  url: 'classes/controller.php?act=deletecurrentstaffPayslip',
                  formData: {
                     staff_id: staff_id
                  },
                  success: function(response, message) {
                     window.location.reload(true);
                  }
               })


            }
         });

         $("#runPayslip").click(function() {
            event.preventDefault();
            if (confirm('Are you sure you want to Run Payslip info for this employee?')) {
               $('#runPayslip').attr('disabled', true);
               $('#runPayslip').html("Transaction is processing");
               var staff_id = $("#thisemployeePayslip").val();
               $("#form_runPayslip").ajaxSubmit({
                  url: 'classes/runPayrollindividual.php',
                  formData: {
                     staff_id: staff_id
                  },
                  success: function(response, message) {
                     if (message == 'success') {
                        $('#runPayslip').attr('disabled', false);
                        $('#runPayslip').html("Run this Employee's Payroll");
                        alert("Employee Payslip Successfully Processed");
                        window.location.reload(true);

                     }
                  }
               })


            }
         });

         $("#newdeductioncode").change(function() {
            var $option = $(this).find('option:selected');
            var $value = $option.val();

            if ($value == 50) {

               $("#form_newedeductioncode").ajaxSubmit({
                  url: 'classes/getPensionValue.php',
                  success: function(response, message) {

                     $("#form").unmask();
                     submitting = false;

                     if (message == 'success') {
                        if ($.trim(response) == '0') {

                           $("#deductionamount").val('');
                           $("#deductionamount").attr('readonly', false);

                        } else {
                           $("#deductionamount").val(response);
                           $("#deductionamount").attr('readonly', false);
                        }
                     } else {
                        gritter("Error", message, 'gritter-item-error', false, false);

                     }


                  }
               });
            } else if ($value == 41) {

               $("#form_newedeductioncode").ajaxSubmit({
                  url: 'classes/getTaxValue.php',
                  success: function(response, message) {

                     $("#form").unmask();
                     submitting = false;

                     if (message == 'success') {
                        if ($.trim(response) == '0') {

                           $("#deductionamount").val('');
                           $("#deductionamount").attr('readonly', false);

                        } else {
                           $("#deductionamount").val(response);
                           $("#deductionamount").attr('readonly', false);
                        }
                     } else {
                        gritter("Error", message, 'gritter-item-error', false, false);

                     }


                  }
               });

            } else {
               $("#deductionamount").val('');
               $("#deductionamount").attr('readonly', false);
            }
         });



         $("#newdeductioncodeloan").change(function() {
            $("#form_newloanemployeededuction").ajaxSubmit({
               url: 'classes/getLoanBalance.php',
               success: function(response, message) {

                  $("#form").unmask();
                  submitting = false;

                  if (message == 'success') {
                     if (response > 0) {
                        // $("#addLoanButton").attr('disabled', true);
                        $("#Balance").val(response);
                     } else {
                        $("#addLoanButton").attr('disabled', false);
                        $("#Balance").val(response);
                     }
                  } else {
                     gritter("Error", message, 'gritter-item-error', false, false);

                  }


               }
            });

         });

         $("#gradeStep").on('shown.bs.modal', function() {

            $("#new_grade").focus();
         })

         $("#newearningcode").change(function() {
            var code = $(this).find(':selected').data("code")

            $("#form_newearningcode").ajaxSubmit({
               url: 'classes/getSalaryValue.php',
               data: {
                  code: code
               },
               success: function(response, message) {

                  $("#form").unmask();
                  submitting = false;

                  if (message == 'success') {
                     if ($.trim(response) == 'manual') {

                        $("#earningamount").val('');
                        $("#earningamount").attr('readonly', false);

                     } else {
                        $("#earningamount").val(response);
                        $("#earningamount").attr('readonly', true);
                     }
                  } else {
                     gritter("Error", message, 'gritter-item-error', false, false);

                  }


               }
            });
         });

         $("#newemployeeearning,#newemployeededuction,#newemployeededuction_union,#newtempemployeededuction,#loan_corporate").on('hidden.bs.modal', function(e) {
            // window.location.reload(true);
         });


         $("#newdeductioncodeunion").change(function() {

            $("#form_newedeductioncodeunion").ajaxSubmit({
               url: 'classes/getUnionValue.php',
               success: function(response, message) {

                  $("#form").unmask();
                  submitting = false;

                  if (message == 'success') {
                     if ($.trim(response) == 'manual') {
                        $("#deductionamountunion").val('');
                        $("#deductionamountunion").attr('readonly', false);

                     } else {

                        $("#deductionamountunion").val(response);
                        $("#deductionamountunion").attr('readonly', true);

                     }
                  } else {
                     gritter("Error", message, 'gritter-item-error', false, false);

                  }


               }
            });



         });

      });
   </script>
   </div>
   <!--end #content-->
   </div>
   <!--end #wrapper-->
   <ul class="ui-autocomplete ui-front ui-menu ui-widget ui-widget-content ui-corner-all" id="ui-id-1" tabindex="0" style="display: none;"></ul>
</body>

</html>
<?php
mysqli_free_result($employee);
?>