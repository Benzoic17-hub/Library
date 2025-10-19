<!DOCTYPE html>
<html>
<head>
    <title>Add Book</title>
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
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .form-container {
            background: white;
            border-radius: 30px;
            padding: 50px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 700px;
        }
        
        .page-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f1f5f9;
        }
        
        .page-header .icon {
            font-size: 48px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .page-header h2 {
            font-size: 36px;
            font-weight: 700;
            color: #1e293b;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: #475569;
            margin-bottom: 8px;
        }
        
        .form-group input {
            width: 100%;
            padding: 16px 20px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 15px;
            font-family: 'Poppins', sans-serif;
            background: #f8fafc;
            color: #1e293b;
            font-weight: 500;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        }
        
        .form-group input::placeholder {
            color: #94a3b8;
        }
        
        .btn-submit {
            width: 100%;
            padding: 18px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 700;
            font-size: 16px;
            font-family: 'Poppins', sans-serif;
            margin-top: 10px;
        }
        
        .btn-submit:hover {
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
        }
        
        .btn-submit:active {
            transform: scale(0.98);
        }
        
        .message {
            margin-top: 25px;
            padding: 16px 20px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .message.success {
            background: #f0fdf4;
            border: 2px solid #22c55e;
            color: #166534;
        }
        
        .message.error {
            background: #fef2f2;
            border: 2px solid #ef4444;
            color: #991b1b;
        }
        
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 25px;
            color: #64748b;
            text-decoration: none;
            font-weight: 600;
            font-size: 15px;
            padding: 12px 20px;
            background: #f8f9ff;
            border-radius: 10px;
            border: 2px solid #e8ecff;
        }
        
        .back-link:hover {
            background: #f1f5ff;
            color: #667eea;
            border-color: #667eea;
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
            
            .form-container {
                padding: 30px;
            }
        }
        
        /* Chrome autofill styling */
        input:-webkit-autofill,
        input:-webkit-autofill:hover,
        input:-webkit-autofill:focus {
            -webkit-text-fill-color: #1e293b;
            -webkit-box-shadow: 0 0 0px 1000px #f8fafc inset;
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
            <a href="add_book.php" class="active">
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
        <div class="form-container">
            <div class="page-header">
                <span class="icon">➕</span>
                <h2>Add New Book</h2>
            </div>
            
            <form method="post">
                <div class="form-group">
                    <label>Book Title</label>
                    <input type="text" name="title" placeholder="Enter book title" required>
                </div>
                
                <div class="form-group">
                    <label>Author</label>
                    <input type="text" name="author" placeholder="Enter author name" required>
                </div>
                
                <div class="form-group">
                    <label>Category</label>
                    <input type="text" name="category" placeholder="Enter category (e.g., Fiction, Science)">
                </div>
                
                <div class="form-group">
                    <label>ISBN</label>
                    <input type="text" name="isbn" placeholder="Enter ISBN number">
                </div>
                
                <div class="form-group">
                    <label>Copies Available</label>
                    <input type="number" name="copies_available" placeholder="Enter number of copies" min="0" required>
                </div>
                
                <button type="submit" name="add" class="btn-submit">Add Book</button>
            </form>

            <?php
            if (isset($_POST['add'])) {
                $conn = new mysqli("localhost", "root", "", "library");
                if ($conn->connect_error) {
                    echo "<div class='message error'>
                            <span style='font-size: 20px;'>❌</span>
                            <span>Connection failed: " . $conn->connect_error . "</span>
                          </div>";
                } else {
                    $title = $conn->real_escape_string($_POST['title']);
                    $author = $conn->real_escape_string($_POST['author']);
                    $category = $conn->real_escape_string($_POST['category']);
                    $isbn = $conn->real_escape_string($_POST['isbn']);
                    $copies = (int)$_POST['copies_available'];

                    $sql = "INSERT INTO books (title, author, category, isbn, copies_available, copies)
                            VALUES ('$title', '$author', '$category', '$isbn', $copies, $copies)";

                    if ($conn->query($sql)) {
                        echo "<div class='message success'>
                                <span style='font-size: 20px;'>✅</span>
                                <span>Book added successfully!</span>
                              </div>";
                    } else {
                        echo "<div class='message error'>
                                <span style='font-size: 20px;'>❌</span>
                                <span>Error: " . $conn->error . "</span>
                              </div>";
                    }

                    $conn->close();
                }
            }
            ?>

            <a href="admin_dashboard.php" class="back-link">
                <span>←</span>
                <span>Back to Dashboard</span>
            </a>
        </div>
    </div>
</body>
</html>