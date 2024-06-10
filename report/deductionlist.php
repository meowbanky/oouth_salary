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
				<a href="../home.php"><i class="fa fa-home"></i> Dashboard</a><a href="index.php">Reports</a><a class="current" href="payrollDept.php">Report Input: Detailed deduction List Report</a>
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
							<?php
							global $conn;
							$deductionName = '';
							if (!isset($_POST['deduction'])) {
								$deduction = -1;
							} else {
								$deduction = $_POST['deduction'];
							}
							try {
								$query = $conn->prepare('SELECT tbl_earning_deduction.ed_id, tbl_earning_deduction.ed FROM tbl_earning_deduction WHERE ed_id = ?');
								$res = $query->execute(array($deduction));
								$out = $query->fetchAll(PDO::FETCH_ASSOC);

								while ($row = array_shift($out)) {
									$deductionName = $row['ed'];
								}
							} catch (PDOException $e) {
								$e->getMessage();
							}

							?>


							<div class="col-md-12 pull-left">
								<img src="img/oouth_logo.gif" width="10%" height="10%" class="header-log" id="header-logo" alt="">
								<h3 style="text-transform: uppercase;" class="inline-block text-center">
									OLABISI ONABANJO UNIVERSITY TEACHING HOSPITAL <br><?php echo $deductionName ?> Report

									for the Month of:
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
								</h3>
							</div>
							<div class="col-md-12 hidden-print">
								<form id="deduction_form" class="form-horizontal form-horizontal-mobiles" method="POST" action="deductionlist.php">
									<div class="form-group">
										<label for="range" class="col-sm-3 col-md-3 col-lg-2 control-label hidden-print">Pay Period :</label>
										<div class="col-sm-9 col-md-9 col-lg-10">&nbsp;
											<div class="input-group">
												<span class="input-group-addon"><i class="fa fa-location-arrow hidden-print"></i></span>
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
									<div class="form-group">
										<label for="range" class="col-sm-3 col-md-3 col-lg-2 control-label hidden-print">Deduction :</label>
										<div class="col-sm-9 col-md-9 col-lg-10">&nbsp;
											<div class="input-group">
												<span class="input-group-addon"><i class="fa fa-location-arrow hidden-print"></i></span>
												<select name="deduction" id="deduction" class="form-control hidden-print">
													<option value="">Select Deduction</option>

													<?php
													global $conn;

													try {
														$query = $conn->prepare('SELECT tbl_earning_deduction.ed_id, tbl_earning_deduction.ed , tbl_earning_deduction.type FROM tbl_earning_deduction WHERE edType > ? and `status` = ? order by ed_id asc');
														$res = $query->execute(array('0', 'Active'));
														$out = $query->fetchAll(PDO::FETCH_ASSOC);

														while ($row = array_shift($out)) {
															echo '<option value="' . $row['ed_id'] . '" data-code="' . $row['type'] . '"';
															if ($row['ed_id'] == $deduction) {
																echo 'selected = "selected"';
															};
															echo ' >' . $row['ed'] . ' - ' . $row['ed_id'] . '</option>';
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

										<button name="send_mail" id="send_mail" type="submit" id="generate_report" class="btn btn-primary submit_button btn-large hidden-print">Send Mail</button>
									</div>

								</form>

							</div>
						</div>
						<?php if ($deductionName != '') { ?><div class="top-panel pull-right hidden-print">
								<div class="btn-group">

									<button type="button" class="btn btn-warning btn-large dropdown-toggle" data-toggle="dropdown">Export to <span class="caret"></span></button>
									<ul class="dropdown-menu" role="menu">
										<li><a onclick="window.print();">Print</a></li>
										<li><a onclick="exportAll('xls','<?php echo $deductionName . ' ' . $month; ?>');" href="javascript://">XLS</a></li>
										<li><a onclick="exportAll('csv','<?php echo $deductionName . ' ' . $month; ?>');" href="javascript://">CSV</a></li>
										<li><a onclick="exportAll('txt','<?php echo $deductionName . ' ' . $month; ?>');" href="javascript://">TXT</a></li>

									</ul>
								</div>
							</div><?php } ?>
						<div id="download"></div>
						<div class="widget-content nopadding">
							<table id="sample_1" class="table_without">
								<thead>
									<tr>

										<th> S/No. </th>
										<th> Staff No. </th>
										<th> Name </th>
										<th> Amount </th>
										<?php if ($deduction == 87 || $deduction == 85) {
											echo '<th> Balance </th>';
										} ?>

									</tr>
								</thead>
								<tbody>
									<?php
									//retrieveData('employment_types', 'id', '2', '1');
									$type = -1;
									if (!isset($_POST['period']) or !isset($_POST['deduction'])) {
										$period = -1;
										$deduction = -1;
									} else {
										$period = $_POST['period'];
										$deduction = $_POST['deduction'];
									}
									try {
										$query = $conn->prepare('SELECT  tbl_earning_deduction.type FROM tbl_earning_deduction WHERE ed_id = ? order by ed_id');
										$res = $query->execute(array($deduction));
										$out = $query->fetchAll(PDO::FETCH_ASSOC);

										while ($row = array_shift($out)) {
											$type = $row['type'];
										}
									} catch (PDOException $e) {
										echo $e->getMessage();
									}

									try {
										if ($type == 1) {
											$query = $conn->prepare('SELECT tbl_master.allow as deduc, master_staff.staff_id, master_staff.`NAME` FROM tbl_master INNER JOIN master_staff ON master_staff.staff_id = tbl_master.staff_id WHERE tbl_master.allow_id = ? and tbl_master.period = ? and master_staff.period = ? order by master_staff.staff_id asc');
										} else {
											$query = $conn->prepare('SELECT tbl_master.deduc as deduc, master_staff.staff_id, master_staff.`NAME` FROM tbl_master INNER JOIN master_staff ON master_staff.staff_id = tbl_master.staff_id WHERE tbl_master.allow_id = ? and tbl_master.period = ? and master_staff.period = ? order by master_staff.staff_id asc');
										}
										$fin = $query->execute(array($deduction, $period, $period));
										$res = $query->fetchAll(PDO::FETCH_ASSOC);
										$numberofstaff = count($res);
										$counter = 1;
										//sdsd
										$sumAll = 0;
										$sumDeduct = 0;
										$sumTotal = 0;
										$i = 1;
										echo '<tr class="odd gradeX">';
										foreach ($res as $row => $link) {
									?>
									<?php
											echo '<td class="stylecaps">' . $i .  '</td><td class="stylecaps">' . $link['staff_id'] .  '</td><td class="stylecaps">' . $link['NAME'] . '</td>';
											echo '<td align="right">' . number_format($link['deduc']) . '</td>';
											if ($deduction == 87 || $deduction == 85) {
												$loan = retrieveLoanStatus($link['staff_id'], $deduction);
												$repayment = retrieveLoanBalanceStatus($link['staff_id'], $deduction, $period);
												echo '<td align="right">' . number_format($loan - $repayment) . '</td>';
											}
											$sumTotal = $sumTotal + floatval($link['deduc']);
											$counter++;
											echo '</tr>';
											++$i;
										}
										echo '<tr class="odd gradeX">';
										echo '<td class="stylecaps" colspan="3"><strong>TOTAL</strong></td>';
										echo '<td align="right"><strong>' . number_format($sumTotal) . '</strong></td>';

										echo '</tr>';
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

			$('#deduction').change(function(e) {
				$('#deduction_form').submit();
			})

			function isBase64(str) {
				// Base64 regular expression pattern
				const base64Pattern = /^[A-Za-z0-9+/]+[=]{0,2}$/;

				// Test the string against the pattern
				return base64Pattern.test(str);
			}

			$('#send_mail').click(function(e) {
				e.preventDefault();
				var code = $('#deduction').find(':selected').data('code')
				var period = $('#period').val()
				var period_text = $('#period option:selected').text()
				var deduction = $('#deduction').val()
				var deduction_text = $('#deduction option:selected').text()
				$.ajax({
					type: "post",
					url: "deductionlist_export.php",
					data: {
						period: period,
						deduction: deduction,
						deduction_text: deduction_text,
						period_text: period_text,
						code: code
					},
					success: function(response) {
						if (isBase64(response)) {
							console.log('The string is a valid base64-encoded string.');
						} else {
							console.log('The string is not a valid base64-encoded string.');
						}
						var downloadLink = document.createElement('a');
						var container = document.getElementById('download');
						downloadLink.href = 'data:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;base64,' + response;
						downloadLink.download = 'excel_file.xlsx';
						downloadLink.innerText = 'Download Here';
						// document.body.appendChild(downloadLink);
						container.appendChild(downloadLink);
						downloadLink.click();
						// document.body.removeChild(downloadLink);
					},
					error: function() {
						// Handle errors here
						console.log('Error downloading Excel file');
					}
				});
			});


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