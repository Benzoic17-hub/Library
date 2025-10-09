<?php
// Connect to database
$conn = new mysqli("localhost", "root", "", "library");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $fullname = trim($_POST['fullname']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];

    if (empty($fullname) || empty($username) || empty($email) || empty($password) || empty($confirm)) {
        $error = "❌ All fields are required.";
    } elseif ($password !== $confirm) {
        $error = "❌ Passwords do not match.";
    } else {
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("INSERT INTO students (fullname, username, email, password) VALUES (?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("ssss", $fullname, $username, $email, $hashed_password);
            if ($stmt->execute()) {
                $success = "✅ Registration successful! You can now login.";
            } else {
                $error = "❌ Error: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $error = "❌ Database error: " . $conn->error;
        }
    }
}
$conn->close();
?>
