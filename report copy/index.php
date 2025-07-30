<?php
require_once('../Connections/paymaster.php');
session_start();

//Check whether the session variable SESS_MEMBER_ID is present or not
if (!isset($_SESSION['SESS_MEMBER_ID']) || (trim($_SESSION['SESS_MEMBER_ID']) == '')) {
	header("location: index.php");
	exit();
}

?>
<!DOCTYPE html>
<html>
<?php include('../header1.php'); ?>

<body data-color="grey" class="flat">
	<div class="modal fade hidden-print" id="myModal"></div>
	<div id="wrapper">
		<div id="header" class="hidden-print">
			<h1><a href="index.php"><img src="support/header_logo.png" class="hidden-print header-log" id="header-logo" alt=""></a></h1>
			<a id="menu-trigger" href="#"><i class="fa fa-bars fa fa-2x"></i></a>
			<div class="clear"></div>
		</div>




		<div id="user-nav" class="hidden-print hidden-xs">
			<ul class="btn-group ">
				<li class="btn  hidden-xs"><a title="" href="switch_user" data-toggle="modal" data-target="#myModal"><i class="icon fa fa-user fa-2x"></i> <span class="text"> Welcome <b> <?php echo $_SESSION['SESS_FIRST_NAME']; ?> </b></span></a></li>
				<li class="btn  hidden-xs disabled">
					<a title="" href="pos/" onclick="return false;"><i class="icon fa fa-clock-o fa-2x"></i> <span class="text">
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


			<div id="content-header">
				<h1> <i class="icon fa fa-bar-chart-o"></i>
					Reports </h1>
			</div>


			<div id="breadcrumb" class="hidden-print">
				<a href="../home.php"><i class="fa fa-home"></i> Dashboard</a><a class="current" href="reports.php">Reports</a>
			</div>
			<div class="clear"></div>

			<div class="row report-listing">
				<div class="col-md-6  ">
					<div class="panel">
						<div class="panel-body">
							<div class="list-group parent-list">
								 
									<a href="#" class="list-group-item" id="sales"><i class="fa fa-shopping-cart"></i> Payroll</a>
								 
								 

									<a href="#" class="list-group-item" id="tax_export"><i class="fa fa-shopping-cart"></i> Export Tax Computation</a>

								 
								 

									<a href="#" class="list-group-item" id="employee"><i class="fa fa-shopping-cart"></i> Employee Report</a>

								 
								 
									<a href="#" class="list-group-item" id="transfer"><i class="fa fa-exchange"></i> Bank Summary</a>
								 
								 
									<a href="#" class="list-group-item" id="inventory"><i class="fa fa-table"></i> Deduction List</a>
								 
								 
									<a href="#" class="list-group-item" id="gross"><i class="fa fa-table"></i> Gross Amount</a>
								 
								 
									<a href="#" class="list-group-item" id="expiry-report">
									 
									 

										<i class="fa fa-search"></i>Net to Bank</a>
								 
								 
									<a href="#" class="list-group-item" id="customers"><i class="fa fa-group"></i> Payslip</a>

								 
								
									<a href="#" class="list-group-item" id="deleted-sales"><i class="fa fa-trash-o"></i> Pension Fund Admin</a>
								 
								 
									<a href="#" class="list-group-item" id="discounts"><i class="fa fa-magic"></i> Variance</a>
								 
								 
									<a href="#" class="list-group-item" id="log"><i class="fa fa-history fa-fw"></i> Audit Log</a>
								 
							</div>
						</div>
					</div> <!-- /panel -->
				</div>
				<div class="col-md-6" id="report_selection">
					<div class="panel">
						<div class="panel-body child-list">
							<h3 class="page-header text-info">« Reports: Make a selection</h3>
							<div class="list-group expiry-report hidden">
								<a href="net2bank.php" class="list-group-item ">
									<i class="fa fa-search report-icon"></i> Detailed Amount to Bank</a>
							</div>
							<div class="list-group customers hidden">
								<a class="list-group-item" href="payslip_all.php"><i class="fa fa-bar-chart-o"></i> Payslip All</a>
								<a class="list-group-item" href="payslip_dept.php"><i class="fa fa-building-o"></i> Paysip Department</a>
								<a class="list-group-item" href="payslip_personal.php"><i class="fa fa-calendar"></i> Individual</a>
							</div>
							<div class="list-group employees hidden">
								<a class="list-group-item" href="#"><i class="fa fa-bar-chart-o"></i> Graphical Reports</a>
								<a class="list-group-item" href="#"><i class="fa fa-building-o"></i> Summary Reports</a>
								<a class="list-group-item" href="#"><i class="fa fa-calendar"></i> Detailed Reports</a>
							</div>
							<div class="list-group sales hidden">
								<!-- <a class="list-group-item" href="#"><i class="fa fa-bar-chart-o"></i> Graphical Reports</a> -->
								<a class="list-group-item" href="payrollsummary_all.php"><i class="fa fa-building-o"></i> Payroll Summary All </a>
								<a class="list-group-item" href="payrollDept.php"><i class="fa fa-calendar"></i> Payroll Summary Dept</a>
								<a class="list-group-item" href="payrollexcel_all.php"><i class="fa fa-calendar"></i> Payroll by Excel2</a>
								<a class="list-group-item" href="payrollTable.php"><i class="fa fa-building-o"></i> Payroll Excel </a>
								<a class="list-group-item" href="payrollTablebyDept.php"><i class="fa fa-building-o"></i> Payroll Excel by Dept</a>

							</div>
							<div class="list-group tax_export hidden">
								<a class="list-group-item" href="taxexport.php"><i class="fa fa-bar-chart-o"></i> Export for Tax Computation</a>
								<a class="list-group-item" href="tax_returns.php"><i class="fa fa-bar-chart-o"></i> Annual Tax Return</a>

							</div>
							<div class="list-group employee hidden">
								<a class="list-group-item" href="employee_report.php"><i class="fa fa-bar-chart-o"></i> Employee Report</a>

							</div>
							<div class="list-group deleted-sales hidden">
								<a href="pfalist.php" class="list-group-item"><i class="fa fa-calendar"></i> PFA report</a>
								<a href="pfasummary.php" class="list-group-item"><i class="fa fa-calendar"></i> PFA report Summary</a>
							</div>
							<div class="list-group register-log hidden">
								<a href="#" class="list-group-item"><i class="fa fa-calendar"></i> Detailed Reports</a>
							</div>
							<div class="list-group transfer hidden">
								<!-- <a href="#" class="list-group-item"><i class="fa fa-bar-chart-o"></i> Graphical Reports</a> -->
								<a href="banksummary.php" class="list-group-item"><i class="fa fa-building-o"></i> Summary Reports</a>
							</div>
							<div class="list-group discounts hidden">
								<a href="variance.php" class="list-group-item"><i class="fa fa-building-o"></i> Variance</a>
							</div>
							<div class="list-group log hidden">
								<a href="log.php" class="list-group-item"><i class="fa fa-bar-chart-o"></i> Graphical Reports</a>
								<a href="#" class="list-group-item"><i class="fa fa-building-o"></i> Summary Reports</a>
							</div>
							<div class="list-group items hidden">
								<a href="#" class="list-group-item"><i class="fa fa-bar-chart-o"></i> Graphical Reports</a>
								<a href="#" class="list-group-item"><i class="fa fa-building-o"></i> Summary Reports</a>
							</div>
							<div class="list-group item-kits hidden">
								<a href="#" class="list-group-item"><i class="fa fa-bar-chart-o"></i> Graphical Reports</a>
								<a href="#" class="list-group-item"><i class="fa fa-building-o"></i> Summary Reports</a>
							</div>
							<div class="list-group payments hidden">
								<a href="#" class="list-group-item"><i class="fa fa-bar-chart-o"></i> Graphical Reports</a>
								<a href="#" class="list-group-item"><i class="fa fa-building-o"></i> Summary Reports</a>
							</div>
							<div class="list-group suppliers hidden">
								<a href="#" class="list-group-item"><i class="fa fa-bar-chart-o"></i> Graphical Reports</a>
								<a href="#" class="list-group-item"><i class="fa fa-building-o"></i> Summary Reports</a>
								<a href="http://www.optimumlinkup.com.ng/pos/index.php/reports/specific_supplier" class="list-group-item"><i class="fa fa-calendar"></i> Detailed Reports</a>
							</div>
							<div class="list-group taxes hidden">
								<a href="#" class="list-group-item"><i class="fa fa-bar-chart-o"></i> Graphical Reports</a>
								<a href="#" class="list-group-item"><i class="fa fa-building-o"></i> Summary Reports</a>
							</div>
							<div class="list-group receivings hidden">
								<a href="report_details_purchase.php" class="list-group-item"><i class="fa fa-calendar"></i> Detailed Reports</a>
							</div>
							<div class="list-group inventory hidden">
								<a href="deductionlist.php" class="list-group-item"><i class="fa fa-calendar"></i> Deduction List</a>
							</div>
							<div class="list-group gross hidden">
								<a href="gross.php" class="list-group-item"><i class="fa fa-calendar"></i> Gross List</a>
							</div>
							<div class="list-group giftcards hidden">
								<a href="#" class="list-group-item"><i class="fa fa-building-o"></i> Summary Reports</a>
								<a href="#" class="list-group-item"><i class="fa fa-calendar"></i> Detailed Reports</a>
							</div>
							<div class="list-group store-accounts hidden">
								<a href="#" class="list-group-item"><i class="fa fa-calendar"></i> Store Account Statements</a>
								<a href="#" class="list-group-item"><i class="fa fa-building-o"></i> Summary Reports</a>
								<a href="#" class="list-group-item"><i class="fa fa-calendar"></i> Detailed Reports</a>
							</div>
							<div class="list-group profit-and-loss hidden">
								<a class="list-group-item" href="#"><i class="fa fa-building-o"></i> Summary Reports</a>
								<a class="list-group-item" href="#"><i class="fa fa-calendar"></i> Detailed Reports</a>
							</div>
						</div>
					</div> <!-- /panel -->
				</div>
			</div>
		</div>
		<script type="text/javascript">
			$('.parent-list a').click(function(e) {
				e.preventDefault();
				$('.parent-list a').removeClass('active');
				$(this).addClass('active');
				var currentClass = '.child-list .' + $(this).attr("id");
				$('.child-list .page-header').html($(this).html());
				$('.child-list .list-group').addClass('hidden');
				$(currentClass).removeClass('hidden');

				$('html, body').animate({
					scrollTop: $("#report_selection").offset().top
				}, 500);
			});
		</script>


		<div id="footer" class="col-md-12 hidden-print">
			Please visit our
			<a href="#" target="_blank">
				website </a>
			to learn the latest information about the project.
			<span class="text-info"> <span class="label label-info"> 14.1</span></span>
		</div>

	</div><!--end #content-->
	<!--end #wrapper-->

</body>

</html>