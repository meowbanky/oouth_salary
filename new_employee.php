<?php require_once('Connections/paymaster.php'); 
include_once('classes/model.php');
 ?>
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


if ((isset($_POST["MM_insert"])) && ($_POST["MM_insert"] == "employee_form")) {

  $insertSQL = sprintf("INSERT INTO personal_info (
personal_info.staff_id,
personal_info.first_name,
personal_info.last_name,
personal_info.middle_name,
personal_info.payType_id,
personal_info.bank,
personal_info.acct_no,
personal_info.pfa,
personal_info.rsa_no,
personal_info.dob,
personal_info.date_employment,
personal_info.dept,
personal_info.grade_level,
personal_info.step,dateOfregistration) VALUES (%s, %s, %s,%s, %s, %s,%s, %s, %s,%s , %s, %s,%s,%s,now())",
                       GetSQLValueString($_POST['emp_no'], "int"),
                       GetSQLValueString($_POST['first_name'], "text"),
                       GetSQLValueString($_POST['last_name'], "text"),
					   GetSQLValueString($_POST['middle_name'], "text"),
                       GetSQLValueString($_POST['payType'], "int"),
                       GetSQLValueString($_POST['bank'], "int"),
					   GetSQLValueString($_POST['acct_no'], "text"),
                       GetSQLValueString($_POST['pfa'], "int"),
                       GetSQLValueString($_POST['rsa_pin'], "text"),
                       GetSQLValueString($_POST['dob'], "date"),
                       GetSQLValueString($_POST['doe'], "date"),
					   GetSQLValueString($_POST['dept'], "int"),
                       GetSQLValueString($_POST['gradeLevel'], "int"),
                       GetSQLValueString($_POST['gradestep'], "int"));

  mysql_select_db($database_salary, $salary);
  $Result1 = mysql_query($insertSQL, $salary) or die(mysql_error());

}

$col_Edit_record = "-1";
if (isset($_GET['id'])) {
$col_Edit_record = $_GET['id'];
}

mysqli_select_db($salary,$database_salary);
$query_bank = 'SELECT tbl_bank.BNAME, tbl_bank.BCODE FROM tbl_bank';
$bank = mysqli_query($salary,$query_bank) or die(mysql_error());
$row_bank = mysqli_fetch_assoc($bank);


mysqli_select_db($salary,$database_salary);
$query_dept = 'SELECT tbl_dept.dept_id, tbl_dept.dept FROM tbl_dept';
$dept = mysqli_query($salary,$query_dept) or die(mysql_error());
$row_dept = mysqli_fetch_assoc($dept);

mysqli_select_db($salary,$database_salary);
$query_state = 'SELECT state.state_id, state.State FROM state';
$state = mysqli_query($salary,$query_state) or die(mysql_error());
$row_state = mysqli_fetch_assoc($state);

mysqli_select_db($salary,$database_salary);
$query_pfa = 'SELECT tbl_pfa.pfacode, tbl_pfa.pfaname FROM tbl_pfa';
$pfa = mysqli_query($salary,$query_pfa) or die(mysql_error());
$row_pfa = mysqli_fetch_assoc($pfa);



?>
<!DOCTYPE html>
<!-- saved from url=(0055)http://www.optimumlinkup.com.ng/pos/index.php/customers -->
<html><?php include('header1.php');?>

<body data-color="grey" class="flat" style="zoom: 1;">
    <div class="modal fade hidden-print" id="myModal"></div>
    <div id="wrapper">
        <div id="header" class="hidden-print">
            <h1><a href="index.php"><img src="img/header_logo.png" class="hidden-print header-log" id="header-logo"
                        alt=""></a></h1>
            <a id="menu-trigger" href="#"><i class="fa fa-bars fa fa-2x"></i></a>
            <div class="clear"></div>
        </div>




        <div id="user-nav" class="hidden-print hidden-xs">
            <ul class="btn-group ">
                <li class="btn  hidden-xs"><a title="" href="switch_user" data-toggle="modal" data-target="#myModal"><i
                            class="icon fa fa-user fa-2x"></i> <span class="text"> Welcome <b>
                                <?php echo $_SESSION['SESS_FIRST_NAME']; ?> </b></span></a></li>
                <li class="btn  hidden-xs disabled">
                    <a title="" href="pos/" onclick="return false;"><i class="icon fa fa-clock-o fa-2x"></i> <span
                            class="text">
                            <?php
								$Today = date('y:m:d',mktime());
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
        <?php include('sidebar.php');?>



        <div id="content" class="clearfix ">

            <div id="content-header" class="hidden-print">
                <h1> <i class="fa fa-pencil"></i> New Employee </h1>
            </div>

            <div id="breadcrumb" class="hidden-print">
                <a href="home.php"><i class="fa fa-home"></i> Dashboard</a><a href="employee.php">Employee</a><a
                    class="current" href="new_employee.php">New Employee</a>
            </div>
            <div class="clear"></div>
            <div class="row" id="form">
                <div class="col-md-12">
                    Fields in red are required <div class="widget-box">
                        <div class="widget-title">
                            <span class="icon">
                                <i class="fa fa-align-justify"></i>
                            </span>
                            <h5>Employee Basic Information</h5>
                        </div>
                        <div class="widget-content">
                            <form action="<?php echo $editFormAction; ?>" method="post" accept-charset="utf-8"
                                id="employee_form" class="form-horizontal" novalidate>
                                <div class="row">
                                    <div class="col-md-12">

                                        <div class="form-group">
                                            <?php
        	 $payp = $conn->prepare('SELECT Max(employee.staff_id) as "nextNo" FROM employee');
            $myperiod = $payp->execute();
            $final = $payp->fetch();
            
                                                                            //End ED Fetch
					?>
                                            <label for="employee_no"
                                                class="required col-sm-3 col-md-3 col-lg-2 control-label ">Employee
                                                No:</label>
                                            <div class="col-sm-9 col-md-9 col-lg-10">
                                                <input type="text" name="emp_no"
                                                    value="<?php echo intval($final['nextNo'])+1;?>"
                                                    class="form-inps focus" id="emp_no" readonly>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label for="first_name"
                                                class="required col-sm-3 col-md-3 col-lg-2 control-label ">Employee
                                                Name:</label>
                                            <div class="col-sm-9 col-md-9 col-lg-10">
                                                <input type="text" name="name" value="" class="form-inps focus"
                                                    id="name" required>
                                            </div>
                                        </div>


                                        <div class="form-group">
                                            <label for="payType"
                                                class="required col-sm-3 col-md-3 col-lg-2 control-label ">Call Duty
                                                Type:</label>
                                            <div class="col-sm-9 col-md-9 col-lg-10">
                                                <p>
                                                    <label>
                                                        <input name="callType" type="radio" class="radio-inline"
                                                            id="payType_01" value="0" checked>
                                                        None</label>
                                                    <br>
                                                    <label>
                                                        <input name="callType" type="radio" class="radio-inline"
                                                            id="payType_0" value="1">
                                                        Doctors</label>
                                                    <br>
                                                    <label>
                                                        <input type="radio" name="callType" value="2" id="payType_1"
                                                            class="radio-inline">
                                                        Nurses</label>
                                                    <br>
                                                    <label>
                                                        <input type="radio" name="callType" value="3" id="payType_2"
                                                            class="radio-inline">
                                                        Others</label>
                                                    <br>
                                                </p>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <label for="state"
                                                class="required col-sm-3 col-md-3 col-lg-2 control-label ">State:</label>
                                            <div class="col-sm-9 col-md-9 col-lg-10">
                                                <select required="required" class="form-inps" id="state_id">
                                                    <option value="">Select State</option>
                                                    <?php while ($row = mysqli_fetch_array($state)){
    echo "<option value='". $row['state_id']."'>".$row['State']."</option>";
    
}?>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <label for="lg"
                                                class="required col-sm-3 col-md-3 col-lg-2 control-label ">Local
                                                Govt:</label>
                                            <div class="col-sm-9 col-md-9 col-lg-10">
                                                <select id="lg" class="form-inps">
                                                    <option value="">Select Local Govt</option>

                                                </select>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <label for="dept"
                                                class="required col-sm-3 col-md-3 col-lg-2 control-label ">Dept:</label>
                                            <div class="col-sm-9 col-md-9 col-lg-10">
                                                <select class="form-inps" id="dept" name='dept'>
                                                    <option>Select Dept</option>
                                                    <?php while ($row = mysqli_fetch_array($dept)){
    echo "<option value='". $row['dept_id']."'>".$row['dept']."</option>";
    
}?>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <label for="grade"
                                                class="required col-sm-3 col-md-3 col-lg-2 control-label ">Grade:</label>
                                            <div class="col-sm-9 col-md-9 col-lg-10">

                                                <input type="text" name="grade" value="" class="form-inps focus"
                                                    id="grade" required maxlength="3">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group">


                                        <label for="grade"
                                            class="required col-sm-3 col-md-3 col-lg-2 control-label ">Step:</label>
                                        <div class="col-sm-9 col-md-9 col-lg-10">
                                            <input type="text" name="gradestep" value="" class="form-inps focus"
                                                id="gradestep" required maxlength="2">
                                        </div>

                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="dob" class="required col-sm-3 col-md-3 col-lg-2 control-label ">Date of
                                        Birth:</label>
                                    <div class="col-sm-9 col-md-9 col-lg-10"><?php $today = date('Y-m-d');?>
                                        <input name="dob" type="date" required="required" class="form-inps" id="dob"
                                            max="<?php echo $today; ?>">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="doe" class="required col-sm-3 col-md-3 col-lg-2 control-label ">Date of
                                        Employment:</label>
                                    <div class="col-sm-9 col-md-9 col-lg-10"><?php $today = date('Y-m-d');?>
                                        <input name="doe" type="date" required="required" class="form-inps" id="doe"
                                            max="<?php echo $today; ?>">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="bank"
                                        class="required col-sm-3 col-md-3 col-lg-2 control-label ">Bank:</label>
                                    <div class="col-sm-9 col-md-9 col-lg-10">
                                        <select name="bank" class="form-inps" id="bank">
                                            <option>Select Bank</option>
                                            <?php while ($row = mysqli_fetch_array($bank)){
    echo "<option value='". $row['BCODE']."'>".$row['BNAME']."</option>";
    
}?>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="acct_no"
                                        class="required col-sm-3 col-md-3 col-lg-2 control-label ">Account No:</label>
                                    <div class="col-sm-9 col-md-9 col-lg-10">
                                        <input name="acct_no" type="text" class="form-inps" id="acct_no"
                                            autocomplete="off">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="pfa"
                                        class="required col-sm-3 col-md-3 col-lg-2 control-label ">PFA:</label>
                                    <div class="col-sm-9 col-md-9 col-lg-10">
                                        <select name="pfa" class="form-inps" id="pfa">
                                            <option>Select PFA</option>
                                            <?php while ($row = mysqli_fetch_array($pfa)){
    echo "<option value='". $row['pfacode']."'>".$row['pfaname']."</option>";
    
}?>
                                        </select>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="rsa_pin"
                                        class="required col-sm-3 col-md-3 col-lg-2 control-label ">PIN:</label>
                                    <div class="col-sm-9 col-md-9 col-lg-10">
                                        <input name="rsa_pin" type="text" required="required" class="form-inps"
                                            id="rsa_pin">
                                    </div>
                                </div>
                                <!--hide below-->
                                <div id="hide_grid" hidden="true">
                                    <div class="form-group">
                                        <label for="email"
                                            class="col-sm-3 col-md-3 col-lg-2 control-label ">E-Mail:</label>
                                        <div class="col-sm-9 col-md-9 col-lg-10">
                                            <input type="text" name="email" class="form-inps" id="email">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="phone_number"
                                            class="col-sm-3 col-md-3 col-lg-2 control-label ">Phone Number:</label>
                                        <div class="col-sm-9 col-md-9 col-lg-10">
                                            <input type="text" name="phone_number" class="form-inps" id="phone_number">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="phone_number"
                                            class="col-sm-3 col-md-3 col-lg-2 control-label ">Choose Avatar:</label>
                                        <div class="col-sm-9 col-md-9 col-lg-10">
                                            <img src="img/avatar.png" class="img-polaroid" id="image_empty" alt="" />
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






                                    <div class="form-group">
                                        <label for="address_1" class="col-sm-3 col-md-3 col-lg-2 control-label ">Address
                                            1:</label>
                                        <div class="col-sm-9 col-md-9 col-lg-10">
                                            <input type="text" name="address_1" class="form-control form-inps"
                                                id="address_1">
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label for="address_2" class="col-sm-3 col-md-3 col-lg-2 control-label ">Address
                                            2:</label>
                                        <div class="col-sm-9 col-md-9 col-lg-10">
                                            <input type="text" name="address_2" class="form-control form-inps"
                                                id="address_2">
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label for="city"
                                            class="col-sm-3 col-md-3 col-lg-2 control-label ">City:</label>
                                        <div class="col-sm-9 col-md-9 col-lg-10">
                                            <input type="text" name="city" class="form-control form-inps" id="city">
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label for="state"
                                            class="col-sm-3 col-md-3 col-lg-2 control-label ">State/Province:</label>
                                        <div class="col-sm-9 col-md-9 col-lg-10">
                                            <input type="text" name="state" class="form-control form-inps" id="state">
                                        </div>
                                    </div>


                                    <div class="form-group">
                                        <label for="country"
                                            class="col-sm-3 col-md-3 col-lg-2 control-label ">Country:</label>
                                        <div class="col-sm-9 col-md-9 col-lg-10">
                                            <input type="text" name="country" class="form-control form-inps"
                                                id="country">
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label for="comments"
                                            class="col-sm-3 col-md-3 col-lg-2 control-label ">Comments:</label>
                                        <div class="col-sm-9 col-md-9 col-lg-10">
                                            <textarea name="comments" cols="17" rows="5" id="comments">
</textarea>
                                        </div>
                                    </div>
                                </div>


                                <!--end of hidden fields-->


                                <div id="consolidated1">

                                </div>


                                <div class="form-group">
                                    <input type="hidden" name="redirect_code" value="0">
                                    <input type="hidden" name="MM_insert" value="employee_form">
                                    <input type="hidden" name="MM_update"
                                        value="<?php if(isset($_GET['id'])){echo 'update';} ?>">

                                    <div class="form-actions">
                                        <input type="submit" name="submitf" value="Submit" id="submitf"
                                            class="btn btn-primary float_right">
                                    </div>

                                    <script type="text/javascript">
                                    $('#image_id').imagePreview({
                                        selector: '#avatar'
                                    }); // Custom preview container

                                    //validation and submit handling
                                    $(document).ready(function() {
                                        $("input[type='text']").attr('autocomplete', 'off');

                                        setTimeout(function() {
                                            $(":input:visible:first", "#employee_form").focus();
                                        }, 100);

                                        $('#state_id').change(function() {

                                            var state_id = $(this).val();
                                            $.ajax({
                                                url: "selectState.php",
                                                method: "POST",
                                                data: {
                                                    state_id: state_id
                                                },
                                                success: function(data) {
                                                    $('#lg').html(data);

                                                }
                                            });

                                        });





                                        $(".module_checkboxes").change(function() {
                                            if ($(this).prop('checked')) {
                                                $(this).parent().find('input[type=checkbox]').not(
                                                    ':disabled').prop('checked', true);
                                            } else {
                                                $(this).parent().find('input[type=checkbox]').not(
                                                    ':disabled').prop('checked', false);
                                            }
                                        });


                                        $('#employee_form').validate({

                                            // Specify the validation rules
                                            rules: {
                                                emp_no: {
                                                    "remote": {
                                                        url: "employee_exists.php",
                                                        type: "post"
                                                    },
                                                    required: true
                                                },
                                                first_name: "required",
                                                last_name: "required",
                                                role: "required",
                                                state_id: "required",
                                                acct_no: {
                                                    required: {
                                                        depends: function(element) {
                                                            if (($("#bank option:selected")
                                                                    .text() != 'CHEQUE/CASH') || $(
                                                                    "#bank option:selected")
                                                                .text() != 'CHEQUE/CASH') {
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
                                                            if ($("#pfa option:selected").text() !=
                                                                'OTHERS') {
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
                                                first_name: "The first name is a required field.",
                                                last_name: "The last name is a required field",
                                                role: "The Role is a required field",
                                                emp_no: {
                                                    remote: "The Employee No already exists",
                                                    required: "Employee No is a required field"
                                                }

                                            },

                                            errorClass: "text-danger",
                                            errorElement: "span",
                                            highlight: function(element, errorClass, validClass) {
                                                $(element).parents('.form-group').removeClass(
                                                    'has-success').addClass('has-error');
                                            },
                                            unhighlight: function(element, errorClass, validClass) {
                                                $(element).parents('.form-group').removeClass(
                                                    'has-error').addClass('has-success');
                                            },

                                            submitHandler: function(form) {

                                                //form.submit();
                                                doEmployeeSubmit(form);
                                            }
                                        });






                                        var submitting = true;

                                        function doEmployeeSubmit(form) {
                                            $("#form").mask("Please wait...");
                                            //if (submitting) return;
                                            //submitting = true;

                                            $(form).ajaxSubmit({
                                                success: function(response, message) {

                                                    $("#form").unmask();
                                                    submitting = false;

                                                    if (message == 'success') {
                                                        gritter("Success",
                                                            "Record Saved Successfully",
                                                            'gritter-item-success', false, true);
                                                        setTimeout(function() {
                                                            window.location.href =
                                                                'employee.php';
                                                        }, 1200);
                                                    } else {
                                                        gritter("Error", message,
                                                            'gritter-item-error', false, false);

                                                    }


                                                }
                                            });
                                        }
                                    });
                                    </script>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>


            <div id="footer" class="col-md-12 hidden-print">
                Please visit our
                <a href="#" target="_blank">
                    website </a>
                to learn the latest information about the project.
                <span class="text-info"> <span class="label label-info"> 14.1</span></span>
            </div>

        </div>
        <!--end #content-->
    </div>
    <!--end #wrapper-->
</body>

</html>
<?php
//mysql_free_result($Edit_record);
?>