<?php
session_start();
$isLoggedIn = isset($_SESSION['username']);
$welcomeMessage = $isLoggedIn ? "Welcome back, " . htmlspecialchars($_SESSION['username']) . "!" : "e-Library Management System";
$role = $isLoggedIn ? $_SESSION['role'] : null;
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
    }
    button {
      background: #007BFF;
      color: white;
      font-weight: bold;
      cursor: pointer;
    }
    button:hover {
      background: #0056b3;
    }
    .role {
      cursor: pointer;
      padding: 12px 20px;
      background: rgba(255,255,255,0.85);
      color: #000;
      border-radius: 8px;
      font-size: 1.1em;
      font-weight: bold;
      transition: transform 0.3s, background 0.3s;
      margin: 10px;
      display: inline-block;
    }
    .role:hover {
      background: rgba(255,255,255,1);
      transform: scale(1.05);
    }
    .hidden { display: none; }
  </style>
</head>
<body>
  <div class="overlay">
    <h1><?php echo $welcomeMessage; ?></h1>

    <?php if (!$isLoggedIn): ?>
      <!-- Role selector -->
      <div>
        <div class="role" onclick="showForm('admin')">Admin 👤</div>
        <div class="role" onclick="showForm('student')">Student 🎓</div>
      </div>

      <!-- Admin Login -->
      <div id="adminForm" class="form-box hidden">
        <h2 class="text-xl font-bold mb-4">Admin Login</h2>
        <form onsubmit="return mockLogin(event, 'admin_dashboard.php')">
          <input type="email" placeholder="Email" required />
          <input type="password" placeholder="Password" required />
          <button type="submit">Sign In</button>
        </form>
      </div>

      <!-- Student Container -->
      <div id="studentContainer" class="form-box hidden">
        <div class="flex justify-center gap-4 mb-4">
          <button id="studentSignInBtn" class="px-4 py-2 bg-blue-600 text-white rounded">Sign In</button>
          <button id="studentSignUpBtn" class="px-4 py-2 bg-gray-300 text-black rounded">Sign Up</button>
        </div>

      <!-- Student Sign In -->
      <div id="studentSignInSection">
  <form method="POST" action="student_login.php">
    <input type="text" name="email" placeholder="Email" required />
    <input type="password" name="password" placeholder="Password" required />
    <button type="submit">Sign In</button>
  </form>
</div>


        <!-- Student Sign Up -->
        <div id="studentSignUpSection" class="hidden">
          <form onsubmit="return handleStudentSignup(event)">
            <input type="text" id="new-username" placeholder="Username" required />
            <input type="email" id="new-email" placeholder="Email" required />
            <input type="password" id="new-password" placeholder="Password" required />
            <input type="password" id="confirm-password" placeholder="Confirm Password" required />
            <button type="submit">Sign Up</button>
          </form>
        </div>
      </div>
    <?php else: ?>
      <div class="role" onclick="window.location='logout.php'">Logout 🚪</div>
      <div class="role" onclick="window.location='<?php echo ($role === 'admin' ? 'admin_dashboard.php' : 'student_dashboard.php'); ?>'">Go to Dashboard 📊</div>
    <?php endif; ?>
  </div>

  <script>
    function showForm(role) {
      document.getElementById('adminForm').classList.add('hidden');
      document.getElementById('studentContainer').classList.add('hidden');

      if (role === 'admin') {
        document.getElementById('adminForm').classList.remove('hidden');
      } else if (role === 'student') {
        document.getElementById('studentContainer').classList.remove('hidden');
        toggleStudentForms(true); // default to Sign In
      }
    }

    function toggleStudentForms(showSignIn) {
      document.getElementById('studentSignInSection').classList.toggle('hidden', !showSignIn);
      document.getElementById('studentSignUpSection').classList.toggle('hidden', showSignIn);

      document.getElementById('studentSignInBtn').classList.toggle('bg-blue-600', showSignIn);
      document.getElementById('studentSignInBtn').classList.toggle('bg-gray-300', !showSignIn);

      document.getElementById('studentSignUpBtn').classList.toggle('bg-blue-600', !showSignIn);
      document.getElementById('studentSignUpBtn').classList.toggle('bg-gray-300', showSignIn);
    }

    document.getElementById('studentSignInBtn').addEventListener('click', () => toggleStudentForms(true));
    document.getElementById('studentSignUpBtn').addEventListener('click', () => toggleStudentForms(false));

    function mockLogin(e, redirect) {
      e.preventDefault();
      alert("Login successful (mock)");
      window.location.href = redirect;
      return false;
    }

    function handleStudentSignup(e) {
      e.preventDefault();
      const username = document.getElementById('new-username').value.trim();
      const email = document.getElementById('new-email').value.trim();
      const password = document.getElementById('new-password').value;
      const confirm = document.getElementById('confirm-password').value;

      if (!username || !email || !password || !confirm) return alert("All fields required");
      if (password !== confirm) return alert("Passwords do not match");

      localStorage.setItem('student-username', username);
      localStorage.setItem('student-email', email);
      localStorage.setItem('student-password', password);
      alert("Student Sign Up Successful!");
      toggleStudentForms(true);
      return false;
    }
  </script>
</body>
</html>
