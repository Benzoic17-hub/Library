<?php
session_start();

// Check if student is logged in
if (!isset($_SESSION['role']) || $_SESSION['role'] !== "student" || !isset($_SESSION['student_id'])) {
    header("Location: student_login.php");
    exit();
}

// Database connection
$conn = new mysqli("localhost", "root", "", "library");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$student_id = $_SESSION['student_id'];
$today = date('Y-m-d');
$message = "";

// Handle profile update
if (isset($_POST['update_profile'])) {
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    
    $stmt = $conn->prepare("UPDATE students SET fullname = ?, email = ? WHERE student_id = ?");
    $stmt->bind_param("ssi", $fullname, $email, $student_id);
    if ($stmt->execute()) {
        $_SESSION['fullname'] = $fullname;
        $_SESSION['email'] = $email;
        $message = "<div class='alert alert-success'>✅ Profile updated successfully!</div>";
    }
    $stmt->close();
}

// Handle password change
if (isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    $stmt = $conn->prepare("SELECT password FROM students WHERE student_id = ?");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    if (password_verify($current_password, $result['password'])) {
        if ($new_password === $confirm_password && strlen($new_password) >= 6) {
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE students SET password = ? WHERE student_id = ?");
            $stmt->bind_param("si", $hashed, $student_id);
            $stmt->execute();
            $message = "<div class='alert alert-success'>✅ Password changed successfully!</div>";
        } else {
            $message = "<div class='alert alert-error'>❌ Passwords don't match or too short (min 6 characters).</div>";
        }
    } else {
        $message = "<div class='alert alert-error'>❌ Current password is incorrect.</div>";
    }
    $stmt->close();
}

// Count active borrows
$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM borrow_return WHERE student_id = ? AND status = 'Borrowed'");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$activeCount = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// Borrow a book
if (isset($_POST['borrow'])) {
    $book_id = intval($_POST['book_id']);
    
    $stmt = $conn->prepare("SELECT * FROM borrow_return WHERE student_id = ? AND book_id = ? AND status = 'Borrowed'");
    $stmt->bind_param("ii", $student_id, $book_id);
    $stmt->execute();
    $duplicate = $stmt->get_result();
    
    if ($duplicate->num_rows > 0) {
        $message = "<div class='alert alert-error'>❌ You already borrowed this book.</div>";
    } elseif ($activeCount >= 5) {
        $message = "<div class='alert alert-error'>❌ Borrow limit reached (5 books max). Return a book first.</div>";
    } else {
        $stmt = $conn->prepare("SELECT copies_available FROM books WHERE book_id = ?");
        $stmt->bind_param("i", $book_id);
        $stmt->execute();
        $book = $stmt->get_result()->fetch_assoc();
        
        if ($book && $book['copies_available'] > 0) {
            $stmt = $conn->prepare("INSERT INTO borrow_return (student_id, book_id, borrow_date, due_date, status) 
                                    VALUES (?, ?, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 14 DAY), 'Borrowed')");
            $stmt->bind_param("ii", $student_id, $book_id);
            $stmt->execute();
            
            $stmt = $conn->prepare("UPDATE books SET copies_available = copies_available - 1 WHERE book_id = ?");
            $stmt->bind_param("i", $book_id);
            $stmt->execute();
            
            $message = "<div class='alert alert-success'>✅ Book borrowed successfully! Due in 14 days.</div>";
            $activeCount++;
        } else {
            $message = "<div class='alert alert-error'>❌ Sorry, this book is not available.</div>";
        }
        $stmt->close();
    }
}

// Return a book
if (isset($_POST['return'])) {
    $borrow_id = intval($_POST['borrow_id']);
    
    $stmt = $conn->prepare("SELECT book_id, due_date FROM borrow_return WHERE id = ?");
    $stmt->bind_param("i", $borrow_id);
    $stmt->execute();
    $borrow_data = $stmt->get_result()->fetch_assoc();
    
    $daysLate = max(0, ceil((strtotime($today) - strtotime($borrow_data['due_date'])) / (60 * 60 * 24)));
    $fine = $daysLate * 1;
    
    $stmt = $conn->prepare("UPDATE borrow_return SET return_date = CURDATE(), status = 'Returned', fine = ? WHERE id = ?");
    $stmt->bind_param("di", $fine, $borrow_id);
    $stmt->execute();
    
    $stmt = $conn->prepare("UPDATE books SET copies_available = copies_available + 1 WHERE book_id = ?");
    $stmt->bind_param("i", $borrow_data['book_id']);
    $stmt->execute();
    
    $message = "<div class='alert alert-success'>✅ Book returned successfully!" . ($fine > 0 ? " Fine: GH₵$fine" : "") . "</div>";
    $stmt->close();
}

// Search and filters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category = isset($_GET['category']) ? trim($_GET['category']) : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - Library</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #f5f7fa;
            color: #1a1a1a;
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: 240px;
            background: #fff;
            border-right: 1px solid #e5e7eb;
            display: flex;
            flex-direction: column;
            position: fixed;
            height: 100vh;
            left: 0;
            top: 0;
        }

        .sidebar-header {
            padding: 24px 20px;
            border-bottom: 1px solid #e5e7eb;
        }

        .sidebar-header h2 {
            font-size: 18px;
            font-weight: 700;
            color: #1a1a1a;
        }

        .sidebar-nav {
            flex: 1;
            padding: 16px 0;
        }

        .nav-item {
            padding: 12px 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            color: #6b7280;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
        }

        .nav-item:hover {
            background: #f9fafb;
            color: #1a1a1a;
        }

        .nav-item.active {
            background: #eff6ff;
            color: #2563eb;
            border-left: 3px solid #2563eb;
        }

        .nav-icon {
            font-size: 20px;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 240px;
            display: flex;
            flex-direction: column;
        }

        /* Top Bar */
        .topbar {
            background: #fff;
            border-bottom: 1px solid #e5e7eb;
            padding: 16px 32px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .topbar-title {
            font-size: 24px;
            font-weight: 700;
            color: #1a1a1a;
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .user-badge {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 8px 16px;
            background: #f9fafb;
            border-radius: 8px;
        }

        .user-name {
            font-size: 14px;
            font-weight: 600;
            color: #1a1a1a;
        }

        .logout-btn {
            padding: 8px 20px;
            background: #ef4444;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: background 0.2s;
        }

        .logout-btn:hover {
            background: #dc2626;
        }

        /* Content Area */
        .content-area {
            flex: 1;
            padding: 32px;
            overflow-y: auto;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 24px;
            margin-bottom: 32px;
        }

        .stat-card {
            background: #fff;
            padding: 24px;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            transition: box-shadow 0.2s;
        }

        .stat-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: #1a1a1a;
            margin-bottom: 8px;
        }

        .stat-label {
            font-size: 14px;
            color: #6b7280;
            font-weight: 500;
        }

        /* Content Card */
        .content-card {
            background: #fff;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            overflow: hidden;
        }

        .card-header {
            padding: 20px 24px;
            border-bottom: 1px solid #e5e7eb;
        }

        .card-title {
            font-size: 18px;
            font-weight: 600;
            color: #1a1a1a;
            margin-bottom: 16px;
        }

        .card-body {
            padding: 24px;
        }

        /* Filters */
        .filters {
            display: flex;
            gap: 12px;
            margin-bottom: 24px;
            flex-wrap: wrap;
        }

        .search-input {
            flex: 1;
            min-width: 250px;
            padding: 10px 16px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.2s;
        }

        .search-input:focus {
            outline: none;
            border-color: #2563eb;
        }

        .filter-select {
            padding: 10px 16px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 14px;
            background: white;
            cursor: pointer;
            transition: border-color 0.2s;
        }

        .filter-select:focus {
            outline: none;
            border-color: #2563eb;
        }

        .btn {
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
        }

        .btn-primary {
            background: #2563eb;
            color: white;
        }

        .btn-primary:hover {
            background: #1d4ed8;
        }

        .btn-secondary {
            background: #f3f4f6;
            color: #374151;
        }

        .btn-secondary:hover {
            background: #e5e7eb;
        }

        /* Books Grid */
        .books-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
        }

        .book-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            overflow: hidden;
            transition: all 0.2s;
            cursor: pointer;
        }

        .book-card:hover {
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
            transform: translateY(-4px);
        }

        .book-cover {
            width: 100%;
            height: 240px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 72px;
            font-weight: 700;
            color: white;
            position: relative;
        }

        .book-actions {
            position: absolute;
            top: 12px;
            right: 12px;
            display: flex;
            gap: 8px;
            opacity: 0;
            transition: opacity 0.2s;
        }

        .book-card:hover .book-actions {
            opacity: 1;
        }

        .action-btn {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.9);
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background 0.2s;
        }

        .action-btn:hover {
            background: white;
        }

        .book-info {
            padding: 16px;
        }

        .book-title {
            font-size: 14px;
            font-weight: 600;
            color: #1a1a1a;
            margin-bottom: 4px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .book-author {
            font-size: 12px;
            color: #6b7280;
            margin-bottom: 12px;
        }

        .book-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }

        .badge-success {
            background: #d1fae5;
            color: #065f46;
        }

        .badge-warning {
            background: #fef3c7;
            color: #92400e;
        }

        .badge-danger {
            background: #fee2e2;
            color: #991b1b;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
            border-radius: 6px;
        }

        /* Table */
        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #f9fafb;
            padding: 12px 16px;
            text-align: left;
            font-size: 12px;
            font-weight: 600;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid #e5e7eb;
        }

        td {
            padding: 16px;
            border-bottom: 1px solid #f3f4f6;
            font-size: 14px;
        }

        tr:hover {
            background: #f9fafb;
        }

        /* Alert */
        .alert {
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 24px;
            font-size: 14px;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        /* Form */
        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: 600;
            color: #374151;
        }

        .form-control {
            width: 100%;
            padding: 10px 16px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.2s;
        }

        .form-control:focus {
            outline: none;
            border-color: #2563eb;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .empty-state {
            text-align: center;
            padding: 48px 24px;
            color: #9ca3af;
        }

        .empty-icon {
            font-size: 48px;
            margin-bottom: 16px;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h2>📚 e-Library</h2>
        </div>
        <nav class="sidebar-nav">
            <div class="nav-item active" onclick="showTab('books')">
                <span class="nav-icon">📖</span>
                <span>Browse Books</span>
            </div>
            <div class="nav-item" onclick="showTab('borrowed')">
                <span class="nav-icon">📚</span>
                <span>My Books</span>
            </div>
            <div class="nav-item" onclick="showTab('history')">
                <span class="nav-icon">📜</span>
                <span>History</span>
            </div>
            <div class="nav-item" onclick="showTab('profile')">
                <span class="nav-icon">👤</span>
                <span>Profile</span>
            </div>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Bar -->
        <div class="topbar">
            <h1 class="topbar-title" id="pageTitle">Browse Books</h1>
            <div class="topbar-right">
                <div class="user-badge">
                    <span class="user-name"><?php echo htmlspecialchars($_SESSION['fullname']); ?></span>
                </div>
                <a href="logout.php" class="logout-btn">Logout</a>
            </div>
        </div>

        <!-- Content Area -->
        <div class="content-area">
            <?php if ($message) echo $message; ?>

            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value"><?php echo $activeCount; ?>/5</div>
                    <div class="stat-label">Books Borrowed</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">
                        <?php
                        $stmt = $conn->prepare("SELECT SUM(fine) AS total FROM borrow_return WHERE student_id = ? AND status = 'Borrowed'");
                        $stmt->bind_param("i", $student_id);
                        $stmt->execute();
                        $totalFine = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
                        echo "₵" . number_format($totalFine, 2);
                        $stmt->close();
                        ?>
                    </div>
                    <div class="stat-label">Outstanding Fines</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">
                        <?php
                        $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM borrow_return WHERE student_id = ?");
                        $stmt->bind_param("i", $student_id);
                        $stmt->execute();
                        echo $stmt->get_result()->fetch_assoc()['total'];
                        $stmt->close();
                        ?>
                    </div>
                    <div class="stat-label">Total Borrowed</div>
                </div>
            </div>

            <!-- Browse Books Tab -->
            <div id="books" class="tab-content active">
                <div class="content-card">
                    <div class="card-header">
                        <h2 class="card-title">Library Collection</h2>
                        <form method="GET" class="filters">
                            <input type="text" name="search" class="search-input" placeholder="Search by title, author, or ISBN..." value="<?php echo htmlspecialchars($search); ?>">
                            <select name="category" class="filter-select">
                                <option value="">All Categories</option>
                                <?php
                                $cats = $conn->query("SELECT DISTINCT category FROM books ORDER BY category");
                                while ($cat = $cats->fetch_assoc()) {
                                    $selected = ($category == $cat['category']) ? 'selected' : '';
                                    echo "<option value='{$cat['category']}' $selected>{$cat['category']}</option>";
                                }
                                ?>
                            </select>
                            <button type="submit" class="btn btn-primary">Apply Filters</button>
                            <?php if ($search || $category): ?>
                                <a href="student_dashboard.php" class="btn btn-secondary" style="text-decoration: none;">Clear</a>
                            <?php endif; ?>
                        </form>
                    </div>
                    <div class="card-body">
                        <div class="books-grid">
                            <?php
                            $query = "SELECT * FROM books WHERE copies_available > 0";
                            $params = [];
                            $types = "";
                            
                            if ($search && $category) {
                                $query .= " AND (title LIKE ? OR author LIKE ? OR isbn LIKE ?) AND category = ?";
                                $searchTerm = "%$search%";
                                $params = [$searchTerm, $searchTerm, $searchTerm, $category];
                                $types = "ssss";
                            } elseif ($search) {
                                $query .= " AND (title LIKE ? OR author LIKE ? OR isbn LIKE ?)";
                                $searchTerm = "%$search%";
                                $params = [$searchTerm, $searchTerm, $searchTerm];
                                $types = "sss";
                            } elseif ($category) {
                                $query .= " AND category = ?";
                                $params = [$category];
                                $types = "s";
                            }
                            
                            $query .= " LIMIT 50";
                            
                            $stmt = $conn->prepare($query);
                            if ($params) {
                                $stmt->bind_param($types, ...$params);
                            }
                            $stmt->execute();
                            $books = $stmt->get_result();
                            
                            if ($books->num_rows > 0) {
                                while ($book = $books->fetch_assoc()) {
                                    $initials = strtoupper(substr($book['title'], 0, 1));
                                    $hasPdf = !empty($book['pdf_file']);
                                    echo "<div class='book-card'>
                                        <div class='book-cover'>
                                            $initials
                                            <div class='book-actions'>
                                                <form method='POST' style='display:inline;'>
                                                    <input type='hidden' name='book_id' value='{$book['book_id']}'>
                                                    <button type='submit' name='borrow' class='action-btn' title='Borrow'>📖</button>
                                                </form>";
                                    if ($hasPdf) {
                                        echo "<a href='read_book.php?book_id={$book['book_id']}' class='action-btn' title='Read Book' target='_blank'>👁️</a>";
                                    }
                                    echo "</div>
                                        </div>
                                        <div class='book-info'>
                                            <div class='book-title' title='{$book['title']}'>{$book['title']}</div>
                                            <div class='book-author'>{$book['author']}</div>
                                            <div class='book-meta'>
                                                <span class='badge badge-success'>{$book['copies_available']} available</span>";
                                    if ($hasPdf) {
                                        echo "<a href='read_book.php?book_id={$book['book_id']}' class='btn btn-primary btn-sm' target='_blank' style='text-decoration:none;'>Read</a>";
                                    }
                                    echo "</div>
                                        </div>
                                    </div>";
                                }
                            } else {
                                echo "<div class='empty-state' style='grid-column: 1/-1;'>
                                    <div class='empty-icon'>📚</div>
                                    <div>No books found matching your criteria</div>
                                </div>";
                            }
                            $stmt->close();
                            ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- My Books Tab -->
            <div id="borrowed" class="tab-content">
                <div class="content-card">
                    <div class="card-header">
                        <h2 class="card-title">Currently Borrowed Books</h2>
                    </div>
                    <div class="card-body">
                        <table>
                            <thead>
                                <tr>
                                    <th>Book</th>
                                    <th>Author</th>
                                    <th>Borrowed</th>
                                    <th>Due Date</th>
                                    <th>Status</th>
                                    <th>Fine</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $stmt = $conn->prepare("SELECT br.id, b.title, b.author, br.borrow_date, br.due_date, br.fine 
                                                        FROM borrow_return br
                                                        JOIN books b ON br.book_id = b.book_id
                                                        WHERE br.student_id = ? AND br.status = 'Borrowed'
                                                        ORDER BY br.due_date ASC");
                                $stmt->bind_param("i", $student_id);
                                $stmt->execute();
                                $borrowed = $stmt->get_result();
                                
                                if ($borrowed->num_rows > 0) {
                                    while ($row = $borrowed->fetch_assoc()) {
                                        $daysLeft = ceil((strtotime($row['due_date']) - strtotime($today)) / (60 * 60 * 24));
                                        $isOverdue = $daysLeft < 0;
                                        $fine = $isOverdue ? abs($daysLeft) * 1 : 0;
                                        
                                        if ($isOverdue && $fine != $row['fine']) {
                                            $stmt_update = $conn->prepare("UPDATE borrow_return SET fine = ? WHERE id = ?");
                                            $stmt_update->bind_param("di", $fine, $row['id']);
                                            $stmt_update->execute();
                                            $stmt_update->close();
                                        }
                                        
                                        $statusBadge = $isOverdue ? 'badge-danger' : 'badge-success';
                                        $statusText = $isOverdue ? abs($daysLeft) . " days overdue" : $daysLeft . " days left";
                                        
                                        echo "<tr>
                                            <td><strong>{$row['title']}</strong></td>
                                            <td>{$row['author']}</td>
                                            <td>{$row['borrow_date']}</td>
                                            <td>{$row['due_date']}</td>
                                            <td><span class='badge $statusBadge'>$statusText</span></td>
                                            <td>₵" . number_format($fine, 2) . "</td>
                                            <td>
                                                <form method='POST' onsubmit='return confirm(\"Confirm return?\");' style='display:inline;'>
                                                    <input type='hidden' name='borrow_id' value='{$row['id']}'>
                                                    <button type='submit' name='return' class='btn btn-primary btn-sm'>Return</button>
                                                </form>
                                            </td>
                                        </tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='7' class='empty-state'>
                                        <div class='empty-icon'>📖</div>
                                        <div>You have no borrowed books</div>
                                    </td></tr>";
                                }
                                $stmt->close();
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- History Tab -->
            <div id="history" class="tab-content">
                <div class="content-card">
                    <div class="card-header">
                        <h2 class="card-title">Borrowing History</h2>
                    </div>
                    <div class="card-body">
                        <table>
                            <thead>
                                <tr>
                                    <th>Book</th>
                                    <th>Borrowed</th>
                                    <th>Due Date</th>
                                    <th>Returned</th>
                                    <th>Status</th>
                                    <th>Fine</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $stmt = $conn->prepare("SELECT b.title, br.borrow_date, br.due_date, br.return_date, br.status, br.fine 
                                                        FROM borrow_return br
                                                        JOIN books b ON br.book_id = b.book_id
                                                        WHERE br.student_id = ?
                                                        ORDER BY br.borrow_date DESC
                                                        LIMIT 50");
                                $stmt->bind_param("i", $student_id);
                                $stmt->execute();
                                $history = $stmt->get_result();
                                
                                if ($history->num_rows > 0) {
                                    while ($row = $history->fetch_assoc()) {
                                        $statusBadge = $row['status'] == 'Borrowed' ? 'badge-warning' : 'badge-success';
                                        $returnDate = $row['return_date'] ?? '-';
                                        $fine = $row['fine'] ?? 0;
                                        
                                        echo "<tr>
                                            <td><strong>{$row['title']}</strong></td>
                                            <td>{$row['borrow_date']}</td>
                                            <td>{$row['due_date']}</td>
                                            <td>$returnDate</td>
                                            <td><span class='badge $statusBadge'>{$row['status']}</span></td>
                                            <td>₵" . number_format($fine, 2) . "</td>
                                        </tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='6' class='empty-state'>
                                        <div class='empty-icon'>📜</div>
                                        <div>No borrowing history</div>
                                    </td></tr>";
                                }
                                $stmt->close();
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Profile Tab -->
            <div id="profile" class="tab-content">
                <div class="content-card">
                    <div class="card-header">
                        <h2 class="card-title">My Profile</h2>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="form-group">
                                <label class="form-label">Full Name</label>
                                <input type="text" name="fullname" class="form-control" value="<?php echo htmlspecialchars($_SESSION['fullname']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($_SESSION['email']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Username</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($_SESSION['username']); ?>" disabled>
                            </div>
                            <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
                        </form>
                    </div>
                </div>

                <div class="content-card" style="margin-top: 24px;">
                    <div class="card-header">
                        <h2 class="card-title">Change Password</h2>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="form-group">
                                <label class="form-label">Current Password</label>
                                <input type="password" name="current_password" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">New Password</label>
                                <input type="password" name="new_password" class="form-control" minlength="6" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Confirm New Password</label>
                                <input type="password" name="confirm_password" class="form-control" minlength="6" required>
                            </div>
                            <button type="submit" name="change_password" class="btn btn-primary">Change Password</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function showTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Remove active from all nav items
            document.querySelectorAll('.nav-item').forEach(item => {
                item.classList.remove('active');
            });
            
            // Show selected tab
            document.getElementById(tabName).classList.add('active');
            
            // Add active to clicked nav item
            event.target.closest('.nav-item').classList.add('active');
            
            // Update page title
            const titles = {
                'books': 'Browse Books',
                'borrowed': 'My Books',
                'history': 'Borrowing History',
                'profile': 'My Profile'
            };
            document.getElementById('pageTitle').textContent = titles[tabName];
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>