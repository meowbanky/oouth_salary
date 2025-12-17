<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>OOUTH Salary Manager</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
        integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/fontawesome.min.css"
        integrity="sha512-d0olNN35C6VLiulAobxYHZiXJmq+vl+BGIgAxQtD5+kqudro/xNMvv2yIHAciGHpExsIbKX3iLg+0B6d0k4+ZA=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />

    <!-- Favicon -->
    <link rel="icon" href="favicon.ico" type="image/x-icon">

    <!-- Core CSS -->
    <link rel="stylesheet" href="css/bootstrap.min.css">

    <!-- Font Awesome -->

    <!-- jQuery UI & Components -->
    <link rel="stylesheet" href="css/jquery-ui.css">
    <link rel="stylesheet" href="css/jquery.gritter.css">
    <link rel="stylesheet" href="css/jquery.loadmask.css">

    <!-- DataTables -->
    <link rel="stylesheet" href="css/dataTables.tableTools.min.css">
    <link rel="stylesheet" href="datatable/datatables.min.css">

    <!-- Form Components -->
    <link rel="stylesheet" href="css/datepicker.css">
    <link rel="stylesheet" href="css/bootstrap-select.css">
    <link rel="stylesheet" href="css/select2.css">
    <link rel="stylesheet" href="css/token-input-facebook.css">

    <!-- Custom Styles -->
    <link rel="stylesheet" href="css/unicorn.css">
    <link rel="stylesheet" href="css/components-md.css">
    <link rel="stylesheet" href="css/custom.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="css/dark-mode.css" rel="stylesheet">

    <!-- Core JavaScript -->
    <script src="js/all.js"></script>
    <script src="js/theme-manager.js"></script>
    <script src="js/alerts.js"></script>
    <script src="js/home.js"></script>

    <!-- jQuery Plugins -->
    <script src="js/select2.js"></script>
    <script src="js/jquery.tabledit.min.js"></script>
    <script src="js/jquery.dataTables.min.js"></script>

    <!-- PDF Generation -->
    <script src="datatable/pdfmake.min.js"></script>
    <script src="datatable/pdfmake-0.1.36/vfs_fonts.js"></script>
    <script src="datatable/datatables.min.js"></script>

    <!--    dropzone-->
    <!-- Add these in your <head> section -->
    <link rel="stylesheet" href="https://unpkg.com/dropzone@5/dist/min/dropzone.min.css" type="text/css" />
    <script src="https://unpkg.com/dropzone@5/dist/min/dropzone.min.js"></script>

    <style>
    .modal .modal-title {
        font-weight: 400;
        color: #FFF;
        text-transform: uppercase;
        text-align: center;
        font-size: 100%;
    }

    .modal .modal-header {
        background: #6e7dc7;
    }

    #box {
        width: 500px;
        background-color: #fff;
        margin: 50px auto;
        padding: 16px;
        text-align: center;
        border: 1px solid #ccc;
        border-radius: 5px;
    }

    .modal-backdrop {
        opacity: 0.65;
    }
    </style>

    <script>
    $(document).ready(function() {
        // Session management
        let isSessionExpired = false;

        function checkSession() {
            $.ajax({
                url: "check_session.php",
                method: "POST",
                success: function(data) {
                    if (data === '1') {
                        $('#loginModal').modal({
                            backdrop: 'static',
                            keyboard: false
                        });
                        isSessionExpired = true;
                    }
                }
            });
        }

        const sessionCheckInterval = setInterval(function() {
            checkSession();
            if (isSessionExpired) {
                clearInterval(sessionCheckInterval);
            }
        }, 10000);

        // Login form handler
        $('#loginform').on('submit', function(event) {
            event.preventDefault();
            doLoginExpire();
        });

        // Alert auto-hide
        setTimeout(function() {
            $('.alert').animate({
                height: "hide",
                opacity: "hide"
            }).text('');
        }, 3000);

        // Input focus handling
        if (!$("#username").val()) {
            $("#username").focus();
        } else if (!$("#password").val()) {
            $("#password").focus();
        }
    });

    // Auto logout functionality
    function autoLogout(sessionTimeout, logoutTimeout) {
        setTimeout(() => {
            const logoutUrl = "logout.php";
            window.location.href = logoutUrl;
        }, logoutTimeout);
    }

    // Initialize auto logout
    autoLogout(1440000, 1500000);
    </script>
</head>