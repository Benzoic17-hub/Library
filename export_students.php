<?php
include 'db_connect.php';
header('Content-Type: text/csv');
header('Content-Disposition: attachment;filename=students.csv');

$output = fopen('php://output', 'w');
fputcsv($output, ['ID', 'Name', 'Email', 'Phone', 'Department']);

$result = mysqli_query($conn, "SELECT * FROM students");
while ($row = mysqli_fetch_assoc($result)) {
    fputcsv($output, $row);
}
fclose($output);
?>
