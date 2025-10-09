<?php
$conn = new mysqli("localhost", "root", "", "library");

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    $result = $conn->query("SELECT * FROM students WHERE reset_token='$token' AND reset_expiry > NOW()");
    
    if ($result->num_rows > 0) {
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $new_pass = password_hash($_POST['password'], PASSWORD_BCRYPT);
            $conn->query("UPDATE students SET password='$new_pass', reset_token=NULL, reset_expiry=NULL WHERE reset_token='$token'");
            echo "✅ Password reset successful! You can now <a href='signin.html'>login</a>";
            exit;
        }
    } else {
        echo "❌ Invalid or expired reset link!";
        exit;
    }
} else {
    echo "❌ No token provided!";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Reset Password</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex justify-content-center align-items-center vh-100">

<div class="card p-4 shadow" style="width: 400px;">
  <h3 class="text-center mb-3">Reset Password</h3>
  <form method="POST">
    <div class="mb-3">
      <label for="password" class="form-label">New Password</label>
      <input type="password" class="form-control" id="password" name="password" required>
    </div>
    <button type="submit" class="btn btn-success w-100">Update Password</button>
  </form>
</div>

</body>
</html>
