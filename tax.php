<?php require_once('Connections/paymaster.php');
include_once('classes/model.php');
require_once 'libs/App.php';
$App = new App();
$App->checkAuthentication();
require_once 'libs/middleware.php';
checkPermission();
?>
<?php
//Start session
session_start();

//Check whether the session variable SESS_MEMBER_ID is present or not
if (!isset($_SESSION['SESS_MEMBER_ID']) || (trim($_SESSION['SESS_MEMBER_ID']) == '') || $_SESSION['role'] != 'Admin') {
	header("location: index.php");
	exit();
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
		<div id="user-nav" class="hidden-print hidden-xs">
			<ul class="btn-group ">
				<li class="btn  hidden-xs">
					<a title="" href="switch_user" data-toggle="modal" data-target="#myModal">
						<i class="icon fa fa-user fa-2x"></i>
						<span class="text"> Welcome
							<b>
								<?php echo $_SESSION['SESS_FIRST_NAME']; ?>
							</b>
						</span>
					</a>
				</li>
				<li class="btn  hidden-xs disabled">
					<a title="" href="pos/" onclick="return false;">
						<i class="icon fa fa-clock-o fa-2x"></i>
						<span class="text">
							<?php
							$Today = date('y:m:d', time());
							$new = date('l, F d, Y', strtotime($Today));
							echo $new;
							?>
						</span>
					</a>
				</li>
				<li class="btn ">
					<a href="#">
						<i class="icon fa fa-cog"></i>
						<span class="text">Settings</span>
					</a>
				</li>
				<li class="btn  ">
					<a href="index.php">
						<i class="fa fa-power-off"></i>
						<span class="text">Logout</span>
					</a>
				</li>
			</ul>
		</div>
		<?php include('sidebar.php'); ?>
		<div id="content" class="clearfix sales_content_minibar">

			<div id="content-header" class="hidden-print">
				<h1>
					<i class="icon fa fa-upload"></i>
					Upload Staff Tax
				</h1>
			</div>
			<div id="breadcrumb" class="hidden-print">
				<a href="home.php">
					<i class="fa fa-home"></i> Dashboard
				</a>
				<a class="current" href="tax.php">Upload Staff Tax</a>
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
			<?php
			if (isset($_SESSION['msg'])) {
				echo '<div class="alert alert-' . $_SESSION['alertcolor'] . ' alert-dismissable role="alert"> <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>' . $_SESSION['msg'] . '</div>';
				unset($_SESSION['msg']);
				unset($_SESSION['alertcolor']);
			}
			?>


			<!-- BEGIN PAGE TITLE-->
			<div class="container">
				<h1 class="page-title"> Upload Staff Tax from Excel

				</h1>
			</div>

			<!-- END PAGE TITLE-->
			<!-- END PAGE HEADER-->


			<!--Begin Page Content-->

			<div class="row">
				<div class="col-md-12">
					<!-- BEGIN EXAMPLE TABLE PORTLET-->
					<div class="portlet light bordered">

						<div class="portlet-body">
							<div class="table-toolbar">
								<form class="form-horizontal" method="post" action="" id="upload_excel" name="upload_excel" enctype="multipart/form-data">
									<div class="row">
										<div class="col-md-4">
											<div class="form-body">
												<div class="form-group">
													<label class="col-md-4 control-label">Select Excel/CSV File to Upload</label>
													<div class="col-md-7">
														<input type="file" name="file" id="file" class="input-large">
													</div>
												</div>

											</div>
										</div>
										<div class="col-md-4">
											<div class="form-body">
												<div class="form-group">
													<label class="col margin-right-10">File Has Header?</label>

													<input type="checkbox" name="hasHeader" id="hasHeader" checked>

												</div>

											</div>
										</div>
										<div class="col-md-4">
											<div class="form-body">
												<div class="form-group">
													<label class="col margin-right-10">Download Template</label>

													<a href="download/tax_template.xlsx">Tax Template</a>

												</div>

											</div>
										</div>
									</div>
							</div>
							<div class="modal-footer">
								<button type="button" class="btn red" id="upload">Upload</button>
							</div>
							<input type="hidden" value="import">
							</form>


						</div>

					</div>
				</div>
				<!-- END EXAMPLE TABLE PORTLET-->
			</div>
		</div>

		<div class="clearfix"></div>
		<!-- END DASHBOARD STATS 1-->



	</div>
	<!-- END CONTENT BODY -->
	</div>
	<!-- END CONTENT -->

	</div>
	<!-- END CONTENT -->



	<div id="footer" class="col-md-12 hidden-print">
		Please visit our
		<a href="#" target="_blank">
			website </a>
		to learn the latest information about the project.
		<span class="text-info">
			<span class="label label-info"> 14.1</span>
		</span>
	</div>

	<script src="js/tableExport.js"></script>
	<script src="js/main.js"></script>
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






			//Ajax submit current location

			$("#upload").click(function() {
				$("#upload_excel").mask();
				event.preventDefault();
				var hasHeaders;
				$("#upload").html("Uploading Please wait");
				$('#upload').attr('disabled', true);
				$('#hasHeader').prop('checked')

				hasHeaders = ($('#hasHeader').is(':checked')) ? 1 : 0
				$("#upload_excel").ajaxSubmit({
					url: 'excel_import/import_tax.php',
					data: {
						hasHeaders: hasHeaders
					},
					success: function(response, message) {

						if (message == 'success') {
							//$("#reloadtable").load(location.href + " #reloadtable");
							//alert(response);
							$('#upload').attr('disabled', false);
							$("#upload").html("Upload");
							$("#upload_excel").trigger("reset");

						} else {
							gritter("Error", message, 'gritter-item-error', false, false);

						}


					}
				});
				$("#upload_excel").unmask();
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

			$(".btn btn-outline dark").click(function() {

				alert('ok');
				location.reload(true);


			});

			$("#newdeductioncode").change(function() {
				var $option = $(this).find('option:selected');
				var $value = $option.val();

				if ($value == 41) {

					$("#form_newedeductioncode").ajaxSubmit({
						url: 'classes/getPensionValue.php',
						success: function(response, message) {

							$("#form").unmask();
							submitting = false;

							if (message == 'success') {
								if ($.trim(response) == 'manual') {

									$("#deductionamount").val('');
									$("#deductionamount").attr('readonly', false);

								} else {
									$("#deductionamount").val(response);
									$("#deductionamount").attr('readonly', true);
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
								$("#addLoanButton").attr('disabled', true);
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

			$("#newearningcode").change(function() {

				$("#form_newearningcode").ajaxSubmit({
					url: 'classes/getSalaryValue.php',
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