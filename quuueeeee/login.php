<?php
session_start();
// Change the condition to check if user is NOT logged in
if(isset($_SESSION['student_id'])) {
    header("Location: services.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Login - NORSU Registrar Queue</title>
    <link rel="stylesheet" href="css/styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="modern-login-body">
    <div class="modern-login-container">
        <div class="modern-login-left">
            <div class="login-welcome">
                <img src="images/norsu-logo.png" alt="NORSU Logo" class="modern-logo">
                <h1>Welcome Back!</h1>
                <p>NORSU Mabinay Campus Registrar Services</p>
            </div>
        </div>
        
        <div class="modern-login-right">
            <div class="modern-login-form">
                <div class="form-toggle">
                    <button class="toggle-btn active" data-form="login">Login</button>
                    <button class="toggle-btn" data-form="register">Register</button>
                </div>

                <div id="loginForm" class="form-section active">
                    <h2>Student Login</h2>
                    <p class="modern-subtitle">Welcome back! Please login to continue</p>
                    
                    <div id="login-error" class="modern-error"></div>
                    
                    <form id="studentLoginForm" method="POST" class="auth-form">
                        <div class="modern-form-group">
                            <i class="fas fa-id-card"></i>
                            <input type="text" name="student_number" required 
                                   pattern="[0-9]{9}" maxlength="9"
                                   placeholder="Student ID Number">
                        </div>

                        <div class="modern-form-group">
                            <i class="fas fa-user"></i>
                            <input type="text" name="name" required 
                                   placeholder="Full Name">
                        </div>

                        <button type="submit" class="modern-button">
                            <span>Login</span>
                            <i class="fas fa-arrow-right"></i>
                        </button>
                    </form>
                </div>

                <div id="registerForm" class="form-section">
                    <h2>Student Registration</h2>
                    <p class="modern-subtitle">Create your account to get started</p>
                    
                    <div id="register-error" class="modern-error"></div>
                    
                    <form method="POST" class="auth-form">
                        <div class="modern-form-group">
                            <i class="fas fa-id-card"></i>
                            <input type="text" name="student_number" required 
                                   pattern="[0-9]{9}" maxlength="9"
                                   placeholder="Student ID Number">
                        </div>

                        <div class="modern-form-group">
                            <i class="fas fa-user"></i>
                            <input type="text" name="name" required 
                                   placeholder="Full Name">
                        </div>

                        <div class="modern-form-group">
                            <i class="fas fa-graduation-cap"></i>
                            <select name="course" required>
                                <option value="">Select Course</option>
                                <option value="BSIT">BS Information Technology</option>
                                <option value="BSED">BS Secondary Education</option>
                                <option value="BEED">BS Elementary Education</option>
                                <option value="BSBA">BS Business Administration</option>
                                <option value="BSA">BS Agriculture</option>
                                <option value="BSC">BS Computer Science</option>
                                <option value="BSA">BS Hospitality Management</option>
                                <option value="BSCRIM">BS Crime Science</option>
                                <option value="BSIT - Major in Automotive">BSIT - Major in Automotive</option>

                            </select>
                        </div>

                        <div class="modern-form-group">
                            <i class="fas fa-layer-group"></i>
                            <select name="year_level" required>
                                <option value="">Year Level</option>
                                <option value="1st Year">1st Year</option>
                                <option value="2nd Year">2nd Year</option>
                                <option value="3rd Year">3rd Year</option>
                                <option value="4th Year">4th Year</option>
                            </select>
                        </div>

                        <button type="submit" class="modern-button">
                            <span>Register</span>
                            <i class="fas fa-user-plus"></i>
                        </button>
                    </form>
                </div>

                <div class="modern-divider">
                    <span>or</span>
                </div>

                <a href="guest.php" class="guest-button">
                    <i class="fas fa-user-friends"></i>
                    <span>Continue as Guest</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Remove this line -->
    <!-- <script src="js/login.js"></script> -->
</body>
</html>
<script>
    // Toggle functionality for login/register forms
    document.querySelectorAll('.toggle-btn').forEach(button => {
        button.addEventListener('click', function() {
            // Remove active class from all buttons and forms
            document.querySelectorAll('.toggle-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.form-section').forEach(form => form.classList.remove('active'));
            
            // Add active class to clicked button and corresponding form
            this.classList.add('active');
            document.getElementById(this.dataset.form + 'Form').classList.add('active');
        });
    });

    // Login form submission
    document.getElementById('studentLoginForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const button = this.querySelector('button');
        const errorDiv = document.getElementById('login-error');
        
        try {
            button.disabled = true;
            button.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Processing...';
            
            const response = await fetch('auth.php', {
                method: 'POST',
                body: formData
            });

            if (!response.ok) {
                throw new Error('Network response was not ok');
            }

            const data = await response.json();
            
            if (data.success) {
                window.location.href = 'services.php';
            } else {
                throw new Error(data.message);
            }
        } catch (error) {
            button.disabled = false;
            button.innerHTML = '<span>Login</span><i class="fas fa-arrow-right"></i>';
            errorDiv.textContent = error.message || 'Login failed. Please try again.';
            errorDiv.style.display = 'block';
        }
    });
</script>