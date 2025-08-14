<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - NORSU Queue</title>
    <link rel="stylesheet" href="../css/admin.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="login-body">
    <div class="admin-login-container">
        <div class="admin-login-box">
            <div class="login-header">
                <img src="../images/norsu-logo.png" alt="NORSU Logo" class="login-logo">
                <h2>Welcome Back</h2>
                <p>Sign in to your administrator account</p>
            </div>
            <form id="adminLoginForm" class="admin-login-form">
                <div class="form-group">
                    <label for="username">Username</label>
                    <div class="input-group">
                        <i class="fas fa-user"></i>
                        <input type="text" id="username" name="username" placeholder="Enter your username" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-group">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="password" name="password" placeholder="Enter your password" required>
                        <i class="fas fa-eye-slash toggle-password"></i>
                    </div>
                </div>
                <div id="login-error" class="error-message"></div>
                <button type="submit" class="admin-login-btn">
                    <span>Sign In</span>
                    <i class="fas fa-arrow-right"></i>
                </button>
            </form>
            <div class="login-footer">
                <p>NORSU Queue Management System</p>
                <p class="copyright">&copy; <?php echo date('Y'); ?> NORSU. All rights reserved.</p>
            </div>
        </div>
    </div>

    <script>
        // Toggle password visibility
        document.querySelector('.toggle-password').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const icon = this;
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            }
        });

        // Form submission
        document.getElementById('adminLoginForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const button = this.querySelector('button');
            const errorDiv = document.getElementById('login-error');
            
            try {
                button.disabled = true;
                button.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Signing in...';
                
                const response = await fetch('auth.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();
                
                if (data.success) {
                    button.innerHTML = '<i class="fas fa-check"></i> Success!';
                    setTimeout(() => {
                        window.location.href = 'dashboard.php';
                    }, 500);
                } else {
                    throw new Error(data.message);
                }
            } catch (error) {
                button.disabled = false;
                button.innerHTML = '<span>Sign In</span><i class="fas fa-arrow-right"></i>';
                errorDiv.textContent = error.message || 'Authentication failed. Please try again.';
                errorDiv.style.display = 'block';
            }
        });
    </script>
</body>
</html>