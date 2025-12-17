<?php require_once('Connections/paymaster.php');
include_once('classes/model.php'); ?>
<?php
if(session_status() == PHP_SESSION_NONE ){
    session_start();
}

$_SESSION['SESS_INVOICE'] = 'SIV-23302280';

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
	function GetSQLValueString($conn, $theValue, $theType, $theDefinedValue = "", $theNotDefinedValue = "")
	{


		$theValue = function_exists("mysqli_real_escape_string") ? mysqli_real_escape_string($conn, $theValue) : mysqli_escape_string($conn, $theValue);

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



if (!isset($_SESSION['SESS_INVOICE']) or ($_SESSION['SESS_INVOICE'] == '')) {
	$_SESSION['SESS_INVOICE'] = 'JU-' . createRandomPassword();
}


if (isset($_POST['cancel']) and ($_POST['cancel'] == 'cancel')) {

	mysqli_select_db($salary, $database_salary);
	$deleteSql = sprintf("delete from tbl_workingfile where session_id = %s", GetSQLValueString($salary, $_SESSION['SESS_INVOICE'], "text"));
	$deleteResult = mysqli_query($salary, $deleteSql) or die(mysqli_error($salary));

	unset($_SESSION['SESS_INVOICE']);

	if (!isset($_SESSION['SESS_INVOICE']) or ($_SESSION['SESS_INVOICE'] == '')) {
		$_SESSION['SESS_INVOICE'] = 'SIV-' . createRandomPassword();
	}
}

if (isset($_POST['saveForm'])) {
	mysqli_select_db($salary, $database_salary);
	$sessionSQL = sprintf("select * from tbl_workingfile where session_id = %s", GetSQLValueString($salary, $_SESSION['SESS_INVOICE'], "text"));
	$session = mysqli_query($salary, $sessionSQL) or die(mysqli_error($salary));
	$row_session = mysqli_fetch_assoc($session);
	$totalRows_session = mysqli_num_rows($session);



	if ($totalRows_session > 0) {
		do {
			if ($row_session['stop_allow'] == 1) {
				$deleteAllow_Deduct =  sprintf(
					'Delete from allow_deduc where staff_id = %s and allow_id = %s',
					GetSQLValueString($salary, $row_session['staff_id'], "text"),
					GetSQLValueString($salary, $row_session['allow_id'], "int")
				);
				$Result1 = mysqli_query($salary, $deleteAllow_Deduct) or die(mysqli_error($salary));

				$deleteSql = sprintf("delete from tbl_workingfile where temp_id = %s", GetSQLValueString($salary, $row_session['temp_id'], "int"));
				$deleteResult = mysqli_query($salary, $deleteSql) or die(mysqli_error($salary));
			} else {
				$checkSQL =  sprintf(
					"select * from allow_deduc where allow_id = %s and staff_id = %s",
					GetSQLValueString($salary, $row_session['allow_id'], "int"),
					GetSQLValueString($salary, $row_session['staff_id'], "text")
				);
				$query_check = mysqli_query($salary, $checkSQL) or die(mysqli_error($salary));
				$row_check = mysqli_fetch_assoc($query_check);
				$totalRows_check = mysqli_num_rows($query_check);
				if ($totalRows_check > 0) {
					$updateSQL = sprintf(
						"UPDATE allow_deduc SET allow_deduc.`value` = %s,allow_deduc.transcode = %s,allow_deduc.counter = %s,inserted_by = %s,date_insert = now() where allow_deduc.staff_id = %s AND allow_deduc.allow_id = %s",
						GetSQLValueString($salary, $row_session['value'], "text"),
						GetSQLValueString($salary, $row_session['type'], "text"),
						GetSQLValueString($salary, $row_session['counter'], "int"),
						GetSQLValueString($salary, $row_session['inserted_by'], "text"),
						GetSQLValueString($salary, $row_session['staff_id'], "text"),
						GetSQLValueString($salary, $row_session['allow_id'], "int")
					);
					$Result1 = mysqli_query($salary, $updateSQL) or die(mysqli_error($salary));

					$deleteSql = sprintf("delete from tbl_workingfile where temp_id = %s", GetSQLValueString($salary, $row_session['temp_id'], "int"));
					$deleteResult = mysqli_query($salary, $deleteSql) or die(mysqli_error($salary));
				} else {
					$insertSQL = sprintf(
						"INSERT INTO allow_deduc (allow_deduc.staff_id,allow_deduc.allow_id,allow_deduc.`value`,allow_deduc.transcode,allow_deduc.counter,inserted_by,date_insert) VALUES (%s,%s,%s,%s,%s,%s,now())",
						GetSQLValueString($salary, $row_session['staff_id'], "text"),
						GetSQLValueString($salary, $row_session['allow_id'], "int"),
						GetSQLValueString($salary, $row_session['value'], "text"),
						GetSQLValueString($salary, $row_session['type'], "text"),
						GetSQLValueString($salary, $row_session['counter'], "int"),
						GetSQLValueString($salary, $row_session['inserted_by'], "text")
					);


					$Result1 = mysqli_query($salary, $insertSQL) or die(mysqli_error($salary));

					$deleteSql = sprintf("delete from tbl_workingfile where temp_id = %s", GetSQLValueString($salary, $row_session['temp_id'], "int"));
					$deleteResult = mysqli_query($salary, $deleteSql) or die(mysqli_error($salary));
				}
			}
		} while ($row_session = mysqli_fetch_assoc($session));
		// code to execute endwhile;
	}

	unset($_SESSION['SESS_INVOICE']);
	if (!isset($_SESSION['SESS_INVOICE']) or ($_SESSION['SESS_INVOICE'] == '')) {
		$_SESSION['SESS_INVOICE'] = 'JU-' . createRandomPassword();
	}
}

if (isset($_POST['amount'])) {
	if (($_POST['amount'] != "") && ($_POST['amount'] > 0)) {
		mysqli_select_db($salary, $database_salary);
		$updateSQL = sprintf(
			"update  tbl_workingfile SET `value` = %s where temp_id = %s",
			GetSQLValueString($salary, $_POST['amount'], "float"),
			GetSQLValueString($salary, $_POST['temp_id'], "int")
		);


		$Result1 = mysqli_query($salary, $updateSQL) or die(mysqli_error($salary));
	}
}

if (isset($_POST['stop_allow'])) {


	mysqli_select_db($salary, $database_salary);

	$updateSQL = sprintf(
		"update tbl_workingfile SET stop_allow = %s where temp_id = %s",
		GetSQLValueString($salary, $_POST['stop_allow'], "int"),
		GetSQLValueString($salary, $_POST['temp_id'], "int")
	);


	$Result1 = mysqli_query($salary, $updateSQL) or die(mysqli_error($salary));
}


if (isset($_POST['newdeductioncode'])) {
	if ($_POST['newdeductioncode'] != 0) {

		mysqli_select_db($salary, $database_salary);

		$selectSQL = sprintf(
			"SELECT tbl_earning_deduction.operator, tbl_earning_deduction.ed_id FROM tbl_earning_deduction WHERE ed_id = %s",
			GetSQLValueString($salary, $_POST['newdeductioncode'], "int")
		);
		$operator = mysqli_query($salary, $selectSQL) or die(mysqli_error($salary));
		$row_operator = mysqli_fetch_assoc($operator);
		$totalRows_operator = mysqli_num_rows($operator);

		$operator = '';
		if ($row_operator['operator'] == '+') {
			$operator = '1';
		} else {
			$operator = '2';
		}
		$updateSQL = sprintf(
			"update  tbl_workingfile SET allow_id = %s,type = %s where temp_id = %s",
			GetSQLValueString($salary, $_POST['newdeductioncode'], "int"),
			GetSQLValueString($salary, $operator, "text"),
			GetSQLValueString($salary, $_POST['temp_id'], "int")
		);


		$Result1 = mysqli_query($salary, $updateSQL) or die(mysqli_error($salary));
	}
}

if (isset($_POST['runningPeriod'])) {
	if (($_POST['runningPeriod'] != "") && ($_POST['runningPeriod'] >= 0)) {
		mysqli_select_db($salary, $database_salary);
		$updateSQL = sprintf(
			"update  tbl_workingfile SET counter = %s where temp_id = %s",
			GetSQLValueString($salary, $_POST['runningPeriod'], "float"),
			GetSQLValueString($salary, $_POST['temp_id'], "int")
		);


		$Result1 = mysqli_query($salary, $updateSQL) or die(mysqli_error($salary));
	}
}

if (isset($_GET['deleteid'])) {

	mysqli_select_db($salary, $database_salary);
	$deleteSQL = sprintf(
		"delete from tbl_workingfile where temp_id = %s",
		GetSQLValueString($salary, $_GET['deleteid'], "int")
	);


	$Result1 = mysqli_query($salary, $deleteSQL) or die(mysqli_error($salary));
}



$col_staffSearch = "-1";
if (isset($_POST['item'])) {
	$col_itemSearch = $_POST['item'];
}
if (!isset($_SESSION['deductoncode'])) {
	$_SESSION['deductoncode'] = -1;
}



if (isset($_POST['item'])) {
	mysqli_select_db($salary, $database_salary);

	$selectSQL = sprintf(
		"SELECT tbl_earning_deduction.operator, tbl_earning_deduction.ed_id FROM tbl_earning_deduction WHERE ed_id = %s",
		GetSQLValueString($salary, $_SESSION['deductoncode'], "int")
	);
	$operator = mysqli_query($salary, $selectSQL) or die(mysqli_error($salary));
	$row_operator = mysqli_fetch_assoc($operator);
	$totalRows_operator = mysqli_num_rows($operator);

	$operator = '';
	if($totalRows_operator > 0){
	if ($row_operator['operator'] == '+') {
		$operator = '1';
	} else {
		$operator = '2';
	}

}
	$insertSQL = sprintf(
		"INSERT INTO tbl_workingfile (session_id,staff_id,allow_id,inserted_by,type,date_insert) VALUES (%s,%s,%s,%s,%s,now())",
		GetSQLValueString($salary, $_SESSION['SESS_INVOICE'], "text"),
		GetSQLValueString($salary, $_POST['item'], "int"),
		GetSQLValueString($salary, $_SESSION['deductoncode'], "text"),
		GetSQLValueString($salary, $_SESSION['SESS_MEMBER_ID'], "text"),
		GetSQLValueString($salary, $operator, "int")
	);


	$Result1 = mysqli_query($salary, $insertSQL) or die(mysqli_error($salary));
}
mysqli_select_db($salary, $database_salary);
$query_saveDetails = sprintf("SELECT
   concat(employee.staff_id,' - ', employee.NAME) AS details,
   employee.staff_id,
   employee.NAME,
   tbl_workingfile.allow_id,
   tbl_workingfile.counter,
   tbl_workingfile.`value`,temp_id,tbl_workingfile.stop_allow
   FROM
   tbl_workingfile
   LEFT JOIN employee ON employee.staff_id = tbl_workingfile.staff_id 
   where session_id = %s order by temp_id desc", GetSQLValueString($salary, $_SESSION['SESS_INVOICE'], "text"));
$saveDetails = mysqli_query($salary, $query_saveDetails) or die(mysqli_error($salary));
$row_saveDetails = mysqli_fetch_assoc($saveDetails);
$totalRows_saveDetails = mysqli_num_rows($saveDetails);


if (!isset($_SESSION['SESS_INVOICE']) or ($_SESSION['SESS_INVOICE'] == '')) {
	$_SESSION['SESS_INVOICE'] = 'JU-' . createRandomPassword();
}

?>
<style>
	/* The Modal (background) */
	.modal {
		display: none;
		/* Hidden by default */
		position: fixed;
		/* Stay in place */
		z-index: 1;
		/* Sit on top */
		left: 0;
		top: 0;
		width: 100%;
		/* Full width */
		height: 100%;
		/* Full height */
		overflow: auto;
		/* Enable scroll if needed */
		background-color: rgb(0, 0, 0);
		/* Fallback color */
		background-color: rgba(0, 0, 0, 0.4);
		/* Black w/ opacity */
	}

	/* Modal Content/Box */
	.modal-content {
		background-color: #fefefe;
		margin: 15% auto;
		/* 15% from the top and centered */
		padding: 20px;
		border: 1px solid #888;
		width: 80%;
		/* Could be more or less, depending on screen size */
	}

	/* The Close Button */
	.close {
		color: #aaa;
		float: right;
		font-size: 28px;
		font-weight: bold;
	}

	.close:hover,
	.close:focus {
		color: black;
		text-decoration: none;
		cursor: pointer;
	}
</style>
<script language="javascript" type="application/javascript">
	//window.location = "new_employee.php";
	$("#suspend_sale_button").click(function() {
		if (confirm("Are you sure you want to suspend this sale?")) {
			$("#register_container").load('SalesAdd.php?suspend=suspend');

		}
	});
</script>
<div id="content-header" class="hidden-print sales_header_container">
	<h1 class="headigs"> <i class="icon fa fa-shopping-cart"></i>
		Periodic Data &nbsp;<?php echo $_SESSION['SESS_INVOICE'] ?><span id="ajax-loader"><img src="img/ajax-loader.gif" alt="" /></span>
	</h1>
</div>
<div class="clear"></div>
<!--Left small box-->
<div class="row">
	<div class="sale_register_leftbox col-md-9">
		<div class="row forms-area">
			<div class="col-md-8 no-padd">
				<div class="input-append">
					<form action="salesAdd.php" method="post" accept-charset="utf-8" id="add_item_form" class="form-inline" autocomplete="off"> <input type="text" name="item" value="" id="item" class="input-xlarge" accesskey="i" placeholder="Enter Staff Name or Staff No" />
						<input name="code" type="hidden" id="code" value="<?php if (isset($error)) {
																				echo $error;
																			} else {
																				echo -1;
																			} ?>" />
					</form>
				</div>
			</div>
			<div class="clear"></div>
			<div class="col-md-12">To stop Deduction/Allowance input 1 in <strong>Stop Field</strong>
			</div>
		</div>
		<div class="row">
			<div class="table-responsive">
				<table id="register" class="table table-bordered">
					<thead>
						<tr>
							<th></th>
							<th class="staff_name_heading">Staff No.</th>
							<th class="sales_item sales_items_number">Name</th>
							<th class="sales_stock">Allowance/Deduction</th>
							<th class="sales_price">Amount</th>
							<th class="sales_quality">Runing Period</th>
							<th class="sales_quality">Stop</th>
						</tr>
					</thead>
					<tbody id="cart_contents" class="sa">
						<?php if ($totalRows_saveDetails > 0) {
							do { ?>
								<tr id="reg_item_top" bgcolor="#eeeeee">
									<td><a href="salesAdd.php?deleteid=<?php echo $row_saveDetails['temp_id']  ?>" class="delete_item"><i class="fa fa-trash-o fa fa-2x text-error"></i> </a></td>
									<td class="text text-success"><?php echo $row_saveDetails['staff_id']; ?></td>
									<td class="text text-info sales_item" id="reg_item_number"><?php echo $row_saveDetails['details'] ?></td>
									<td class="text text-warning sales_stock" id="reg_item_stock">
										<form action="salesAdd.php" method="post" accept-charset="utf-8" class="line_item_form" autocomplete="off">
											<input name="temp_id" type="hidden" value="<?php echo $row_saveDetails['temp_id']  ?>" />


											<select required="required" class="form-control" id="newdeductioncode" name="newdeductioncode">
												<option>- - Select Deduction/Allowance - -</option>
												<?php
												try {
													global $conn;
													$query = $conn->prepare('SELECT tbl_earning_deduction.ed_id,tbl_earning_deduction.ed,tbl_earning_deduction.edDesc FROM tbl_earning_deduction');
													$res = $query->execute();
													$out = $query->fetchAll(PDO::FETCH_ASSOC);
													if (!isset($row_saveDetails['allow_id'])) {
														$row_saveDetails['allow_id'] = $_SESSION['deductoncode'];
													}
													while ($row = array_shift($out)) {
												?>
														<!--echo('<option value="' . $row['ed_id'].'">' . $row['ed_id'] . ' - ' . $row['edDesc'] . '</option>');-->
														<option value="<?php echo $row['ed_id']; ?>" <?php if ($row['ed_id'] == $row_saveDetails['allow_id']) { ?>selected="selected" <?php } ?>><?php echo $row['edDesc'] . ' - ' . $row['ed_id'] ?></option>
												<?PHP
													}
												} catch (PDOException $e) {
													echo $e->getMessage();
												}
												?>
											</select>
										</form>
									</td>
									<td>
										<form action="salesAdd.php" method="post" accept-charset="utf-8" class="line_item_form" autocomplete="off">
											<input type="number" required="required" min="0" id="amount" class="form-control" name="amount" value="<?php echo $row_saveDetails['value'] ?>" autofocus />
											<input name="temp_id" type="hidden" value="<?php echo $row_saveDetails['temp_id']  ?>" />
										</form>
									</td>
									<td id="reg_item_qty">
										<form action="salesAdd.php" method="post" accept-charset="utf-8" class="line_item_form" autocomplete="off">
											<input type="number" class="form-control" id="runningPeriod" name="runningPeriod" value="<?php echo $row_saveDetails['counter']  ?>" class="input-small" accesskey="q" />
											<input name="temp_id" type="hidden" value="<?php echo $row_saveDetails['temp_id']  ?>" />
										</form>
									</td>
									<td id="reg_item_qty">
										<form action="salesAdd.php" method="post" accept-charset="utf-8" class="line_item_form" autocomplete="off">
											<input type="number" class="form-control input-small" id="stop_allow" name="stop_allow" value="<?php echo $row_saveDetails['stop_allow']; ?>" accesskey="q" />
											<input name="temp_id" type="hidden" value="<?php echo $row_saveDetails['temp_id']  ?>" />
										</form>
									</td>
								</tr>
						<?php } while ($row_saveDetails = mysqli_fetch_assoc($saveDetails));
						} ?>
					</tbody>
				</table>
			</div>
			<ul class="list-inline pull-left">
			</ul>
		</div>
	</div>
	<!-- Right small box  -->
	<div class="col-md-3 sale_register_rightbox">
		<ul class="list-group">
			<li class="list-group-item nopadding">
				<!-- Cancel and suspend buttons -->
				<div class='sale_form_main'>
					<form action="salesAdd.php" method="post" accept-charset="utf-8" id="cancel_sale_form" autocomplete="off">
						<input type="button" class="btn btn-danger button_dangers" id="cancel_sale_button" value="Cancel Entry" accesskey="c" />
						<input name="cancel" type="hidden" id="cancel" value="cancel" />
					</form>
				</div>
			</li>
			<li class="list-group-item spacing">
			</li>
			<li class="list-group-item nopadding">
				<div id='sale_details'>
				</div>
			</li>
			<li class="list-group-item spacing">
			</li>
			<li class="list-group-item nopadding">
				<div id="Payment_Types">
				</div>
			</li>
			<li class="list-group-item">

				<form action="salesAdd.php" method="POST" accept-charset="utf-8" id="finish_sale_form" autocomplete="off">
					<?php if ($totalRows_saveDetails > 0) { ?>
						<input type='button' class='btn btn-success btn-large btn-block' name='finish_sale_button' id='finish_sale_button' value='Finish' />
						<input type='hidden' name='<?php echo $_SESSION['SESS_INVOICE']; ?>' <?php } ?> </div>
						<input name="saveForm" type="hidden" id="saveForm" value="save" />
				</form>
			</li>
		</ul>
	</div>
</div>
<!-- Trigger/Open The Modal -->
<!-- The Modal -->
<script type="text/javascript">
	// gritter("Warning","Warning, Desired Quantity is Insufficient. You can still process the sale, but check
	// your inventory",'gritter-item-warning',false,false);
</script>
<script type="text/javascript" language="javascript">
	var submitting = false;
	var last_focused_id = null;

	$(document).ready(function() {
		//Here just in case the loader doesn't go away for some reason
		$("#ajax-loader").hide();

		$(document).focusin(function(event) {

			last_focused_id = $(event.target).attr('id');

		});

		//if (last_focused_id && last_focused_id != 'item' && $('#'+last_focused_id).is('input[type=text]'))
		if ($('#' + last_focused_id) == 'amount') {
			$('#' + last_focused_id).focus();
			$('#' + last_focused_id).select();

		}

		// alert($('#' + last_focused_id));



		$('#mode_form, #select_customer_form, #add_payment_form, .line_item_form, #discount_all_form').ajaxForm({
			target: "#register_container",
			beforeSubmit: salesBeforeSubmit
		});

		$('#add_item_form').ajaxForm({
			target: "#register_container",
			beforeSubmit: salesBeforeSubmit,
			success: itemScannedSuccess
		});
		$("#cart_contents input").change(function() {
			$(this.form).ajaxSubmit({
				target: "#register_container",
				beforeSubmit: salesBeforeSubmit
			});
		});
		$('.form-control').change(function() {

			$(this.form).ajaxSubmit({
				target: "#register_container",
				beforeSubmit: salesBeforeSubmit
			});
		});
		$("#item").autocomplete({
			source: 'searchStaff.php',
			type: 'GET',
			delay: 10,
			autoFocus: false,
			minLength: 1,
			select: function(event, ui) {
				event.preventDefault();
				$("#item").val(ui.item.value);
				$('#add_item_form').ajaxSubmit({
					target: "#register_container",
					beforeSubmit: salesBeforeSubmit,
					success: itemScannedSuccess
				});
			}
		});

		$('#item,#customer').click(function() {
			$(this).attr('value', '');
		});


		$('#item').blur(function() {
			$(this).attr('value', "Enter Staff Name or Staff No.");
		});


		$('#newdeductioncode').change(function() {

			$.post('setdeductionsession.php', {
				deductoncode: $('#newdeductioncode').val()
			}, function() {



			});

		});


		$('#change_sale_date_enable').is(':checked') ? $("#change_sale_input").show() : $("#change_sale_input").hide();

		$('#change_sale_date_enable').click(function() {
			if ($(this).is(':checked')) {
				$("#change_sale_input").show();
			} else {
				$("#change_sale_input").hide();
			}
		});

		$('#use_saved_cc_info').change(function() {
			$.post('#', {
				use_saved_cc_info: $('#use_saved_cc_info').is(':checked') ? '1' : '0'
			});
		});

		$("#finish_sale_button").click(function() {



			$("#finish_sale_button").hide();


			$("#register_container").mask("Please wait...");
			$("#finish_sale_button").hide();


			if (!confirm("Are you sure you want to Save All Adjustments?")) {
				//Bring back submit and unmask if fail to confirm


				$("#finish_sale_button").show();
				$("#register_container").unmask();

				//$("#register_container").load('SalesAdd.php?saveForm=save');


			} else {
				$('#finish_sale_form').ajaxSubmit({
					target: "#register_container",
					beforeSubmit: salesBeforeSubmit
				});
				$("#finish_sale_button").show();
				$("#register_container").unmask();
			}







		});



		$("#suspend_sale_button").click(function() {
			if (confirm("Are you sure you want to suspend this sale?")) {
				$("#register_container").load('SalesAdd.php?suspend=suspend');
			}
		});

		$("#cancel_sale_button").click(function() {
			if (confirm("Are you sure you want to clear this sale? All items will cleared.")) {
				$('#cancel_sale_form').ajaxSubmit({
					target: "#register_container",
					beforeSubmit: salesBeforeSubmit
				});
			}
		});

		$("#add_payment_button").click(function() {
			$('#add_payment_form').ajaxSubmit({
				target: "#register_container",
				beforeSubmit: salesBeforeSubmit
			});
		});






		$('.delete_item, .delete_payment, #delete_customer').click(function(event) {
			event.preventDefault();
			$("#register_container").load($(this).attr('href'));
		});


		$("input[type=text]").not(".description").click(function() {
			$(this).select();
		});

		$("input[type=checkbox]").not(".input-small").change(function() {
			//$(this).select();
			if ($("stop_allow").is(":checked")) {
				$("stop_allow").is(":checked") = false
			}
			$("stop_allow").val(2)
			alert($("stop_allow").val())
		});

		//alert(screen.width);


		$("#new-customer").click(function() {
			$("body").mask("Please wait...");
		});
	});



	function salesBeforeSubmit(formData, jqForm, options) {
		if (submitting) {
			return false;
		}
		submitting = true;
		$("#ajax-loader").show();
		$("#add_payment_button").hide();
		$("#finish_sale_button").hide();
	}

	function itemScannedSuccess(responseText, statusText, xhr, $form) {

		if (($('#code').val()) == 1) {
			gritter("Error", 'Item not Found', 'gritter-item-error', false, true);

		} else {
			gritter("Success", "Item Addedd Successfully", 'gritter-item-success', false, true)
		}
		setTimeout(function() {
			$('#item').focus();
		}, 10);

		setTimeout(function() {

			$.gritter.removeAll();
			return false;

		}, 1000);

	}
</script>