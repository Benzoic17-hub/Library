<?php
session_start();

// If already logged in, redirect to appropriate dashboard
if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: admin_dashboard.php");
    } else {
        header("Location: student_dashboard.php");
    }
    exit();
}

// Connect to database
$conn = new mysqli("localhost", "root", "", "library");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$error = "";

// Handle login form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $loginInput = trim($_POST['username']); 
    $password = $_POST['password'];

    if (empty($loginInput) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {
        // First, check if it's an admin
        $stmt = $conn->prepare("SELECT id, name, email, password FROM admins WHERE email = ? OR name = ?");
        $stmt->bind_param("ss", $loginInput, $loginInput);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows === 1) {
            // Admin found
            $stmt->bind_result($admin_id, $admin_name, $admin_email, $hashed_password);
            $stmt->fetch();

            if (password_verify($password, $hashed_password)) {
                session_regenerate_id(true);
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id'] = $admin_id;
                $_SESSION['username'] = $admin_name;
                $_SESSION['email'] = $admin_email;
                $_SESSION['role'] = "admin";

                header("Location: admin_dashboard.php");
                exit();
            } else {
                $error = "Incorrect password.";
            }
        } else {
            // Not an admin, check if it's a student
            $stmt->close();
            $stmt = $conn->prepare("SELECT student_id, username, password, email, fullname FROM students WHERE username = ? OR email = ?");
            $stmt->bind_param("ss", $loginInput, $loginInput);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows === 1) {
                // Student found
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
                    $error = "Incorrect password.";
                }
            } else {
                $error = "No account found with that username or email.";
            }
        }

        $stmt->close();
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>e-Library Management System</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet" />
  <style>
    body {
      margin: 0;
      font-family: Arial, sans-serif;
      height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
      text-align: center;
      background: url('https://images.unsplash.com/photo-1512820790803-83ca734da794') no-repeat center center/cover;
      position: relative;
      color: white;
    }
    body::before {
      content: "";
      position: absolute;
      top: 0; left: 0; right: 0; bottom: 0;
      background: rgba(0, 0, 0, 0.5);
      z-index: 0;
    }
    .overlay {
      position: relative;
      z-index: 1;
      background: rgba(255,255,255,0.1);
      backdrop-filter: blur(6px);
      -webkit-backdrop-filter: blur(6px);
      padding: 30px;
      border-radius: 12px;
      width: 90%;
      max-width: 500px;
      box-shadow: 0 8px 20px rgba(0,0,0,0.5);
    }
    .form-box {
      background: rgba(255,255,255,0.9);
      color: black;
      padding: 20px;
      border-radius: 10px;
      margin-top: 20px;
    }
    input, button {
      width: 100%;
      padding: 10px;
      margin: 10px 0;
      border-radius: 6px;
      border: 1px solid #ccc;
      box-sizing: border-box;
    }
    button {
      background: #007BFF;
      color: white;
      font-weight: bold;
      cursor: pointer;
      border: none;
    }
    button:hover {
      background: #0056b3;
    }
    .error {
      background: #f8d7da;
      color: #721c24;
      padding: 10px;
      margin-bottom: 15px;
      border-left: 5px solid #dc3545;
      border-radius: 5px;
      font-size: 14px;
    }
    .register-link {
      text-align: center;
      margin-top: 15px;
    }
    .register-link a {
      color: #007BFF;
      text-decoration: none;
      font-weight: bold;
    }
    .register-link a:hover {
      text-decoration: underline;
    }
  </style>
</head>
<body>
  <div class="overlay">
    <h1>📚 e-Library Management System</h1>
    
    <div class="form-box">
      <h2 class="text-xl font-bold mb-4">Welcome! Please Sign In</h2>
      
      <?php if (!empty($error)): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
      <?php endif; ?>
      
      <form method="POST" action="">
        <input type="text" name="username" placeholder="Email or Username" required />
        <input type="password" name="password" placeholder="Password" required />
        <button type="submit">Sign In</button>
      </form>
      
      <div class="register-link">
        Don't have an account? <a href="register.php">Register here</a>
      </div>
    </div>
  </div>
</body>
</html>