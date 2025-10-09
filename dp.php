<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.php");
    exit();
}

// Database connection
$host = "localhost";
$user = "root";
$pass = "";
$db = "library";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="d-flex">

<!-- Sidebar -->
<div class="bg-dark text-white p-3" style="width:220px; height:100vh;">
  <h4>📚 Admin</h4>
  <ul class="nav flex-column">
    <li class="nav-item"><a href="dp.php?page=books" class="nav-link text-white">Manage Books</a></li>
    <li class="nav-item"><a href="dp.php?page=students" class="nav-link text-white">Manage Students</a></li>
    <li class="nav-item"><a href="dp.php?page=borrow" class="nav-link text-white">Borrow Records</a></li>
    <li class="nav-item"><a href="dp.php?page=return" class="nav-link text-white">Return Records</a></li>
    <li class="nav-item"><a href="logout.php" class="nav-link text-danger">Logout</a></li>
  </ul>
</div>

<!-- Main Content -->
<div class="container p-4">
<?php
if (isset($_GET['page'])) {
    $page = $_GET['page'];

    if ($page == "books") {
        echo "<h2>📘 Manage Books</h2>";
        $res = $conn->query("SELECT * FROM books");
        echo "<table class='table table-bordered'>
                <tr><th>ID</th><th>Title</th><th>Author</th><th>Actions</th></tr>";
        while ($row = $res->fetch_assoc()) {
            echo "<tr>
                    <td>{$row['book_id']}</td>
                    <td>{$row['title']}</td>
                    <td>{$row['author']}</td>
                    <td><a href='delete_book.php?id={$row['book_id']}' class='btn btn-danger btn-sm'>Delete</a></td>
                  </tr>";
        }
        echo "</table>";

    } elseif ($page == "students") {
        echo "<h2>👨‍🎓 Manage Students</h2>";
        $res = $conn->query("SELECT * FROM students");
        echo "<table class='table table-bordered'>
                <tr><th>ID</th><th>Name</th><th>Email</th></tr>";
        while ($row = $res->fetch_assoc()) {
            echo "<tr>
                    <td>{$row['id']}</td>
                    <td>{$row['fullname']}</td>
                    <td>{$row['email']}</td>
                  </tr>";
        }
        echo "</table>";

    } elseif ($page == "borrow") {
        echo "<h2>📖 Borrow Records</h2>";
        $res = $conn->query("SELECT br.id, s.fullname, b.title, br.borrow_date, br.due_date, br.status 
                             FROM borrow_return br
                             JOIN students s ON br.student_id = s.id
                             JOIN books b ON br.book_id = b.book_id
                             WHERE br.status = 'Borrowed'");
        echo "<table class='table table-bordered'>
                <tr><th>ID</th><th>Student</th><th>Book</th><th>Borrow Date</th><th>Due Date</th><th>Status</th></tr>";
        while ($row = $res->fetch_assoc()) {
            echo "<tr>
                    <td>{$row['id']}</td>
                    <td>{$row['fullname']}</td>
                    <td>{$row['title']}</td>
                    <td>{$row['borrow_date']}</td>
                    <td>{$row['due_date']}</td>
                    <td>{$row['status']}</td>
                  </tr>";
        }
        echo "</table>";

    } elseif ($page == "return") {
        echo "<h2>🔙 Return Records</h2>";
        $res = $conn->query("SELECT br.id, s.fullname, b.title, br.return_date, br.fine 
                             FROM borrow_return br
                             JOIN students s ON br.student_id = s.id
                             JOIN books b ON br.book_id = b.book_id
                             WHERE br.status = 'Returned'");
        echo "<table class='table table-bordered'>
                <tr><th>ID</th><th>Student</th><th>Book</th><th>Return Date</th><th>Fine</th></tr>";
        while ($row = $res->fetch_assoc()) {
            echo "<tr>
                    <td>{$row['id']}</td>
                    <td>{$row['fullname']}</td>
                    <td>{$row['title']}</td>
                    <td>{$row['return_date']}</td>
                    <td>GH₵{$row['fine']}</td>
                  </tr>";
        }
        echo "</table>";
    }
} else {
    echo "<h2>👋 Welcome Admin</h2><p>Select an option from the sidebar.</p>";
}
?>
</div>
</body>
</html>
