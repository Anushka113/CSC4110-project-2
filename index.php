<?php
require_once 'db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Anna Johnson Home Cleaning Service</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 900px;
            margin: 40px auto;
        }
        header {
            border-bottom: 1px solid #ccc;
            margin-bottom: 20px;
        }
        nav a {
            margin-right: 15px;
        }
        .status-ok {
            color: green;
        }
    </style>
</head>
<body>
<header>
    <h1>Anna Johnson Home Cleaning Service</h1>
    <p>CSC4110 Project 2</p>
</header>

<section>
    <h2>System Status</h2>
    <p class="status-ok">PHP is running ✔</p>
    <p class="status-ok">Database connection successful ✔</p>
</section>

<section>
    <h2>Navigation</h2>
    <nav>
        <a href="register.php">Client Registration</a>
        <a href="new_request.php">Submit Service Request</a>
        <a href="client_quotes.php">Client - View Quotes</a>
        <a href="anna_requests.php">Anna - View Pending Requests</a>
        <a href="anna_orders.php">Anna - Orders</a>
        <a href="client_bills.php">Client - Bills</a>
        <a href="dashboard_anna.php">Anna Dashboard</a>

    </nav>
</section>
</body>
</html>
