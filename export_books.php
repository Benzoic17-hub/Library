<?php
include 'db_connect.php';

header('Content-Type: text/csv');
header('Content-Disposition: attachment;filename=books.csv');

$output = fopen('php://output', 'w');
fputcsv($output, ['ID', 'Title', 'Author', 'Category', 'ISBN', 'Copies']);

$result = $conn->query("SELECT * FROM books ORDER BY title ASC");
while ($row = $result->fetch_assoc()) {
    fputcsv($output, [
        $row['book_id'],
        $row['title'],
        $row['author'],
        $row['category'],
        $row['isbn'],
        $row['copies']
    ]);
}
fclose($output);
?>
