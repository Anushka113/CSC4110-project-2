<?php
require_once 'db.php';

// For filtering accepted quotes by month/year
$year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');

function runQuery($conn, $sql) {
    $res = $conn->query($sql);
    if (!$res) {
        return ["error" => $conn->error];
    }
    return ["result" => $res];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Anna Dashboard</title>

<style>
    body {
        font-family: 'Segoe UI', Arial, sans-serif;
        background: #f4f7fb;
        margin: 0;
        padding: 0;
    }

    header {
        background: #4a90e2;
        color: white;
        padding: 25px 40px;
        font-size: 32px;
        font-weight: bold;
        letter-spacing: 1px;
        box-shadow: 0 3px 8px rgba(0,0,0,0.15);
    }

    .container {
        max-width: 1100px;
        margin: 30px auto;
        background: white;
        padding: 30px;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }

    h2 {
        background: linear-gradient(to right, #4a90e2, #67b1f7);
        color: white;
        padding: 10px 18px;
        border-radius: 6px;
        margin-top: 40px;
        font-size: 22px;
        box-shadow: 0 2px 6px rgba(0,0,0,0.15);
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 12px;
    }

    th {
        background: #bee3ff;
        padding: 10px;
        border: 1px solid #a0cfff;
        font-weight: bold;
        text-align: left;
    }

    td {
        padding: 10px;
        border: 1px solid #d0e6ff;
        background: #f9fcff;
    }

    tr:hover td {
        background: #eef7ff;
    }

    a.back {
        background: #4a90e2;
        color: white;
        padding: 8px 14px;
        border-radius: 5px;
        text-decoration: none;
        font-size: 14px;
    }

    .info {
        font-size: 14px;
        color: #555;
        margin-top: 5px;
    }

    /* Highlights */
    .good {
        background: #d4ffd4 !important;
        font-weight: bold;
    }

    .bad {
        background: #ffd4d4 !important;
        font-weight: bold;
    }

    form.inline input, form.inline button {
        padding: 6px 10px;
        margin: 4px;
        border-radius: 5px;
        border: 1px solid #a0cfff;
    }

    form.inline button {
        background: #4a90e2;
        color: white;
        border: none;
    }

    .nodata {
        padding: 10px;
        background: #ffeecf;
        border-left: 5px solid #ffb84d;
        margin-top: 10px;
        border-radius: 5px;
    }

    .error {
        padding: 10px;
        background: #ffd4d4;
        border-left: 5px solid #d9534f;
        margin-top: 10px;
        border-radius: 5px;
        color: #900;
        font-weight: bold;
    }
</style>

</head>

<body>

<header>Anna Johnson — Analytics Dashboard</header>

<div class="container">

<p><a class="back" href="index.php">← Back to Home</a></p>

<!-- 3) Frequent clients -->
<h2>3. Frequent Clients</h2>
<p class="info">Clients with the most completed service orders.</p>
<?php
$sql3 = "
    SELECT c.client_id, c.first_name, c.last_name, COUNT(o.order_id) AS completed_orders
    FROM Client c
    JOIN `Order` o ON c.client_id = o.client_id
    WHERE o.status = 'completed'
    GROUP BY c.client_id, c.first_name, c.last_name
    ORDER BY completed_orders DESC
";
$q3 = runQuery($conn, $sql3);

if (isset($q3['error'])) echo "<div class='error'>{$q3['error']}</div>";
elseif ($q3['result']->num_rows == 0) echo "<div class='nodata'>No completed orders yet.</div>";
else {
    echo "<table><tr><th>Client ID</th><th>Name</th><th>Completed Orders</th></tr>";
    while ($r = $q3['result']->fetch_assoc()) {
        echo "<tr>
                <td>{$r['client_id']}</td>
                <td>{$r['first_name']} {$r['last_name']}</td>
                <td>{$r['completed_orders']}</td>
              </tr>";
    }
    echo "</table>";
}
?>

<!-- 4) Uncommitted clients -->
<h2>4. Uncommitted Clients</h2>
<p class="info">Clients who submitted 3+ requests but never completed an order.</p>

<?php
$sql4 = "
    SELECT c.client_id, c.first_name, c.last_name,
           COUNT(sr.request_id) AS request_count
    FROM Client c
    JOIN ServiceRequest sr ON c.client_id = sr.client_id
    LEFT JOIN `Order` o 
        ON sr.request_id = o.request_id AND o.status = 'completed'
    GROUP BY c.client_id, c.first_name, c.last_name
    HAVING COUNT(sr.request_id) >= 3
      AND SUM(CASE WHEN o.order_id IS NOT NULL THEN 1 ELSE 0 END) = 0
";
$q4 = runQuery($conn, $sql4);

if (isset($q4['error'])) echo "<div class='error'>{$q4['error']}</div>";
elseif ($q4['result']->num_rows == 0) echo "<div class='nodata'>No uncommitted clients.</div>";
else {
    echo "<table><tr><th>Client ID</th><th>Name</th><th># Requests</th></tr>";
    while ($r = $q4['result']->fetch_assoc()) {
        echo "<tr class='bad'>
                <td>{$r['client_id']}</td>
                <td>{$r['first_name']} {$r['last_name']}</td>
                <td>{$r['request_count']}</td>
              </tr>";
    }
    echo "</table>";
}
?>

<!-- 5) This month's accepted quotes -->
<h2>5. Accepted Quotes for Month</h2>
<p class="info">Select a month and year to view accepted quotes.</p>

<form method="get" class="inline">
    <input type="number" name="year" value="<?php echo $year; ?>" placeholder="Year">
    <input type="number" name="month" min="1" max="12" value="<?php echo $month; ?>" placeholder="Month">
    <button type="submit">Filter</button>
</form>

<?php
$sql5 = "
    SELECT q.quote_id, q.request_id, q.created_at, c.client_id, c.first_name, c.last_name
    FROM Quote q
    JOIN ServiceRequest sr ON q.request_id = sr.request_id
    JOIN Client c ON sr.client_id = c.client_id
    WHERE q.status = 'accepted'
      AND YEAR(q.created_at) = $year
      AND MONTH(q.created_at) = $month
    ORDER BY q.created_at DESC
";
$q5 = runQuery($conn, $sql5);

if (isset($q5['error'])) echo "<div class='error'>{$q5['error']}</div>";
elseif ($q5['result']->num_rows == 0) echo "<div class='nodata'>No accepted quotes for this month.</div>";
else {
    echo "<table><tr><th>Quote ID</th><th>Client</th><th>Request</th><th>Accepted At</th></tr>";
    while ($r = $q5['result']->fetch_assoc()) {
        echo "<tr>
                <td>{$r['quote_id']}</td>
                <td>{$r['client_id']} - {$r['first_name']} {$r['last_name']}</td>
                <td>{$r['request_id']}</td>
                <td>{$r['created_at']}</td>
              </tr>";
    }
    echo "</table>";
}
?>

<!-- 6) Prospective clients -->
<h2>6. Prospective Clients</h2>
<p class="info">Clients who registered but never submitted any request.</p>

<?php
$sql6 = "
    SELECT c.client_id, c.first_name, c.last_name, c.email
    FROM Client c
    LEFT JOIN ServiceRequest sr ON c.client_id = sr.client_id
    WHERE sr.request_id IS NULL
";
$q6 = runQuery($conn, $sql6);

if (isset($q6['error'])) echo "<div class='error'>{$q6['error']}</div>";
elseif ($q6['result']->num_rows == 0) echo "<div class='nodata'>No prospective clients.</div>";
else {
    echo "<table><tr><th>Client ID</th><th>Name</th><th>Email</th></tr>";
    while ($r = $q6['result']->fetch_assoc()) {
        echo "<tr>
                <td>{$r['client_id']}</td>
                <td>{$r['first_name']} {$r['last_name']}</td>
                <td>{$r['email']}</td>
              </tr>";
    }
    echo "</table>";
}
?>

<!-- 7) Largest job -->
<h2>7. Largest Job Completed</h2>
<p class="info">Service requests with the highest number of rooms.</p>

<?php
$sql7 = "
    SELECT sr.request_id, sr.num_rooms, o.order_id,
           c.client_id, c.first_name, c.last_name
    FROM ServiceRequest sr
    JOIN `Order` o ON sr.request_id = o.request_id
    JOIN Client c ON sr.client_id = c.client_id
    WHERE o.status = 'completed'
      AND sr.num_rooms = (
            SELECT MAX(sr2.num_rooms)
            FROM ServiceRequest sr2
            JOIN `Order` o2 ON sr2.request_id = o2.request_id
            WHERE o2.status = 'completed'
      )
";
$q7 = runQuery($conn, $sql7);

if (isset($q7['error'])) echo "<div class='error'>{$q7['error']}</div>";
elseif ($q7['result']->num_rows == 0) echo "<div class='nodata'>No completed jobs yet.</div>";
else {
    echo "<table><tr><th>Request ID</th><th>Order ID</th><th>Client</th><th># Rooms</th></tr>";
    while ($r = $q7['result']->fetch_assoc()) {
        echo "<tr>
                <td>{$r['request_id']}</td>
                <td>{$r['order_id']}</td>
                <td>{$r['client_id']} - {$r['first_name']} {$r['last_name']}</td>
                <td>{$r['num_rooms']}</td>
              </tr>";
    }
    echo "</table>";
}
?>

<!-- 8) Overdue bills -->
<h2>8. Overdue Bills</h2>
<p class="info">Bills unpaid for more than 7 days.</p>

<?php
$sql8 = "
    SELECT b.bill_id, b.order_id, b.amount, b.generated_at
    FROM Bill b
    WHERE b.status = 'unpaid'
      AND b.generated_at < (NOW() - INTERVAL 7 DAY)
";
$q8 = runQuery($conn, $sql8);

if (isset($q8['error'])) echo "<div class='error'>{$q8['error']}</div>";
elseif ($q8['result']->num_rows == 0) echo "<div class='nodata'>No overdue bills.</div>";
else {
    echo "<table><tr><th>Bill ID</th><th>Order ID</th><th>Amount</th><th>Generated At</th></tr>";
    while ($r = $q8['result']->fetch_assoc()) {
        echo "<tr class='bad'>
                <td>{$r['bill_id']}</td>
                <td>{$r['order_id']}</td>
                <td>{$r['amount']}</td>
                <td>{$r['generated_at']}</td>
              </tr>";
    }
    echo "</table>";
}
?>

<!-- 9) Bad clients -->
<h2>9. Bad Clients</h2>
<p class="info">Clients with overdue unpaid bills.</p>

<?php
$sql9 = "
    SELECT DISTINCT c.client_id, c.first_name, c.last_name
    FROM Client c
    JOIN `Order` o ON c.client_id = o.client_id
    JOIN Bill b ON o.order_id = b.order_id
    WHERE b.generated_at < (NOW() - INTERVAL 7 DAY)
      AND b.status <> 'paid'
";
$q9 = runQuery($conn, $sql9);

if (isset($q9['error'])) echo "<div class='error'>{$q9['error']}</div>";
elseif ($q9['result']->num_rows == 0) echo "<div class='nodata'>No bad clients.</div>";
else {
    echo "<table><tr><th>Client ID</th><th>Name</th></tr>";
    while ($r = $q9['result']->fetch_assoc()) {
        echo "<tr class='bad'>
                <td>{$r['client_id']}</td>
                <td>{$r['first_name']} {$r['last_name']}</td>
              </tr>";
    }
    echo "</table>";
}
?>

<!-- 10) Good clients -->
<h2>10. Good Clients</h2>
<p class="info">Clients who ALWAYS paid bills within 24 hours.</p>

<?php
$sql10 = "
    SELECT c.client_id, c.first_name, c.last_name
    FROM Client c
    JOIN `Order` o ON c.client_id = o.client_id
    JOIN Bill b ON o.order_id = b.order_id
    JOIN Payment p ON b.bill_id = p.bill_id
    GROUP BY c.client_id, c.first_name, c.last_name
    HAVING SUM(
            CASE
                WHEN TIMESTAMPDIFF(HOUR, b.generated_at, p.paid_at) <= 24
                THEN 0 ELSE 1
            END
           ) = 0
";
$q10 = runQuery($conn, $sql10);

if (isset($q10['error'])) echo "<div class='error'>{$q10['error']}</div>";
elseif ($q10['result']->num_rows == 0) echo "<div class='nodata'>No good clients yet.</div>";
else {
    echo "<table><tr><th>Client ID</th><th>Name</th></tr>";
    while ($r = $q10['result']->fetch_assoc()) {
        echo "<tr class='good'>
                <td>{$r['client_id']}</td>
                <td>{$r['first_name']} {$r['last_name']}</td>
              </tr>";
    }
    echo "</table>";
}
?>

</div>
</body>
</html>
