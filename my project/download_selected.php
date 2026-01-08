
<?php
$conn = mysqli_connect("localhost", "root", "", "mydb");

if (!isset($_POST['selected_ids'])) {
    die("No records selected.");
}

$ids = $_POST['selected_ids'];
$id_list = implode(",", $ids);

$query = "SELECT * FROM students WHERE id IN ($id_list)";
$result = mysqli_query($conn, $query);

// CSV headers
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="selected_students.csv"');

$output = fopen("php://output", "w");

// CSV column names
fputcsv($output, ['Name', 'Email', 'Phone', 'Department', 'Domain']);

while ($row = mysqli_fetch_assoc($result)) {
    fputcsv($output, [$row['name'], $row['email'], $row['phone'], $row['department'], $row['domain']]);
}

fclose($output);
exit;
?>
