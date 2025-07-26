<?php
// reset_password.php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Reset Password | OOUTH Salary Manager</title>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.10.4/dist/full.css" rel="stylesheet" type="text/css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <style>
        body {
            background: linear-gradient(120deg, #4868b1 0%, #9ad1e6 90%);
            min-height: 100vh;
        }
        .fade-in-up { animation: fadeInUp 1.05s cubic-bezier(0.4, 0, 0.2, 1);}
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
            <img src="img/header_logo.png" alt="OOUTH Logo" class="w-20 h-20 rounded-full shadow bg-white/70 ring-2 ring-blue-200 mb-2">
            <div class="font-bold text-2xl text-blue-800 tracking-wide mt-2">OOUTH Salary Manager</div>
            <div class="text-blue-700/80 text-sm mt-1">Reset Your Password</div>
        </div>
        <!-- Reset Password Card -->
        <div class="card glass shadow-xl p-8 pb-7 border border-blue-100 fade-in-up w-full max-w-md">
            <form id="resetform" autocomplete="off" class="flex flex-col gap-4">
                <div class="text-blue-800/90 mb-1 text-base">
                    Enter the 6-digit code sent to your email and set a new password.
                </div>
                <div>
                    <label for="email" class="label label-text text-sm text-blue-700">Email</label>
                    <div class="relative">
                        <input id="email" name="email" type="email"
                               class="input input-bordered input-lg w-full pl-11"
                               placeholder="Email used for reset" required />
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-blue-400 text-xl">
                            <i class="fa fa-envelope"></i>
                        </span>
                    </div>
                </div>
                <div>
                    <label for="otp" class="label label-text text-sm text-blue-700">Reset Code (OTP)</label>
                    <div class="relative">
                        <input id="otp" name="otp" type="text"
                               class="input input-bordered input-lg w-full pl-11"
                               placeholder="6-digit code" maxlength="6" pattern="\d{6}" required />
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-blue-400 text-xl">
                            <i class="fa fa-key"></i>
                        </span>
                    </div>
                </div>
                <div>
                    <label for="password" class="label label-text text-sm text-blue-700">New Password</label>
                    <div class="relative">
                        <input id="password" name="password" type="password"
                               class="input input-bordered input-lg w-full pl-11"
                               placeholder="New password" minlength="6" required />
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-blue-400 text-xl">
                            <i class="fa fa-lock"></i>
                        </span>
                    </div>
                </div>
                <div>
                    <label for="confirm" class="label label-text text-sm text-blue-700">Confirm Password</label>
                    <div class="relative">
                        <input id="confirm" name="confirm" type="password"
                               class="input input-bordered input-lg w-full pl-11"
                               placeholder="Re-type password" minlength="6" required />
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-blue-400 text-xl">
                            <i class="fa fa-lock"></i>
                        </span>
                    </div>
                </div>
                <div id="msgbox" class="alert hidden px-4 py-2 text-[15px] mt-1"></div>
                <button id="submit" type="submit"
                        class="btn btn-primary btn-lg mt-3 w-full transition-transform duration-200 active:scale-95 hover:scale-105 hover:shadow-lg shadow-md">
                    <i class="fa fa-key mr-2"></i> Reset Password
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
            $('#resetform').on('submit', function (e) {
                e.preventDefault();
                $('#msgbox').removeClass('alert-success alert-error alert-info').addClass('hidden').text('');
                const email = $('#email').val().trim();
                const otp = $('#otp').val().trim();
                const password = $('#password').val();
                const confirm = $('#confirm').val();
                if (!email || !otp || !password || !confirm) {
                    showMsg('All fields are required.', 'alert-error');
                    return;
                }
                if (password.length < 6) {
                    showMsg('Password must be at least 6 characters.', 'alert-error');
                    $('#password').focus();
                    return;
                }
                if (password !== confirm) {
                    showMsg('Passwords do not match.', 'alert-error');
                    $('#confirm').focus();
                    return;
                }
                $('#submit').addClass('loading').attr('disabled', true).html('<span class="loading loading-spinner"></span> Resetting...');
                $.ajax({
                    url: 'reset_password_handler.php',
                    method: 'POST',
                    dataType: 'json',
                    data: { email, otp, password },
                    success: function (data) {
                        if (data.success == true || data.success === "true") {
                            showMsg(data.message || "Password reset successful.", 'alert-success');
                            setTimeout(function() { window.location = 'index.php'; }, 2200);
                        } else {
                            showMsg(data.message || "Reset failed.", 'alert-error');
                        }
                    },
                    error: function () {
                        showMsg("Server error. Please try again.", 'alert-error');
                    },
                    complete: function () {
                        $('#submit').removeClass('loading').removeAttr('disabled').html('<i class="fa fa-key mr-2"></i> Reset Password');
                    }
                });
            });

            function showMsg(msg, cls) {
                $('#msgbox').removeClass('hidden alert-success alert-error alert-info')
                            .addClass(cls).text(msg).fadeIn(120);
                setTimeout(() => $('#msgbox').fadeOut(350, function() { $(this).addClass('hidden'); }), 3800);
            }
        });
    </script>
</body>
</html>
