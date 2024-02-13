<?php require_once('Connections/paymaster.php'); ?>
<?php
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

 
session_start();
unset($_SESSION['SESS_MEMBER_ID']);
unset($_SESSION['SESS_PRICE_AJUSTMENT']);
unset($_SESSION['currentPage']);

?>

<!DOCTYPE html>
<html lang="en">
    <?php include("header1.php") ?>
    
    <style type="text/css">
.error {
	border:1px solid #CC0000;
	background-color:#FFC4C4;
	font-family:sans-serif, Verdana, Geneva;
	color:#000;
	font-size:12px;
	padding:10px;
	padding-left:10px;
	width:334px;
}
</style>
    <style>
        body{

        }
    </style>
    <body>
        

        <div id="container">
<div id="logo">
            <img src="img/header_logo.png" alt="">                </div>
            <div id="loginbox" style="height: 350px;">   
                <div id="logo">
                                    </div>
                
                <form action="login.php" method="post" accept-charset="utf-8" class="form login-form" id="loginform" autocomplete="off">				<div style="font-weight:normal; font-size: 12px; text-align: center;padding:20px;">Welcome to Inventory Manager. To continue, please login using your username and password below.</div>
                <div class="input-group">
                    <span class="input-group-addon"><i class="fa fa-user"></i></span>
                    <input type="text" name="username" value="" id="username" class="form-control" required placeholder="Username" size="20"  />                </div>
                <div class="input-group">
                    <span class="input-group-addon"><i class="fa fa-lock"></i></span>
                    <input type="password" name="password" value="" id="password" class="form-control" required placeholder="Password" size="20"  />
                </div>
                <div class="form-actions">

                    <div class="text-right">
                    <a href="#" class="flip-link to-recover"><a href="#" style="font-size:13px; color:#555; text-decoration:none;letter-spacing:0.6px">Reset password?</a></div>
                    <div>
                        <input type="button" class="btn btn-success form-control " onclick="doLogin()" value="Login" />
                    </div>
                    <div class="version">  
                        2018 Version <span class="label label-info">15.5</span> 
                    </div>
                    <div class="error btn" style="margin-top: 10px"></div>
                </div>
                </form>

            </div>

            
            
                          
                        


    </div>
        
 <script type="text/javascript">
            $(document).ready(function ()
			
			{
			//setTimeout(function(){

			//$(".alert").hide();
			//return false;

		//},5000);
		
		setTimeout(function(){ $('.alert').animate({height:"hide", opacity:"hide"}); $('.alert').text(''); }, 3000);
            
                //If we have an empty username focus
                if ($("#username").val() == '')
                {
                    $("#username").focus();
                } elseif ($("#password").val() == '')
                {
                    $("#password").focus();
                } elseif  ($("#location").val()=== '')
				{
					 $("#location").focus();
				}
            });
        </script>       

<?php unset($_SESSION['ERRMSG_ARR']);?>
                
    </body>
</html>