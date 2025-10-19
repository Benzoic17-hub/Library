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
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f1f5f9;
        }
        
        .page-title {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .page-title .icon {
            font-size: 48px;
        }
        
        .page-title h2 {
            font-size: 36px;
            font-weight: 700;
            color: #1e293b;
        }
        
        .btn-add {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 16px 32px;
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
            color: white;
            border: none;
            border-radius: 50px;
            cursor: pointer;
            font-weight: 700;
            font-size: 16px;
            font-family: 'Poppins', sans-serif;
            text-decoration: none;
        }
        
        .btn-add:hover {
            background: linear-gradient(135deg, #16a34a 0%, #22c55e 100%);
        }
        
        /* Table Styles */
        .table-container {
            overflow-x: auto;
            border-radius: 20px;
            border: 2px solid #f1f5f9;
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
        
        /* Action Buttons */
        .action-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        
        .btn-action {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 13px;
            white-space: nowrap;
        }
        
        .btn-edit-name {
            background: #fef3c7;
            color: #92400e;
            border: 2px solid #fde68a;
        }
        
        .btn-edit-name:hover {
            background: #fde68a;
        }
        
        .btn-edit-email {
            background: #dbeafe;
            color: #1e40af;
            border: 2px solid #bfdbfe;
        }
        
        .btn-edit-email:hover {
            background: #bfdbfe;
        }
        
        .btn-edit-username {
            background: #e0e7ff;
            color: #3730a3;
            border: 2px solid #c7d2fe;
        }
        
        .btn-edit-username:hover {
            background: #c7d2fe;
        }
        
        .btn-delete {
            background: #fee2e2;
            color: #991b1b;
            border: 2px solid #fecaca;
        }
        
        .btn-delete:hover {
            background: #fecaca;
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
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 20px;
            }
            
            .action-buttons {
                flex-direction: column;
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
            <a href="manage_students.php" class="active">
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
            <div class="page-header">
                <div class="page-title">
                    <span class="icon">🎓</span>
                    <h2>Manage Students</h2>
                </div>
                <a class="btn-add" href="add_student.php">
                    <span>➕</span>
                    <span>Add Student</span>
                </a>
            </div>

            <div class="table-container">
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
                                <div class="action-buttons">
                                    <a class="btn-action btn-edit-name" href="edit_name.php?id=<?= $row['student_id'] ?>">
                                        <span>✏️</span>
                                        <span>Edit Name</span>
                                    </a>
                                    <a class="btn-action btn-edit-email" href="edit_email.php?id=<?= $row['student_id'] ?>">
                                        <span>✉️</span>
                                        <span>Edit Email</span>
                                    </a>
                                    <a class="btn-action btn-edit-username" href="edit_username.php?id=<?= $row['student_id'] ?>">
                                        <span>👤</span>
                                        <span>Edit Username</span>
                                    </a>
                                    <a class="btn-action btn-delete" href="manage_students.php?delete=<?= $row['student_id'] ?>" onclick="return confirm('Are you sure you want to delete this student?');">
                                        <span>🗑️</span>
                                        <span>Delete</span>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>