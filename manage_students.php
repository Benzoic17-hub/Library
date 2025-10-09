<?php
// connect to database
$host = "localhost";
$user = "root";
$pass = "";
$db   = "library";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// delete student
if (isset($_GET['delete'])) {
    $student_id = intval($_GET['delete']);
    $conn->query("DELETE FROM students WHERE student_id=$student_id");
    header("Location: manage_students.php");
    exit;
}

// fetch students
$result = $conn->query("SELECT * FROM students ORDER BY student_id DESC");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Students</title>
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
        
        h2 {
            color: white;
            font-size: 36px;
            font-weight: 800;
            margin-bottom: 30px;
            text-shadow: 0 0 20px rgba(255, 255, 255, 0.3);
            letter-spacing: 1px;
            text-align: center;
        }
        
        .btn-add {
            display: inline-block;
            padding: 16px 40px;
            background: linear-gradient(135deg, #11998e, #38ef7d);
            color: white;
            border: none;
            border-radius: 50px;
            cursor: pointer;
            font-weight: 700;
            font-size: 16px;
            transition: all 0.3s;
            box-shadow: 0 8px 25px rgba(56, 239, 125, 0.4);
            font-family: 'Poppins', sans-serif;
            letter-spacing: 0.5px;
            text-decoration: none;
            margin-bottom: 30px;
        }
        
        .btn-add:hover {
            background: linear-gradient(135deg, #38ef7d, #11998e);
            transform: translateY(-3px);
            box-shadow: 0 12px 35px rgba(56, 239, 125, 0.6);
        }
        
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 12px;
            margin-top: 20px;
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
            transform: scale(1.01);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
        }
        
        tbody tr td:first-child {
            border-radius: 12px 0 0 12px;
        }
        
        tbody tr td:last-child {
            border-radius: 0 12px 12px 0;
        }
        
        a.btn-edit {
            display: inline-block;
            padding: 8px 16px;
            background: linear-gradient(135deg, #ffc107, #ff9800);
            color: black;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            font-size: 13px;
            transition: all 0.3s;
            margin: 2px;
            box-shadow: 0 4px 15px rgba(255, 193, 7, 0.4);
        }
        
        a.btn-edit:hover {
            background: linear-gradient(135deg, #ff9800, #ffc107);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 193, 7, 0.6);
        }
        
        a.btn-delete {
            display: inline-block;
            padding: 8px 16px;
            background: linear-gradient(135deg, #ff6b6b, #ee5a6f);
            color: white;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            font-size: 13px;
            transition: all 0.3s;
            margin: 2px;
            box-shadow: 0 4px 15px rgba(238, 90, 111, 0.4);
        }
        
        a.btn-delete:hover {
            background: linear-gradient(135deg, #ee5a6f, #ff6b6b);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(238, 90, 111, 0.6);
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
        <a href="logout.php">🚪 Logout</a>
    </div>

    <div class="content">
        <div class="box">
            <h2>🎓 Manage Students</h2>

            <a class="btn-add" href="add_student.php">➕ Add Student</a>

            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Username</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $result->fetch_assoc()) { ?>
                    <tr>
                        <td><?= $row['student_id'] ?></td>
                        <td><?= htmlspecialchars($row['fullname']) ?></td>
                        <td><?= htmlspecialchars($row['email']) ?></td>
                        <td><?= htmlspecialchars($row['username']) ?></td>
                        <td>
                            <a class="btn-edit" href="edit_name.php?id=<?= $row['student_id'] ?>">✏️ Edit Name</a>
                            <a class="btn-edit" href="edit_email.php?id=<?= $row['student_id'] ?>">✉️ Edit Email</a>
                            <a class="btn-edit" href="edit_username.php?id=<?= $row['student_id'] ?>">👤 Edit Username</a>
                            <a class="btn-delete" href="manage_students.php?delete=<?= $row['student_id'] ?>" onclick="return confirm('Are you sure you want to delete this student?');">🗑️ Delete</a>
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
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