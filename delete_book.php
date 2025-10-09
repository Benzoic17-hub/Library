<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "library");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    // Instead of permanent delete, mark book as Inactive
    $stmt = $conn->prepare("UPDATE books SET status='Inactive' WHERE id=?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        header("Location: admin_dashboard.php?msg=book_deleted");
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }
}
?>
