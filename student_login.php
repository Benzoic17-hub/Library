<?php
session_start();
include 'db_connection.php';

// Connect to DB
$conn = new mysqli("localhost", "root", "", "library");
if ($conn->connect_error) {
    die("DB Connection failed: " . $conn->connect_error);
}

$error = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $loginInput = isset($_POST['username']) ? trim($_POST['username']) : '';
$password   = isset($_POST['password']) ? $_POST['password'] : '';


    if (empty($loginInput) || empty($password)) {
        $error = "❌ Please enter both username/email and password.";
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

                    // Redirect to dashboard (avoids ERR_CACHE_MISS)
                    header("Location: student_dashboard.php");
                    exit();
                } else {
                    $error = "❌ Invalid password.";
                }
            } else {
                $error = "❌ No account found with that username or email.";
            }

            $stmt->close();
        } else {
            $error = "❌ Database error: " . $conn->error;
        }
    }
    $error = "";
if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}

}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Student Login</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .login-box {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.2);
            width: 350px;
        }
        .login-box h2 {
            margin-bottom: 20px;
            text-align: center;
        }
        .login-box input {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
            border: 1px solid #ccc;
        }
        .login-box button {
            width: 100%;
            padding: 10px;
            background: #007BFF;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .login-box button:hover {
            background: #0056b3;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 10px;
            margin-top: 10px;
            border-left: 5px solid #dc3545;
        }
        .register-link {
            text-align: center;
            margin-top: 15px;
        }
        .register-link a {
            color: #28a745;
            text-decoration: none;
            font-weight: bold;
        }
        .register-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-box">
        <h2>📚 Student Login</h2>
        <?php if (!empty($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
     <form method="POST" action="student_login.php">

            <input type="text" name="username" placeholder="Username or Email" required />
            <input type="password" name="password" placeholder="Password" required />
            <button type="submit">Login</button>
        </form>
        <div class="register-link">
            Don't have an account? <a href="register.php">Register here</a>
        </div>
    </div>
</body>
</html>
