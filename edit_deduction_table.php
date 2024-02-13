<?php ini_set('max_execution_time', '300');
require_once('Connections/paymaster.php');
include_once('classes/model.php'); ?>
<?php

//Start session
session_start();

//Check whether the session variable SESS_MEMBER_ID is present or not
if (!isset($_SESSION['SESS_MEMBER_ID']) || (trim($_SESSION['SESS_MEMBER_ID']) == '')) {
	header("location: index.php");
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

$currentPage = $_SERVER["PHP_SELF"];






$today = '';
$today = date('Y-m-d');
?>
<!DOCTYPE html>
<!-- saved from url=(0055)http://www.optimumlinkup.com.ng/pos/index.php/customers -->
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
				<h1> <i class="icon fa fa-table"></i>
					Deduction Table</h1>


			</div>


			<div id="breadcrumb" class="hidden-print">
				<a href="home.php"><i class="fa fa-home"></i> Dashboard</a><a class="current" href="edit_deduction.php">Edit Salary Table</a>
			</div>
			<div class="clear"></div>
			<div id="datatable_wrapper"></div>
			<div class=" pull-right">
				<div class="row">
					<div id="datatable_wrapper"></div>
					<div class="col-md-12 center" style="text-align: center;">
						<?php
						if (isset($_SESSION['msg'])) {
							echo '<div class="alert alert-' . $_SESSION['alertcolor'] . ' alert-dismissable role="alert"> <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>' . $_SESSION['msg'] . '</div>';
							unset($_SESSION['msg']);
							unset($_SESSION['alertcolor']);
						}
						?>
						<?php
						if (isset($_SESSION['msg'])) {
							echo '<div class="alert alert-' . $_SESSION['alertcolor'] . ' alert-dismissable role="alert"> <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>' . $_SESSION['msg'] . '</div>';
							unset($_SESSION['msg']);
							unset($_SESSION['alertcolor']);
						}
						?>

					</div>
				</div>
			</div>


			<div class="row">
				<div class="col-md-12">
					<div class="widget-box">
						<div class="widget-title">
							<span class="icon">
								<i class="fa fa-th"></i>
							</span>
							<h5>Deduction Table</h5>
							<span title="" class="label label-info tip-left" data-original-title="total Employee">Total
								Allowance/Deduction<?php echo '100' ?></span>

						</div>
						<!--endbegiing of employee details-->
						<div id="datatable_wrapper">

							<div class="row top-spacer-20">

								<div class="col-md-12">

									<div class="container">
										<nav aria-label="page navigation example" class="hidden-print">
											<ul class="pagination">

												<?php
												$results_per_page = 100;
												if (isset($_GET['page'])) {
													$page = $_GET['page'];
												} else {
													$page = 1;
												}
												$results_per_page = 100;
												if (!isset($_GET['item'])) {
													$sql = 'SELECT count(*) as Total FROM deductiontable';
												} else {
													$sql = 'SELECT count(*) as Total FROM deductiontable';
												}

												$result = $conn->query($sql);
												$row = $result->fetch();
												$total_pages = ceil($row['Total'] / $results_per_page);
												for ($i = 1; $i <= $total_pages; $i++) {
													echo '<li class="page-item ';
													if ($i == $page) {
														echo ' active"';
													};
													echo '"><a class="page-link" href="edit_deduction_table.php?page=' . $i . '">' . $i . '</a></li>';
												}
												?>
											</ul>
										</nav>
									</div>

									<table class="table table-striped table-bordered table-hover table-checkable order-column tblbtn" id="sample_1">
										<thead>
											<tr>
												<th> id </th>
												<th> Allowance </th>
												<th> Rate type </th>
												<th> Percentage </th>
												<th> Grade/Step </th>
												<th> Value </th>

											</tr>
										</thead>
										<tbody>

											<!--Begin Data Table-->
											<?php
											//retrieveData('employment_types', 'id', '2', '1');
											$results_per_page = 100;
											if (isset($_GET['page'])) {
												$page = $_GET['page'];
											} else {
												$page = 1;
											}

											try {
												$start_from = ($page - 1) * $results_per_page;
												if (!isset($_GET['item'])) {
													$sql = 'SELECT
	tbl_earning_deduction.edDesc, 
	deductiontable.allowcode, 
	deductiontable.grade, 
	deductiontable.step, 
	deductiontable.`value`, 
	deductiontable.category, 
	deductiontable.ratetype, 
	deductiontable.percentage
FROM
	deductiontable
	INNER JOIN
	tbl_earning_deduction
	ON 
		deductiontable.allowcode = tbl_earning_deduction.ed_id
		ORDER BY edDesc,allowcode,grade,step LIMIT ' . $start_from . ',' . $results_per_page;
												} else {
													$sql = 'SELECT
	dedcode.ADJDESC, 
	deductiontable.allowcode, 
	deductiontable.grade, 
	deductiontable.step, 
	deductiontable.`value`, 
	deductiontable.category, 
	deductiontable.ratetype, 
	deductiontable.percentage
FROM
	deductiontable
	INNER JOIN
	dedcode
	ON 
		deductiontable.allowcode = dedcode.ADJCD
		WHERE ADJDESC = "' . $_GET['item'] . '"
ORDER BY
	ADJDESC ASC, 
	GRADE ASC, 
	STEP ASC ';
												}
												$query = $conn->prepare($sql);
												$fin = $query->execute();
												$res = $query->fetchAll(PDO::FETCH_ASSOC);
												//sdsd

												foreach ($res as $row => $link) {
											?><tr class="odd gradeX">
														<?php
														$thisemployeealterid = $link['allowcode'];
														$thisemployeeNum = $link['allowcode'];
														echo '<td>' . $link['allowcode'] .  '</td><td class="stylecaps">' . $link['edDesc'] . '</td>';

														echo '<td>';
														echo $link['ratetype'];
														echo '</td>';

														echo '<td>';
														echo $link['percentage'];
														echo '</td>';

														echo '<td>';
														echo $link['grade'] . '/' . $link['step'];
														echo '</td><td>';
														echo $link['value'];
														echo '</td>';
														echo '</tr>';
														?>

												<?php
												}
											} catch (PDOException $e) {
												echo $e->getMessage();
											}
												?>
												<!--End Data Table-->





										</tbody>
									</table>







								</div>
								<div class="container">
									<nav aria-label="page navigation example" class="hidden-print">
										<ul class="pagination">

											<?php
											$results_per_page = 100;
											if (isset($_GET['page'])) {
												$page = $_GET['page'];
											} else {
												$page = 1;
											}
											$results_per_page = 100;
											if (!isset($_GET['item'])) {
												$sql = 'SELECT count(*)  as Total FROM deductiontable';
											} else {
												$sql = 'SELECT count(*) as Total FROM deductiontable';
											}

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
												echo '"><a class="page-link" href="edit_conhess_conmess.php?page=' . $i . '">' . $i . '</a></li>';
											}
											?>
										</ul>
									</nav>
								</div>
							</div>
						</div>
					</div>
					<!-- Button trigger modal -->


					<!-- Modal -->






				</div>
			</div>
			<div id="footer" class="col-md-12 hidden-print">
				Please visit our
				<a href="#" target="_blank">
					website </a>
				to learn the latest information about the project.
				<span class="text-info"> <span class="label label-info"> 14.1</span></span>
			</div>



			<script type="text/javascript">
				COMMON_SUCCESS = "Success";
				COMMON_ERROR = "Error";
				$.ajaxSetup({
					cache: false,
					headers: {
						"cache-control": "no-cache"
					}
				});

				$(document).ready(function() {

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
							//	window.location.reload(true);
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



					$('#item').click(function() {
						$(this).attr('placeholder', '');
					});
					//Ajax submit current location
					$("#employee_current_location_id").change(function() {
						$("#form_set_employee_current_location_id").ajaxSubmit(function() {
							window.location.reload(true);
						});
					});


					$('#employee_form').validate({

						// Specify the validation rules
						rules: {

							namee: "required",
							dept: "required",
							acct_no: {
								required: {
									depends: function(element) {
										if (($("#bank option:selected").text() != 'CHEQUE/CASH') || $(
												"#bank option:selected").text() != 'CHEQUE/CASH') {
											return true;
										} else {
											return false;
										}
									}
								},
								//"required": false,
								minlength: 10,
								maxlength: 10,
								number: true
							},

							rsa_pin: {
								required: {
									depends: function(element) {
										if ($("#pfa option:selected").text() != 'OTHERS') {
											return true;
										} else {
											return false;
										}
									}
								},
								number: true
							}


						},

						// Specify the validation error messages
						messages: {
							namee: "The name is a required field.",


						},

						errorClass: "text-danger",
						errorElement: "span",
						highlight: function(element, errorClass, validClass) {
							$(element).parents('.form-group').removeClass('has-success').addClass(
								'has-error');
						},
						unhighlight: function(element, errorClass, validClass) {
							$(element).parents('.form-group').removeClass('has-error').addClass(
								'has-success');
						},

						submitHandler: function(form) {

							//form.submit();
							doEmployeeSubmit(form);
						}
					});


				});
			</script>


			<script>
				$(document).ready(function() {



					$('#sample_1').Tabledit({
						url: 'deduction_Table_edit.php',
						deleteButton: false,
						columns: {
							identifier: [0, "id"],
							editable: [
								[3, 'percentage'],
								[5, 'value']
							]

						},
						dropdowns: {},
						dblclick: true,
						keyboard: true,
						hideIdentifier: true,
						restoreButton: false,
						onSuccess: function(data, textStatus, jqXHR) {
							if (data.action == 'delete') {
								$('#' + data.id).remove();
							}
						}
					});

				});
			</script>
			<script src="js/tableExport.js"></script>
			<script src="js/main.js"></script>
		</div>
		<!--end #content-->
	</div>
	<!--end #wrapper-->

	<ul class="ui-autocomplete ui-front ui-menu ui-widget ui-widget-content ui-corner-all" id="ui-id-1" tabindex="0" style="display: none;"></ul>

</body>

</html>
<?php
//mysqli_free_result($employee);
?>