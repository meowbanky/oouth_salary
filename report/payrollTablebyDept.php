<?php
session_start();
ini_set('max_execution_time', '0');
include_once('../classes/model.php');
require_once('Connections/paymaster.php');
if (!isset($_SESSION['SESS_MEMBER_ID']) || (trim($_SESSION['SESS_MEMBER_ID']) == '')) {
	header("location: ../index.php");
	exit();
}



?>
<!DOCTYPE html>
<?php include('../header1.php'); ?>

<body data-color="grey" class="flat">
	<div class="modal fade hidden-print" id="myModal"></div>
	<div id="wrapper">
		<div id="header" class="hidden-print">
			<h1><a href="../index.php"><img src="img/header_logo.png" class="hidden-print header-log" id="header-logo" alt=""></a></h1>
			<a id="menu-trigger" href="#"><i class="fa fa-bars fa fa-2x"></i></a>
			<div class="clear"></div>
		</div>




		<div id="user-nav" class="hidden-print hidden-xs">
			<ul class="btn-group ">
				<li class="btn  hidden-xs"><a title="" href="switch_user" data-toggle="modal" data-target="#myModal"><i class="icon fa fa-user fa-2x"></i> <span class="text"> Welcome <b> <?php echo $_SESSION['SESS_FIRST_NAME']; ?> </b></span></a></li>
				<li class="btn  hidden-xs disabled">
					<a title="" href="/" onclick="return false;"><i class="icon fa fa-clock-o fa-2x"></i> <span class="text">
							<?php
							$Today = date('y:m:d', time());
							$new = date('l, F d, Y', strtotime($Today));
							echo $new;
							?> </span></a>
				</li>
				<li class="btn "><a href="#"><i class="icon fa fa-cog"></i><span class="text">Settings</span></a></li>
				<li class="btn  ">
					<a href="index.php"><i class="fa fa-power-off"></i><span class="text">Logout</span></a>
				</li>
			</ul>
		</div>
		<?php include("report_sidebar.php"); ?>



		<div id="content" class="clearfix sales_content_minibar">

			<div id="content-header" class="hidden-print">
				<h1><i class="fa fa-beaker"></i> Report Input</h1> <span id="ajax-loader"><img src="img/ajax-loader.gif" alt="" /></span>
			</div>

			<div id="breadcrumb" class="hidden-print">
				<a href="../home.php"><i class="fa fa-home"></i> Dashboard</a><a href="index.php">Reports</a><a class="current" href="payrollDept.php">Report Input: Detailed Payroll by Dept Report</a>
			</div>
			<div class="clear"></div>
			<div class="row">
				<div class="col-md-12">
					<div class="widget-box">
						<div class="widget-title">
							<span class="icon">
								<i class="fa fa-align-justify"></i>
							</span>
							<h5 align="center"></h5>
							<div class="clear"></div>
							<div class="clear"></div>

						</div>
						<div class="row">
							<div class="col-md-12 pull-left">
								<img src="img/oouth_logo.gif" width="10%" height="10%" class="header-log" id="header-logo" alt="">
								<h2 class="page-title pull-right">
									<p align="center"> OLABISI ONABANJO UNIVERSITY TEACHING HOSPITAL<br> DEPARTMENTAL PAYROLL SUMMARY BETWEEN THE MONTH OF
									<p align="center">

										<?php $monthFrom = '';

										$periodTo = -1;
										$periodFrom = -1;
										$monthTo = '';
										global $conn;
										if (!isset($_GET['periodFrom'])) {
											$periodFrom = -1;
										} else {
											$periodFrom = $_GET['periodFrom'];
										}
										if (!isset($_GET['periodTo'])) {
											$periodTo = -1;
										} else {
											$periodTo = $_GET['periodTo'];
										}
										try {
											$query = $conn->prepare('SELECT payperiods.description, payperiods.periodYear, payperiods.periodId FROM payperiods WHERE periodId = ?');
											$res = $query->execute(array($periodFrom));
											$out = $query->fetchAll(PDO::FETCH_ASSOC);

											while ($row = array_shift($out)) {
												echo ($monthFrom = $row['description'] . '-' . $row['periodYear']);
											}
										} catch (PDOException $e) {
											$e->getMessage();
										}

										?> TO <?php $month = '';
												global $conn;
												if (!isset($_GET['periodTo'])) {
													$periodTo = -1;
												} else {
													$periodTo = $_GET['periodTo'];
												}
												try {
													$query = $conn->prepare('SELECT payperiods.description, payperiods.periodYear, payperiods.periodId FROM payperiods WHERE periodId = ?');
													$res = $query->execute(array($periodTo));
													$out = $query->fetchAll(PDO::FETCH_ASSOC);

													while ($row = array_shift($out)) {
														echo ($monthTo = $row['description'] . '-' . $row['periodYear']);
													}
												} catch (PDOException $e) {
													$e->getMessage();
												}

												?>
								</h2>
							</div>
							<div class="col-md-12 hidden-print">
								<form class="form-horizontal form-horizontal-mobiles" method="GET" action="payrollTablebyDept.php">
									<div class="form-group">
										<div class="row">
											<div class="col-md-6">
												<div class="form-group">
													<label class="control-label">Period:</label>

													<select name="periodFrom" id="periodFrom" class="form-control hidden-print" required="required">
														<option value="">Select Pay Period</option>

														<?php
														global $conn;

														try {
															$query = $conn->prepare('SELECT payperiods.description, payperiods.periodYear, payperiods.periodId FROM payperiods WHERE payrollRun = ? order by periodId desc');
															$res = $query->execute(array('1'));
															$out = $query->fetchAll(PDO::FETCH_ASSOC);

															while ($row = array_shift($out)) {
																echo '<option value="' . $row['periodId'] . '"';
																if ($row['periodId'] == $_SESSION['currentactiveperiod']) {
																	echo 'selected = "selected"';
																};
																echo ' >' . $row['description'] . ' - ' . $row['periodYear'] . '</option>';
															}
														} catch (PDOException $e) {
															echo $e->getMessage();
														}

														?>
													</select>
												</div>
											</div>

											<div class="col-md-6">
												<div class="form-group">
													<label class="control-label">Period:</label>

													<select name="periodTo" id="periodTo" class="form-control hidden-print" required="required">
														<option value="">Select Pay Period</option>

														<?php
														global $conn;

														try {
															$query = $conn->prepare('SELECT payperiods.description, payperiods.periodYear, payperiods.periodId FROM payperiods WHERE payrollRun = ? order by periodId desc');
															$res = $query->execute(array('1'));
															$out = $query->fetchAll(PDO::FETCH_ASSOC);

															while ($row = array_shift($out)) {
																echo '<option value="' . $row['periodId'] . '"';
																if ($row['periodId'] == $_SESSION['currentactiveperiod']) {
																	echo 'selected = "selected"';
																};
																echo ' >' . $row['description'] . ' - ' . $row['periodYear'] . '</option>';
															}
														} catch (PDOException $e) {
															echo $e->getMessage();
														}

														?>
													</select>
												</div>
											</div>

											<div class="col-md-6">
												<div class="form-group">
													<label class="control-label">Department:</label>
													<select name="dept" id="dept" class="form-control hidden-print" required="required">
														<option value="">Select Dept</option>
														<?php retrieveSelectwithoutWhere('tbl_dept', '*', 'dept', 'dept_id', 'dept'); ?>
													</select>
												</div>
											</div>
										</div>

									</div>

									<div class="form-actions">
										<button name="generate_report" type="submit" id="generate_report" class="btn btn-primary submit_button btn-large hidden-print">Submit</button>
									</div>
								</form>
							</div>
						</div>
						<?php

						//												 		
						if (!isset($_GET['dept']) || ($_GET['dept'] == '')) {
							$dept = -1;
						} else {
							$dept = $_GET['dept'];
						}

						$results_per_page = 100;
						if (isset($_GET['page'])) {
							$page = $_GET['page'];
						} else {
							$page = 1;
						}


						?>
						<div class="top-panel pull-right hidden-print">
							<div class="btn-group">

								<button type="button" class="btn btn-warning btn-large dropdown-toggle" data-toggle="dropdown">Export to <span class="caret"></span></button>
								<ul class="dropdown-menu" role="menu">
									<li><a onclick="window.print();">Print</a></li>
									<li><a onclick="exportAll('xls','<?php retrieveDescSingleFilter('tbl_dept', 'dept', 'dept_id', $dept);
																		echo ' ' . $month . ' Page ' . $page; ?>');" href="javascript://">XLS</a></li>
									<li><a onclick="exportAll('csv','<?php retrieveDescSingleFilter('tbl_dept', 'dept', 'dept_id', $dept);
																		echo ' ' . $month . ' Page ' . $page; ?>');" href="javascript://">CSV</a></li>
									<li><a onclick="exportAll('txt','<?php retrieveDescSingleFilter('tbl_dept', 'dept', 'dept_id', $dept);
																		echo ' ' . $month . ' Page ' . $page; ?>');" href="javascript://">TXT</a></li>

								</ul>
							</div>
						</div>
						<div class="widget-content nopadding">
							<div class="table-responsive">

								<nav aria-label="page navigation example" class="hidden-print">
									<ul class="pagination">

										<?php


										//$results_per_page = 100;

										$sql = 'SELECT count(staff_id) as "Total" FROM master_staff WHERE period BETWEEN ' . $periodFrom . ' AND ' . $periodTo . ' AND master_staff.DEPTCD = ' . $dept;
										$result = $conn->query($sql);
										$row = $result->fetch();
										$total_pages = ceil($row['Total'] / $results_per_page);
										for ($i = 1; $i <= $total_pages; $i++) {
											//echo "<a href='payslip_all.php?page=".$i."'";
											//	if($i ==$page){echo " class='curPage'";}
											//	echo "> ".$i." </a>";
											echo '<li class="page-item ';
											if ($i == $page) {
												echo ' active"';
											};
											echo '"><a class="page-link" href="payrollTablebyDept.php?page=' . $i . '&periodFrom=' . $periodFrom . '&periodTo=' . $periodTo . '&dept=' . $dept . '">' . $i . '</a></li>';
										}
										?>
									</ul>

									<table border="1" class="table table-striped table-bordered table-hover table-checkable order-column" id="sample_1">
										<thead>
											<tr>


												<th>STAFF NO</th>
												<th> NAME</th>
												<th> PAY PERIOD</th>
												<th> DEPT</th>
												<?php global $conn;
												$query = $conn->prepare('SELECT tbl_earning_deduction.ed_id, tbl_earning_deduction.ed FROM tbl_earning_deduction WHERE tbl_earning_deduction.edType = ?');
												$res = $query->execute(array(1));
												$out = $query->fetchAll(PDO::FETCH_ASSOC);

												while ($row = array_shift($out)) {
													echo '<th>' . $row['ed'] . '</th>';
												}
												echo '<th> TOTAL ALLOW </th>';


												global $conn;
												$query = $conn->prepare('SELECT tbl_earning_deduction.ed_id, tbl_earning_deduction.ed FROM tbl_earning_deduction WHERE tbl_earning_deduction.edType > ?');
												$res = $query->execute(array(1));
												$out = $query->fetchAll(PDO::FETCH_ASSOC);
												//get employee info                                          
												while ($row = array_shift($out)) {
													echo '<th>' . $row['ed'] . '</th>';
												}


												echo '<th>TOTAL DEDUC</th>';
												echo '<th> NET PAY</th>';
												?>

											</tr>
										</thead>
										<tbody>
											<?php
											//retrieveData('employment_types', 'id', '2', '1');




											try {
												$start_from = ($page - 1) * $results_per_page;
												$query = $conn->prepare('SELECT
master_staff.staff_id, DEPTCD,ANY_VALUE(master_staff.`NAME`) AS `NAME`, ANY_VALUE(tbl_dept.dept) AS dept,ANY_VALUE(concat(payperiods.description," ",payperiods.periodYear)) as period FROM
master_staff INNER JOIN tbl_dept ON tbl_dept.dept_id = master_staff.DEPTCD INNER JOIN payperiods ON payperiods.periodId = master_staff.period 
WHERE master_staff.period BETWEEN ? AND ? AND DEPTCD = ? GROUP BY master_staff.staff_id ORDER BY DEPTCD, staff_id LIMIT ' . $start_from . ',' . $results_per_page);
												$fin = $query->execute(array($periodFrom, $periodTo, $dept));
												$res = $query->fetchAll(PDO::FETCH_ASSOC);
												$numberofstaff = count($res);
												$counter = 1;
												//sdsd
												$sumAll = 0;
												$sumDeduct = 0;
												$sumTotal = 0;
												$countStaff = 0;
												echo '<tr class="odd gradeX">';
												foreach ($res as $row => $link) {
											?>
											<?php

													//                                                                $query2 = $conn->prepare('SELECT Count(master_staff.DEPTCD) as "numb" FROM master_staff WHERE STATUSCD = ? and DEPTCD = ? GROUP BY DEPTCD ');
													//                                                                $fin2 = $query2->execute(array('A',$link['DEPTCD']));
													//                                                                $res2 = $query2->fetchAll(PDO::FETCH_ASSOC);
													//                                                                foreach($res2 as $row2 => $link2){
													//                                                                	$numb =  $link2['numb'];
													//                                                                	$countStaff = $countStaff + $numb;
													//                                                                }
													$allow = 0;
													$dedu = 0;
													$net = 0;
													echo '<td class="stylecaps">' . $link['staff_id'] .  '</td><td class="stylecaps"">' . $link['NAME'] . '</td>';
													echo '<td align="left"> From ' . $monthFrom . 'To ' . $monthTo . '</td>';
													echo '<td >' . $link['dept'] . '</td>';

													global $conn;
													$query = $conn->prepare('SELECT tbl_earning_deduction.ed_id, tbl_earning_deduction.ed FROM tbl_earning_deduction WHERE tbl_earning_deduction.edType = ?');
													$res = $query->execute(array(1));
													$out = $query->fetchAll(PDO::FETCH_ASSOC);
													//get employee info 
													$allow = 0;
													while ($row = array_shift($out)) {
														$j = retrievePayroll($periodFrom, $periodTo, $link['staff_id'], $row['ed_id']);
														echo '<td>' . number_format($j) . '</td>';
														$allow = $allow + $j;
													}
													echo '<td>' . number_format($allow) . '</td>';

													$dedu = 0;
													global $conn;
													$query = $conn->prepare('SELECT tbl_earning_deduction.ed_id, tbl_earning_deduction.ed FROM tbl_earning_deduction WHERE tbl_earning_deduction.edType > ?');
													$res = $query->execute(array(1));
													$out = $query->fetchAll(PDO::FETCH_ASSOC);
													//get employee info                                          
													while ($row = array_shift($out)) {
														$j = retrievePayroll($periodFrom, $periodTo, $link['staff_id'], $row['ed_id']);
														echo '<td>' . number_format($j) . '</td>';
														$dedu = $dedu + $j;
													}


													echo '<td>' .  number_format($dedu) . '</td>';
													echo '<td>' .  number_format(floatval($allow) - floatval($dedu)) . '</td>';
													echo '</tr>';
												}
												//																echo '<tr class="odd gradeX">';
												//																echo '<td class="stylecaps">TOTAL</td><td align="right"> <strong>' . number_format($countStaff) . '</strong></td><td align="right"> <strong>' . number_format($sumAll) . '</strong></td>';
												//                                   											echo '<td align="right"><strong>'.number_format($sumDeduct).'</strong></td>';
												//                                                                               
												//                                                        echo '<td align="right"><strong>'. number_format($sumTotal).'</strong></td>';
												//																											
												//																echo '</tr>';
											} catch (PDOException $e) {
												echo $e->getMessage();
											}
											?>


										</tbody>
									</table>
							</div>
						</div>
					</div>
				</div>
				<div id="register_container" class="receiving"></div>
			</div>

		</div>

		<div id="footer" class="col-md-12 hidden-print">
			Please visit our
			<a href="http://www.oouth.com/" target="_blank">
				website </a>
			to learn the latest information about the project.
			<span class="text-info"> <span class="label label-info"> 14.1</span></span>
		</div>

	</div><!--end #content-->
	<!--end #wrapper-->


	<script type="text/javascript" language="javascript">
		$(document).ready(function() {
			//'sales_report.php');


			$("#start_month, #start_day, #start_year, #end_month, #end_day, #end_year").change(function() {
				$("#complex_radio").prop('checked', true);
			});

			$("#report_date_range_simple").change(function() {
				$("#simple_radio").prop('checked', true);
			});



			function receivingsBeforeSubmit(formData, jqForm, options) {
				var submitting = false;
				if (submitting) {
					return false;
				}
				submitting = true;

				$("#ajax-loader").show();
				//	$("#finish_sale_button").hide();
			}
		})
	</script>
	<script src="js/tableExport.js"></script>
	<script src="js/main.js"></script>
</body>

</html>