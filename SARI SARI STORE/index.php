<?php
session_start();

// Check if user is already logged in
if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    header("Location: dashboard.php");
    exit;
}

// Database connection
require_once 'includes/db.php';

// Handle login form submission
if($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    
    // Validate credentials
    $sql = "SELECT id, username, password FROM users WHERE username = ?";
    
    if($stmt = $mysqli->prepare($sql)) {
        $stmt->bind_param("s", $username);
        
        if($stmt->execute()) {
            $stmt->store_result();
            
            if($stmt->num_rows == 1) {
                $stmt->bind_result($id, $username, $hashed_password);
                if($stmt->fetch()) {
                    if(password_verify($password, $hashed_password)) {
                        // Password is correct, start a new session
                        session_start();
                        
                        // Store data in session variables
                        $_SESSION['loggedin'] = true;
                        $_SESSION['id'] = $id;
                        $_SESSION['username'] = $username;                    
                        
                        // Redirect user to dashboard
                        header("Location: dashboard.php");
                        exit;
                    } else {
                        // Display an error message if password is not valid
                        $login_err = "Invalid username or password.";
                    }
                }
            } else {
                // Display an error message if username doesn't exist
                $login_err = "Invalid username or password.";
            }
        } else {
            echo "Oops! Something went wrong. Please try again later.";
        }
        
        $stmt->close();
    }
    
    $mysqli->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Sari-Sari Store - Login</title>
    <link rel="stylesheet" href="assets/css/index.css" />
</head>
<body>
    <div class="login-container">
        <div class="login-logo">CODEEE</div>
        <h1 class="login-title">GAMA GAMA SYSTEM Sari-Sari Store</h1>

        <?php if(!empty($login_err)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($login_err) ?></div>
        <?php endif; ?>

        <form action="<?= htmlspecialchars($_SERVER["PHP_SELF"]) ?>" method="post" class="login-form" autocomplete="off">
            <div class="form-group">
                <input type="text" id="username" name="username" required placeholder=" " />
                <label for="username">Username</label>
            </div>

            <div class="form-group">
                <input type="password" id="password" name="password" required placeholder=" " />
                <label for="password">Password</label>
            </div>

            <button type="submit" class="login-btn">Login</button>
        </form>
    </div>
</body>
</html>