<?php
session_start();

// If already logged in as student, redirect to dashboard
if (isset($_SESSION['role']) && $_SESSION['role'] === "student") {
    header("Location: student_dashboard.php");
    exit();
}

// Connect to DB
$conn = new mysqli("localhost", "root", "", "library");
if ($conn->connect_error) {
    die("DB Connection failed: " . $conn->connect_error);
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $loginInput = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    if (empty($loginInput) || empty($password)) {
        $error = "Please enter both username/email and password.";
    } else {
        $stmt = $conn->prepare("SELECT student_id, username, password, email, fullname 
                                FROM students WHERE username = ? OR email = ?");
        
        if ($stmt) {
            $stmt->bind_param("ss", $loginInput, $loginInput);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows === 1) {
                $stmt->bind_result($student_id, $username, $hashed_password, $email, $fullname);
                $stmt->fetch();

                if (password_verify($password, $hashed_password)) {
                    session_regenerate_id(true);
                    $_SESSION['role'] = "student";
                    $_SESSION['student_id'] = $student_id;
                    $_SESSION['username'] = $username;
                    $_SESSION['email'] = $email;
                    $_SESSION['fullname'] = $fullname;

                    header("Location: student_dashboard.php");
                    exit();
                } else {
                    $error = "Invalid password.";
                }
            } else {
                $error = "No account found with that username or email.";
            }

            $stmt->close();
        } else {
            $error = "Database error: " . $conn->error;
        }
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Login - Library</title>
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
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .login-container {
            background: white;
            padding: 50px;
            border-radius: 30px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 450px;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .login-header .logo {
            font-size: 48px;
            margin-bottom: 10px;
        }
        
        .login-header h2 {
            font-size: 32px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 8px;
        }
        
        .login-header p {
            color: #64748b;
            font-size: 15px;
        }
        
        .error {
            background: #fee2e2;
            color: #991b1b;
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            font-size: 14px;
            font-weight: 500;
            border: 2px solid #fecaca;
            display: flex;
            align-items: center;
            gap: 10px;
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
        
        .divider {
            text-align: center;
            margin: 30px 0;
            position: relative;
        }
        
        .divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: #e2e8f0;
        }
        
        .divider span {
            background: white;
            padding: 0 15px;
            color: #94a3b8;
            font-size: 14px;
            position: relative;
            z-index: 1;
        }
        
        .register-link {
            text-align: center;
            padding: 20px;
            background: #f8fafc;
            border-radius: 12px;
            margin-top: 20px;
        }
        
        .register-link p {
            color: #64748b;
            font-size: 14px;
            margin-bottom: 8px;
        }
        
        .register-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            font-size: 15px;
        }
        
        .register-link a:hover {
            text-decoration: underline;
        }
        
        .admin-link {
            text-align: center;
            margin-top: 20px;
        }
        
        .admin-link a {
            color: #64748b;
            text-decoration: none;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        
        .admin-link a:hover {
            color: #667eea;
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
    <div class="login-container">
        <div class="login-header">
            <div class="logo">📚</div>
            <h2>Welcome! Please Sign In</h2>
            <p>Access your library account</p>
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="error">
                <span style="font-size: 18px;">⚠️</span>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="student_login.php">
            <div class="form-group">
                <label>Username or Email</label>
                <input type="text" name="username" placeholder="Enter your username or email" required />
            </div>
            
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" placeholder="Enter your password" required />
            </div>
            
            <button type="submit" class="btn-submit">Sign In</button>
        </form>
        
        <div class="register-link">
            <p>Don't have an account?</p>
            <a href="register.php">Register here</a>
        </div>
        
        <div class="divider">
            <span>OR</span>
        </div>
        
        <div class="admin-link">
            <a href="admin_login.php">
                <span>🔐</span>
                <span>Admin Login</span>
            </a>
        </div>
    </div>
</body>
</html>