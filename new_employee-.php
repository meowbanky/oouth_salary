<?php require_once('Connections/pos.php'); ?>
<?php
session_start();
	
	//Check whether the session variable SESS_MEMBER_ID is present or not
	if(!isset($_SESSION['SESS_MEMBER_ID']) || (trim($_SESSION['SESS_MEMBER_ID']) == '')|| $_SESSION['role'] != 'Admin') {
		header("location: index.php");
		exit();
	}
	
if (!function_exists("GetSQLValueString")) {
function GetSQLValueString($theValue, $theType, $theDefinedValue = "", $theNotDefinedValue = "") 
{
  if (PHP_VERSION < 6) {
    $theValue = get_magic_quotes_gpc() ? stripslashes($theValue) : $theValue;
  }

  $theValue = function_exists("mysql_real_escape_string") ? mysql_real_escape_string($theValue) : mysql_escape_string($theValue);

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

$editFormAction = $_SERVER['PHP_SELF'];
if (isset($_SERVER['QUERY_STRING'])) {
  $editFormAction .= "?" . htmlentities($_SERVER['QUERY_STRING']);
}

$col_Edit_record = "-1";
if (isset($_GET['id'])) {
$col_Edit_record = $_GET['id'];
}


mysql_select_db($database_pos, $pos);
$query_Edit_record = sprintf("SELECT employees.username, employees.position,people.first_name, people.last_name, people.phone_number, people.email, people.address_1, people.address_2, people.city, people.state, people.country, people.comments, people.image_id, people.person_id FROM employees INNER JOIN people ON people.person_id = employees.person_id WHERE people.person_id = %s", GetSQLValueString($col_Edit_record, "text"));
$Edit_record = mysql_query($query_Edit_record, $pos) or die(mysql_error());
$row_Edit_record = mysql_fetch_assoc($Edit_record);
$totalRows_Edit_record = mysql_num_rows($Edit_record);


if ((isset($_POST["MM_update"])) && ($_POST["MM_update"] == "update")) {
  $updateSQL = sprintf("UPDATE people SET first_name=%s, last_name=%s, phone_number=%s, email=%s, address_1=%s, address_2=%s, city=%s, `state`=%s, country=%s, comments=%s WHERE person_id=%s",
                       GetSQLValueString($_POST['first_name'], "text"),
                       GetSQLValueString($_POST['last_name'], "text"),
                       GetSQLValueString($_POST['phone_number'], "text"),
                       GetSQLValueString($_POST['email'], "text"),
                       GetSQLValueString($_POST['address_1'], "text"),
                       GetSQLValueString($_POST['address_2'], "text"),
                       GetSQLValueString($_POST['city'], "text"),
                       GetSQLValueString($_POST['state'], "text"),
                       GetSQLValueString($_POST['country'], "text"),
                       GetSQLValueString($_POST['comments'], "text"),
                       GetSQLValueString($_GET['id'], "text"));

  mysql_select_db($database_pos, $pos);
  $Result1 = mysql_query($updateSQL, $pos) or die(mysql_error());
  
  

  $updateSQL = sprintf("UPDATE employees SET username=%s, password=%s, role=%s WHERE person_id=%s",
                       GetSQLValueString($_POST['username'], "text"),
                       GetSQLValueString($_POST['password'], "text"),
                        GetSQLValueString($_POST['role'], "text"),
					   GetSQLValueString($_GET['id'], "text"));

  mysql_select_db($database_pos, $pos);
  $Result1 = mysql_query($updateSQL, $pos) or die(mysql_error());
 

						
	$insertGoTo = "employee.php";
  if (isset($_SERVER['QUERY_STRING'])) {
     }
  header(sprintf("Location: %s", $insertGoTo));
  
}

if ((isset($_POST["MM_insert"])) && ($_POST["MM_insert"] == "employee_form")) {
  $insertSQL = sprintf("INSERT INTO people (first_name,last_name,email,phone_number,address_1,address_2,city,state,country,comments) VALUES (%s, %s, %s,%s, %s, %s,%s, %s, %s,%s )",
                       GetSQLValueString($_POST['first_name'], "text"),
                       GetSQLValueString($_POST['last_name'], "text"),
                       GetSQLValueString($_POST['email'], "text"),
					   GetSQLValueString($_POST['phone_number'], "text"),
                       GetSQLValueString($_POST['address_1'], "text"),
                       GetSQLValueString($_POST['address_2'], "text"),
					   GetSQLValueString($_POST['city'], "text"),
                       GetSQLValueString($_POST['state'], "text"),
                       GetSQLValueString($_POST['country'], "text"),
					   GetSQLValueString($_POST['comments'], "text"));

  mysql_select_db($database_pos, $pos);
  $Result1 = mysql_query($insertSQL, $pos) or die(mysql_error());
  
  $person_id = mysql_insert_id();
  
  $insertSQL_info = sprintf("INSERT INTO employees (person_id,username,password,deleted,role) VALUES (%s, %s, %s,%s, %s)",
                       GetSQLValueString($person_id, "int"),
						GetSQLValueString($_POST['username'], "text"),
						GetSQLValueString($_POST['password'], "text"),
						GetSQLValueString(0, "int"),
						GetSQLValueString($_POST['role'], "text"));
						
	  mysql_select_db($database_pos, $pos);
  $Result2 = mysql_query($insertSQL_info, $pos) or die(mysql_error());
	
	
						
	$insertGoTo = "employee.php";
  if (isset($_SERVER['QUERY_STRING'])) {
     }
  header(sprintf("Location: %s", $insertGoTo));

}








?>
<!DOCTYPE html>
<!-- saved from url=(0055)http://www.optimumlinkup.com.ng/pos/index.php/customers -->
<html><head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
		<title>OOUTH Inventory Manager</title>
		
		<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
		<!--<base href="http://www.optimumlinkup.com.ng/pos/">--><base href=".">
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
                    
				<script type="text/javascript">
			var SITE_URL= "index.php";
		</script>
		
					<script src="js/all.js" type="text/javascript" language="javascript" charset="UTF-8"></script>
			<script src="js/jquery.dataTables.min.js" type="text/javascript" language="javascript" charset="UTF-8"></script>
		
		<script type="text/javascript">
			COMMON_SUCCESS = "Success";
			COMMON_ERROR = "Error";
			$.ajaxSetup ({
				cache: false,
				headers: { "cache-control": "no-cache" }
			});
			
			$(document).ready(function()
			{
				//Ajax submit current location
				$("#employee_current_location_id").change(function()
				{
					$("#form_set_employee_current_location_id").ajaxSubmit(function()
					{
						window.location.reload(true);
					});
				});	
			});
		</script>
		
	<style>@font-face{font-family:uc-nexus-iconfont;src:url(chrome-extension://pogijhnlcfmcppgimcaccdkmbedjkmhi/res/font_1471832554_080215.woff) format('woff'),url(chrome-extension://pogijhnlcfmcppgimcaccdkmbedjkmhi/res/font_1471832554_080215.ttf) format('truetype')}</style></head>
	<body data-color="grey" class="flat" style="zoom: 1;">
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
					<a href="http://www.optimumlinkup.com.ng/pos/index.php/home/logout"><i class="fa fa-power-off"></i><span class="text">Logout</span></a>				</li>
			</ul>
		</div>
				<div id="sidebar" class="hidden-print minibar ">
			
			<ul style="display: block;">
            					<li><a href="home.php"><i class="icon fa fa-dashboard"></i><span class="hidden-minibar">Dashboard</span></a></li>
									<li ><a href="customer.php"><i class="fa fa-group"></i><span class="hidden-minibar">Customers</span></a></li>
									<li><a href="item.php"><i class="fa fa-table"></i><span class="hidden-minibar">Items</span></a></li>
									<li><a href="supplier.php"><i class="fa fa-download"></i><span class="hidden-minibar">Suppliers</span></a></li>
									<li><a href="reports.php"><i class="fa fa-bar-chart-o"></i><span class="hidden-minibar">Reports</span></a></li>
									<li><a href="receiving.php"><i class="fa fa-cloud-download"></i><span class="hidden-minibar">Receivings</span></a></li>
<li><a href="price_adjustment.php"><i class="fa fa-money"></i><span class="hidden-minibar">Price Adjustment</span></a></li>
									<li><a href="sales.php"><i class="fa fa-shopping-cart"></i><span class="hidden-minibar">Issue Out</span></a></li>
<li><a href="sales_receipt_list.php"><i class="fa fa-print"></i><span class="hidden-minibar">Reprint Receipt</span></a></li>
									<li class="active"><a href="employee.php"><i class="fa fa-user"></i><span class="hidden-minibar">Employees</span></a></li>
									<li><a href="companyinfo.php"><i class="fa fa-home"></i><span class="hidden-minibar">Locations</span></a></li>
				                <li>
                	<a href="index.php"><i class="fa fa-power-off"></i><span class="hidden-minibar">Logout</span></a>
                </li>
			</ul>
		</div>
        
       
        
		<div id="content"  class="clearfix " >
		
<div id="content-header" class="hidden-print">
	<h1 > <i class="fa fa-pencil"></i>  New Employee	</h1>
</div>

<div id="breadcrumb" class="hidden-print">
	<a href="home.php"><i class="fa fa-home"></i> Dashboard</a><a href="employee.php">Employee</a><a  class="current" href="new_employee.php">New Employee</a></div>
<div class="clear"></div>
<div class="row" id="form">
	<div class="col-md-12">
			Fields in red are required			<div class="widget-box">
				<div class="widget-title">
					<span class="icon">
						<i class="fa fa-align-justify"></i>									
					</span>
					<h5>Employee Basic Information</h5>
				</div>
				<div class="widget-content">
					<form action="<?php echo $editFormAction; ?>" method="post" accept-charset="utf-8" id="employee_form" class="form-horizontal" novalidate>
						<div class="row">
	<div class="col-md-12">
					<div class="form-group">
			<label for="first_name" class="required col-sm-3 col-md-3 col-lg-2 control-label ">First Name:</label>			<div class="col-sm-9 col-md-9 col-lg-10">
				<input type="text" name="first_name" value="<?php echo $row_Edit_record['first_name']; ?>" class="form-inps focus" id="first_name">			</div>
		</div>

				  <div class="form-group">
			<label for="last_name" class="required col-sm-3 col-md-3 col-lg-2 control-label ">Last Name:</label>			<div class="col-sm-9 col-md-9 col-lg-10">
			<input type="text" name="last_name" value="<?php echo $row_Edit_record['last_name']; ?>" class="form-inps" id="last_name">			</div>
		</div>

					<div class="form-group">
			<label for="email" class="col-sm-3 col-md-3 col-lg-2 control-label ">E-Mail:</label>			<div class="col-sm-9 col-md-9 col-lg-10">
			<input type="text" name="email" value="<?php echo $row_Edit_record['email']; ?>" class="form-inps" id="email">			</div>
		</div>
					<div class="form-group">	
		<label for="phone_number" class="col-sm-3 col-md-3 col-lg-2 control-label ">Phone Number:</label>			<div class="col-sm-9 col-md-9 col-lg-10">
			<input type="text" name="phone_number" value="<?php echo $row_Edit_record['phone_number']; ?>" class="form-inps" id="phone_number">			</div>
		</div>
		<div class="form-group">	
		<label for="phone_number" class="col-sm-3 col-md-3 col-lg-2 control-label ">Choose Avatar:</label>			<div class="col-sm-9 col-md-9 col-lg-10">
	      		<img src="img/avatar.png" class="img-polaroid" id="image_empty" alt=""/>			
			</div>
		</div>
	
	</div>
	<div class="form-group">
    <label class="col-sm-3 col-md-3 col-lg-2 control-label ">&nbsp;</label>
	<div class="col-sm-9 col-md-9 col-lg-10">
	<ul class="list-unstyled text-center">
	<li>
	<div id="avatar">
	
	<div class="col-sm-9 col-md-9 col-lg-10">
	      
			
			</div>
	</div>
		<br><br>
	</li>
		<li>
			 <input type="file" name="image_id" value="" id="image_id">     
				

		</li>
	
	</ul>
	
	</div>
	</div>
	
	
</div>
	


<div class="form-group">	
<label for="address_1" class="col-sm-3 col-md-3 col-lg-2 control-label ">Address 1:</label>	<div class="col-sm-9 col-md-9 col-lg-10">
	<input type="text" name="address_1" value="<?php echo $row_Edit_record['address_1']; ?>" class="form-control form-inps" id="address_1">	</div>
</div>

			<div class="form-group">	
<label for="address_2" class="col-sm-3 col-md-3 col-lg-2 control-label ">Address 2:</label>	<div class="col-sm-9 col-md-9 col-lg-10">
	<input type="text" name="address_2" value="<?php echo $row_Edit_record['address_2']; ?>" class="form-control form-inps" id="address_2">	</div>
</div>

			<div class="form-group">	
<label for="city" class="col-sm-3 col-md-3 col-lg-2 control-label ">City:</label>	<div class="col-sm-9 col-md-9 col-lg-10">
	<input type="text" name="city" value="<?php echo $row_Edit_record['city']; ?>" class="form-control form-inps" id="city">	</div>
</div>

			<div class="form-group">	
<label for="state" class="col-sm-3 col-md-3 col-lg-2 control-label ">State/Province:</label>	<div class="col-sm-9 col-md-9 col-lg-10">
	<input type="text" name="state" value="<?php echo $row_Edit_record['state']; ?>" class="form-control form-inps" id="state">	</div>
</div>

			
			<div class="form-group">	
<label for="country" class="col-sm-3 col-md-3 col-lg-2 control-label ">Country:</label>	<div class="col-sm-9 col-md-9 col-lg-10">
	<input type="text" name="country" value="<?php echo $row_Edit_record['country']; ?>" class="form-control form-inps" id="country">	</div>
</div>

	<div class="form-group">	
<label for="comments" class="col-sm-3 col-md-3 col-lg-2 control-label ">Comments:</label>	<div class="col-sm-9 col-md-9 col-lg-10">
	<textarea name="comments" cols="17" rows="5" id="comments"><?php echo $row_Edit_record['comments']; ?></textarea>	
	</div>
</div>
 
					<legend class="page-header text-info"> &nbsp; &nbsp; Employee Login Info</legend>
					<div class="form-group">	
					<label for="username" class="col-sm-3 col-md-3 col-lg-2 control-label required">Username:</label>					<div class="col-sm-9 col-md-9 col-lg-10">
						<input type="text" name="username" value="<?php echo $row_Edit_record['username']; ?>" id="username" class="form-control" autocomplete="off">						</div>
					</div>

					<div class="form-group">	
					<div class="form-group">	
					<label for="password" class="col-sm-3 col-md-3 col-lg-2 control-label">Password:</label>					<div class="col-sm-9 col-md-9 col-lg-10">
						<input type="password" name="password" value="" id="password" class="form-control">						</div>
					</div>

					<div class="form-group">	
					<label for="repeat_password" class="col-sm-3 col-md-3 col-lg-2 control-label">Password Again:</label>					<div class="col-sm-9 col-md-9 col-lg-10">
						<input type="password" name="repeat_password" value="" id="repeat_password" class="form-control">						</div>
					</div>
					
					<div class="form-group">	
					<label for="role" class="col-sm-3 col-md-3 col-lg-2 control-label">Role:</label>					<div class="col-sm-9 col-md-9 col-lg-10">
						
                       <select name="role"  id="role" class="form-control">
                         <option value="" <?php if (!(strcmp("", $row_Edit_record['position']))) {echo "selected=\"selected\"";} ?>>Select</option>
                         <option value="Admin" <?php if (!(strcmp("Admin", $row_Edit_record['position']))) {echo "selected=\"selected\"";} ?>>Admin</option>
                         <option value="Cashier" <?php if (!(strcmp("Cashier", $row_Edit_record['position']))) {echo "selected=\"selected\"";} ?>>Cashier</option>
					</select> 
                        				</div>
					</div>
					
											

<input type="hidden" name="redirect_code" value="0">
<input type="hidden" name="MM_insert" value="employee_form">
<input type="hidden" name="MM_update" value="<?php if(isset($_GET['id'])){echo 'update';} ?>">

					<div class="form-actions">
					<input type="submit" name="submitf" value="Submit" id="submitf" class="btn btn-primary float_right">					</div>
					
<script type="text/javascript">
$('#image_id').imagePreview({ selector : '#avatar' }); // Custom preview container

//validation and submit handling
$(document).ready(function()
{
	
    setTimeout(function(){$(":input:visible:first","#employee_form").focus();},100);
	$(".module_checkboxes").change(function()
	{
		if ($(this).prop('checked'))
		{
			$(this).parent().find('input[type=checkbox]').not(':disabled').prop('checked', true);
		}
		else
		{
			$(this).parent().find('input[type=checkbox]').not(':disabled').prop('checked', false);			
		}
	});


    $('#employee_form').validate({

        // Specify the validation rules
        rules:
        {
            first_name: "required",
            last_name: "required",
            role:"required",
            username:
            {
                remote: 
			    { 
					url: "exmployee_exists.php", 
					type: "post"
			    }, 
				required:true,
                minlength: 5
            },

            password:
            {
                required:true,
                minlength: 5
            },
            repeat_password:
            {
                equalTo: "#password"

            },
            email: {
                "required": false,
                "email": true
            },
        },

        // Specify the validation error messages
        messages:
        {
            first_name: "The first name is a required field.",
            last_name: "The last name is a required field",
            role:"The Role is a required field",
            username:
            {
                remote: "The username already exists",
                required: "Username is a required field",
                minlength: "The username must be at least 5 characters"     		},
            password:
            {
                required:"Password is required",
                minlength: "Passwords must be at least 8 characters"			},
            repeat_password:
            {
                equalTo: "Passwords do not match"     		},
            email: "The e-mail address is not in the proper format"
 },

        errorClass: "text-danger",
    errorElement: "span",
        highlight:function(element, errorClass, validClass) {
            $(element).parents('.form-group').removeClass('has-success').addClass('has-error');
        },
        unhighlight: function(element, errorClass, validClass) {
            $(element).parents('.form-group').removeClass('has-error').addClass('has-success');
        },

        submitHandler: function(form) {
            form.submit();
            //doEmployeeSubmit(form);
        }
    });


	});



var submitting = true;

function doEmployeeSubmit(form)
				{
					$("#form").mask("Please wait...");
					//if (submitting) return;
					//submitting = true;

					$(form).ajaxSubmit({
						success:function(response,message)
						{
							$("#form").unmask();
							submitting = false;
							
								if (message == 'success')
								{
									gritter("Success","Record Saved Successfully",'gritter-item-success',false,true);
							setTimeout(function()
							{
								window.location.href = 'employee.php';								
							}, 1200);
								}
								else
								{
									gritter("Error",message,'gritter-item-error',false,false);

								}
							
							
						}
					});
				}
			</script>
</div></form></div></div></div>
		</div>

	
<div id="footer" class="col-md-12 hidden-print">
	Please visit our 
		<a href="#" target="_blank">
			website		</a> 
	to learn the latest information about the project.
		<span class="text-info"> <span class="label label-info"> 14.1</span></span>
</div>

</div><!--end #content-->
</div><!--end #wrapper-->
</body>
</html>
<?php
mysql_free_result($Edit_record);
?>
