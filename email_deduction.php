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
					<i class="icon fa fa-envelope"></i>
					DEDUCTION EMAIL LIST
				</h1>
			</div>
			<div id="breadcrumb" class="hidden-print">
				<a href="home.php">
					<i class="fa fa-home"></i> Dashboard
				</a>
				<a class="current" href="email_deduction.php">email Deduction</a>
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


					</div>
				</div>
			</div>

			<div class="row">
				<div class="col-md-12">
					<!-- BEGIN EXAMPLE TABLE PORTLET-->
					<div class="portlet light bordered">

						<div class="portlet-body">
							<div class="table-toolbar">
								<div class="row">
									<div class="col-md-6">
										<div class="btn-group">
											<a class="btn red" data-toggle="modal" data-target="#responsive"> Add New <i class="fa fa-plus"></i></a>
										</div>
									</div>
									<div class="col-md-6">
										<div class="btn-group pull-right">
											<button class="btn blue  btn-outline dropdown-toggle" data-toggle="dropdown">Export
												<i class="fa fa-angle-down"></i>
											</button>
											<ul class="dropdown-menu pull-right">
												<li>
													<a href="javascript:;">
														<i class="fa fa-print"></i> Print </a>
												</li>
												<li><a onclick="exportAll('csv');" href="javascript://">CSV</a></li>
												<li><a onclick="exportAll('txt');" href="javascript://">TXT</a></li>
												<li><a onclick="exportAll('xls');" href="javascript://">XLS</a></li>
											</ul>
										</div>
									</div>
								</div>


								<!-- responsive -->
								<div id="responsive" class="modal fade" tabindex="-1" data-width="560">
									<div class="modal-dialog">
										<div class="modal-content">
											<div class="modal-header" style="background: #6e7dc7;">
												<button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
												<h4 class="modal-title">Add email to List</h4>
											</div>
											<div class="modal-body">
												<form class="form-horizontal" method="post" action="classes/controller.php?act=email_deductionlist">
													<div class="row">
														<div class="form-group">

															<div class="col-md-12">
																<label class="control-label">Description</label>
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
															<div class="col-md-12">
																<label class="control-label"> email Deduction List</label>
																<input type="email" required class="form-control" name="email" placeholder="email">
															</div>

														</div>
														<div class="form-group">
															<div class="col-md-12">
																<label class="control-label"> CC email Deduction List</label>
																<input type="email" required class="form-control" name="cc_email" placeholder="cc_email">
															</div>

														</div>
													</div>
													<div class="modal-footer">
														<button type="button" data-dismiss="modal" class="btn btn-outline dark">Cancel</button>
														<button type="submit" class="btn red">Create New</button>
													</div>
												</form>
											</div>
										</div>
									</div>
								</div>


							</div>
							<table class="table table-striped table-bordered table-hover table-checkable order-column" id="sample_1">
								<thead>
									<tr>
										<th> #</th>
										<th> ID </th>
										<th> List Name </th>
										<th> email </th>
										<th> cc email </th>
									</tr>
								</thead>
								<tbody>

									<!--Begin Data Table-->
									<?php
									try {
										$query = $conn->prepare(
											'SELECT tbl_earning_deduction.edDesc, email_deductionlist.allow_id, email_deductionlist.email, email_deductionlist.bcc FROM email_deductionlist INNER JOIN tbl_earning_deduction ON  email_deductionlist.allow_id = tbl_earning_deduction.ed_id'
										);
										$fin = $query->execute();
										$res = $query->fetchAll(PDO::FETCH_ASSOC);
										//print_r($res);

										foreach ($res as $row => $link) {
									?><tr class="odd gradeX">
												<td><input type="checkbox"></td> <?php echo '<td>' . $link['allow_id'] .  '</td><td>' . $link['edDesc'] .  '</td><td>' . $link['email'] . '</td><td>' . $link['bcc'] . '</td>
                                                           </tr>';
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
					<!-- END EXAMPLE TABLE PORTLET-->
				</div>
				<!-- BEGIN PAGE TITLE-->

				<!-- END PAGE TITLE-->
				<!-- END PAGE HEADER-->



				<div class="clearfix"></div>
				<!-- END DASHBOARD STATS 1-->



			</div>
			<!-- END CONTENT BODY -->
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
		<script>
			$(document).ready(function() {



				$('#sample_1').Tabledit({
					url: 'email_deductionlist_edit.php',
					deleteButton: false,
					columns: {
						identifier: [1, "id"],
						editable: [
							[3, 'value'],
							[4, 'value2']
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