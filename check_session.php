<?php
// Session management and authentication check
function checkLogin($required_role = null) {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
        header("Location: login.php");
        exit();
    }
    
    if ($required_role && isset($_SESSION['role']) && $_SESSION['role'] !== $required_role) {
        header("Location: unauthorized.php");
        exit();
    }
    
    return true;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['username']);
}

function getUserRole() {
    return isset($_SESSION['role']) ? $_SESSION['role'] : null;
}

function getUserId() {
    return isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
}

function getUsername() {
    return isset($_SESSION['username']) ? $_SESSION['username'] : null;
}
?>
