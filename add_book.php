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
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
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
        
        .container {
            max-width: 600px;
            width: 90%;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            padding: 50px;
            border-radius: 30px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
            position: relative;
            z-index: 100;
            animation: slideIn 0.8s ease;
        }
        
        @keyframes slideIn {
            from {
                transform: translateY(50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        .container::before {
            content: '';
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            background: linear-gradient(45deg, #667eea, #764ba2, #f093fb, #4facfe);
            border-radius: 30px;
            z-index: -1;
            animation: borderGlow 3s ease infinite;
        }
        
        @keyframes borderGlow {
            0%, 100% { opacity: 0.5; }
            50% { opacity: 1; }
        }
        
        h2 {
            text-align: center;
            color: white;
            font-size: 36px;
            font-weight: 800;
            margin-bottom: 35px;
            text-shadow: 0 0 30px rgba(255, 255, 255, 0.5);
            letter-spacing: 1px;
        }
        
        form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        input[type="text"],
        input[type="number"] {
            width: 100%;
            padding: 18px 25px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 15px;
            font-size: 16px;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            color: white;
            font-weight: 500;
            transition: all 0.3s;
            font-family: 'Poppins', sans-serif;
        }
        
        input[type="text"]::placeholder,
        input[type="number"]::placeholder {
            color: rgba(255, 255, 255, 0.7);
        }
        
        input[type="text"]:focus,
        input[type="number"]:focus {
            outline: none;
            border-color: rgba(255, 255, 255, 0.6);
            background: rgba(255, 255, 255, 0.25);
            box-shadow: 0 0 30px rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }
        
        input[type="submit"] {
            width: 100%;
            padding: 18px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 15px;
            cursor: pointer;
            font-weight: 700;
            font-size: 18px;
            transition: all 0.3s;
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
            font-family: 'Poppins', sans-serif;
            letter-spacing: 1px;
            margin-top: 10px;
        }
        
        input[type="submit"]:hover {
            background: linear-gradient(135deg, #764ba2, #667eea);
            transform: translateY(-3px);
            box-shadow: 0 12px 35px rgba(102, 126, 234, 0.6);
        }
        
        input[type="submit"]:active {
            transform: translateY(0);
        }
        
        .message {
            text-align: center;
            margin-top: 20px;
            padding: 15px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 16px;
            animation: slideDown 0.5s ease;
        }
        
        @keyframes slideDown {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        .message.success {
            background: rgba(56, 239, 125, 0.2);
            border: 2px solid rgba(56, 239, 125, 0.5);
            color: white;
            box-shadow: 0 0 20px rgba(56, 239, 125, 0.3);
        }
        
        .message.error {
            background: rgba(255, 107, 107, 0.2);
            border: 2px solid rgba(255, 107, 107, 0.5);
            color: white;
            box-shadow: 0 0 20px rgba(255, 107, 107, 0.3);
        }
        
        a {
            display: block;
            text-align: center;
            margin-top: 25px;
            color: white;
            text-decoration: none;
            font-weight: 600;
            font-size: 16px;
            padding: 12px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            transition: all 0.3s;
            border: 2px solid rgba(255, 255, 255, 0.2);
        }
        
        a:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateX(-5px);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
        }
        
        /* Chrome autofill styling */
        input:-webkit-autofill,
        input:-webkit-autofill:hover,
        input:-webkit-autofill:focus {
            -webkit-text-fill-color: white;
            -webkit-box-shadow: 0 0 0px 1000px rgba(255, 255, 255, 0.2) inset;
            transition: background-color 5000s ease-in-out 0s;
        }
    </style>
</head>
<body>
    <div class="particles" id="particles"></div>
    
    <div class="container">
        <h2>➕ Add New Book</h2>
        <form method="post">
            <input type="text" name="title" placeholder="Book Title" required>
            <input type="text" name="author" placeholder="Author" required>
            <input type="text" name="category" placeholder="Category">
            <input type="text" name="isbn" placeholder="ISBN">
            <input type="number" name="copies_available" placeholder="Copies Available" min="0" required>
            <input type="submit" name="add" value="Add Book">
        </form>

        <?php
        if (isset($_POST['add'])) {
            $conn = new mysqli("localhost", "root", "", "library");
            if ($conn->connect_error) {
                echo "<div class='message error'>Connection failed: " . $conn->connect_error . "</div>";
            } else {
                $title = $conn->real_escape_string($_POST['title']);
                $author = $conn->real_escape_string($_POST['author']);
                $category = $conn->real_escape_string($_POST['category']);
                $isbn = $conn->real_escape_string($_POST['isbn']);
                $copies = (int)$_POST['copies_available'];

                $sql = "INSERT INTO books (title, author, category, isbn, copies_available, copies)
                        VALUES ('$title', '$author', '$category', '$isbn', $copies, $copies)";

                if ($conn->query($sql)) {
                    echo "<div class='message success'>✅ Book added successfully!</div>";
                } else {
                    echo "<div class='message error'>❌ Error: " . $conn->error . "</div>";
                }

                $conn->close();
            }
        }
        ?>

        <a href="admin_dashboard.php">← Back to Dashboard</a>
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