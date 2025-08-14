<?php
function is_logged_in() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true;
}

function require_login() {
    if (!is_logged_in()) {
        header("Location: index.php");
        exit;
    }
}

function is_admin() {
    return is_logged_in();
}

function require_admin() {
    require_login();
    if (!is_admin()) {
        header("HTTP/1.1 403 Forbidden");
        exit;
    }
}

function verify_password($input_password, $hashed_password) {
    return password_verify($input_password, $hashed_password);
}
?>