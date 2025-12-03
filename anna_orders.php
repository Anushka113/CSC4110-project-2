<?php
require_once 'db.php';

$sql = "
    SELECT o.order_id, o.status,
           sr.request_id, sr.service_address, sr.cleaning_type,
           c.client_id, c.first_name, c.last_name
    FROM `Order` o
    JOIN ServiceRequest sr ON o.request_id = sr.request_id
    JOIN Client c ON o.client_id = c.client_id
    ORDER BY o.order_id DESC
";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Anna - Orders</title>
<style>
    body { font-family: 'Segoe UI', Arial, sans-serif; background: #f4f7fb; margin: 0; }
    header { background: #4a90e2; color: #fff; padding: 18px 30px; box-shadow: 0 3px 8px rgba(0,0,0,0.15); }
    header h1 { margin: 0; font-size: 24px; }
    .container {
        max-width: 1000px;
        margin: 30px auto;
        background: #fff;
        padding: 25px 30px;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    a.back { text-decoration: none; color: #4a90e2; font-size: 13px; }
    table { width: 100%; border-collapse: collapse; margin-top: 15px; }
    th, td { border: 1px solid #d0e6ff; padding: 8px 10px; text-align: left; }
    th { background: #bee3ff; }
    tr:nth-child(even) td { background: #f9fcff; }
    tr:hover td { background: #eef7ff; }
    .btn {
        display: inline-block;
        padding: 5px 10px;
        border-radius: 5px;
        border: 1px solid #4a90e2;
        text-decoration: none;
        color: #4a90e2;
        font-size: 13px;
        background: #fff;
    }
    .btn:hover { background: #e7f2ff; }
</style>
</head>
<body>
<header>
    <h1>Anna - Orders</h1>
</header>

<div class="container">
    <p><a class="back" href="index.php">&larr; Back to Home</a></p>

    <?php if ($result && $result->num_rows > 0): ?>
        <table>
            <tr>
                <th>Order ID</th>
                <th>Client</th>
                <th>Request</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td>#<?php echo $row['order_id']; ?></td>
                    <td><?php echo htmlspecialchars($row['first_name'] . " " . $row['last_name']); ?> (ID: <?php echo $row['client_id']; ?>)</td>
                    <td>
                        Request #<?php echo $row['request_id']; ?><br>
                        <?php echo htmlspecialchars($row['cleaning_type']); ?> cleaning<br>
                        <?php echo htmlspecialchars($row['service_address']); ?>
                    </td>
                    <td><?php echo htmlspecialchars($row['status']); ?></td>
                    <td>
                        <a class="btn" href="bill_generate.php?order_id=<?php echo $row['order_id']; ?>">Generate Bill</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </table>
    <?php else: ?>
        <p>No orders yet.</p>
    <?php endif; ?>
</div>
</body>
</html>
