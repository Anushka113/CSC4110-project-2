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
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #f4f7fb;
            margin: 0;
            padding: 0;
        }
        header {
            background: #4a90e2;
            color: #fff;
            padding: 25px 40px;
            box-shadow: 0 3px 8px rgba(0,0,0,0.15);
        }
        header h1 {
            margin: 0;
            font-size: 30px;
        }
        header p {
            margin: 5px 0 0;
            font-size: 14px;
            opacity: 0.9;
        }
        .container {
            max-width: 1000px;
            margin: 30px auto;
            background: #fff;
            border-radius: 12px;
            padding: 25px 30px 30px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        h2 {
            margin-top: 0;
            color: #2c3e50;
        }
        .status {
            display: flex;
            flex-direction: column;
            gap: 6px;
            margin-bottom: 25px;
        }
        .status-ok {
            color: #2e7d32;
            font-weight: 600;
        }
        .nav-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
            gap: 15px;
        }
        .nav-card {
            background: #f4f9ff;
            border-radius: 10px;
            padding: 16px 18px;
            border: 1px solid #d0e6ff;
            transition: transform 0.15s ease, box-shadow 0.15s ease, background 0.15s;
        }
        .nav-card:hover {
            background: #e7f2ff;
            transform: translateY(-3px);
            box-shadow: 0 3px 8px rgba(0,0,0,0.08);
        }
        .nav-card h3 {
            margin: 0 0 8px;
            font-size: 18px;
            color: #1f3a93;
        }
        .nav-card p {
            margin: 0 0 10px;
            font-size: 13px;
            color: #555;
        }
        .btn-link {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 6px;
            background: #4a90e2;
            color: #fff;
            text-decoration: none;
            font-size: 13px;
        }
        .btn-link:hover {
            background: #3b7ccc;
        }
        footer {
            text-align: center;
            font-size: 12px;
            color: #777;
            margin: 15px 0 25px;
        }
    </style>
</head>
<body>
<header>
    <h1>Anna Johnson Home Cleaning Service</h1>
    <p>CSC4110 Project 2 • Database-Driven Web App</p>
</header>

<div class="container">
    <section class="status">
        <h2>System Status</h2>
        <div class="status-ok">✔ PHP is running</div>
        <div class="status-ok">✔ Database connection successful</div>
    </section>

    <section>
        <h2>Navigation</h2>
        <div class="nav-grid">
            <div class="nav-card">
                <h3>Client Registration</h3>
                <p>New clients can sign up with contact information and stored card details.</p>
                <a class="btn-link" href="register.php">Go to Registration</a>
            </div>

            <div class="nav-card">
                <h3>Submit Service Request</h3>
                <p>Existing clients can request cleaning, choose type, rooms, budget, and upload photos.</p>
                <a class="btn-link" href="new_request.php">New Service Request</a>
            </div>

            <div class="nav-card">
                <h3>Client Quotes</h3>
                <p>Clients can review quotes from Anna and accept them to create official orders.</p>
                <a class="btn-link" href="client_quotes.php">View Quotes</a>
            </div>

            <div class="nav-card">
                <h3>Client Bills</h3>
                <p>View bills, pay immediately, or dispute a bill with a note.</p>
                <a class="btn-link" href="client_bills.php">View Bills</a>
            </div>

            <div class="nav-card">
                <h3>Anna: Pending Requests</h3>
                <p>Anna can review incoming service requests and either reject or send a quote.</p>
                <a class="btn-link" href="anna_requests.php">View Pending Requests</a>
            </div>

            <div class="nav-card">
                <h3>Anna: Orders & Billing</h3>
                <p>Manage accepted orders and generate bills after job completion.</p>
                <a class="btn-link" href="anna_orders.php">View Orders</a>
            </div>

            <div class="nav-card">
                <h3>Anna Analytics Dashboard</h3>
                <p>See frequent/uncommitted clients, overdue bills, largest jobs, and more.</p>
                <a class="btn-link" href="dashboard_anna.php">Open Dashboard</a>
            </div>
        </div>
    </section>
</div>

<footer>
    &copy; <?php echo date('Y'); ?> Anna Johnson Cleaning • CSC4110 Project 2
</footer>
</body>
</html>
