
<?php
$conn = mysqli_connect("localhost", "root", "", "mydb");

if (isset($_GET['date'])) {
    $selected_date = $_GET['date'];

    // Filter query
    $sql = "
        SELECT * FROM students 
        WHERE DATE(created_at) = '$selected_date'
    ";

    $result = mysqli_query($conn, $sql);

    echo "<h3>Data for: $selected_date</h3>";

    if (mysqli_num_rows($result) > 0) {
        echo "<table border='1' cellpadding='8'>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Photos</th>
                    <th></th>
                </tr>";

        while ($row = mysqli_fetch_assoc($result)) {
            echo "
            <tr>
                <td>{$row['id']}</td>
                <td>{$row['name']}</td>
                <td><img src='uploads/{$row['photo']}' width='60'></td>
                <td>{$row['created_at']}</td>
            </tr>";
        }

        echo "</table>";
    } else {
        echo "<p>No records found for this date.</p>";
    }
}
?>
