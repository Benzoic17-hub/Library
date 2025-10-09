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

$book = [];
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $conn->prepare("SELECT * FROM books WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $book = $stmt->get_result()->fetch_assoc();
}

if (isset($_POST['update'])) {
    $id = intval($_POST['id']);
    $title = trim($_POST['title']);
    $author = trim($_POST['author']);
    $category = trim($_POST['category']);
    $copies = intval($_POST['copies']);

    $stmt = $conn->prepare("UPDATE books SET title=?, author=?, category=?, copies_available=? WHERE id=?");
    $stmt->bind_param("sssii", $title, $author, $category, $copies, $id);
    if ($stmt->execute()) {
        header("Location: admin_dashboard.php?msg=updated");
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Book</title>
    <style>
        body { font-family: Arial; background: #f4f4f4; padding: 20px; }
        .box { background: white; padding: 20px; border-radius: 8px; max-width: 400px; margin: auto; box-shadow: 0px 0px 5px gray; }
        input[type="text"], input[type="number"] { width: 100%; padding: 10px; margin: 5px 0; border: 1px solid #ccc; border-radius: 5px; }
        input[type="submit"] { padding: 10px 20px; background: #28a745; color: white; border: none; border-radius: 5px; cursor: pointer; }
        input[type="submit"]:hover { background: #218838; }
    </style>
</head>
<body>
    <div class="box">
        <h2>Edit Book</h2>
        <form method="post">
            <input type="hidden" name="id" value="<?= $book['id'] ?>">
            
            <label>Title:</label>
            <input type="text" name="title" value="<?= htmlspecialchars($book['title']) ?>" required>
            
            <label>Author:</label>
            <input type="text" name="author" value="<?= htmlspecialchars($book['author']) ?>" required>
            
            <label>Category:</label>
            <input type="text" name="category" value="<?= htmlspecialchars($book['category']) ?>" required>
            
            <label>Copies Available:</label>
            <input type="number" name="copies" value="<?= $book['copies_available'] ?>" required>
            
            <input type="submit" name="update" value="Update Book">
        </form>
    </div>
</body>
</html>
