<?php
$conn = new mysqli("localhost", "root", "", "library");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$id = intval($_GET['id']);
$result = $conn->query("SELECT email FROM students WHERE id=$id");
$row = $result->fetch_assoc();

if (isset($_POST['update'])) {
    $newEmail = $conn->real_escape_string($_POST['email']);
    $conn->query("UPDATE students SET email='$newEmail' WHERE id=$id");
    header("Location: manage_students.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head><title>Edit Email</title></head>
<body>
    <h2>Edit Student Email</h2>
    <form method="post">
        <input type="email" name="email" value="<?= $row['email'] ?>" required>
        <input type="submit" name="update" value="Update Email">
    </form>
</body>
</html>
