<?php
session_start();

// If user already logged in → go to main dashboard
if (isset($_SESSION['client_id'])) {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Welcome | Cleaning Service</title>
<style>
    body { font-family: 'Segoe UI', Arial, sans-serif; background:#f4f7fb; margin:0; }
    .container {
        max-width: 450px;
        margin: 120px auto;
        background: #fff;
        padding: 35px;
        border-radius: 12px;
        text-align:center;
        box-shadow:0 4px 12px rgba(0,0,0,0.15);
    }
    h1 { color:#4a90e2; }
    .btn {
        display:block;
        width:100%;
        padding:12px;
        margin:12px 0;
        background:#4a90e2;
        color:white;
        border:none;
        border-radius:8px;
        font-size:17px;
        cursor:pointer;
        text-decoration:none;
    }
    .btn:hover { background:#3b7ccc; }
</style>
</head>
<body>

<div class="container">
    <h1>Welcome</h1>
    <p>Please login or register to continue.</p>

    <a class="btn" href="login.php">Login</a>
    <a class="btn" href="register.php" style="background:#67b26f;">Register</a>
</div>

</body>
</html>
