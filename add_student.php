<?php
// connect to database
$host = "localhost";
$user = "root";   
$pass = "";       
$db   = "library"; 

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// add student
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $fullname = $_POST['fullname'];
    $email    = $_POST['email'];
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO students (fullname, email, username, password) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $fullname, $email, $username, $password);
    $stmt->execute();

    header("Location: manage_students.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Add Student</title>
    <style>
        body { font-family: Arial; background: #f4f6f9; margin: 20px; }
        .form-container {
            background: white; padding: 20px; border-radius: 8px;
            box-shadow: 0 0 5px gray; width: 400px; margin: auto;
        }
        input { width: 100%; padding: 10px; margin: 8px 0; border: 1px solid #ccc; border-radius: 5px; }
        input[type="submit"] { background: #28a745; color: white; border: none; cursor: pointer; }
        input[type="submit"]:hover { background: #218838; }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>➕ Add Student</h2>
        <form method="post">
            <input type="text" name="fullname" placeholder="Full Name" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <input type="submit" value="Add Student">
        </form>
    </div>
</body>
</html>
