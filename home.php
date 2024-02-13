<?php require_once('Connections/paymaster.php');
include_once('classes/model.php');
include_once('backup.php');
session_start();
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

$query = $conn->prepare('SELECT periodId, description, periodYear FROM payperiods WHERE active = ? ORDER BY periodId ASC LIMIT 1');
$res = $query->execute(array(1));
$out = $query->fetchAll(PDO::FETCH_ASSOC);

while ($row = array_shift($out)) {
	$_SESSION['currentactiveperiod'] = $row['periodId'];
	$_SESSION['activeperiodDescription'] = $row['description'] . " " . $row['periodYear'];
}

//Start session


//Check whether the session variable SESS_MEMBER_ID is present or not
if (!isset($_SESSION['SESS_MEMBER_ID']) || (trim($_SESSION['SESS_MEMBER_ID']) == '')) {
	header("location: index.php");
	exit();
}
?>

<!DOCTYPE html>
<!-- saved from url=(0050)http://www.optimumlinkup.com.ng/pos/index.php/home -->
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

			<div id="content-header" class="hidden-print">
				<h1><i class="icon fa fa-dashboard"></i> Dashboard </h1>
			</div>
			<div id="breadcrumb" class="hidden-print">
				<a href="home.php"><i class="fa fa-home"></i> Dashboard</a>
			</div>
			<div class="clear"></div>
			<div class="text-center">
				<?php
				if (isset($_SESSION['msg'])) {
					echo '<div class="alert alert-' . $_SESSION['alertcolor'] . ' alert-dismissable role="alert"> <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>' . $_SESSION['msg'] . '</div>';
					unset($_SESSION['msg']);
					unset($_SESSION['alertcolor']);
				}
				?>
				<h3><strong style="font-size: 15px; color: #31708f;">WELCOME TO SALARY MANAGEMENT SYSTEM</strong></h3>
				<ul class="quick-actions">

					<?php if (($_SESSION['role'] == 'Admin') || ($_SESSION['role'] == 'user')) { ?> <li>
							<a class="padding-top" href="multiAdjustment.php"> <i class="text-info fa fa-shopping-cart left fa-3x "></i><br>
								Periodic Data</a>
						</li> <?php  } ?>

					<?php if ($_SESSION['role'] == 'Admin') { ?>
						<li>
							<a class="padding-top" id="link_deletetransaction"> <i class="text-info fa fa-cloud-download left fa-3x "></i><br><br><?php if (($_SESSION['role'] == 'Admin')) { ?> Delete Transaction <?php } ?></a>
						</li>
					<?php  } ?>
					<li>
						<a class="padding-top" href="report/index.php"> <i class="text-info fa fa-bar-chart-o left fa-3x "></i><br> Reports</a>
					</li>

					<?php if ($_SESSION['role'] == 'Admin') { ?> <li>
							<a class="padding-top" href="tax.php"> <i class="text-info fa fa-upload left fa-3x "></i><br> Update Tax</a>
						</li> <?php  } ?>

					<?php if ($_SESSION['role'] == 'Admin') { ?> <li>
							<a class="padding-top" href="upload_grade_step.php"> <i class="text-info fa fa-upload left fa-3x "></i><br> Bulk Grade/Step</a>
						</li> <?php  } ?>

					<?php if ($_SESSION['role'] == 'Admin') { ?> <li>
							<a class="padding-top" href="edit_email.php"> <i class="text-info fa fa-envelope left fa-3x "></i><br> Update Email</a>
						</li> <?php  } ?>

					<?php if ($_SESSION['role'] == 'Admin') { ?> <li>
							<a class="padding-top" href="payperiods.php"> <i class="text-info fa fa-calendar left fa-3x "></i><br> Pay Periods</a>
						</li> <?php  } ?>
					<?php if ($_SESSION['role'] == 'Admin') { ?> <li>
							<a class="padding-top" href="empearnings.php"> <i class="text-info fa fa-credit-card left fa-3x "></i><br> Emp Earnings/Deductions</a>
						</li> <?php  } ?>

					<?php if ($_SESSION['role'] == 'Admin') { ?> <li>
							<a class="padding-top" href="edit_conhess_conmess.php"> <i class="text-info fa fa-table left fa-3x "></i><br> Salary Table</a>
						</li> <?php  } ?>

					<?php if ($_SESSION['role'] == 'Admin') { ?> <li>
							<a class="padding-top" href="edit_deduction_table.php"> <i class="text-info fa fa-table left fa-3x "></i><br> Deduction Table</a>
						</li> <?php  } ?>

					<?php if ($_SESSION['role'] == 'Admin') { ?> <li>
							<a class="padding-top" href="call_backup.php"> <i class="text-info fa fa-hdd-o left fa-3x "></i><br> Backup</a>
						</li> <?php  } ?>

					<?php if ($_SESSION['role'] == 'Admin' || $_SESSION['role'] == 'pfa') { ?> <li>
							<a class="padding-top" href="pfa.php"> <i class="text-info fa fa-download left fa-3x "></i><br> Pension Fund Update</a>
						</li> <?php  } ?>


					<?php if ($_SESSION['role'] == 'Admin') { ?> <li>
							<a class="padding-top" href="earningsdeductions.php"> <i class="text-info fa fa-money left fa-3x "></i><br> Create New Deduction/<br>Allownance</a>
						</li> <?php  } ?>

					<?php if ($_SESSION['role'] == 'Admin') { ?> <li>
							<a class="padding-top" href="users.php"> <i class="text-info fa fa-group left fa-3x "></i><br> Users</a>
						</li> <?php  } ?>

					<?php if ($_SESSION['role'] == 'Admin') { ?> <li>
							<a class="padding-top" href="employee.php"> <i class="text-info fa fa-user left fa-3x "></i><br> Employees</a>
						</li> <?php  } ?>

					<?php if ($_SESSION['role'] == 'Admin') { ?> <li>
							<a class="padding-top" href="email_deduction.php"> <i class="text-info fa fa-envelope left fa-3x "></i><br> email Deduction List</a>
						</li> <?php  } ?>


					<?php if ($_SESSION['role'] == 'Admin') { ?> <li>
							<a class="padding-top" href="payprocess.php" accesskey="2"> <i class="text-info fa fa-cog left fa-3x "></i><br> Process Payroll</a>
						</li> <?php  } ?>

				</ul>

			</div>


		</div><!--end #content-->
	</div><!--end #wrapper-->
	<div class="modal fade" id="deletetransaction" tabindex="-1" role="dialog" aria-labelledby="creatededuction" aria-hidden="true">
		<div class="modal-dialog" role="document">
			<form class="form-horizontal" method="post" action="classes/controller.php?act=deletecurrentperiod">
				<div class="modal-content">
					<div class="modal-header modal-title" style="background: #6e7dc7;">
						<h5 class=" modal-title" id="newemployeeearning">Delete current payroll Transaction</h5>
						<button type="button" class="close" data-dismiss="modal" aria-label="Close">
							<span aria-hidden="true">&times;</span>
						</button>
					</div>
					<div class="modal-body">
						<form class="form-horizontal" name="form_newedeductioncode" id="form_delete" method="post" action="classes/controller.php?act=addallowance_deduction">
							<div class="row">
								<div class="col-md-12">
									<div class="form-group">
										<label class="col-md-4 control-label"><b>Current Active Payroll Period</b></label>
										<div class="col-md-4">

											<input type="hidden" name="activeperiodID" id="activeperiodID" value="<?php echo $_SESSION['currentactiveperiod']; ?>">
											<input type="hidden" name="activeperiodName" id="activeperiodName" value="<?php echo $_SESSION['activeperiodDescription']; ?>">


											<?php
											/*$query = $conn->prepare('SELECT description FROM payperiods WHERE companyId = ? AND active =?');
                                                                            $query->execute([$_SESSION['companyid'], '1']);
                                                                            $ftres = $query->fetchAll(PDO::FETCH_COLUMN);
                                                                            //print_r($ftres);
                                                                            $closingperiodname = $ftres[0];*/
											?>

											<input type="text" required class="form-control" name="activeperiod" value="<?php echo $_SESSION['activeperiodDescription']; ?>" disabled>

										</div>
									</div>




								</div>
							</div>

					</div>
					<div class="modal-footer">
						<button type="button" data-dismiss="modal" class="btn btn-outline dark">Cancel</button>
						<button type="submit" id="addDeductionButton" class="btn red">Delete</button>
			</form>
		</div>
	</div>
	</div>
	</div>

	<script type="text/javascript">
		$(document).ready(function() {
			$('#link_deletetransaction').click(function() {
				$('#deletetransaction').modal('show');
			});

			$('#addDeductionButton').click(function() {
				event.preventDefault();
				var activeperiodID = $('#activeperiodID').val();
				if (confirm('Are you sure you want to delete ' + $('#activeperiodName').val() + ' Transactions')) {
					$('#addDeductionButton').ajaxSubmit({
						formData: {
							activeperiodID: activeperiodID
						},
						url: 'classes/controller.php?act=deletecurrentperiod&activeperiodID=' + activeperiodID,
						success: function(response, message) {


							submitting = false;

							if (message == 'success') {
								if (response == 0) {

									alert("Payroll for the month has not been run");
									location.reload(true);
									gritter("Error", message, 'gritter-item-error', false, false);
								} else {
									alert("Payroll for the month succesfully deleted");
									//location.reload(true);
								}

							} else {
								gritter("Error", message, 'gritter-item-error', false, false);

							}


						}
					})
				}



			})

		})
	</script>
</body>

</html>
<?php

?>