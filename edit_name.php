<?php
$conn = new mysqli("localhost", "root", "", "library");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$id = intval($_GET['id']);
$result = $conn->query("SELECT fullname FROM students WHERE id=$id");
$row = $result->fetch_assoc();

if (isset($_POST['update'])) {
    $newName = $conn->real_escape_string($_POST['fullname']);
    $conn->query("UPDATE students SET fullname='$newName' WHERE id=$id");
    header("Location: manage_students.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head><title>Edit Name</title></head>
<body>
    <h2>Edit Student Name</h2>
    <form method="post">
        <input type="text" name="fullname" value="<?= $row['fullname'] ?>" required>
        <input type="submit" name="update" value="Update Name">
    </form>
</body>
</html>
