<?php
session_start();

// ----------------------
// Database connection
// ----------------------
$host = "localhost";
$user = "root";
$pass = "";
$db   = "library";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    // Show friendly error and stop. You can log the actual error elsewhere.
    die("Database connection failed: " . $conn->connect_error);
}
$conn->set_charset('utf8mb4');

// ----------------------
// Session / access check
// ----------------------
if (!isset($_SESSION['role']) || $_SESSION['role'] !== "student" || !isset($_SESSION['student_id'])) {
    die("<p style='color:red;'>Access Denied! Only students can borrow/return books.</p>");
}

$student_id = (int) $_SESSION['student_id'];
$today = date('Y-m-d');
$message = "";

// ----------------------
// Count active borrows
// ----------------------
$activeCount = 0;
$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM borrow_return WHERE student_id = ? AND status = 'Borrowed'");
if ($stmt) {
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $activeCount = (int) ($res->fetch_assoc()['total'] ?? 0);
    $stmt->close();
}

// ----------------------
// Borrow a book
// ----------------------
if (isset($_POST['borrow'])) {
    $book_id = intval($_POST['book_id']);

    try {
        $conn->begin_transaction();

        // 1) Check duplicate borrow
        $stmt = $conn->prepare("SELECT id FROM borrow_return WHERE student_id = ? AND book_id = ? AND status = 'Borrowed'");
        $stmt->bind_param("ii", $student_id, $book_id);
        $stmt->execute();
        $dupRes = $stmt->get_result();
        $stmt->close();

        if ($dupRes && $dupRes->num_rows > 0) {
            $message = "<div class='error'>❌ You already borrowed this book.</div>";
            $conn->rollback();
        } elseif ($activeCount >= 5) {
            $message = "<div class='error'>❌ Borrow limit reached. Return a book to borrow more.</div>";
            $conn->rollback();
        } else {
            // 2) Lock and check availability
            // Note: SELECT ... FOR UPDATE requires InnoDB and an active transaction.
            $stmt = $conn->prepare("SELECT copies_available FROM books WHERE book_id = ? FOR UPDATE");
            $stmt->bind_param("i", $book_id);
            $stmt->execute();
            $bookRow = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            $copies = (int) ($bookRow['copies_available'] ?? 0);
            if ($copies > 0) {
                // 3) Insert borrow record
                $stmt = $conn->prepare("INSERT INTO borrow_return (student_id, book_id, borrow_date, due_date, status, fine) VALUES (?, ?, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 14 DAY), 'Borrowed', 0)");
                $stmt->bind_param("ii", $student_id, $book_id);
                $stmt->execute();
                $stmt->close();

                // 4) Decrement copies
                $stmt = $conn->prepare("UPDATE books SET copies_available = copies_available - 1 WHERE book_id = ?");
                $stmt->bind_param("i", $book_id);
                $stmt->execute();
                $stmt->close();

                $conn->commit();
                $message = "<div class='success'>✅ Book borrowed successfully!</div>";

                // update activeCount for this page load (so limit check is accurate if user continues)
                $activeCount++;
            } else {
                $conn->rollback();
                $message = "<div class='error'>❌ Sorry, this book is currently not available.</div>";
            }
        }
    } catch (Exception $e) {
        $conn->rollback();
        $message = "<div class='error'>❌ An error occurred while borrowing: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}

// ----------------------
// Return a book
// ----------------------
if (isset($_POST['return'])) {
    $borrow_id = intval($_POST['borrow_id']);

    try {
        $conn->begin_transaction();

        // Get borrow record (locked)
        $stmt = $conn->prepare("SELECT book_id, due_date, status FROM borrow_return WHERE id = ? FOR UPDATE");
        $stmt->bind_param("i", $borrow_id);
        $stmt->execute();
        $borrowRow = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$borrowRow) {
            $conn->rollback();
            $message = "<div class='error'>❌ Borrow record not found.</div>";
        } elseif ($borrowRow['status'] !== 'Borrowed') {
            $conn->rollback();
            $message = "<div class='error'>❌ This book is already returned.</div>";
        } else {
            $book_id = (int)$borrowRow['book_id'];
            $due_date = $borrowRow['due_date'];

            // Calculate fine (GH₵1 per day late) — adjust to your policy
            $fine = 0;
            if ($today > $due_date) {
                $daysLate = ceil((strtotime($today) - strtotime($due_date)) / (60 * 60 * 24));
                $fine = $daysLate * 1;
            }

            // Update borrow_return
            $stmt = $conn->prepare("UPDATE borrow_return SET return_date = CURDATE(), status = 'Returned', fine = ? WHERE id = ?");
            $stmt->bind_param("ii", $fine, $borrow_id);
            $stmt->execute();
            $stmt->close();

            // Update book copies
            $stmt = $conn->prepare("UPDATE books SET copies_available = copies_available + 1 WHERE book_id = ?");
            $stmt->bind_param("i", $book_id);
            $stmt->execute();
            $stmt->close();

            $conn->commit();
            $message = "<div class='success'>✅ Book returned successfully!" . ($fine > 0 ? " You have a fine of GH₵$fine." : "") . "</div>";
        }
    } catch (Exception $e) {
        $conn->rollback();
        $message = "<div class='error'>❌ An error occurred while returning: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Borrow / Return Books</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f4f6f9; }
        h2 { color: #007BFF; }
        form, table { margin-top: 20px; }
        select, button { padding: 8px; margin-right: 10px; }
        table { width: 100%; border-collapse: collapse; background: white; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background: #007BFF; color: white; }
        .overdue { color: red; font-weight: bold; }
        .ontime { color: green; }
        .success { background: #d4edda; color: #155724; padding: 10px; margin-top: 10px; border-left: 5px solid #28a745; }
        .error { background: #f8d7da; color: #721c24; padding: 10px; margin-top: 10px; border-left: 5px solid #dc3545; }
        .muted { color: #666; font-size: 0.95em; }
    </style>
    <script>
        function confirmReturn() {
            return confirm("Are you sure you want to return this book?");
        }
    </script>
</head>
<body>
    <h2>📖 Borrow a Book</h2>
    <?php if (!empty($message)) echo $message; ?>

    <form method="POST">
        <label>Select Book:</label>
        <select name="book_id" required>
            <?php
            $stmt = $conn->prepare("SELECT book_id, title, copies_available FROM books ORDER BY title ASC");
            $stmt->execute();
            $books = $stmt->get_result();
            while ($b = $books->fetch_assoc()) {
                $title = htmlspecialchars($b['title']);
                $copies = (int)$b['copies_available'];
                $disabled = $copies <= 0 ? 'disabled' : '';
                $display = $copies > 0 ? "{$title} ({$copies} available)" : "{$title} (Out of stock)";
                echo "<option value='{$b['book_id']}' {$disabled}>{$display}</option>";
            }
            $stmt->close();
            ?>
        </select>
        <button type="submit" name="borrow">Borrow</button>
    </form>

    <h2>📚 Your Borrowed Books</h2>
    <table>
        <tr>
            <th>Book</th>
            <th>Borrow Date</th>
            <th>Due Date</th>
            <th>Status</th>
            <th>Fine</th>
            <th>Action</th>
        </tr>
        <?php
        $stmt = $conn->prepare("
            SELECT br.id, b.title, br.borrow_date, br.due_date, br.status, br.return_date, br.fine
            FROM borrow_return br
            JOIN books b ON br.book_id = b.book_id
            WHERE br.student_id = ?
            ORDER BY br.borrow_date DESC
        ");
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $borrows = $stmt->get_result();

        $totalFine = 0;

        if ($borrows && $borrows->num_rows > 0) {
            while ($row = $borrows->fetch_assoc()) {
                $isOverdue = ($row['status'] == 'Borrowed' && $today > $row['due_date']);
                $daysLate = $isOverdue ? ceil((strtotime($today) - strtotime($row['due_date'])) / (60 * 60 * 24)) : 0;
                $calculatedFine = $isOverdue ? $daysLate * 1 : (int)($row['fine'] ?? 0);
                $totalFine += $calculatedFine;

                // If overdue, update stored fine (non-blocking)
                if ($isOverdue && $calculatedFine != (int)$row['fine']) {
                    $u = $conn->prepare("UPDATE borrow_return SET fine = ? WHERE id = ?");
                    $u->bind_param("ii", $calculatedFine, $row['id']);
                    $u->execute();
                    $u->close();
                }

                $fineDisplay = $calculatedFine > 0 ? "<span class='overdue'>GH₵{$calculatedFine}</span>" : "<span class='ontime'>GH₵0</span>";
                echo "<tr>
                        <td>" . htmlspecialchars($row['title']) . "</td>
                        <td>{$row['borrow_date']}</td>
                        <td>{$row['due_date']}</td>
                        <td>{$row['status']}</td>
                        <td>{$fineDisplay}</td>
                        <td>";
                if ($row['status'] == 'Borrowed') {
                    echo "<form method='POST' onsubmit='return confirmReturn();' style='margin:0;'>
                            <input type='hidden' name='borrow_id' value='{$row['id']}'>
                            <button type='submit' name='return'>Return</button>
                          </form>";
                } else {
                    echo "Returned on " . ($row['return_date'] ?? '-') ;
                }
                echo "</td></tr>";
            }
        } else {
            echo "<tr><td colspan='6' style='text-align:center; color:gray;'>No borrowed books found</td></tr>";
        }
        $stmt->close();
        ?>
    </table>

    <?php if ($totalFine > 0): ?>
        <div class="error">💰 Total Outstanding Fine: GH₵<?php echo $totalFine; ?></div>
    <?php endif; ?>

</body>
</html>

<?php
// Close DB connection
$conn->close();
?>
