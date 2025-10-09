<!DOCTYPE html>
<html>
<head>
    <title>Search Books</title>
</head>
<body>
    <h2>Library Book Search</h2>

    <!-- Search Form -->
    <form method="GET" action="">
        <input type="text" name="keyword" placeholder="Search by title, author, or category">
        <button type="submit">Search</button>
    </form>

    <br>

    <!-- Table to show results -->
    <table border="1" cellpadding="10">
        <tr>
            <th>ID</th>
            <th>Title</th>
            <th>Author</th>
            <th>Category</th>
            <th>Copies Available</th>
        </tr>
        <<?php
// Database connection
$servername = "localhost";
$username = "root";   // default in XAMPP
$password = "";       // default in XAMPP is empty
$dbname = "library_system";

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle search
$keyword = "";
if (isset($_GET['keyword'])) {
    $keyword = $_GET['keyword'];
    $sql = "SELECT * FROM books 
            WHERE title LIKE ? OR author LIKE ? OR category LIKE ?";
    $stmt = $conn->prepare($sql);
    $searchTerm = "%" . $keyword . "%";
    $stmt->bind_param("sss", $searchTerm, $searchTerm, $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    // If no search, display all books
    $sql = "SELECT * FROM books";
    $result = $conn->query($sql);
}

// Display books
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "<tr>
                <td>".$row['id']."</td>
                <td>".$row['title']."</td>
                <td>".$row['author']."</td>
                <td>".$row['category']."</td>
                <td>".$row['copies_available']."</td>
              </tr>";
    }
} else {
    echo "<tr><td colspan='5'>No books found</td></tr>";
}

$conn->close();
?>
    </table>
</body>
</html>
