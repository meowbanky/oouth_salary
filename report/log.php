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
							$from = date('y:m:d', time());;
							$to = date('y:m:d', time());;
							if (isset($_POST['from'])) {
								$from = $_POST['from'];
							}
							if (isset($_POST['to'])) {
								$to = $_POST['to'];
							}
							
							// Define variables for export filename
							$deductionName = 'LOG_REPORT';
							$month = date('M_Y', strtotime($from));
							try {
								$query = $conn->prepare('SELECT tbl_earning_deduction.edDesc, allow_deduc.`value`, A.`NAME` AS STAFFNAME,  counter, B.`NAME`,date_insert FROM allow_deduc INNER JOIN tbl_earning_deduction ON  allow_deduc.allow_id = tbl_earning_deduction.ed_id INNER JOIN employee AS A ON  allow_deduc.staff_id = A.staff_id INNER JOIN employee AS B ON  allow_deduc.inserted_by = B.staff_id WHERE
								date_insert BETWEEN ? AND ?	ORDER BY date_insert DESC');
								$res = $query->execute(array($from, $to));
								$out = $query->fetchAll(PDO::FETCH_ASSOC);

							?>


								<div class="col-md-12 pull-left">
									<img src="img/oouth_logo.gif" width="10%" height="10%" class="header-log" id="header-logo" alt="">
									<h3 style="text-transform: uppercase;" class="inline-block text-center">
										OLABISI ONABANJO UNIVERSITY TEACHING HOSPITAL<br>
										LOG OF WORD DONE BETWEEN <?php if (isset($_POST['from'])) {
																		echo $_POST['from'];
																	} ?> AND <?php if (isset($_POST['to'])) {
																					echo $_POST['to'];
																				} ?>


									</h3>
								</div>
								<div class="col-md-12 hidden-print">
									<form id="deduction_form" class="form-horizontal form-horizontal-mobiles" method="POST" action="log.php">
										<div class="form-group">
											<label for="range" class="col-sm-3 col-md-3 col-lg-2 control-label hidden-print">From :</label>
											<div class="col-sm-9 col-md-9 col-lg-10">&nbsp;
												<div class="input-group">
													<span class="input-group-addon"><i class="fa fa-location-arrow hidden-print"></i></span>
													<input type="date" name="from" id="from">
												</div>
											</div>

										</div>
										<div class="form-group">
											<label for="range" class="col-sm-3 col-md-3 col-lg-2 control-label hidden-print">To :</label>
											<div class="col-sm-9 col-md-9 col-lg-10">&nbsp;
												<div class="input-group">
													<span class="input-group-addon"><i class="fa fa-location-arrow hidden-print"></i></span>
													<input type="date" name="to" id="to">
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
									<li><a onclick="exportAll('xls','<?php echo $deductionName . ' ' . $month; ?>');" href="javascript://">XLS</a></li>
									<li><a onclick="exportAll('csv','<?php echo $deductionName . ' ' . $month; ?>');" href="javascript://">CSV</a></li>
									<li><a onclick="exportAll('txt','<?php echo $deductionName . ' ' . $month; ?>');" href="javascript://">TXT</a></li>

								</ul>
							</div>
						</div>
						<div id="download"></div>
						<div class="widget-content nopadding">
							<table border="1" id="sample_1" class="table_without">
								<thead>
									<tr>

										<th> S/No. </th>
										<th> Staff Name </th>
										<th> ALLOWANCE/DEDUCTION </th>
										<th> Amount </th>
										<th> EDITED BY </th>
										<th> DATE EDITED </th>
									</tr>
								</thead>
								<tbody>
									<?php

									$i = 1;
									while ($row = array_shift($out)) {

									?>
								<?php
										echo '<tr>';
										echo '<td class="stylecaps">' . $i .  '</td>
										<td class="stylecaps">' . $row['STAFFNAME'] .  '</td><td class="stylecaps">' . $row['edDesc'] . '</td>';
										echo '<td align="right">' . number_format($row['value']) . '</td>';
										echo '<td>' . ($row['NAME']) . '</td>';
										echo '<td>' . ($row['date_insert']) . '</td>';
										echo '</tr>';
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
						var downloadrow = document.createElement('a');
						var container = document.getElementById('download');
						downloadrow.href = 'data:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;base64,' + response;
						downloadrow.download = 'excel_file.xlsx';
						downloadrow.innerText = 'Download Here';
						// document.body.appendChild(downloadrow);
						container.appendChild(downloadrow);
						downloadrow.click();
						// document.body.removeChild(downloadrow);
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