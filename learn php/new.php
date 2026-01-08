
<?php
// AJAX request handler (no page refresh)
if (isset($_GET['ajax_sort'])) {

    $conn = mysqli_connect("localhost","root","","mydb");
    $sort = $_GET['ajax_sort'] === "desc" ? "DESC" : "ASC";

    $sql = "SELECT * FROM employee ORDER BY id $sort";
    $result = mysqli_query($conn, $sql);

    while ($row = mysqli_fetch_assoc($result)) {
        echo "
        <tr>
            <td>{$row['id']}</td>
            <td>{$row['name']}</td>
            <td>{$row['mobile']}</td>
            <td>{$row['email']}</td>
            <td>{$row['native']}</td>
            <td><img src='upload/{$row['photo']}' width='70'></td>
        </tr>";
    }

    exit(); // stop full page output
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>simple registration form</title>
    <link rel="stylesheet" type="text/css" href="design.css">
  
</head>
<body>
   <h1>simple registration form</h1>

   <form class="form" action="example.php" method="POST" enctype="multipart/form-data">
        <label for="name">Name:</label><br>
        <input type="text" name="name" required><br>

        <label for="mobile">Mobile:</label><br>
        <input type="text" name="mobile" required><br>

        <label for="email">Email:</label><br>
        <input type="email" name="email" required><br>

        <label for="native">Native:</label><br>
        <input type="text" name="native" required><br>
        <label for="upload">Upload file:</label><br>
        <input type="file" name="Upload" id="Upload"><br><br>

        <input type="submit" name="submit" value="submit">
   </form>




<?php
// ---------------- INSERT DATA ------------------
if($_SERVER['REQUEST_METHOD'] == "POST") {

    $conn = mysqli_connect("localhost","root","","mydb");
    if(!$conn){
        die("connection failed:".mysqli_connect_error());
    }

    $name   = $_POST['name'];
    $mobile = $_POST['mobile'];
    $email  = $_POST['email'];
    $native = $_POST['native'];
    
    // IMAGE UPLOAD
    $image_name = $_FILES['Upload']['name'];
    $tmp_name = $_FILES['Upload']['tmp_name'];

    $upload_path = "upload/" . $image_name;

    move_uploaded_file($tmp_name, $upload_path);

    $sql = "INSERT INTO employee(name,mobile,email,native,photo)
            VALUES('$name','$mobile','$email','$native','$image_name')";

    mysqli_query($conn, $sql);

    mysqli_close($conn);

    // redirect to avoid form resubmission
    header("Location: example.php?success=1");
    exit();
}
?>



<?php
$showTable = false;

// Show table after submit or sorting
if (isset($_GET['success']) || isset($_GET['sort'])) {
    if (isset($_GET['success'])) {
        echo "<h2 style='color:red;'>Record inserted successfully!</h2>";
    }
    $showTable = true;
}


?>

<?php if ($showTable): ?>
<h2>Employee Details</h2>

<table class="one" cellpadding="8">
    <tr>
        <th>
    id
    <span id="sortArrow" style="cursor:pointer; margin-left:5px; font-size:14px;" onclick="sortTable()">▲</span>
</th>

        <th>name</th>
        <th>mobile</th>
        <th>email</th>
        <th>native</th>
        <th>photo</th>
    </tr>

 <?php
    $conn = mysqli_connect("localhost","root","","mydb");
    $sort = isset($_GET['sort']) && $_GET['sort'] == "desc" ? "DESC" : "ASC";
    $sql = "SELECT * FROM employee ORDER BY id $sort";
    $result = mysqli_query($conn, $sql);
  ?>
   <tbody id="tableBody">
    <?php while ($row = mysqli_fetch_assoc($result)) { ?>
     
    <tr>
        <td><?php echo $row['id']; ?></td>
        <td><?php echo $row['name']; ?></td>
        <td><?php echo $row['mobile']; ?></td>
        <td><?php echo $row['email']; ?></td>
        <td><?php echo $row['native']; ?></td>
        <td><img src="upload/<?php echo $row['photo']; ?>" width="70"></td>
    </tr>
    <?php } ?>
    </tbody>
</table>
<?php endif; ?>

<script>
let currentSort = "asc";

function sortTable() {
    // toggle asc ↔ desc
    currentSort = currentSort === "asc" ? "desc" : "asc";

    // update arrow
    document.getElementById("sortArrow").innerHTML =
        currentSort === "asc" ? "▲" : "▼";

    // request data without refresh
    fetch("example.php?ajax_sort=" + currentSort)
        .then(res => res.text())
        .then(data => {
            document.getElementById("tableBody").innerHTML = data;
        });
}
</script>



</body>
</html>
