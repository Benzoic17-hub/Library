<?php
session_start();
include 'db_connection.php';

// Check if student is logged in
if (!isset($_SESSION['role']) || $_SESSION['role'] !== "student" || !isset($_SESSION['student_id'])) {
    header("Location: student_login.php");
    exit();
}

// Get book ID
$book_id = isset($_GET['book_id']) ? intval($_GET['book_id']) : 0;

if ($book_id <= 0) {
    die("Invalid book ID");
}

// Database connection
$conn = new mysqli("localhost", "root", "", "library");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch book details
$stmt = $conn->prepare("SELECT title, author, pdf_file FROM books WHERE book_id = ?");
$stmt->bind_param("i", $book_id);
$stmt->execute();
$book = $stmt->get_result()->fetch_assoc();
$stmt->close();
$conn->close();



if (!$book) {
    die("Book not found");
}

if (!$book['pdf_file']) {
    die("PDF not available for this book");
}

$pdf_path = "books/pdfs/" . $book['pdf_file'];

if (!file_exists($pdf_path)) {
    die("PDF file not found on server.");
}
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo htmlspecialchars($book['title']); ?> - Reader</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap');
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 25%, #f093fb 50%, #4facfe 75%, #00f2fe 100%);
            background-size: 400% 400%;
            animation: gradientShift 1m ease infinite;
            overflow: hidden;
            height: 100vh;
        }
        
        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        .reader-container {
            position: relative;
            width: 100%;
            height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .reader-header {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            padding: 20px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            z-index: 1000;
        }
        
        .book-info {
            color: white;
        }
        
        .book-info h1 {
            font-size: 24px;
            font-weight: 700;
            text-shadow: 0 0 20px rgba(255, 255, 255, 0.5);
            margin-bottom: 5px;
        }
        
        .book-info p {
            font-size: 14px;
            opacity: 0.9;
            font-weight: 500;
        }
        
        .reader-controls {
            display: flex;
            gap: 15px;
            align-items: center;
        }
        
        .control-btn {
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            border: 2px solid rgba(255, 255, 255, 0.3);
            color: white;
            padding: 12px 24px;
            border-radius: 50px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .control-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);
        }
        
        .pdf-viewer {
            flex: 1;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            margin: 20px;
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            position: relative;
        }
        
        .pdf-viewer iframe {
            width: 100%;
            height: 100%;
            border: none;
            border-radius: 20px;
        }
        
        .zoom-controls {
            position: absolute;
            bottom: 30px;
            right: 30px;
            display: flex;
            gap: 10px;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(20px);
            padding: 15px;
            border-radius: 50px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
            z-index: 100;
        }
        
        .zoom-btn {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 20px;
            font-weight: bold;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .zoom-btn:hover {
            background: rgba(255, 255, 255, 0.4);
            transform: scale(1.1);
        }
        
        .loading {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: white;
            font-size: 24px;
            font-weight: 600;
            text-shadow: 0 0 20px rgba(255, 255, 255, 0.5);
        }
        
        .fullscreen-btn {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border: none;
        }
        
        .fullscreen-btn:hover {
            background: linear-gradient(135deg, #764ba2, #667eea);
        }
        
        .close-btn {
            background: linear-gradient(135deg, #ff6b6b, #ee5a6f);
        }
        
        .close-btn:hover {
            background: linear-gradient(135deg, #ee5a6f, #ff6b6b);
        }
    </style>
</head>
<body>
    <div class="reader-container">
        <div class="reader-header">
            <div class="book-info">
                <h1>📖 <?php echo htmlspecialchars($book['title']); ?></h1>
                <p>by <?php echo htmlspecialchars($book['author']); ?></p>
            </div>
            <div class="reader-controls">
                <button class="control-btn fullscreen-btn" onclick="toggleFullscreen()">
                    🖥️ Fullscreen
                </button>
                <a href="student_dashboard.php" class="control-btn close-btn">
                    ✖️ Close
                </a>
            </div>
        </div>
        
        <div class="pdf-viewer" id="pdfContainer">
            <div class="loading" id="loading">Loading PDF...</div>
            <iframe id="pdfFrame" src="<?php echo $pdf_path; ?>#toolbar=1&navpanes=0&scrollbar=1" style="display:none;"></iframe>
        </div>
        
        <div class="zoom-controls">
            <button class="zoom-btn" onclick="zoomOut()" title="Zoom Out">-</button>
            <button class="zoom-btn" onclick="resetZoom()" title="Reset Zoom">⟲</button>
            <button class="zoom-btn" onclick="zoomIn()" title="Zoom In">+</button>
        </div>
    </div>

    <script>
        const pdfFrame = document.getElementById('pdfFrame');
        const loading = document.getElementById('loading');
        const pdfContainer = document.getElementById('pdfContainer');
        
        // Show PDF when loaded
        pdfFrame.onload = function() {
            loading.style.display = 'none';
            pdfFrame.style.display = 'block';
        };
        
        // Fullscreen toggle
        function toggleFullscreen() {
            if (!document.fullscreenElement) {
                pdfContainer.requestFullscreen().catch(err => {
                    alert('Error attempting to enable fullscreen');
                });
            } else {
                document.exitFullscreen();
            }
        }
        
        let currentZoom = 100;
        
        function zoomIn() {
            currentZoom += 10;
            applyZoom();
        }
        
        function zoomOut() {
            if (currentZoom > 50) {
                currentZoom -= 10;
                applyZoom();
            }
        }
        
        function resetZoom() {
            currentZoom = 100;
            applyZoom();
        }
        
        function applyZoom() {
            pdfFrame.style.transform = `scale(${currentZoom / 100})`;
            pdfFrame.style.transformOrigin = 'top left';
        }
        
        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                window.location.href = 'student_dashboard.php';
            } else if (e.key === 'f' || e.key === 'F') {
                toggleFullscreen();
            } else if (e.key === '+' || e.key === '=') {
                zoomIn();
            } else if (e.key === '-' || e.key === '_') {
                zoomOut();
            } else if (e.key === '0') {
                resetZoom();
            }
        });
    </script>
</body>
</html>