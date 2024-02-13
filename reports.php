<?php 
require_once('Connections/pos.php');
session_start();
	
	//Check whether the session variable SESS_MEMBER_ID is present or not
	if(!isset($_SESSION['SESS_MEMBER_ID']) || (trim($_SESSION['SESS_MEMBER_ID']) == '')) {
		header("location: index.php");
		exit();
	}

?>
<!DOCTYPE html>
<html><head>
<meta http-equiv="content-type" content="text/html; charset=UTF-8">
		<title>OOUTH Inventory Manager</title>
		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
		<!-- base href="http://www.optimumlinkup.com.ng/pos/" -->
		<link rel="icon" href="favicon.ico" type="image/x-icon">
		
					<link href="css/bootstrap.min.css" rel="stylesheet" rev="stylesheet" type="text/css" media="all">
					<link href="css/jquery.gritter.css" rel="stylesheet" rev="stylesheet" type="text/css" media="all">
					<link href="css/jquery-ui.css" rel="stylesheet" rev="stylesheet" type="text/css" media="all">
					<link href="css/unicorn.css" rel="stylesheet" rev="stylesheet" type="text/css" media="all">
					<link href="css/custom.css" rel="stylesheet" rev="stylesheet" type="text/css" media="all">
					<link href="css/datepicker.css" rel="stylesheet" rev="stylesheet" type="text/css" media="all">
					<link href="css/bootstrap-select.css" rel="stylesheet" rev="stylesheet" type="text/css" media="all">
					<link href="css/select2.css" rel="stylesheet" rev="stylesheet" type="text/css" media="all">
					<link href="css/font-awesome.min.css" rel="stylesheet" rev="stylesheet" type="text/css" media="all">
					<link href="css/jquery.loadmask.css" rel="stylesheet" rev="stylesheet" type="text/css" media="all">
					<link href="css/token-input-facebook.css" rel="stylesheet" rev="stylesheet" type="text/css" media="all">
<link href="css/dataTables.tableTools.css" rel="stylesheet" rev="stylesheet" type="text/css" media="all">
<link href="css/dataTables.tableTools.min.css" rel="stylesheet" rev="stylesheet" type="text/css" media="all">
				<script type="text/javascript">
			var SITE_URL= "index.php";
		</script>
		
					<script type="text/javascript" src="js/shortcut.js"></script>

<script>
    shortcut.add("F4", function() {
       window.location = "new_employee.php";
	   
	      });   
    shortcut.add("ctrl+d", function() {
        // Do something
		alert("ok");
    }); 
</script>


				<script type="text/javascript">
			var SITE_URL= "index.php";
		</script>
			
		
<script src="support/all.js" type="text/javascript" language="javascript" charset="UTF-8"></script>
<script src="support/bootstrap-datepicker.js" type="text/javascript" language="javascript" charset="UTF-8"></script>

<script type="text/javascript">
$(document).ready(function() {
   
	 $("#search").focus();
		
} );


</script>
		 <script>

                    var isNS4=(navigator.appName=="Netscape")?1:0;

                    function auto_logout(iSessionTimeout,iSessTimeOut,sessiontimeout)

                    {

                             window.setTimeout('', iSessionTimeout);

                              window.setTimeout('winClose()', iSessTimeOut);

                    }

                    function winClose() {

                        //alert("Your Application session is expired.");

                   if(!isNS4)

	           {

		          window.navigate("index.php");

	           }

                  else

	          {

		        window.location="index.php";

	           }

             }

            auto_logout(1440000,1500000,1500)

</script>
	</head>
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
				<li class="btn  hidden-xs"><a title="" href="switch_user" data-toggle="modal" data-target="#myModal"><i class="icon fa fa-user fa-2x"></i> <span class="text">	Welcome <b> <?php echo $_SESSION['SESS_FIRST_NAME']; ?> </b></span></a></li>
				<li class="btn  hidden-xs disabled">
					<a title="" href="pos/" onclick="return false;"><i class="icon fa fa-clock-o fa-2x"></i> <span class="text">
				  <?php
								$Today = date('y:m:d',mktime());
								$new = date('l, F d, Y', strtotime($Today));
								echo $new;
								?>				</span></a>
				</li>
									<li class="btn "><a href="#"><i class="icon fa fa-cog"></i><span class="text">Settings</span></a></li>
				        <li class="btn  ">
					<a href="index.php"><i class="fa fa-power-off"></i><span class="text">Logout</span></a>				</li>
			</ul>
		</div>
				<?php include("sidebar.php");?>
        
       
        
		<div id="content" class="clearfix sales_content_minibar">
		

<div id="content-header">
	<h1>	<i class="icon fa fa-bar-chart-o"></i>
		Reports	</h1>
</div>


<div id="breadcrumb" class="hidden-print">
	<a href="home.php"><i class="fa fa-home"></i> Dashboard</a><a class="current" href="reports.php">Reports</a></div>
<div class="clear"></div>

<div class="row report-listing">
	<div class="col-md-6  ">
		<div class="panel">
			<div class="panel-body">
				<div class="list-group parent-list">
											<a href="#" class="list-group-item" id="sales"><i class="fa fa-shopping-cart"></i>	Sales</a>
											<a href="#" class="list-group-item" id="transfer"><i class="fa fa-exchange"></i>	Transfer</a>
                    
										<a href="#" class="list-group-item" id="inventory"><i class="fa fa-table"></i>	Inventory Reports</a>
                    
											<a href="#" class="list-group-item" id="expiry-report">
							<i class="fa fa-search"></i>	Expiry Report						</a>
										
											<a href="#" class="list-group-item" id="customers"><i class="fa fa-group"></i>	Customers</a>
										
						
						<a href="#" class="list-group-item" id="deleted-sales"><i class="fa fa-trash-o"></i>	Deleted Sales</a>
										
											<a href="#" class="list-group-item" id="discounts"><i class="fa fa-magic"></i>	Discounts</a>
					
											<a href="#" class="list-group-item" id="employees"><i class="fa fa-user"></i>	Employees</a>
										
											<a href="#" class="list-group-item" id="giftcards"><i class="fa fa-credit-card"></i>	Giftcards</a>
					
										
						
					
										
						<a href="#" class="list-group-item" id="item-kits"><i class="fa fa-inbox"></i>	Item Kits</a>
					

										
						<a href="#" class="list-group-item" id="items"><i class="fa fa-table"></i>	Items</a>
					
										
						<a href="#" class="list-group-item" id="payments"><i class="fa fa-money"></i>	Payments</a>
										
											<a href="#" class="list-group-item" id="profit-and-loss"><i class="fa fa-shopping-cart"></i>	Profit and Loss</a>
										
											<a href="#" class="list-group-item" id="receivings"><i class="fa fa-cloud-download"></i>	Purchase</a>
										
																		<a href="#" class="list-group-item" id="register-log"><i class="fa fa-search"></i>	Register Logs</a>
																
											
										
																		<a href="#" class="list-group-item" id="store-accounts"><i class="fa fa-credit-card"></i> Store Accounts</a>
											
											<a href="#" class="list-group-item" id="suppliers"><i class="fa fa-download"></i>	Suppliers</a>
										
											<a href="#" class="list-group-item" id="taxes"><i class="fa fa-book"></i>	Taxes</a>
									</div>
			</div>
		</div> <!-- /panel -->
	</div>
	<div class="col-md-6" id="report_selection">
		<div class="panel">
			<div class="panel-body child-list">
			<h3 class="page-header text-info">« Reports: Make a selection</h3>
				<div class="list-group expiry-report hidden">
					<a href="report_details_expiry.php" class="list-group-item ">
						<i class="fa fa-search report-icon"></i>  Detailed Expiry Report					</a>
				</div>
				<div class="list-group customers hidden">
					<a class="list-group-item" href="#"><i class="fa fa-bar-chart-o"></i> Graphical Reports</a>
					<a class="list-group-item" href="#"><i class="fa fa-building-o"></i> Summary Reports</a>
					<a class="list-group-item" href="#"><i class="fa fa-calendar"></i> Detailed Reports</a>
				</div>
				<div class="list-group employees hidden">
					<a class="list-group-item" href="#"><i class="fa fa-bar-chart-o"></i> Graphical Reports</a>
					<a class="list-group-item" href="#"><i class="fa fa-building-o"></i> Summary Reports</a>
					<a class="list-group-item" href="#"><i class="fa fa-calendar"></i> Detailed Reports</a>
				</div>
				<div class="list-group sales hidden">
					<a class="list-group-item" href="#"><i class="fa fa-bar-chart-o"></i> Graphical Reports</a>
					<a class="list-group-item" href="#"><i class="fa fa-building-o"></i> Summary Reports</a>
					<a class="list-group-item" href="report_details_sales.php"><i class="fa fa-calendar"></i> Detailed Reports</a>
				</div>
				<div class="list-group deleted-sales hidden">
					<a href="#" class="list-group-item"><i class="fa fa-calendar"></i> Detailed Reports</a>
				</div>
				<div class="list-group register-log hidden">
					<a href="#" class="list-group-item"><i class="fa fa-calendar"></i> Detailed Reports</a>
				</div>
				<div class="list-group transfer hidden">
					<a href="#" class="list-group-item"><i class="fa fa-bar-chart-o"></i> Graphical Reports</a>
					<a href="report_details_transfer.php" class="list-group-item"><i class="fa fa-building-o"></i> Summary Reports</a>
				</div>
				<div class="list-group discounts hidden">
					<a href="#" class="list-group-item"><i class="fa fa-bar-chart-o"></i> Graphical Reports</a>
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
					<a href="report_details_critical_inventory.php" class="list-group-item"><i class="fa fa-calendar"></i> Critical Inventory</a>
                    <a href="report_details_low_inventory.php" class="list-group-item"><i class="fa fa-calendar"></i> Low Inventory</a>
					<a href="report_details_inventory.php" class="list-group-item"><i class="fa fa-calendar"></i>  Inventory Summary</a>
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
 $('.parent-list a').click(function(e){
 	e.preventDefault();
 	$('.parent-list a').removeClass('active');
 	$(this).addClass('active');
 	var currentClass='.child-list .'+ $(this).attr("id");
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
			website		</a> 
	to learn the latest information about the project.
		<span class="text-info"> <span class="label label-info"> 14.1</span></span>
</div>

</div><!--end #content-->
<!--end #wrapper-->

</body></html>