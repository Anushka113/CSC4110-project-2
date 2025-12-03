<?php
require_once 'db.php';

// For "this month's accepted quotes"
$year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');

// Helper to run simple SELECTs and return result
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
        body { font-family: Arial, sans-serif; max-width: 1100px; margin: 30px auto; }
        h1 { margin-bottom: 5px; }
        h2 { margin-top: 30px; border-bottom: 1px solid #ccc; padding-bottom: 5px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #ccc; padding: 6px; text-align: left; }
        th { background: #f0f0f0; }
        .small { font-size: 0.9em; color: #666; }
        .error { color: #b30000; font-weight: bold; }
        form.inline { display: inline-block; margin-top: 5px; }
    </style>
</head>
<body>
    <h1>Anna Johnson Dashboard</h1>
    <p><a href="index.php">&larr; Back to Home</a></p>

    <!-- 3) Frequent clients -->
    <h2>3. Frequent Clients</h2>
    <p class="small">Clients who completed the most service orders (status = 'completed').</p>
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
    if (isset($q3['error'])) {
        echo '<p class="error">Error: ' . htmlspecialchars($q3['error']) . '</p>';
    } elseif ($q3['result']->num_rows == 0) {
        echo "<p>No completed orders yet.</p>";
    } else {
        echo "<table><tr><th>Client ID</th><th>Name</th><th>Completed Orders</th></tr>";
        while ($row = $q3['result']->fetch_assoc()) {
            echo "<tr>
                    <td>{$row['client_id']}</td>
                    <td>" . htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) . "</td>
                    <td>{$row['completed_orders']}</td>
                  </tr>";
        }
        echo "</table>";
    }
    ?>

    <!-- 4) Uncommitted clients -->
    <h2>4. Uncommitted Clients</h2>
    <p class="small">Clients who submitted 3+ requests but never completed an order.</p>
    <?php
    $sql4 = "
        SELECT c.client_id, c.first_name, c.last_name,
               COUNT(sr.request_id) AS request_count
        FROM Client c
        JOIN ServiceRequest sr ON c.client_id = sr.client_id
        LEFT JOIN `Order` o 
               ON sr.request_id = o.request_id 
              AND o.status = 'completed'
        GROUP BY c.client_id, c.first_name, c.last_name
        HAVING COUNT(sr.request_id) >= 3
           AND SUM(CASE WHEN o.order_id IS NOT NULL THEN 1 ELSE 0 END) = 0
    ";
    $q4 = runQuery($conn, $sql4);
    if (isset($q4['error'])) {
        echo '<p class="error">Error: ' . htmlspecialchars($q4['error']) . '</p>';
    } elseif ($q4['result']->num_rows == 0) {
        echo "<p>No uncommitted clients (yet).</p>";
    } else {
        echo "<table><tr><th>Client ID</th><th>Name</th><th># Requests</th></tr>";
        while ($row = $q4['result']->fetch_assoc()) {
            echo "<tr>
                    <td>{$row['client_id']}</td>
                    <td>" . htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) . "</td>
                    <td>{$row['request_count']}</td>
                  </tr>";
        }
        echo "</table>";
    }
    ?>

    <!-- 5) This month's accepted quotes -->
    <h2>5. This Month's Accepted Quotes</h2>
    <p class="small">Quotes agreed upon in a given month.</p>
    <form method="get" class="inline" action="dashboard_anna.php">
        <label>Year: <input type="number" name="year" value="<?php echo $year; ?>" style="width:80px;"></label>
        <label>Month: <input type="number" name="month" value="<?php echo $month; ?>" min="1" max="12" style="width:60px;"></label>
        <button type="submit">Filter</button>
    </form>
    <?php
    $sql5 = "
        SELECT q.quote_id, q.request_id, q.created_at, q.status,
               c.client_id, c.first_name, c.last_name
        FROM Quote q
        JOIN ServiceRequest sr ON q.request_id = sr.request_id
        JOIN Client c ON sr.client_id = c.client_id
        WHERE q.status = 'accepted'
          AND YEAR(q.created_at) = $year
          AND MONTH(q.created_at) = $month
        ORDER BY q.created_at DESC
    ";
    $q5 = runQuery($conn, $sql5);
    if (isset($q5['error'])) {
        echo '<p class="error">Error: ' . htmlspecialchars($q5['error']) . '</p>';
    } elseif ($q5['result']->num_rows == 0) {
        echo "<p>No accepted quotes for $year-$month.</p>";
    } else {
        echo "<table><tr><th>Quote ID</th><th>Client</th><th>Request ID</th><th>Accepted At</th></tr>";
        while ($row = $q5['result']->fetch_assoc()) {
            echo "<tr>
                    <td>{$row['quote_id']}</td>
                    <td>{$row['client_id']} - " . htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) . "</td>
                    <td>{$row['request_id']}</td>
                    <td>{$row['created_at']}</td>
                  </tr>";
        }
        echo "</table>";
    }
    ?>

    <!-- 6) Prospective clients -->
    <h2>6. Prospective Clients</h2>
    <p class="small">Clients who registered but never submitted any request.</p>
    <?php
    $sql6 = "
        SELECT c.client_id, c.first_name, c.last_name, c.email
        FROM Client c
        LEFT JOIN ServiceRequest sr ON c.client_id = sr.client_id
        WHERE sr.request_id IS NULL
    ";
    $q6 = runQuery($conn, $sql6);
    if (isset($q6['error'])) {
        echo '<p class="error">Error: ' . htmlspecialchars($q6['error']) . '</p>';
    } elseif ($q6['result']->num_rows == 0) {
        echo "<p>No prospective clients (everyone has at least one request).</p>";
    } else {
        echo "<table><tr><th>Client ID</th><th>Name</th><th>Email</th></tr>";
        while ($row = $q6['result']->fetch_assoc()) {
            echo "<tr>
                    <td>{$row['client_id']}</td>
                    <td>" . htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) . "</td>
                    <td>" . htmlspecialchars($row['email']) . "</td>
                  </tr>";
        }
        echo "</table>";
    }
    ?>

    <!-- 7) Largest job -->
    <h2>7. Largest Job</h2>
    <p class="small">Service requests with the largest number of rooms ever completed.</p>
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
    if (isset($q7['error'])) {
        echo '<p class="error">Error: ' . htmlspecialchars($q7['error']) . '</p>';
    } elseif ($q7['result']->num_rows == 0) {
        echo "<p>No completed jobs yet.</p>";
    } else {
        echo "<table><tr><th>Request ID</th><th>Order ID</th><th>Client</th><th># Rooms</th></tr>";
        while ($row = $q7['result']->fetch_assoc()) {
            echo "<tr>
                    <td>{$row['request_id']}</td>
                    <td>{$row['order_id']}</td>
                    <td>{$row['client_id']} - " . htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) . "</td>
                    <td>{$row['num_rooms']}</td>
                  </tr>";
        }
        echo "</table>";
    }
    ?>

    <!-- 8) Overdue bills -->
    <h2>8. Overdue Bills</h2>
    <p class="small">Unpaid bills older than one week.</p>
    <?php
    $sql8 = "
        SELECT b.bill_id, b.order_id, b.amount, b.generated_at, b.status
        FROM Bill b
        WHERE b.status = 'unpaid'
          AND b.generated_at < (NOW() - INTERVAL 7 DAY)
        ORDER BY b.generated_at
    ";
    $q8 = runQuery($conn, $sql8);
    if (isset($q8['error'])) {
        echo '<p class="error">Error: ' . htmlspecialchars($q8['error']) . '</p>';
    } elseif ($q8['result']->num_rows == 0) {
        echo "<p>No overdue bills.</p>";
    } else {
        echo "<table><tr><th>Bill ID</th><th>Order ID</th><th>Amount</th><th>Generated At</th></tr>";
        while ($row = $q8['result']->fetch_assoc()) {
            echo "<tr>
                    <td>{$row['bill_id']}</td>
                    <td>{$row['order_id']}</td>
                    <td>{$row['amount']}</td>
                    <td>{$row['generated_at']}</td>
                  </tr>";
        }
        echo "</table>";
    }
    ?>

    <!-- 9) Bad clients -->
    <h2>9. Bad Clients</h2>
    <p class="small">Clients who currently have overdue bills that are not paid.</p>
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
    if (isset($q9['error'])) {
        echo '<p class="error">Error: ' . htmlspecialchars($q9['error']) . '</p>';
    } elseif ($q9['result']->num_rows == 0) {
        echo "<p>No bad clients (no unpaid overdue bills).</p>";
    } else {
        echo "<table><tr><th>Client ID</th><th>Name</th></tr>";
        while ($row = $q9['result']->fetch_assoc()) {
            echo "<tr>
                    <td>{$row['client_id']}</td>
                    <td>" . htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) . "</td>
                  </tr>";
        }
        echo "</table>";
    }
    ?>

    <!-- 10) Good clients -->
    <h2>10. Good Clients</h2>
    <p class="small">Clients who always paid their bills within 24 hours of being generated.</p>
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
                   WHEN b.status = 'paid'
                    AND TIMESTAMPDIFF(HOUR, b.generated_at, p.paid_at) <= 24
                   THEN 0 
                   ELSE 1
                 END
               ) = 0
    ";
    $q10 = runQuery($conn, $sql10);
    if (isset($q10['error'])) {
        echo '<p class="error">Error: ' . htmlspecialchars($q10['error']) . '</p>';
    } elseif ($q10['result']->num_rows == 0) {
        echo "<p>No good clients yet (or no payments in system).</p>";
    } else {
        echo "<table><tr><th>Client ID</th><th>Name</th></tr>";
        while ($row = $q10['result']->fetch_assoc()) {
            echo "<tr>
                    <td>{$row['client_id']}</td>
                    <td>" . htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) . "</td>
                  </tr>";
        }
        echo "</table>";
    }
    ?>

</body>
</html>
