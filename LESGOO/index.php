<?php
session_start();
if(isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - NORSU Library System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #1e3c72;
            --secondary-color: #2a5298;
        }
        body {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        .login-card {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(10px);
            animation: fadeIn 0.5s ease-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .login-header {
            text-align: center;
            padding: 2rem;
            background: white;
            border-radius: 15px 15px 0 0;
        }
        .login-body {
            padding: 2rem;
        }
        .form-control {
            border-radius: 8px;
            padding: 0.8rem 1rem;
            border: 1px solid #dee2e6;
            transition: all 0.3s ease;
        }
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(30, 60, 114, 0.15);
        }
        .btn-primary {
            background: var(--primary-color);
            border: none;
            border-radius: 8px;
            padding: 0.8rem;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            background: var(--secondary-color);
            transform: translateY(-2px);
        }
        .error-message {
            background: rgba(220, 53, 69, 0.1);
            color: #dc3545;
            padding: 0.75rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            animation: shake 0.5s ease-in-out;
        }
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
        .school-name {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--primary-color);
            margin: 1rem 0;
        }
        .campus-name {
            color: #6c757d;
            margin-bottom: 2rem;
        }
        .form-floating > label {
            padding-left: 1rem;
        }
        .form-floating > .form-control {
            height: calc(3.5rem + 2px);
            line-height: 1.25;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="login-card">
                    <div class="login-header">
                        <img src="assets/img/norsu-logo.png" alt="NORSU Logo" style="width: 100px;" class="mb-3">
                        <div class="school-name">CODEEEE Library System</div>
                        <div class="campus-name">CODEEEE UNIVERSITY</div>
                    </div>
                    <div class="login-body">
                        <?php if(isset($_GET['error'])): ?>
                        <div class="error-message">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <?php echo htmlspecialchars($_GET['error']); ?>
                        </div>
                        <?php endif; ?>

                        <form action="auth/login.php" method="POST" class="needs-validation" novalidate>
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" id="username" name="username" placeholder="Username" required>
                                <label for="username">
                                    <i class="fas fa-user me-2"></i>Username
                                </label>
                                <div class="invalid-feedback">Please enter your username</div>
                            </div>

                            <div class="form-floating mb-4">
                                <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                                <label for="password">
                                    <i class="fas fa-lock me-2"></i>Password
                                </label>
                                <div class="invalid-feedback">Please enter your password</div>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-sign-in-alt me-2"></i>Login
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Form validation
        (function() {
            'use strict';
            const forms = document.querySelectorAll('.needs-validation');
            Array.from(forms).forEach(form => {
                form.addEventListener('submit', event => {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });
        })();

        // Password visibility toggle
        $(document).ready(function() {
            $('#togglePassword').click(function() {
                const passwordInput = $('#password');
                const icon = $(this).find('i');
                
                if (passwordInput.attr('type') === 'password') {
                    passwordInput.attr('type', 'text');
                    icon.removeClass('fa-eye').addClass('fa-eye-slash');
                } else {
                    passwordInput.attr('type', 'password');
                    icon.removeClass('fa-eye-slash').addClass('fa-eye');
                }
            });
        });
    </script>
</body>
</html>