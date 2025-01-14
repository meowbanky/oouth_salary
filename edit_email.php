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
					Edit Email</h1>


			</div>


			<div id="breadcrumb" class="hidden-print">
				<a href="home.php"><i class="fa fa-home"></i> Dashboard</a><a class="current" href="edit_email.php">Edit Email </a>
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
						<div class="btn-group  ">
							<div id="buttons">

								<button type="button" class="btn btn-warning btn-large dropdown-toggle" data-toggle="dropdown">Export to <span class="caret"></span></button>
								<ul class="dropdown-menu" role="menu">
									<li><a onclick="window.print();">Print</a></li>
									<li><a onclick="exportAll('xls','<?php echo 'pfa'; ?>');" href="javascript://">XLS</a></li>
									<li><a onclick="exportAll('csv','<?php echo 'pfa'; ?>');" href="javascript://">CSV</a></li>
									<li><a onclick="exportAll('txt','<?php echo 'pfa'; ?>');" href="javascript://">TXT</a></li>

								</ul>

							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="row ">
				<form action="edit_email.php" method="post" accept-charset="utf-8" id="add_item_form" autocomplete="off">
					<span role="status" aria-live="polite" class="ui-helper-hidden-accessible"></span>
					<input type="text" name="item" value="" id="item" class="ui-autocomplete-input" accesskey="i" placeholder="Enter Staff Name or Staff No" />
					<span id="ajax-loader"><img src="img/ajax-loader.gif" alt="" /></span>
				</form>
			</div>

			<div class="row">
				<div class="col-md-12">
					<div class="widget-box">
						<div class="widget-title">
							<span class="icon">
								<i class="fa fa-th"></i>
							</span>
							<h5>Salary Table</h5>
							<span title="" class="label label-info tip-left" data-original-title="total Employee">Total Employee<?php echo '100' ?></span>

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
													$sql = "SELECT count(*) as Total FROM employee WHERE STATUSCD = 'A'";
												} else {
													$sql = "SELECT count(*) as Total FROM employee WHERE STATUSCD = 'A' AND staff_id = {$_GET['item']}";
												}

												$result = $conn->query($sql);
												$row = $result->fetch();
												$total_pages = ceil($row['Total'] / $results_per_page);
												for ($i = 1; $i <= $total_pages; $i++) {
													echo '<li class="page-item ';
													if ($i == $page) {
														echo ' active"';
													};
													echo '"><a class="page-link" href="edit_email.php?page=' . $i . '">' . $i . '</a></li>';
												}
												?>
											</ul>
										</nav>
									</div>
									<div style="overflow-x: auto;">
										<table class="table table-striped table-bordered table-hover table-checkable order-column tblbtn w-auto" id="sample_1">
											<thead>
												<tr>
													<th> Staff No </th>
													<th> Name </th>
													<th> Department </th>
													<th> Email </th>
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
														$sql = 'SELECT employee.`NAME`, employee.EMAIL, employee.staff_id, tbl_dept.dept FROM employee INNER JOIN tbl_dept ON employee.DEPTCD = tbl_dept.dept_id WHERE STATUSCD = "A" ORDER BY NAME ASC LIMIT ' . $start_from . ',' . $results_per_page;
													} else {
														$sql = "SELECT employee.`NAME`, employee.EMAIL, employee.staff_id, tbl_dept.dept FROM employee INNER JOIN tbl_dept ON employee.DEPTCD = tbl_dept.dept_id WHERE STATUSCD = 'A' AND staff_id = {$_GET['item']} ORDER BY NAME ASC LIMIT " . $start_from . ',' . $results_per_page;
													}
													$query = $conn->prepare($sql);
													$fin = $query->execute();
													$res = $query->fetchAll(PDO::FETCH_ASSOC);
													//sdsd

													foreach ($res as $row => $link) {
												?><tr class="odd gradeX">
															<?php
															$thisemployeealterid = $link['staff_id'];
															$thisemployeeNum = $link['staff_id'];
															echo '<td>' . $link['staff_id'] .  '</td><td class="stylecaps">' . $link['NAME'] . '</td>';
															echo '<td>';
															echo $link['dept'];
															echo '</td>';
															echo '<td>';
															echo $link['EMAIL'];
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
												$sql = "SELECT count(*)  as Total FROM employee WHERE STATUSCD = 'A'";
											} else {
												$sql = "SELECT count(*) as Total FROM employee  WHERE STATUSCD = 'A' AND staff_id = {$_GET['item']}";
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
												echo '"><a class="page-link" href="edit_email.php?page=' . $i . '">' . $i . '</a></li>';
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

					$("#item").autocomplete({
						source: 'searchStaff.php',
						type: 'POST',
						delay: 10,
						autoFocus: false,
						minLength: 1,
						select: function(event, ui) {
							event.preventDefault();
							$("#item").val(ui.item.value);
							$item = $("#item").val();
							//$('#add_item_form').ajaxSubmit({beforeSubmit: salesBeforeSubmit, success: itemScannedSuccess});
							$('#add_item_form').ajaxSubmit({
								beforeSubmit: salesBeforeSubmit,
								type: "POST",
								url: "pfa.php",
								success: function(data) {
									window.location.href = "edit_email.php?item=" + $item;
								}


							});
						}
					});

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




						},

						// Specify the validation error messages
						messages: {
							namee: "The name is a required field.",


						},

						errorClass: "text-danger",
						errorElement: "span",
						highlight: function(element, errorClass, validClass) {
							$(element).parents('.form-group').removeClass('has-success').addClass('has-error');
						},
						unhighlight: function(element, errorClass, validClass) {
							$(element).parents('.form-group').removeClass('has-error').addClass('has-success');
						},

						submitHandler: function(form) {

							//form.submit();
							doEmployeeSubmit(form);
						}
					});

					document.getElementById('item').focus();

					//						$('#sample_1').Tabledit({
					//			      url:'action.php',
					//			      columns:{
					//			       identifier:[0, "StaffNo"],
					//			       editable:[[5, 'PFAPIN']
					//			      },
					//			      restoreButton:false,
					//			      onSuccess:function(data, textStatus, jqXHR)
					//			      {
					//			       if(data.action == 'delete')
					//			       {
					//			        $('#'+data.id).remove();
					//			       }
					//			      }
					//			     });


				});
			</script>


			<script>
				$(document).ready(function() {



					$('#sample_1').Tabledit({
						url: 'emailTable_edit.php',
						deleteButton: false,
						columns: {
							identifier: [0, "id"],
							editable: [
								[3, 'value']
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
		</div><!--end #content-->
	</div><!--end #wrapper-->

	<ul class="ui-autocomplete ui-front ui-menu ui-widget ui-widget-content ui-corner-all" id="ui-id-1" tabindex="0" style="display: none;"></ul>

</body>

</html>
<?php
//mysqli_free_result($employee);
?>