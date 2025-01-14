<?php
session_start();

include_once('../classes/model.php');
require_once('../Connections/paymaster.php');
if (!isset($_SESSION['SESS_MEMBER_ID']) || (trim($_SESSION['SESS_MEMBER_ID']) == '')) {
	header("location: ../index.php");
	exit();
}
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
				<a href="../home.php"><i class="fa fa-home"></i> Dashboard</a><a href="index.php">Reports</a><a class="current" href="payrollDept.php">Report Input: Detailed Payroll Summary Report</a>
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
							<div class="col-md-12">
								<h4 style="text-transform: uppercase;" class="inline-block text-center"><img src="img/oouth_logo.gif" width="10%" height="10%" class="header-log" id="header-logo" alt="">

									olabisi ONABANJO UNIVERSITY TEACHING HOSPITAL<br> payroll SUMMARY FOR THE MONTH OF



									<?php $month = '';
									global $conn;
									if (!isset($_POST['period'])) {
										$period = -1;
									} else {
										$period = $_POST['period'];
									}
									try {
										$query = $conn->prepare('SELECT payperiods.description, payperiods.periodYear, payperiods.periodId FROM payperiods WHERE periodId = ?');
										$res = $query->execute(array($period));
										$out = $query->fetchAll(PDO::FETCH_ASSOC);

										while ($row = array_shift($out)) {
											echo ($month = $row['description'] . '-' . $row['periodYear']);
										}
									} catch (PDOException $e) {
										$e->getMessage();
									}

									?>
								</h4>
							</div>

							<div class="col-md-12 hidden-print">
								<form class="form-horizontal form-horizontal-mobiles" method="POST" action="payrollsummary_all.php">
									<div class="form-group">
										<label for="range" class="col-sm-3 col-md-3 col-lg-2 control-label hidden-print">Pay Period :</label>
										<div class="col-sm-9 col-md-9 col-lg-10">&nbsp;
											<div class="input-group">
												<span class="input-group-addon"><i class="fa fa-location-arrow"></i></span>
												<select name="period" id="period" class="form-control hidden-print">
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

									</div>

									<div class="form-actions">
										<button name="generate_report" type="submit" id="generate_report" class="btn btn-primary submit_button btn-large hidden-print">Submit</button>
									</div>
								</form>
							</div>
						</div>
						<?php if ($month != '') { ?><div class="top-panel pull-right hidden-print">
								<div class="btn-group">

									<button type="button" class="btn btn-warning btn-large dropdown-toggle" data-toggle="dropdown">Export to <span class="caret"></span></button>
									<ul class="dropdown-menu" role="menu">
										<li><a onclick="window.print();">Print</a></li>
										<li><a onclick="exportAll('xls','<?php echo $month . ' payrollsummary'; ?>');" href="javascript://">XLS</a></li>
										<li><a onclick="exportAll('csv','<?php echo $month . ' payrollsummary'; ?>');" href="javascript://">CSV</a></li>
										<li><a onclick="exportAll('txt','<?php echo $month . ' payrollsummary'; ?>');" href="javascript://">TXT</a></li>

									</ul>
								</div>
							</div><?php } ?>
						<div class="widget-content nopadding">
							<div class="table-responsive">
								<table id="sample_1" class="table_without">
									<thead>
										<tr>

											<th> Code </th>
											<th> Description </th>
											<th> Amount </th>



										</tr>

									</thead>
									<tbody>
										<tr>


											<td colspan="3" class="stylecaps"> Earnings </td>



										</tr>
										<?php
										//retrieveData('employment_types', 'id', '2', '1');
										if (!isset($_POST['period'])) {
											$period = -1;
										} else {
											$period = $_POST['period'];
										}
										try {
											$query = $conn->prepare('SELECT sum(tbl_master.allow) as allow,allow_id, tbl_earning_deduction.ed FROM tbl_master INNER JOIN tbl_earning_deduction ON tbl_earning_deduction.ed_id = tbl_master.allow_id WHERE tbl_master.type = ? and period = ? GROUP BY tbl_master.allow_id ');
											$fin = $query->execute(array('1', $period));
											$res = $query->fetchAll(PDO::FETCH_ASSOC);
											$numberofstaff = count($res);
											$counter = 1;
											//sdsd
											$sumAll = 0;
											$sumDeduct = 0;
											$sumTotal = 0;
											echo '<tr class="odd gradeX">';
											if ($numberofstaff > 0) {
												foreach ($res as $row => $link) {
										?>
												<?php
													echo '<td class="stylecaps">' . $link['allow_id'] .  '</td>';
													echo '<td class="stylecaps">' . $link['ed'] .  '</td><td align="right">' . number_format($link['allow']) . '</td>';
													$sumAll = $sumAll + floatval($link['allow']);
													$counter++;
													echo '</tr>';
												}
												echo '<tr class="odd gradeX">';

												echo '<td class="stylecaps" colspan="2">TOTAL earnings</td><td align="right"> ' . number_format($sumAll) . '</td>';


												echo '</tr>';
											}
										} catch (PDOException $e) {
											echo $e->getMessage();
										}


										echo '<tr class="odd gradeX">';
										echo '<td class="stylecaps"></td><td align="right"> </td>';


										echo '</tr>';

										echo '<tr class="odd gradeX">';

										echo '<td class="stylecaps" colspan=3>DEDUCTIONS</td>';


										echo '</tr>';

										//Deduction summary

										try {
											$query = $conn->prepare('SELECT sum(tbl_master.deduc) as deduct, allow_id,tbl_earning_deduction.ed FROM tbl_master INNER JOIN tbl_earning_deduction ON tbl_earning_deduction.ed_id = tbl_master.allow_id WHERE tbl_master.type = ? and period = ? GROUP BY tbl_master.allow_id ');
											$fin = $query->execute(array('2', $period));
											$res = $query->fetchAll(PDO::FETCH_ASSOC);
											$numberofstaff = count($res);
											$counter = 1;
											//sdsd

											$sumDeduct = 0;
											$sumTotal = 0;
											echo '<tr class="odd gradeX">';
											if ($numberofstaff > 0) {
												foreach ($res as $row => $link) {
												?>
										<?php
													echo '<td class="stylecaps">' . $link['allow_id'] .  '</td>';
													echo '<td class="stylecaps">' . $link['ed'] .  '</td><td align="right">' . number_format($link['deduct']) . '</td>';
													$sumDeduct = $sumDeduct + floatval($link['deduct']);
													$counter++;
													echo '</tr>';
												}
												echo '<tr class="odd gradeX">';

												echo '<td colspan="2" class="stylecaps">TOTAL DEDUCTIONS</td><td align="right"> ' . number_format($sumDeduct) . '</td>';


												echo '</tr>';
												echo '<tfoot>';
												echo '<t class="odd gradeX">';

												echo '<td colspan="2" class="stylecaps">NET PAY</td><td align="right"> ' . number_format(floatval($sumAll) - floatval($sumDeduct)) . '</td>';


												echo '</t>';
												echo '</tfoot>';
											}
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