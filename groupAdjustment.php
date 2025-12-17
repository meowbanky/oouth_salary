<?php
session_start();
require_once('Connections/paymaster.php');
include_once('classes/model.php');

require_once 'libs/App.php';
$App = new App();
$App->checkAuthentication();
require_once 'libs/middleware.php';
checkPermission();

if (!isset($_SESSION['SESS_MEMBER_ID']) || (trim($_SESSION['SESS_MEMBER_ID']) == '')) {
	header("location: index.php");
	exit();
}
function createRandomPassword()
{
	$chars = "003232303232023232023456789";
	srand((float)microtime() * 1000000);
	$i = 0;
	$pass = '';
	while ($i <= 7) {

		$num = rand() % 33;

		$tmp = substr($chars, $num, 1);

		$pass = $pass . $tmp;

		$i++;
	}
	return $pass;
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
<?php include('header1.php'); ?>

<body data-color="grey" class="flat" style="zoom: 1;">
	<div class="modal fade hidden-print" id="myModal"></div>
	<div id="wrapper" class="minibar">
		<div id="header" class="hidden-print">
			<h1><a href="index.php"><img src="img/header_logo.png" class="hidden-print header-log" id="header-logo" alt=""></a></h1>
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

		<?php include('sidebar.php'); ?>



		<div id="content" class="clearfix sales_content_minibar">

			<div class="clear"></div>

			<div id="sale-grid-big-wrapper" class="clearfix">
				<div class="clearfix" id="category_item_selection_wrapper">


				</div>
			</div>
			<div id="breadcrumb" class="hidden-print">
				<a href="home.php"><i class="fa fa-home"></i> Dashboard</a><a class="current" href="groupAdjustment.php">Group Adjustment</a>
			</div>
			<div class="container margin-top-40">

				<div id="register_container" class="sales clearfix">
					<div class="row">
						<span id="ajax-loader"><img src="img/ajax-loader.gif" alt="" /></span>
						<div class="col">
							<form action="salesAdd.php" method="post" accept-charset="utf-8" class="line_item_form" autocomplete="off">

								<label class="label-icon aero blue-gradient-button">Select Group criteria <i class="fa fa-group" aria-hidden="true"></i></label>
								<select required="required" class="form-control" id="selecCriteria" name="selecCriteria">
									<option value="">Criteria</option>
									<option value="1">Department</option>
									<option value="0">Deduction List</option>
								</select>
							</form>
						</div>
						<div class="col" style="display:none;" id="groupDepartSelect">
							<form action="salesAdd.php" method="post" accept-charset="utf-8" class="line_item_form" autocomplete="off">

								<label class="label-icon aero blue-gradient-button">Select Department <i class="fa fa-group" aria-hidden="true"></i></label>
								<select required="required" class="form-control" id="groupDept" name="groupDept">
									<option value="">- - Select Dept - -</option>
									<?php
									try {
										global $conn;
										$query = $conn->prepare('SELECT * FROM tbl_dept');
										$res = $query->execute();
										$out = $query->fetchAll(PDO::FETCH_ASSOC);

										while ($row = array_shift($out)) {
									?>
											<option value="<?php echo $row['dept_id']; ?>"><?php echo $row['dept'] . ' - ' . $row['dept_id'] ?></option>
									<?PHP
										}
									} catch (PDOException $e) {
										echo $e->getMessage();
									}
									?>
								</select>
							</form>
						</div>
						<div class="col" style="display:none;" id="groupUnionSelect">
							<form action="salesAdd.php" method="post" accept-charset="utf-8" class="line_item_form" autocomplete="off">

								<label class="label-icon aero blue-gradient-button">Criteria to Group by <i class="fa fa-group" aria-hidden="true"></i></label>
								<select required="required" class="form-control" id="groupUnion" name="groupUnion">
									<option value="">- Select Union -</option>
									<?php
									try {
										global $conn;
										$query = $conn->prepare('SELECT tbl_earning_deduction.ed_id,tbl_earning_deduction.edType,tbl_earning_deduction.ed,tbl_earning_deduction.edDesc FROM tbl_earning_deduction');
										$res = $query->execute();
										$out = $query->fetchAll(PDO::FETCH_ASSOC);
										if (!isset($row_saveDetails['allow_id'])) {
											$row_saveDetails['allow_id'] = $_SESSION['deductoncode'];
										}
										while ($row = array_shift($out)) {
									?>
											<!--echo('<option value="' . $row['ed_id'].'">' . $row['ed_id'] . ' - ' . $row['edDesc'] . '</option>');-->
											<option value="<?php echo $row['ed_id']; ?>" data-code="<?php echo $row['edType'] ?>"><?php echo $row['edDesc'] . ' - ' . $row['ed_id'] ?></option>
									<?PHP
										}
									} catch (PDOException $e) {
										echo $e->getMessage();
									}
									?>
								</select>
							</form>
						</div>
						<div class="col">
							<label class="label-icon aero blue-gradient-button">Select Deduction </label>
							<select required="required" class="form-control" id="deduction" name="deduction">
								<option value="">- - Select Deduction - -</option>
								<?php
								try {
									global $conn;
									$query = $conn->prepare('SELECT tbl_earning_deduction.ed_id,tbl_earning_deduction.edType,tbl_earning_deduction.ed,tbl_earning_deduction.edDesc FROM tbl_earning_deduction');
									$res = $query->execute();
									$out = $query->fetchAll(PDO::FETCH_ASSOC);
									if (!isset($row_saveDetails['allow_id'])) {
										$row_saveDetails['allow_id'] = $_SESSION['deductoncode'];
									}
									while ($row = array_shift($out)) {
								?>
										<option value="<?php echo $row['ed_id']; ?>" data-code="<?php echo $row['edType'] ?>" <?php if ($row['ed_id'] == $row_saveDetails['allow_id']) { ?>selected="selected" <?php } ?>><?php echo $row['edDesc'] . ' - ' . $row['ed_id'] ?></option>
								<?PHP
									}
								} catch (PDOException $e) {
									echo $e->getMessage();
								}
								?>
							</select>
						</div>
						<div class="col">
							<label>Add/Delete</label>

							<select required="required" class="form-control" id="stop_allow" name="stop_allow">
								<option value="">- - Add/Delete - -</option>
								<option value="0">- - Add - -</option>
								<option value="1">- - Delete - -</option>
							</select>
						</div>
						<div id="add_delete" class="hidden">
							<div class="col">
								<label>Amount<i class="fa fa-sort-amount-asc" aria-hidden="true"></i></label>
								<input type="number" required="required" min="0" id="amount" class="form-control" name="amount" value="" autofocus />
							</div>
							<div class="col">
								<label>No of Months</label>
								<input type="number" class="form-control" id="runningPeriod" name="runningPeriod" value="0" class="input-small" accesskey="q" />
							</div>
						</div>
						<div class="col">
							<label>Run</label>
							<input type='button' class='btn btn-success btn-large btn-block' name='finish_sale_button' id='finish_sale_button' value='Finish' />
							<input type="hidden" name="saveForm" id="saveForm" value="save">
						</div>
					</div>



				</div>

				<div class="row margin-top-40">
					<div class="col">
						<div id="list" class="sales clearfix">

						</div>
					</div>
				</div>
			</div>




			<script type="text/javascript">
				$(document).ready(function() {
					$("#ajax-loader").hide();
					$('#groupUnion').change(function() {

						$.post('getGroup.php', {
								groupUnion: $('#groupUnion').val()
							},
							function(data) {

								$('#list').html(data)
							});

					});

					$('#groupDept').change(function() {

						$.post('getGroup.php', {
								groupDept: $('#groupDept').val()
							},
							function(data) {

								$('#list').html(data)
							});

					});


					$('#selecCriteria').change(function() {
						var selectedValue = $(this).val();
						if (selectedValue == 1) {
							$('#groupDepartSelect').css('display', 'block');
							$('#groupUnionSelect').css('display', 'none');
						} else {
							$('#groupUnionSelect').css('display', 'block');
							$('#groupDepartSelect').css('display', 'none');
						}

					});
					$('#stop_allow').change(function() {

						if ($('#stop_allow').val() == 0) {
							$('#add_delete').toggleClass('hidden', false)
						} else {
							$('#add_delete').toggleClass('hidden', true)
						}
					})
					$('#finish_sale_button').click(function() {
						$("#finish_sale_button").hide();
						if (($('#groupUnion').val() == '') && ($('#groupDept').val() == '')) {
							alert('Select Group or Dept criteria');
							$("#finish_sale_button").show();
							return false
						}

						if ($('#deduction').val() == '') {
							alert('Select Deduction')
							$("#finish_sale_button").show();
							return false
						}
						if ($('#stop_allow').val() == '') {
							alert('Add/Delete can\'t be empty');
							$("#finish_sale_button").show();
							return false
						}
						if ($('#stop_allow').val() == 0) {
							if ($('#amount').val() == '') {
								alert('Amount can\'t be empty');
								$("#finish_sale_button").show();
								return false
							}
							if ($('#runningPeriod').val() == '') {
								alert('Running Period can\'t be empty');
								$("#finish_sale_button").show();
								return false
							}
						}




						if (!confirm("Are you sure you want to Save All Adjustments?\n This Action is not reversable")) {
							//Bring back submit and unmask if fail to confirm


							$("#finish_sale_button").show();
							$("#register_container").unmask();


						} else {
							$('.container').mask("Please wait...");
							$("#finish_sale_button").hide();

							var criteria = $('#selecCriteria').val();

							$.post('getGroup.php', {
									groupUnion: $('#groupUnion').val(),
									deduction: $('#deduction').val(),
									code: $('#deduction').find(':selected').data('code'),
									amount: $('#amount').val(),
									runningPeriod: $('#runningPeriod').val(),
									stop_allow: $('#stop_allow').val(),
									saveForm: $('#saveForm').val(),
									groupDept: $('#groupDept').val(),
									criteria: criteria

								},
								function(data) {

									// $('#list').html(data)
									$("#finish_sale_button").show();
									$(".container").unmask();
								});
						}
					});

					var last_focused_id = null;
					setTimeout(function() {
						$('#item').focus();
					}, 10);

				});

				function salesBeforeSubmit(formData, jqForm, options) {
					if (submitting) {
						return false;
					}
					submitting = true;
					$("#ajax-loader").show();
				}
			</script>


			<div id="footer" class="col-md-12 hidden-print footer">
				Please visit our
				<a href="#" target="_blank">
					website </a>
				to learn the latest information about the project.
				<span class="text-info"> <span class="label label-info"> 14.1</span></span>
			</div>

		</div><!--end #content-->
	</div><!--end #wrapper-->


	<ul class="ui-autocomplete ui-front ui-menu ui-widget ui-widget-content ui-corner-all" id="ui-id-1" tabindex="0" style="display: none;"></ul>
	<ul class="ui-autocomplete ui-front ui-menu ui-widget ui-widget-content ui-corner-all" id="ui-id-2" tabindex="0" style="display: none;"></ul>
</body>

</html>