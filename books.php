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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        /* Sidebar Styles */
        .sidebar {
            width: 280px;
            background: white;
            height: 100vh;
            position: fixed;
            display: flex;
            flex-direction: column;
            box-shadow: 4px 0 20px rgba(0, 0, 0, 0.1);
            z-index: 1000;
        }
        
        .sidebar-header {
            padding: 40px 30px 30px;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .sidebar-header h1 {
            font-size: 42px;
            font-weight: 800;
            color: #667eea;
            font-family: cursive;
            margin-bottom: 8px;
        }
        
        .sidebar-header .underline {
            width: 60px;
            height: 4px;
            background: #fbbf24;
            border-radius: 10px;
        }
        
        .sidebar-nav {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
        }
        
        .sidebar-nav a {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 16px 20px;
            color: #64748b;
            text-decoration: none;
            border-radius: 12px;
            margin-bottom: 8px;
            font-weight: 500;
            font-size: 15px;
        }
        
        .sidebar-nav a:hover {
            background: #f8f9ff;
            color: #667eea;
        }
        
        .sidebar-nav a.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            position: relative;
        }
        
        .sidebar-nav a.active::after {
            content: '';
            position: absolute;
            right: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 4px;
            height: 60%;
            background: white;
            border-radius: 10px 0 0 10px;
        }
        
        .sidebar-nav a .icon {
            font-size: 22px;
            width: 24px;
            text-align: center;
        }
        
        .sidebar-footer {
            padding: 20px;
            border-top: 1px solid #f0f0f0;
        }
        
        .admin-profile {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            margin-bottom: 15px;
        }
        
        .admin-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 18px;
        }
        
        .admin-info p {
            font-weight: 600;
            color: #1e293b;
            font-size: 14px;
        }
        
        .sidebar-footer a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 15px;
            color: #64748b;
            text-decoration: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 500;
        }
        
        .sidebar-footer a:hover {
            background: #f1f5f9;
            color: #667eea;
        }
        
        /* Main Content Area */
        .content {
            margin-left: 280px;
            flex: 1;
            padding: 40px;
        }
        
        .main-container {
            background: white;
            border-radius: 30px;
            padding: 40px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            min-height: calc(100vh - 80px);
        }
        
        .page-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f1f5f9;
        }
        
        .page-header .icon {
            font-size: 48px;
        }
        
        .page-header h2 {
            font-size: 36px;
            font-weight: 700;
            color: #1e293b;
        }
        
        /* Search Section */
        .search-section {
            background: #f8fafc;
            padding: 30px;
            border-radius: 20px;
            margin-bottom: 30px;
        }
        
        .search-form {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
            margin-bottom: 15px;
        }
        
        .search-input {
            flex: 1;
            min-width: 300px;
            padding: 16px 24px;
            border: 2px solid #e2e8f0;
            border-radius: 50px;
            font-size: 15px;
            font-family: 'Poppins', sans-serif;
            background: white;
            color: #1e293b;
        }
        
        .search-input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        }
        
        .search-input::placeholder {
            color: #94a3b8;
        }
        
        .btn {
            padding: 16px 32px;
            border: none;
            border-radius: 50px;
            font-size: 15px;
            font-weight: 600;
            font-family: 'Poppins', sans-serif;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
        }
        
        .btn-success {
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
            color: white;
        }
        
        .btn-success:hover {
            background: linear-gradient(135deg, #16a34a 0%, #22c55e 100%);
        }
        
        /* Table Styles */
        .table-container {
            overflow-x: auto;
            border-radius: 20px;
            border: 2px solid #f1f5f9;
            margin-bottom: 30px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        thead {
            background: #f8fafc;
        }
        
        th {
            padding: 20px;
            text-align: left;
            color: #64748b;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 13px;
            border-bottom: 2px solid #e2e8f0;
        }
        
        td {
            padding: 20px;
            color: #1e293b;
            font-weight: 500;
            border-bottom: 1px solid #f1f5f9;
            font-size: 15px;
        }
        
        tbody tr:hover {
            background: #f8f9ff;
        }
        
        tbody tr:last-child td {
            border-bottom: none;
        }
        
        .btn-delete {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            background: #fee2e2;
            color: #991b1b;
            border: 2px solid #fecaca;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 13px;
        }
        
        .btn-delete:hover {
            background: #fecaca;
        }
        
        /* Pagination */
        .pagination {
            display: flex;
            gap: 10px;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 30px;
        }
        
        .pagination a {
            padding: 12px 20px;
            background: #f8fafc;
            color: #64748b;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
            border: 2px solid #e2e8f0;
        }
        
        .pagination a:hover {
            background: #f1f5ff;
            color: #667eea;
            border-color: #667eea;
        }
        
        .pagination a.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-color: transparent;
        }
        
        .no-books {
            text-align: center;
            padding: 60px 20px;
            color: #94a3b8;
        }
        
        .no-books-icon {
            font-size: 64px;
            margin-bottom: 16px;
            opacity: 0.5;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .content {
                margin-left: 0;
                padding: 20px;
            }
            
            .search-form {
                flex-direction: column;
            }
            
            .search-input {
                width: 100%;
            }
        }
        
        /* Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f5f9;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h1>Library</h1>
            <div class="underline"></div>
        </div>
        
        <nav class="sidebar-nav">
            <a href="admin_dashboard.php">
                <span class="icon">🏠</span>
                <span>Dashboard</span>
            </a>
            <a href="books.php" class="active">
                <span class="icon">📚</span>
                <span>All Books</span>
            </a>
            <a href="add_book.php">
                <span class="icon">➕</span>
                <span>Add Book</span>
            </a>
            <a href="#search">
                <span class="icon">🔍</span>
                <span>Search Books</span>
            </a>
            <a href="manage_students.php">
                <span class="icon">🎓</span>
                <span>Manage Students</span>
            </a>
            
            </a>
        </nav>
        
        <div class="sidebar-footer">
            <div class="admin-profile">
                <div class="admin-avatar">AD</div>
                <div class="admin-info">
                    <p>Admin User</p>
                </div>
            </div>
            <a href="admin_logout.php">
                <span class="icon">🚪</span>
                <span>Log Out</span>
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="content">
        <div class="main-container">
            <div class="page-header">
                <span class="icon">📖</span>
                <h2>Book Management</h2>
            </div>
            
            <!-- Search Section -->
            <div class="search-section">
                <form method="GET" action="books.php" class="search-form">
                    <input 
                        type="text" 
                        name="query" 
                        class="search-input" 
                        placeholder="🔍 Search by title, author, or category..." 
                        value="<?php echo htmlspecialchars($searchQuery); ?>"
                    />
                    <button type="submit" class="btn btn-primary">Search</button>
                </form>

                <form method="POST" action="export_books.php">
                    <button type="submit" class="btn btn-success">
                        <span>📥</span>
                        <span>Export Books CSV</span>
                    </button>
                </form>
            </div>

            <?php if ($searchResults && $searchResults->num_rows > 0): ?>
                <!-- Table -->
                <div class="table-container">
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
                                    <td><?php echo htmlspecialchars($book['isbn'] ?? ''); ?></td>
                                    <td><?php echo $book['copies_available']; ?></td>
                                    <td>
                                        <a href="delete_book.php?id=<?php echo $book['book_id']; ?>" class="btn-delete" onclick="return confirm('Delete this book?');">
                                            <span>🗑️</span>
                                            <span>Delete</span>
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($totalBooks > $limit): ?>
                    <div class="pagination">
                        <?php
                        $totalPages = ceil($totalBooks / $limit);
                        for ($i = 1; $i <= $totalPages; $i++) {
                            $link = "books.php?page=$i";
                            if (!empty($searchQuery)) {
                                $link .= "&query=" . urlencode($searchQuery);
                            }
                            $activeClass = ($i == $page) ? 'active' : '';
                            echo "<a href='$link' class='$activeClass'>$i</a> ";
                        }
                        ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="no-books">
                    <div class="no-books-icon">📚</div>
                    <p>No books found.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>