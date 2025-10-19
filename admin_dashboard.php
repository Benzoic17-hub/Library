<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin_login.php");
    exit();
}

include 'db_connection.php';

// Fetch stats
$totalBooks = $conn->query("SELECT COUNT(*) AS count FROM books")->fetch_assoc()['count'];
$borrowedBooks = $conn->query("SELECT COUNT(*) AS count FROM borrow_return WHERE status = 'Borrowed'")->fetch_assoc()['count'];
$overdueBooks = $conn->query("SELECT COUNT(*) AS count FROM borrow_return WHERE status = 'Borrowed' AND due_date < CURDATE()")->fetch_assoc()['count'];

// Fetch today's borrows for notification
$todayBorrows = $conn->query("SELECT COUNT(*) AS count FROM borrow_return WHERE DATE(borrow_date) = CURDATE()")->fetch_assoc()['count'];

// Fetch recent activity (last 10 borrows)
$recentActivity = $conn->query("
    SELECT br.id, br.borrow_date, br.due_date, br.status,
           bk.title, s.fullname as student_name 
    FROM borrow_return br
    JOIN books bk ON br.book_id = bk.book_id
    JOIN students s ON br.student_id = s.student_id
    ORDER BY br.borrow_date DESC
    LIMIT 10
");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
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
            margin-bottom: 40px;
        }
        
        .page-header h2 {
            font-size: 36px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 10px;
        }
        
        .page-header p {
            color: #64748b;
            font-size: 15px;
        }
        
        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #f8f9ff 0%, #f1f5ff 100%);
            padding: 30px;
            border-radius: 20px;
            border: 2px solid #e8ecff;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200px;
            height: 200px;
            background: radial-gradient(circle, rgba(102, 126, 234, 0.1) 0%, transparent 70%);
            border-radius: 50%;
        }
        
        .stat-card:hover {
            border-color: #667eea;
        }
        
        .stat-card.danger {
            background: linear-gradient(135deg, #fff5f5 0%, #ffe8e8 100%);
            border-color: #fecaca;
        }
        
        .stat-card.danger::before {
            background: radial-gradient(circle, rgba(239, 68, 68, 0.1) 0%, transparent 70%);
        }
        
        .stat-card.success {
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
            border-color: #bbf7d0;
        }
        
        .stat-card.success::before {
            background: radial-gradient(circle, rgba(34, 197, 94, 0.1) 0%, transparent 70%);
        }
        
        .stat-label {
            font-size: 14px;
            color: #64748b;
            font-weight: 500;
            margin-bottom: 10px;
        }
        
        .stat-value {
            font-size: 42px;
            font-weight: 800;
            color: #667eea;
            position: relative;
        }
        
        .stat-card.danger .stat-value {
            color: #ef4444;
        }
        
        .stat-card.success .stat-value {
            color: #22c55e;
        }
        
        /* Search Section */
        .search-section {
            background: #f8f9ff;
            padding: 35px;
            border-radius: 20px;
            margin-bottom: 40px;
        }
        
        .search-form {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
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
        }
        
        .search-input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
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
        
        /* Activity Section */
        .activity-section {
            background: white;
            border-radius: 20px;
            padding: 35px;
            border: 2px solid #f1f5f9;
        }
        
        .activity-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f1f5f9;
        }
        
        .activity-header h3 {
            font-size: 24px;
            font-weight: 700;
            color: #1e293b;
        }
        
        .activity-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .activity-item {
            display: flex;
            align-items: center;
            gap: 20px;
            padding: 20px;
            background: #f8f9ff;
            border-radius: 15px;
            border: 2px solid transparent;
        }
        
        .activity-item:hover {
            border-color: #667eea;
        }
        
        .activity-item.new {
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
            border-color: #22c55e;
            position: relative;
        }
        
        .activity-icon {
            width: 60px;
            height: 60px;
            background: white;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
        }
        
        .activity-details {
            flex: 1;
        }
        
        .activity-student {
            font-weight: 700;
            color: #1e293b;
            font-size: 16px;
            margin-bottom: 4px;
        }
        
        .activity-book {
            color: #64748b;
            font-size: 14px;
            margin-bottom: 4px;
        }
        
        .activity-time {
            color: #94a3b8;
            font-size: 12px;
            font-weight: 500;
        }
        
        .new-badge {
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
            color: white;
            padding: 6px 16px;
            border-radius: 50px;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 1px;
        }
        
        .no-activity {
            text-align: center;
            padding: 60px 20px;
            color: #94a3b8;
        }
        
        .no-activity-icon {
            font-size: 64px;
            margin-bottom: 16px;
            opacity: 0.5;
        }
        
        /* Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
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
        
        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .content {
                margin-left: 0;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
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
            <a href="admin_dashboard.php" class="active">
                <span class="icon">🏠</span>
                <span>Dashboard</span>
            </a>
            <a href="books.php">
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
            <!-- Page Header -->
            <div class="page-header">
                <h2>Welcome Back, Admin</h2>
                <p>Here's what's happening with your library today</p>
            </div>

            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-label">Total Books</div>
                    <div class="stat-value"><?= $totalBooks ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Borrowed Books</div>
                    <div class="stat-value"><?= $borrowedBooks ?></div>
                </div>
                <div class="stat-card danger">
                    <div class="stat-label">Overdue Books</div>
                    <div class="stat-value"><?= $overdueBooks ?></div>
                </div>
                <div class="stat-card success">
                    <div class="stat-label">Today's Borrows</div>
                    <div class="stat-value"><?= $todayBorrows ?></div>
                </div>
            </div>

            <!-- Search Section -->
            <div class="search-section" id="search">
                <form method="post" action="books.php" class="search-form">
                    <input 
                        type="text" 
                        name="keyword" 
                        class="search-input" 
                        placeholder="🔍 Search for books by title, author, or category..." 
                        required
                    >
                    <button type="submit" name="search" class="btn btn-primary">
                        Search Books
                    </button>
                </form>
                
                <form method="post" action="export_books.php" style="margin-top: 15px;">
                    <button type="submit" class="btn btn-success">
                        📥 Export Books CSV
                    </button>
                </form>
            </div>

            <!-- Recent Activity -->
            <div class="activity-section">
                <div class="activity-header">
                    <span style="font-size: 28px;">📋</span>
                    <h3>Recent Borrowing Activity</h3>
                </div>
                
                <div class="activity-list">
                    <?php if ($recentActivity->num_rows > 0): ?>
                        <?php while($activity = $recentActivity->fetch_assoc()): 
                            $isToday = date('Y-m-d', strtotime($activity['borrow_date'])) == date('Y-m-d');
                        ?>
                            <div class="activity-item <?= $isToday ? 'new' : '' ?>">
                                <div class="activity-icon">📚</div>
                                <div class="activity-details">
                                    <div class="activity-student"><?= htmlspecialchars($activity['student_name']) ?></div>
                                    <div class="activity-book">borrowed "<?= htmlspecialchars($activity['title']) ?>"</div>
                                    <div class="activity-time">
                                        <?php
                                        $borrowDate = strtotime($activity['borrow_date']);
                                        $now = time();
                                        $diff = $now - $borrowDate;
                                        
                                        if ($diff < 3600) {
                                            echo floor($diff / 60) . " minutes ago";
                                        } elseif ($diff < 86400) {
                                            echo floor($diff / 3600) . " hours ago";
                                        } else {
                                            echo date('M d, Y', $borrowDate);
                                        }
                                        ?>
                                    </div>
                                </div>
                                <?php if ($isToday): ?>
                                    <div class="new-badge">NEW</div>
                                <?php endif; ?>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="no-activity">
                            <div class="no-activity-icon">📭</div>
                            <p>No recent activity to display</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>