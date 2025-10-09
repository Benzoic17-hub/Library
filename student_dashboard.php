<?php
session_start();
include 'db_connection.php';

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
        $message = "<div class='success'>✅ Profile updated successfully!</div>";
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
            $message = "<div class='success'>✅ Password changed successfully!</div>";
        } else {
            $message = "<div class='error'>❌ Passwords don't match or too short (min 6 characters).</div>";
        }
    } else {
        $message = "<div class='error'>❌ Current password is incorrect.</div>";
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
        $message = "<div class='error'>❌ You already borrowed this book.</div>";
    } elseif ($activeCount >= 5) {
        $message = "<div class='error'>❌ Borrow limit reached (5 books max). Return a book first.</div>";
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
            
            $message = "<div class='success'>✅ Book borrowed successfully! Due in 14 days.</div>";
            $activeCount++;
        } else {
            $message = "<div class='error'>❌ Sorry, this book is not available.</div>";
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
    
    $message = "<div class='success'>✅ Book returned successfully!" . ($fine > 0 ? " Fine: GH₵$fine" : "") . "</div>";
    $stmt->close();
}

// Search books
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
?>

<!DOCTYPE html>
<html>
<head>
    <title>Student Dashboard</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap');
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            min-height: 100vh;
            overflow-x: hidden;
            position: relative;
            background: url('images/library_bg2.jpg') no-repeat center center fixed;
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
        
        .header {
            position: relative;
            z-index: 100;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            padding: 20px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            animation: slideDown 0.8s ease;
        }
        
        @keyframes slideDown {
            from {
                transform: translateY(-100px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        .header h1 {
            font-size: 28px;
            font-weight: 700;
            color: white;
            text-shadow: 0 0 20px rgba(255, 255, 255, 0.5);
            letter-spacing: 1px;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 20px;
            color: white;
            font-weight: 500;
        }
        
        .logout-btn {
            background: linear-gradient(135deg, #ff6b6b, #ee5a6f);
            color: white;
            padding: 12px 28px;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(238, 90, 111, 0.4);
            border: 2px solid rgba(255, 255, 255, 0.2);
        }
        
        .logout-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(238, 90, 111, 0.6);
            background: linear-gradient(135deg, #ee5a6f, #ff6b6b);
        }
        
        .container {
            position: relative;
            z-index: 100;
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
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
        
        .stat-card {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            padding: 30px;
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            text-align: center;
            transition: all 0.4s;
            position: relative;
            overflow: hidden;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }
        
        .stat-card::before {
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
        
        .stat-card:hover::before {
            left: 100%;
        }
        
        .stat-card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.2);
            border-color: rgba(255, 255, 255, 0.5);
        }
        
        .stat-card h3 {
            color: white;
            font-size: 48px;
            font-weight: 800;
            margin-bottom: 10px;
            text-shadow: 0 0 30px rgba(255, 255, 255, 0.5);
            animation: pulse 2s ease infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        .stat-card p {
            color: rgba(255, 255, 255, 0.9);
            font-size: 16px;
            font-weight: 500;
            letter-spacing: 1px;
        }
        
        .tabs {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
            flex-wrap: wrap;
            animation: fadeIn 1.2s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .tab-btn {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border: 2px solid rgba(255, 255, 255, 0.2);
            padding: 14px 28px;
            border-radius: 50px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            color: white;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .tab-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
            background: rgba(255, 255, 255, 0.25);
            border-color: rgba(255, 255, 255, 0.4);
        }
        
        .tab-btn.active {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.3), rgba(255, 255, 255, 0.2));
            border: 2px solid rgba(255, 255, 255, 0.5);
            box-shadow: 0 0 30px rgba(255, 255, 255, 0.3);
        }
        
        .tab-content {
            display: none;
            animation: fadeIn 0.5s ease;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .card {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            padding: 35px;
            border-radius: 25px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            margin-bottom: 25px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            transition: all 0.3s;
        }
        
        .card:hover {
            box-shadow: 0 12px 48px rgba(0, 0, 0, 0.15);
            transform: translateY(-5px);
        }
        
        .card h2 {
            color: white;
            margin-bottom: 25px;
            font-size: 28px;
            font-weight: 700;
            text-shadow: 0 0 20px rgba(255, 255, 255, 0.3);
        }
        
        .search-box {
            display: flex;
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .search-box input {
            flex: 1;
            padding: 15px 20px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50px;
            font-size: 16px;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            color: white;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .search-box input::placeholder {
            color: rgba(255, 255, 255, 0.7);
        }
        
        .search-box input:focus {
            outline: none;
            border-color: rgba(255, 255, 255, 0.6);
            background: rgba(255, 255, 255, 0.25);
            box-shadow: 0 0 20px rgba(255, 255, 255, 0.3);
        }
        
        .search-box button {
            padding: 15px 35px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 50px;
            cursor: pointer;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }
        
        .search-box button:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.6);
        }
        
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 10px;
            margin-top: 20px;
        }
        
        th, td {
            padding: 18px;
            text-align: left;
            color: white;
            font-weight: 500;
        }
        
        th {
            background: rgba(255, 255, 255, 0.2);
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-size: 14px;
            border-radius: 10px;
        }
        
        tr {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            transition: all 0.3s;
        }
        
        tbody tr {
            border-radius: 10px;
        }
        
        tbody tr:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: scale(1.02);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }
        
        .btn {
            padding: 10px 22px;
            border: none;
            border-radius: 50px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #11998e, #38ef7d);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(56, 239, 125, 0.4);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #ff6b6b, #ee5a6f);
            color: white;
        }
        
        .btn-danger:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(238, 90, 111, 0.4);
        }
        
        .btn-info {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }
        
        .btn-info:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }
        
        .overdue {
            color: #ff6b6b;
            font-weight: 700;
            text-shadow: 0 0 10px rgba(255, 107, 107, 0.5);
        }
        
        .ontime {
            color: #38ef7d;
            font-weight: 700;
            text-shadow: 0 0 10px rgba(56, 239, 125, 0.5);
        }
        
        .success {
            background: rgba(56, 239, 125, 0.2);
            backdrop-filter: blur(10px);
            color: white;
            padding: 18px;
            margin-bottom: 25px;
            border-left: 5px solid #38ef7d;
            border-radius: 10px;
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(56, 239, 125, 0.2);
            animation: slideInRight 0.5s ease;
        }
        
        @keyframes slideInRight {
            from {
                transform: translateX(50px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        .error {
            background: rgba(255, 107, 107, 0.2);
            backdrop-filter: blur(10px);
            color: white;
            padding: 18px;
            margin-bottom: 25px;
            border-left: 5px solid #ff6b6b;
            border-radius: 10px;
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(255, 107, 107, 0.2);
            animation: shake 0.5s ease;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            color: white;
            font-size: 15px;
            text-shadow: 0 0 10px rgba(255, 255, 255, 0.3);
        }
        
        .form-group input, .form-group select {
            width: 100%;
            padding: 14px 20px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 12px;
            font-size: 15px;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            color: white;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: rgba(255, 255, 255, 0.6);
            background: rgba(255, 255, 255, 0.25);
            box-shadow: 0 0 20px rgba(255, 255, 255, 0.3);
        }
        
        .form-group input::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }
        
        .badge {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 50px;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 0.5px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }
        
        .badge-success {
            background: linear-gradient(135deg, #11998e, #38ef7d);
            color: white;
        }
        
        .badge-warning {
            background: linear-gradient(135deg, #f093fb, #f5576c);
            color: white;
        }
        
        .badge-danger {
            background: linear-gradient(135deg, #ff6b6b, #ee5a6f);
            color: white;
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
    </style>
</head>
<body>
    <!-- Animated Particles Background -->
    <div class="particles" id="particles"></div>

    <div class="header">
        <h1>📚 Library Management System</h1>
        <div class="user-info">
            <span>Welcome, <strong><?php echo htmlspecialchars($_SESSION['fullname']); ?></strong></span>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </div>

    <div class="container">
        <?php if ($message) echo $message; ?>
        
        <div class="stats">
            <div class="stat-card">
                <h3><?php echo $activeCount; ?>/5</h3>
                <p>Books Borrowed</p>
            </div>
            <div class="stat-card">
                <h3>
                    <?php
                    $stmt = $conn->prepare("SELECT SUM(fine) AS total FROM borrow_return WHERE student_id = ? AND status = 'Borrowed'");
                    $stmt->bind_param("i", $student_id);
                    $stmt->execute();
                    $totalFine = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
                    echo "GH₵" . number_format($totalFine, 2);
                    $stmt->close();
                    ?>
                </h3>
                <p>Outstanding Fines</p>
            </div>
            <div class="stat-card">
                <h3>
                    <?php
                    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM borrow_return WHERE student_id = ?");
                    $stmt->bind_param("i", $student_id);
                    $stmt->execute();
                    echo $stmt->get_result()->fetch_assoc()['total'];
                    $stmt->close();
                    ?>
                </h3>
                <p>Total Borrowed</p>
            </div>
        </div>

        <div class="tabs">
            <button class="tab-btn active" onclick="showTab('search')">🔍 Search Books</button>
            <button class="tab-btn" onclick="showTab('borrowed')">📖 Borrowed Books</button>
            <button class="tab-btn" onclick="showTab('history')">📜 Borrowing History</button>
            <button class="tab-btn" onclick="showTab('profile')">👤 Profile</button>
            <button onclick="window.location.href='index.php'" 
          style="background:#007bff;
                 color:white;
                 border:none;
                 padding:10px 18px;
                 border-radius:8px;
                 cursor:pointer;
                 display:flex;
                 align-items:center;
                 gap:6px;
                 font-size:15px;
                 box-shadow:0 2px 5px rgba(0,0,0,0.2);">
    <!-- Back Icon -->
    <span style="font-size:18px;">🔙</span>
    <span>Back</span>
  </button>
        </div>

        <!-- Search Books Tab -->
        <div id="search" class="tab-content active">
            <div class="card">
                <h2>Search & Borrow Books</h2>
                <form method="GET" class="search-box">
                    <input type="text" name="search" placeholder="Search by title, author, or ISBN..." value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit">Search</button>
                    <?php if ($search): ?>
                        <a href="student_dashboard.php" class="btn btn-info">Clear</a>
                    <?php endif; ?>
                </form>
                
                <table>
                    <tr>
                        <th>Title</th>
                        <th>Author</th>
                        <th>ISBN</th>
                        <th>Category</th>
                        <th>Available</th>
                        <th>Action</th>
                    </tr>
                    <?php
                    if ($search) {
                        $searchTerm = "%$search%";
                        $stmt = $conn->prepare("SELECT * FROM books WHERE (title LIKE ? OR author LIKE ? OR isbn LIKE ?) AND copies_available > 0");
                        $stmt->bind_param("sss", $searchTerm, $searchTerm, $searchTerm);
                    } else {
                        $stmt = $conn->prepare("SELECT * FROM books WHERE copies_available > 0 LIMIT 20");
                    }
                    $stmt->execute();
                    $books = $stmt->get_result();
                    
                    if ($books->num_rows > 0) {
                        while ($book = $books->fetch_assoc()) {
                            echo "<tr>
                                <td>{$book['title']}</td>
                                <td>{$book['author']}</td>
                                <td>{$book['isbn']}</td>
                                <td>{$book['category']}</td>
                                <td><span class='badge badge-success'>{$book['copies_available']} available</span></td>
                                <td>
                                    <form method='POST' style='display:inline;'>
                                        <input type='hidden' name='book_id' value='{$book['book_id']}'>
                                        <button type='submit' name='borrow' class='btn btn-primary'>Borrow</button>
                                    </form>
                                    " . ($book['pdf_file'] ? "<a href='read_book.php?book_id={$book['book_id']}' class='btn btn-info' style='margin-left:5px;' target='_blank'>Read</a>" : "") . "
                                </td>
                            </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='6' style='text-align:center;'>No books found</td></tr>";
                    }
                    $stmt->close();
                    ?>
                </table>
            </div>
        </div>

        <!-- Borrowed Books Tab -->
        <div id="borrowed" class="tab-content">
            <div class="card">
                <h2>Currently Borrowed Books</h2>
                <table>
                    <tr>
                        <th>Book Title</th>
                        <th>Borrow Date</th>
                        <th>Due Date</th>
                        <th>Days Left</th>
                        <th>Fine</th>
                        <th>Action</th>
                    </tr>
                    <?php
                    $stmt = $conn->prepare("SELECT br.id, br.book_id, b.title, br.borrow_date, br.due_date, br.fine 
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
                            
                            if ($isOverdue) {
                                $stmt_update = $conn->prepare("UPDATE borrow_return SET fine = ? WHERE id = ?");
                                $stmt_update->bind_param("di", $fine, $row['id']);
                                $stmt_update->execute();
                                $stmt_update->close();
                            }
                            
                            $statusClass = $isOverdue ? 'overdue' : 'ontime';
                            $daysText = $isOverdue ? abs($daysLeft) . " days overdue" : $daysLeft . " days left";
                            
                            echo "<tr>
                                <td>{$row['title']}</td>
                                <td>{$row['borrow_date']}</td>
                                <td>{$row['due_date']}</td>
                                <td><span class='$statusClass'>$daysText</span></td>
                                <td><span class='$statusClass'>GH₵" . number_format($fine, 2) . "</span></td>
                                <td>
                                    <form method='POST' onsubmit='return confirm(\"Confirm return?\");'>
                                        <input type='hidden' name='borrow_id' value='{$row['id']}'>
                                        <button type='submit' name='return' class='btn btn-danger'>Return</button>
                                    </form>
                                </td>
                            </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='6' style='text-align:center;'>You have no borrowed books</td></tr>";
                    }
                    $stmt->close();
                    ?>
                </table>
            </div>
        </div>

        <!-- Borrowing History Tab -->
        <div id="history" class="tab-content">
            <div class="card">
                <h2>Complete Borrowing History</h2>
                <table>
                    <tr>
                        <th>Book Title</th>
                        <th>Borrow Date</th>
                        <th>Due Date</th>
                        <th>Return Date</th>
                        <th>Status</th>
                        <th>Fine</th>
                    </tr>
                    <?php
                    $stmt = $conn->prepare("SELECT b.title, br.borrow_date, br.due_date, br.return_date, br.status, br.fine 
                                            FROM borrow_return br
                                            JOIN books b ON br.book_id = b.book_id
                                            WHERE br.student_id = ?
                                            ORDER BY br.borrow_date DESC");
                    $stmt->bind_param("i", $student_id);
                    $stmt->execute();
                    $history = $stmt->get_result();
                    
                    if ($history->num_rows > 0) {
                        while ($row = $history->fetch_assoc()) {
                            $statusBadge = $row['status'] == 'Borrowed' ? 'badge-warning' : 'badge-success';
                            $returnDate = $row['return_date'] ?? '-';
                            $fine = $row['fine'] ?? 0;
                            
                            echo "<tr>
                                <td>{$row['title']}</td>
                                <td>{$row['borrow_date']}</td>
                                <td>{$row['due_date']}</td>
                                <td>$returnDate</td>
                                <td><span class='badge $statusBadge'>{$row['status']}</span></td>
                                <td>GH₵" . number_format($fine, 2) . "</td>
                            </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='6' style='text-align:center;'>No borrowing history</td></tr>";
                    }
                    $stmt->close();
                    ?>
                </table>
            </div>
        </div>

        <!-- Profile Tab -->
        <div id="profile" class="tab-content">
            <div class="card">
                <h2>My Profile</h2>
                <form method="POST">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="fullname" value="<?php echo htmlspecialchars($_SESSION['fullname']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($_SESSION['email']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" value="<?php echo htmlspecialchars($_SESSION['username']); ?>" disabled>
                    </div>
                    <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
                </form>
            </div>

            <div class="card">
                <h2>Change Password</h2>
                <form method="POST">
                    <div class="form-group">
                        <label>Current Password</label>
                        <input type="password" name="current_password" required>
                    </div>
                    <div class="form-group">
                        <label>New Password</label>
                        <input type="password" name="new_password" minlength="6" required>
                    </div>
                    <div class="form-group">
                        <label>Confirm New Password</label>
                        <input type="password" name="confirm_password" minlength="6" required>
                    </div>
                    <button type="submit" name="change_password" class="btn btn-primary">Change Password</button>
                </form>
            </div>
        </div>
    </div>
    <!-- Stylish Back Button -->
<div style="text-align:left; padding:15px;">
  
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
        
        function showTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Remove active class from all buttons
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Show selected tab
            document.getElementById(tabName).classList.add('active');
            
            // Add active class to clicked button
            event.target.classList.add('active');
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>