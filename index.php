<?php require_once('Connections/paymaster.php'); ?>
<?php




session_start();
unset($_SESSION['SESS_MEMBER_ID']);
unset($_SESSION['SESS_PRICE_AJUSTMENT']);
unset($_SESSION['currentPage']);
unset($_SESSION['currentactiveperiod']);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>OOUTH Salary Manager</title>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />


    <link rel="stylesheet" rev="stylesheet" href="css/bootstrap.min.css">
    <link href="css/font-awesome.min.css" rel="stylesheet" rev="stylesheet" type="text/css" media="all">
    <link rel="stylesheet" rev="stylesheet" href="css/unicorn-login.css">
    <link rel="stylesheet" rev="stylesheet" href="css/unicorn-login-custom.css">

    <script src="js/jquery.min.js" type="text/javascript" language="javascript" charset="UTF-8"></script>
    <script type="text/javascript" src="js/home.js"></script>
    <script src="js/bootstrap.min.js" type="text/javascript" language="javascript" charset="UTF-8"></script>
    <script src="js/jquery.form.js"></script>

</head>
<style type="text/css">
    .error {
        border: 1px solid #CC0000;
        background-color: #FFC4C4;
        font-family: sans-serif, Verdana, Geneva;
        color: #000;
        font-size: 12px;
        padding: 10px;
        padding-left: 10px;
        width: 334px;
    }
</style>
<style>
    body {}
</style>

<body>


    <div id="container">
        <div id="logo">
            <img src="img/header_logo.png" alt="">
        </div>
        <div id="loginbox">
            <div id="logo">
            </div>

            <form method="post" accept-charset="utf-8" class="form login-form" id="loginform" autocomplete="off">
                <div style="font-weight:normal; font-size: 12px; text-align: center;padding:20px;">Welcome to Salary Manager System. To continue, please login using your username and password below.</div>
                <div class="input-group">
                    <span class="input-group-addon"><i class="fa fa-user"></i></span>
                    <input type="text" name="username" value="" id="username" class="form-control" required placeholder="Username" size="20" />
                </div>
                <div class="input-group">
                    <span class="input-group-addon"><i class="fa fa-lock"></i></span>
                    <input type="password" name="password" value="" id="password" class="form-control" required placeholder="Password" size="20" />
                </div>
                <div class="error btn" style="margin-top: 10px"></div>
                <div class="form-actions">

                    <div class="text-right">
                        <a href="#" class="flip-link to-recover"><a href="#" style="font-size:13px; color:#555; text-decoration:none;letter-spacing:0.6px">Reset password?</a>
                    </div>
                    <div>
                        <input type="submit" id="submit" class="btn btn-success form-control " value="Login" />
                    </div>
                    <div class="version">
                        2018 Version <span class="label label-info">15.5</span>
                    </div>

                </div>
            </form>

        </div>







    </div>




    <?php unset($_SESSION['ERRMSG_ARR']); ?>
    <script type="text/javascript">
        $(document).ready(function()

            {
                //setTimeout(function(){

                //$(".alert").hide();
                //return false;

                //},5000);

                $('#loginform').on('submit', function(event) {
                    event.preventDefault();
                    doLogin();


                })
                setTimeout(function() {
                    $('.alert').animate({
                        height: "hide",
                        opacity: "hide"
                    });
                    $('.alert').text('');
                }, 3000);

                //If we have an empty username focus
                if ($("#username").val() == '') {
                    $("#username").focus();
                } else if ($("#password").val() == '') {
                    $("#password").focus();
                }
            });
    </script>
</body>

</html>