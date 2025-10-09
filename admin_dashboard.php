<?php
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
        
        /* Animated Background Particles */
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
            width: 100%;
            transition: all 0.3s;
        }
        
        .box:hover {
            box-shadow: 0 12px 48px rgba(0, 0, 0, 0.2);
            transform: translateY(-5px);
        }
        
        .box h2 {
            color: white;
            font-size: 36px;
            font-weight: 800;
            margin-bottom: 30px;
            text-shadow: 0 0 20px rgba(255, 255, 255, 0.3);
            letter-spacing: 1px;
        }
        
        .stats-bar {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }
        
        .stats-bar div {
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            padding: 25px;
            border-radius: 18px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            transition: all 0.4s;
            position: relative;
            overflow: hidden;
            color: white;
            font-weight: 500;
            font-size: 16px;
        }
        
        .stats-bar div::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            transform: rotate(45deg);
            transition: all 0.6s;
        }
        
        .stats-bar div:hover::before {
            left: 100%;
        }
        
        .stats-bar div:hover {
            transform: translateY(-8px) scale(1.05);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.25);
            border-color: rgba(255, 255, 255, 0.5);
        }
        
        .stats-bar div strong {
            display: block;
            font-size: 36px;
            font-weight: 800;
            margin-top: 10px;
            text-shadow: 0 0 20px rgba(255, 255, 255, 0.4);
            animation: pulse 2s ease infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        .stats-bar div:nth-child(3) {
            background: rgba(255, 107, 107, 0.25);
            border: 1px solid rgba(255, 107, 107, 0.4);
        }
        
        .stats-bar div:nth-child(3) strong {
            color: #ff6b6b;
            text-shadow: 0 0 20px rgba(255, 107, 107, 0.6);
        }
        
        form {
            margin-top: 30px;
        }
        
        input[type="text"] {
            padding: 18px 25px;
            width: 100%;
            max-width: 600px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50px;
            font-size: 16px;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            color: white;
            font-weight: 500;
            transition: all 0.3s;
            font-family: 'Poppins', sans-serif;
            margin-bottom: 15px;
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
        }
        
        input[type="submit"]:hover, .export-btn:hover {
            background: linear-gradient(135deg, #764ba2, #667eea);
            transform: translateY(-3px);
            box-shadow: 0 12px 35px rgba(102, 126, 234, 0.6);
        }
        
        .export-btn {
            background: linear-gradient(135deg, #11998e, #38ef7d);
            box-shadow: 0 8px 25px rgba(56, 239, 125, 0.4);
            margin-left: 15px;
        }
        
        .export-btn:hover {
            background: linear-gradient(135deg, #38ef7d, #11998e);
            box-shadow: 0 12px 35px rgba(56, 239, 125, 0.6);
        }
        
        /* Scrollbar Styling */
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
        
        /* Activity Section Styles */
        .activity-container {
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin-top: 20px;
        }
        
        .activity-item {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            padding: 20px;
            border-radius: 15px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            gap: 20px;
            transition: all 0.3s;
            position: relative;
        }
        
        .activity-item:hover {
            background: rgba(255, 255, 255, 0.15);
            transform: translateX(5px);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
        }
        
        .activity-item.new-activity {
            background: rgba(56, 239, 125, 0.15);
            border-color: rgba(56, 239, 125, 0.4);
            animation: glowPulse 2s ease infinite;
        }
        
        @keyframes glowPulse {
            0%, 100% {
                box-shadow: 0 0 10px rgba(56, 239, 125, 0.3);
            }
            50% {
                box-shadow: 0 0 25px rgba(56, 239, 125, 0.6);
            }
        }
        
        .activity-icon {
            font-size: 32px;
            background: rgba(255, 255, 255, 0.15);
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
        }
        
        .activity-details {
            flex: 1;
        }
        
        .activity-student {
            color: white;
            font-weight: 700;
            font-size: 18px;
            margin-bottom: 5px;
        }
        
        .activity-book {
            color: rgba(255, 255, 255, 0.9);
            font-size: 15px;
            margin-bottom: 5px;
        }
        
        .activity-time {
            color: rgba(255, 255, 255, 0.6);
            font-size: 13px;
            font-weight: 500;
        }
        
        .new-badge {
            background: linear-gradient(135deg, #38ef7d, #11998e);
            color: white;
            padding: 6px 16px;
            border-radius: 50px;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 1px;
            animation: bounce 1s ease infinite;
        }
        
        @keyframes bounce {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-5px);
            }
        }
    </style>
</head>
<body>
    <!-- Animated Particles Background -->
    <div class="particles" id="particles"></div>

    <div class="sidebar">
    <h2>📚 Admin</h2>
    <a href="admin_dashboard.php">🏠 Dashboard</a>
    <a href="books.php">📚 All Books</a>
    <a href="add_book.php">➕ Add Book</a>
    <a href="#search">🔍 Search Books</a>
    <a href="manage_students.php">🎓 Manage Students</a>

    <a href="admin_logout.php">🚪 Logout</a>
</div>

    <div class="content">
        <div class="box">
            <h2>Welcome Admin</h2>

            <div class="stats-bar">
                <div>
                    Total Books
                    <strong><?= $totalBooks ?></strong>
                </div>
                <div>
                    Borrowed
                    <strong><?= $borrowedBooks ?></strong>
                </div>
                <div>
                    Overdue
                    <strong><?= $overdueBooks ?></strong>
                </div>
                <div style="background: rgba(56, 239, 125, 0.25); border: 1px solid rgba(56, 239, 125, 0.4);">
                    Today's Borrows
                    <strong style="color: #38ef7d; text-shadow: 0 0 20px rgba(56, 239, 125, 0.6);"><?= $todayBorrows ?></strong>
                </div>
            </div>

            <form method="post" action="books.php" id="search">
                <input type="text" name="keyword" placeholder="🔍 Enter book title, author, or category..." required>
                <input type="submit" name="search" value="Search">
            </form>

            <form method="post" action="export_books.php" style="margin-top: 20px;">
                <button type="submit" class="export-btn">📥 Export Books CSV</button>
            </form>
        </div>

        <!-- Recent Activity Section -->
        <div class="box" style="margin-top: 30px;">
            <h2>📋 Recent Borrowing Activity</h2>
            <div class="activity-container">
                <?php if ($recentActivity->num_rows > 0): ?>
                    <?php while($activity = $recentActivity->fetch_assoc()): 
                        $isToday = date('Y-m-d', strtotime($activity['borrow_date'])) == date('Y-m-d');
                    ?>
                        <div class="activity-item <?= $isToday ? 'new-activity' : '' ?>">
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
                    <div style="text-align: center; color: rgba(255,255,255,0.7); padding: 30px;">
                        No recent activity
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Create animated particles
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