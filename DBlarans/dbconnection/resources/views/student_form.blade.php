<!DOCTYPE html>
<html>
<head>
    <title>Student Form</title>
</head>
<body>

<h2>Student Form</h2>

@if(session('success'))
    <p style="color:green">{{ session('success') }}</p>
@endif

<form method="POST" action="/student/store">
    @csrf

    <input type="text" name="name" placeholder="Name" required><br><br>
    <input type="email" name="email" placeholder="Email" required><br><br>
    <input type="number" name="mobile" placeholder="number" required><br><br>
     <input type="number" name="age" placeholder="age" required><br><br>
    <button type="submit">Save</button>
</form>

</body>
</html>
