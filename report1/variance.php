<?php
session_start();
ini_set('max_execution_time', '0');
include_once('../classes/model.php');
require_once('../Connections/paymaster.php');
if (!isset($_SESSION['SESS_MEMBER_ID']) || (trim($_SESSION['SESS_MEMBER_ID']) == '')) {
	header("location: ../index.php");
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
				<a href="../home.php"><i class="fa fa-home"></i> Dashboard</a><a href="index.php">Reports</a><a class="current" href="payrollDept.php">Report Input: Detailed Variance Report</a>
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
									<p align="center"> OLABISI ONABANJO UNIVERSITY TEACHING HOSPITAL<br> PAYROLL VARIANCE BETWEEN THE MONTH OF
									<p align="center">

										<?php $monthTo = '';
										$monthFrom = '';
										$periodTo = -1;
										$periodFrom = -1;
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

										?> AND <?php $month = '';
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
								<form class="form-horizontal form-horizontal-mobiles" method="GET" action="variance.php">
									<div class="form-group">
										<div class="col-md-6">
											<div class="form-group">
												<label class="control-label">Current Month:</label>

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
															if ($row['periodId'] == $periodFrom) {
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
												<label class="control-label">Previous Month:</label>

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
															if ($row['periodId'] == $periodTo) {
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

									</div>

									<div class="form-actions">
										<button name="generate_report" type="submit" id="generate_report" class="btn btn-primary submit_button btn-large hidden-print">Submit</button>
									</div>
								</form>
							</div>
						</div>
						<?php


						$results_per_page = 100;
						if (isset($_GET['page'])) {
							$page = $_GET['page'];
						} else {
							$page = 1;
						} ?>
						<div class="top-panel pull-right hidden-print">
							<div class="btn-group">

								<button type="button" class="btn btn-warning btn-large dropdown-toggle" data-toggle="dropdown">Export to <span class="caret"></span></button>
								<ul class="dropdown-menu" role="menu">
									<li><a onclick="window.print();">Print</a></li>
									<li><a onclick="exportAll('xls','<?php echo 'variance btw ' . $monthTo . ' AND ' . $monthFrom; ?>');" href="javascript://">XLS</a></li>
									<li><a onclick="exportAll('csv','<?php echo 'variance btw ' . $monthTo . ' AND ' . $monthFrom; ?>');" href="javascript://">CSV</a></li>
									<li><a onclick="exportAll('txt','<?php echo 'variance btw ' . $monthTo . ' AND ' . $monthFrom; ?>');" href="javascript://">TXT</a></li>

								</ul>
							</div>
						</div>
						<div class="widget-content nopadding">
							<div class="table-responsive">

								<table border="1" class="table table-striped table-bordered table-hover table-checkable order-column table_without" id="sample_1">
									<thead>
										<tr>

											<th>S/N</th>
											<th>STAFF NO</th>
											<th> NAME</th>
											<th> <?php echo $monthFrom; ?></th>
											<th> <?php echo $monthTo; ?></th>
											<th> VARIANCE</th>

										</tr>
									</thead>
									<tbody>
										<?php
										//retrieveData('employment_types', 'id', '2', '1');




										try {

//											$query = $conn->prepare('SELECT master_staff.staff_id, ANY_VALUE(master_staff.`NAME`) AS `NAME`, ANY_VALUE(tbl_dept.dept) AS dept,ANY_VALUE(concat(payperiods.description," ",payperiods.periodYear)) as period FROM master_staff INNER JOIN tbl_dept ON tbl_dept.dept_id = master_staff.DEPTCD INNER JOIN payperiods ON payperiods.periodId = master_staff.period
//                                            WHERE master_staff.period = ? GROUP BY master_staff.staff_id ORDER BY master_staff.staff_id');

                                            $query = $conn->prepare('SELECT staff_id,ANY_VALUE(master_staff.`NAME`) AS `NAME` FROM master_staff WHERE period = ?
                                                                        UNION SELECT staff_id,ANY_VALUE(master_staff.`NAME`) AS `NAME` FROM master_staff WHERE period = ?
                                                                        ORDER BY staff_id;');
                                            $fin = $query->execute(array($periodTo,$periodFrom));
											$res = $query->fetchAll(PDO::FETCH_ASSOC);
											$numberofstaff = count($res);
											$counter = 1;
											//sdsd
											$i = 1;
											$sumCurrent = 0;
											$sumPrevious = 0;
											echo '<tr class="odd gradeX">';
											foreach ($res as $row => $link) {

												echo '<td class="stylecaps">' . $i .  '</td>';
												echo '<td class="stylecaps">' . $link['staff_id'] .  '</td>';
												echo '<td class="stylecaps"">' . $link['NAME'] . '</td>';

												$j = variance($periodFrom, $link['staff_id']);
												$sumCurrent = $j + $sumCurrent;
												echo '<td align="right">' . number_format($j) . '</td>';
												$k = variance($periodTo, $link['staff_id']);
												$sumPrevious = $k + $sumPrevious;
												echo '<td align="right">' . number_format($k) . '</td>';
												echo '<td align="right">' .  number_format($j - $k) . '</td>';
												echo '</tr>';

												$i = $i + 1;
											}
											echo '<tr class="odd gradeX">';
											echo '<td class="stylecaps" colspan="3">TOTAL</td>';
											echo '<td align="right"> <strong>' . number_format($sumCurrent) . '</strong></td>';
											echo '<td align="right"> <strong>' . number_format($sumPrevious) . '</strong></td>';
											echo '<td align="right"> <strong>' . number_format($sumCurrent - $sumPrevious) . '</strong></td>';
											echo '</tr>';
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
	</script>
	<script src="js/tableExport.js"></script>
	<script src="js/main.js"></script>
</body>

</html>