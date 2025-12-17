<?php
// forgot_password.php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Forgot Password | OOUTH Salary Manager</title>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <!-- TailwindCSS & DaisyUI CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.10.4/dist/full.css" rel="stylesheet" type="text/css" />
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
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
            <div class="text-blue-700/80 text-sm mt-1">Forgot Password</div>
        </div>
        <!-- Forgot Password Card -->
        <div class="card glass shadow-xl p-8 pb-7 border border-blue-100 fade-in-up w-full max-w-md">
            <form id="forgotform" autocomplete="off" class="flex flex-col gap-4">
                <div class="text-blue-800/90 mb-1 text-base">
                    Enter your <b>Username</b> or <b>Email</b> to reset your password.
                </div>
                <div>
                    <label for="user_or_email" class="label label-text text-sm text-blue-700">Username or Email</label>
                    <div class="relative">
                        <input id="user_or_email" name="user_or_email" type="text"
                               class="input input-bordered input-lg w-full pl-11 transition focus:ring focus:ring-blue-300/50"
                               placeholder="Username or Email" required autofocus />
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-blue-400 text-xl">
                            <i class="fa fa-user"></i>
                        </span>
                    </div>
                </div>
                <div id="msgbox" class="alert hidden px-4 py-2 text-[15px] mt-1"></div>
                <button id="submit" type="submit"
                        class="btn btn-primary btn-lg mt-3 w-full transition-transform duration-200 active:scale-95 hover:scale-105 hover:shadow-lg shadow-md">
                    <i class="fa fa-paper-plane mr-2"></i> Reset Password
                </button>
                <a href="index.php" class="text-blue-600 text-sm mt-2 hover:underline text-center">
                    <i class="fa fa-arrow-left"></i> Back to Login
                </a>
            </form>
        </div>
        <div class="text-center text-blue-900/70 mt-6 text-sm opacity-75">
            &copy; <?php echo date('Y'); ?> Olabisi Onabanjo University Teaching Hospital, Payroll Unit
        </div>
    </div>
    <script>
        $(function () {
            $('#forgotform').on('submit', function (e) {
                e.preventDefault();
                $('#msgbox').removeClass('alert-success alert-error alert-info').addClass('hidden').text('');
                const user_or_email = $('#user_or_email').val().trim();
                if (user_or_email === "") {
                    showMsg('Please enter your Username or Email.', 'alert-error');
                    $('#user_or_email').focus();
                    return;
                }

                $('#submit').addClass('loading').attr('disabled', true).html('<span class="loading loading-spinner"></span> Please wait...');
                // Replace 'forgot_handler.php' with your real endpoint
                $.ajax({
                    url: 'libs/forgot_handler.php',
                    method: 'POST',
                    dataType: 'json',
                    data: { user_or_email },
                    success: function (data) {
                        if (data.success == true || data.success === "true") {
                            showMsg(data.message || "If your information exists, a reset link has been sent.", 'alert-success');
                        } else {
                            showMsg(data.message || "Could not find a matching user.", 'alert-error');
                        }
                    },
                    error: function () {
                        showMsg("Server error. Please try again.", 'alert-error');
                    },
                    complete: function () {
                        $('#submit').removeClass('loading').removeAttr('disabled').html('<i class="fa fa-paper-plane mr-2"></i> Reset Password');
                    }
                });
            });

            function showMsg(msg, cls) {
                $('#msgbox').removeClass('hidden alert-success alert-error alert-info')
                            .addClass(cls).text(msg).fadeIn(120);
                setTimeout(() => $('#msgbox').fadeOut(340, function() { $(this).addClass('hidden'); }), 3500);
            }
        });
    </script>
</body>
</html>
