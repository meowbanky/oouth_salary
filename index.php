<?php
session_start();
require_once('Connections/paymaster.php');
unset($_SESSION['SESS_MEMBER_ID'], $_SESSION['SESS_PRICE_AJUSTMENT'], $_SESSION['currentPage'], $_SESSION['currentactiveperiod']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>OOUTH Salary Manager | Login</title>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <!-- TailwindCSS & DaisyUI CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.10.4/dist/full.css" rel="stylesheet" type="text/css" />
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <!-- jQuery & jquery.form -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="js/jquery.form.js"></script>
    <style>
        body {
            background: linear-gradient(120deg, #4868b1 0%, #9ad1e6 90%);
            min-height: 100vh;
        }
        .fade-in-up {
            animation: fadeInUp 1.05s cubic-bezier(0.4, 0, 0.2, 1);
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(44px);}
            to   { opacity: 1; transform: translateY(0);}
        }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen overflow-x-hidden">
    <div class="flex flex-col items-center w-full min-h-screen pt-10 pb-8">
        <!-- Logo & Title -->
        <div class="flex flex-col items-center mb-7 fade-in-up">
            <img src="img/oouth_logo.png" alt="OOUTH Logo" class="w-20 h-20 rounded-full shadow bg-white/70 ring-2 ring-blue-200 mb-2">
            <div class="font-bold text-2xl text-blue-800 tracking-wide mt-2">OOUTH Salary Manager</div>
            <div class="text-blue-700/80 text-sm mt-1">Secure Payroll System Access</div>
        </div>
        <!-- Login Card -->
        <div class="card glass shadow-xl p-8 pb-7 border border-blue-100 fade-in-up w-full max-w-md">
            <form id="loginform" autocomplete="off" class="flex flex-col gap-4">
                <div>
                    <label for="username" class="label label-text text-sm text-blue-700">Username</label>
                    <div class="relative">
                        <input id="username" name="username" type="text"
                               class="input input-bordered input-lg w-full pl-11 transition focus:ring focus:ring-blue-300/50"
                               placeholder="Enter your username" autocomplete="username" required autofocus />
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-blue-400 text-xl">
                            <i class="fa fa-user"></i>
                        </span>
                    </div>
                </div>
                <div>
                    <label for="password" class="label label-text text-sm text-blue-700">Password</label>
                    <div class="relative">
                        <input id="password" name="password" type="password"
                               class="input input-bordered input-lg w-full pl-11 transition focus:ring focus:ring-blue-300/50"
                               placeholder="Enter your password" autocomplete="current-password" required />
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-blue-400 text-xl">
                            <i class="fa fa-lock"></i>
                        </span>
                    </div>
                </div>
                <div id="errorbox" class="alert alert-error flex items-center gap-3 shadow mb-0 hidden px-4 py-2 text-[15px]"></div>
                <div class="flex justify-between items-center text-xs mt-1">
                    <a href="forgot_password.php" class="link text-blue-500 hover:underline hover:text-blue-800">Forgot password?</a>
                    <span class="opacity-60 text-xs">Version <b>15.5</b></span>
                </div>
                <button id="submit" type="submit"
                        class="btn btn-primary btn-lg mt-3 w-full transition-transform duration-200 active:scale-95 hover:scale-105 hover:shadow-lg shadow-md">
                    <i class="fa fa-arrow-right-to-bracket mr-2"></i> Login
                </button>
            </form>
        </div>
        <div class="text-center text-blue-900/70 mt-6 text-sm opacity-75">
            &copy; <?php echo date('Y'); ?> Olabisi Onabanjo University Teaching Hospital, Payroll Unit
        </div>
    </div>
    <script>
        $(function () {
            $('#loginform').on('submit', function (e) {
                e.preventDefault();
                $('#errorbox').addClass('hidden').text('');
                const username = $('#username').val().trim();
                const password = $('#password').val().trim();

                if (username === "") {
                    showError('Username is required');
                    $('#username').focus();
                    return;
                }
                if (password === "") {
                    showError('Password is required');
                    $('#password').focus();
                    return;
                }

                $('#submit').addClass('loading').attr('disabled', true).html('<span class="loading loading-spinner"></span> Logging in...');
                $(this).ajaxSubmit({
                    url: 'classes/controller.php?act=login',
                    type: 'POST',
                    dataType: 'json',
                    data: { username, password },
                    success: function (data) {
                        // Accepts both boolean and string
                        if (data.success == true || data.success === "true") {
                            window.location = data.redirect || 'home.php';
                        } else {
                            showError(data.message || 'Invalid login credentials');
                        }
                    },
                    error: function () {
                        showError('Could not connect. Try again.');
                    },
                    complete: function () {
                        $('#submit').removeClass('loading').removeAttr('disabled').html('<i class="fa fa-arrow-right-to-bracket mr-2"></i> Login');
                    }
                });
            });

            function showError(msg) {
                $('#errorbox').hide().text(msg).removeClass('hidden').fadeIn(180);
                setTimeout(() => $('#errorbox').fadeOut(300, function() { $(this).addClass('hidden'); }), 3400);
            }
        });
    </script>
</body>
</html>
