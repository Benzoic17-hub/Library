<?php
session_start();

// Save role before destroying session
$role = isset($_SESSION['role']) ? $_SESSION['role'] : null;

// Clear session
$_SESSION = array();
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

// Redirect based on role
if ($role === 'admin') {
    header("Location: admin_login.php"); // back to admin login
} else {
    header("Location: index.php"); // back to student login
}
exit();
?>
