<?php
session_start();

include_once('../classes/model.php');
require_once('../Connections/paymaster.php');
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
				<a href="../home.php"><i class="fa fa-home"></i> Dashboard</a><a href="index.php">Reports</a><a class="current" href="employee_report.php">Report Input: Detailed Employee Report</a>
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
								<h3 style="text-transform: uppercase;" class="inline-block text-center"><img src="img/oouth_logo.gif" width="10%" height="10%" class="header-log" id="header-logo" alt="">

									OLABISI ONABANJO UNIVERSITY TEACHING HOSPITAL<br>Employee Report for

									for the Month of:
									<?php

									$month = '';
									global $conn;
									if (!isset($_POST['period'])) {
										$period = -1;
									} else {
										$period = $_POST['period'];
									}

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
								</h3>
							</div>
							<div class="col-md-12 hidden-print">
								<form class="form-horizontal form-horizontal-mobiles" method="POST" action="employee_report.php">
									<div class="form-group">
										<label for="range" class="col-sm-3 col-md-3 col-lg-2 control-label hidden-print">Pay Period :</label>
										<div class="col-sm-9 col-md-9 col-lg-10">&nbsp;
											<div class="input-group">
												<span class="input-group-addon"><i class="fa fa-location-arrow hidden-print"></i></span>
												<select name="period" id="period" class="form-control hidden-print" required="required">
													<option value="">Select Pay Period</option>

													<?php
													global $conn;

													try {
														$query = $conn->prepare('SELECT payperiods.description, payperiods.periodYear, payperiods.periodId FROM payperiods WHERE payrollRun = ? order by periodId desc');
														$res = $query->execute(array('1'));
														$out = $query->fetchAll(PDO::FETCH_ASSOC);

														while ($row = array_shift($out)) {
															echo '<option value="' . $row['periodId'] . '"';
															if ($row['periodId'] == $period) {
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
						<div class="top-panel pull-right hidden-print">
							<div class="btn-group">

								<button type="button" class="btn btn-warning btn-large dropdown-toggle" data-toggle="dropdown">Export to <span class="caret"></span></button>
								<ul class="dropdown-menu" role="menu">
									<li><a onclick="window.print();">Print</a></li>
									<li><a onclick="exportAll('xls','<?php echo 'employee' . ' ' . $month; ?>');" href="javascript://">XLS</a></li>
									<li><a onclick="exportAll('csv','<?php echo 'employee' . ' ' . $month; ?>');" href="javascript://">CSV</a></li>
									<li><a onclick="exportAll('txt','<?php echo 'employee' . ' ' . $month; ?>');" href="javascript://">TXT</a></li>

								</ul>
							</div>
						</div>
						<div class="widget-content nopadding" style="max-width: 5000px; overflow-y: auto;">
							<table class="table_without" id="sample_1">
								<thead>
									<tr>

										<th> S/No </th>
										<th> Staff No. </th>
										<th> Name </th>
										<th> email </th>
										<th> Dept </th>
										<th> Emp Date </th>
										<th> Post </th>
										<th> Grade </th>
										<th> Step </th>
										<th> Bank </th>
										<th> Acct. No. </th>
										<!-- <th> PFA </th> -->
										<!-- <th> PIN </th> -->

									</tr>
								</thead>
								<tbody>
									<?php
									//retrieveData('employment_types', 'id', '2', '1');

									try {

										$sql = 'SELECT
												master_staff.staff_id, 
												master_staff.`NAME`, 
												tbl_dept.dept, 
												master_staff.GRADE, 
												master_staff.STEP, 
												tbl_bank.BNAME, 
												master_staff.ACCTNO, 
												employee.EMPDATE,employee.EMAIL,employee.POST
											FROM
												master_staff
												INNER JOIN
												tbl_dept
												ON 
													master_staff.DEPTCD = tbl_dept.dept_id
												INNER JOIN
												tbl_bank
												ON 
													master_staff.BCODE = tbl_bank.BCODE
												INNER JOIN
												employee
												ON 
													master_staff.staff_id = employee.staff_id WHERE master_staff.period = ?';
										$query = $conn->prepare($sql);
										$fin = $query->execute(array($period));
										$res = $query->fetchAll(PDO::FETCH_ASSOC);
										$numberofstaff = count($res);
										$counter = 1;

										$i = 1;
										echo '<tr class="odd gradeX">';
										foreach ($res as $row => $link) {
									?>
									<?php
											echo '<td class="stylecaps">' . $i .  '</td>';
											echo '<td class="stylecaps">' . $link['staff_id'] .  '</td>';
											echo '<td>' . $link['NAME'] . '</td>';
											echo '<td>' . $link['EMAIL'] . '</td>';
											echo '<td>' . $link['dept'] . '</td>';
											echo '<td>' . $link['EMPDATE'] . '</td>';
											echo '<td>' . $link['POST'] . '</td>';
											echo '<td>' . $link['GRADE'] . '</td>';
											echo '<td>' . $link['STEP'] . '</td>';
											echo '<td>' . $link['BNAME'] . '</td>';
											echo '<td>' . $link['ACCTNO'] . '</td>';

											echo '</tr>';
											$counter++;

											++$i;
										}
									} catch (PDOException $e) {
										echo $e->getMessage();
									}
									?>


									<!--Begin Data Table-->


									<!--End Data Table-->

								</tbody>
							</table>
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