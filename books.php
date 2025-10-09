<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin_login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "library");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Pagination setup
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Search handling
$searchQuery = "";
$totalBooks = 0;

if (isset($_GET['query']) && !empty(trim($_GET['query']))) {
    $searchQuery = trim($_GET['query']);
    $like = "%$searchQuery%";
    $stmt = $conn->prepare("SELECT book_id, title, author, category, isbn, copies_available FROM books WHERE title LIKE ? OR author LIKE ? OR category LIKE ? ORDER BY title ASC LIMIT ? OFFSET ?");
    $stmt->bind_param("sssii", $like, $like, $like, $limit, $offset);
    $stmt->execute();
    $searchResults = $stmt->get_result();

    $countStmt = $conn->prepare("SELECT COUNT(*) AS count FROM books WHERE title LIKE ? OR author LIKE ? OR category LIKE ?");
    $countStmt->bind_param("sss", $like, $like, $like);
    $countStmt->execute();
    $totalBooks = $countStmt->get_result()->fetch_assoc()['count'];
} else {
    $searchResults = $conn->query("SELECT book_id, title, author, category, isbn, copies_available FROM books ORDER BY title ASC LIMIT $limit OFFSET $offset");
    $totalBooks = $conn->query("SELECT COUNT(*) AS count FROM books")->fetch_assoc()['count'];
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Books Management</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap');
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            display: flex;
            min-height: 100vh;
            overflow-x: hidden;
            position: relative;
            background: url('images/library_bg1.jpg') no-repeat center center fixed;
            background-size: cover;
        }
        
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.7) 0%, rgba(118, 75, 162, 0.7) 25%, rgba(240, 147, 251, 0.6) 50%, rgba(79, 172, 254, 0.6) 75%, rgba(0, 242, 254, 0.7) 100%);
            background-size: 400% 400%;
            animation: gradientShift 15s ease infinite;
            z-index: 1;
            pointer-events: none;
        }
        
        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        .particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 2;
            pointer-events: none;
        }
        
        .particle {
            position: absolute;
            width: 10px;
            height: 10px;
            background: rgba(255, 255, 255, 0.5);
            border-radius: 50%;
            animation: float 20s infinite;
        }
        
        @keyframes float {
            0%, 100% {
                transform: translateY(0) translateX(0) rotate(0deg);
                opacity: 0;
            }
            10% { opacity: 1; }
            90% { opacity: 1; }
            100% {
                transform: translateY(-1000px) translateX(500px) rotate(720deg);
                opacity: 0;
            }
        }
        
        .sidebar {
            width: 260px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            color: white;
            height: 100vh;
            position: fixed;
            padding: 30px 20px;
            z-index: 100;
            border-right: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 5px 0 30px rgba(0, 0, 0, 0.2);
            animation: slideInLeft 0.8s ease;
        }
        
        @keyframes slideInLeft {
            from {
                transform: translateX(-100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        .sidebar h2 {
            text-align: center;
            margin-bottom: 40px;
            font-size: 28px;
            font-weight: 800;
            text-shadow: 0 0 20px rgba(255, 255, 255, 0.5);
            letter-spacing: 2px;
            padding-bottom: 20px;
            border-bottom: 2px solid rgba(255, 255, 255, 0.3);
        }
        
        .sidebar a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 15px 20px;
            color: white;
            text-decoration: none;
            transition: all 0.3s;
            border-radius: 12px;
            margin-bottom: 10px;
            font-weight: 500;
            font-size: 15px;
            position: relative;
            overflow: hidden;
        }
        
        .sidebar a::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 0;
            background: linear-gradient(90deg, rgba(255, 255, 255, 0.2), transparent);
            transition: width 0.4s;
            border-radius: 12px;
        }
        
        .sidebar a:hover::before {
            width: 100%;
        }
        
        .sidebar a:hover {
            background: rgba(255, 255, 255, 0.15);
            transform: translateX(8px);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);
        }
        
        .content {
            margin-left: 260px;
            padding: 40px;
            flex: 1;
            position: relative;
            z-index: 100;
            animation: fadeInUp 1s ease;
        }
        
        @keyframes fadeInUp {
            from {
                transform: translateY(50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        .box {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            padding: 40px;
            border-radius: 25px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
            transition: all 0.3s;
        }
        
        .box:hover {
            box-shadow: 0 12px 48px rgba(0, 0, 0, 0.2);
        }
        
        .box h2 {
            color: white;
            font-size: 36px;
            font-weight: 800;
            margin-bottom: 30px;
            text-shadow: 0 0 20px rgba(255, 255, 255, 0.3);
            letter-spacing: 1px;
        }
        
        form {
            margin-bottom: 25px;
        }
        
        input[type="text"] {
            padding: 18px 25px;
            width: 70%;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50px;
            font-size: 16px;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            color: white;
            font-weight: 500;
            transition: all 0.3s;
            font-family: 'Poppins', sans-serif;
        }
        
        input[type="text"]::placeholder {
            color: rgba(255, 255, 255, 0.7);
        }
        
        input[type="text"]:focus {
            outline: none;
            border-color: rgba(255, 255, 255, 0.6);
            background: rgba(255, 255, 255, 0.25);
            box-shadow: 0 0 30px rgba(255, 255, 255, 0.3);
        }
        
        input[type="submit"], .export-btn {
            padding: 16px 40px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 50px;
            cursor: pointer;
            font-weight: 700;
            font-size: 16px;
            transition: all 0.3s;
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
            font-family: 'Poppins', sans-serif;
            letter-spacing: 0.5px;
            margin-left: 15px;
        }
        
        input[type="submit"]:hover, .export-btn:hover {
            background: linear-gradient(135deg, #764ba2, #667eea);
            transform: translateY(-3px);
            box-shadow: 0 12px 35px rgba(102, 126, 234, 0.6);
        }
        
        .export-btn {
            background: linear-gradient(135deg, #11998e, #38ef7d);
            box-shadow: 0 8px 25px rgba(56, 239, 125, 0.4);
            margin-top: 15px;
            display: inline-block;
        }
        
        .export-btn:hover {
            background: linear-gradient(135deg, #38ef7d, #11998e);
            box-shadow: 0 12px 35px rgba(56, 239, 125, 0.6);
        }
        
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 12px;
            margin-top: 30px;
        }
        
        th {
            padding: 18px;
            text-align: left;
            color: white;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-size: 14px;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
        }
        
        th:first-child {
            border-radius: 12px 0 0 12px;
        }
        
        th:last-child {
            border-radius: 0 12px 12px 0;
        }
        
        td {
            padding: 20px 18px;
            color: white;
            font-weight: 500;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
        }
        
        tr {
            transition: all 0.3s;
        }
        
        tbody tr:hover td {
            background: rgba(255, 255, 255, 0.2);
            transform: scale(1.02);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
        }
        
        tbody tr td:first-child {
            border-radius: 12px 0 0 12px;
        }
        
        tbody tr td:last-child {
            border-radius: 0 12px 12px 0;
        }
        
        .btn-delete {
            background: linear-gradient(135deg, #ff6b6b, #ee5a6f);
            color: white;
            padding: 10px 20px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s;
            display: inline-block;
            box-shadow: 0 4px 15px rgba(238, 90, 111, 0.4);
        }
        
        .btn-delete:hover {
            background: linear-gradient(135deg, #ee5a6f, #ff6b6b);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(238, 90, 111, 0.6);
        }
        
        .pagination {
            margin-top: 30px;
            display: flex;
            gap: 10px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .pagination a {
            padding: 12px 20px;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            color: white;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s;
            border: 2px solid rgba(255, 255, 255, 0.2);
        }
        
        .pagination a:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);
        }
        
        .no-books {
            text-align: center;
            color: white;
            font-size: 18px;
            padding: 40px;
            font-weight: 500;
        }
        
        ::-webkit-scrollbar {
            width: 12px;
        }
        
        ::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
        }
        
        ::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.3);
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.5);
        }
    </style>
</head>
<body>
    <div class="particles" id="particles"></div>

    <div class="sidebar">
        <h2>📚 Admin</h2>
        <a href="admin_dashboard.php">🏠 Dashboard</a>
        <a href="books.php">📚 All Books</a>
        <a href="add_book.php">➕ Add Book</a>
        <a href="manage_students.php">🎓 Manage Students</a>
        <a href="borrow_return.php">📖 Borrow/Return</a>
        <a href="logout.php">🚪 Logout</a>
    </div>

    <div class="content">
        <div class="box">
            <h2>📖 Book Management</h2>
            
            <form method="GET" action="books.php">
                <input type="text" name="query" placeholder="🔍 Search by title, author, or category..." value="<?php echo htmlspecialchars($searchQuery); ?>" />
                <input type="submit" value="Search" />
            </form>

            <form method="POST" action="export_books.php">
                <button type="submit" class="export-btn">📥 Export Books CSV</button>
            </form>

            <?php if ($searchResults && $searchResults->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Author</th>
                            <th>Category</th>
                            <th>ISBN</th>
                            <th>Copies</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($book = $searchResults->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $book['book_id']; ?></td>
                                <td><?php echo htmlspecialchars($book['title']); ?></td>
                                <td><?php echo htmlspecialchars($book['author']); ?></td>
                                <td><?php echo htmlspecialchars($book['category']); ?></td>
                                <td><?php echo htmlspecialchars($row['isbn'] ?? ''); ?></td>
                                <td><?php echo $book['copies_available']; ?></td>
                                <td>
                                    <a href="delete_book.php?id=<?php echo $book['book_id']; ?>" class="btn-delete" onclick="return confirm('Delete this book?');">🗑️ Delete</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>

                <?php if ($totalBooks > $limit): ?>
                    <div class="pagination">
                        <?php
                        $totalPages = ceil($totalBooks / $limit);
                        for ($i = 1; $i <= $totalPages; $i++) {
                            $link = "books.php?page=$i";
                            if (!empty($searchQuery)) {
                                $link .= "&query=" . urlencode($searchQuery);
                            }
                            echo "<a href='$link'>$i</a> ";
                        }
                        ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <p class="no-books">No books found.</p>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function createParticles() {
            const particlesContainer = document.getElementById('particles');
            const particleCount = 50;
            
            for (let i = 0; i < particleCount; i++) {
                const particle = document.createElement('div');
                particle.classList.add('particle');
                particle.style.left = Math.random() * 100 + '%';
                particle.style.animationDelay = Math.random() * 20 + 's';
                particle.style.animationDuration = (Math.random() * 10 + 15) + 's';
                particlesContainer.appendChild(particle);
            }
        }
        
        createParticles();
    </script>
</body>
</html>