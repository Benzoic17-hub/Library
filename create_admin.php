<?php
$conn = new mysqli("localhost", "root", "", "library");
if ($conn->connect_error) {
    die("DB connection failed: " . $conn->connect_error);
}

$name = "Benjamin";
$email = "benzola560@gmail.com";
$password = "123456"; // plain text

$hashed = password_hash($password, PASSWORD_DEFAULT);

$sql = "INSERT INTO admins (name, email, password) VALUES ('$name', '$email', '$hashed')";
if ($conn->query($sql) === TRUE) {
    echo "✅ Admin created successfully!<br>";
    echo "Email: $email<br>Password: $password<br>";
} else {
    echo "Error: " . $conn->error;
}

$conn->close();
?>
