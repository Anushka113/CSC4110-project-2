<?php
require_once 'db.php';

$message = "";

// Load all orders
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
        body { font-family: Arial, sans-serif; max-width: 1000px; margin: 30px auto; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        th { background: #f0f0f0; }
        a.btn { padding: 4px 8px; border: 1px solid #007bff; border-radius: 4px; text-decoration: none; }
    </style>
</head>
<body>
    <h1>Orders</h1>
    <p><a href="index.php">&larr; Back to Home</a></p>

    <?php if ($result->num_rows > 0): ?>
    <table>
        <tr>
            <th>Order ID</th>
            <th>Client</th>
            <th>Request</th>
            <th>Actions</th>
        </tr>
        <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td>#<?php echo $row['order_id']; ?></td>
            <td><?php echo $row['first_name'] . " " . $row['last_name']; ?> (ID: <?php echo $row['client_id']; ?>)</td>
            <td>
                Request #<?php echo $row['request_id']; ?><br>
                <?php echo $row['cleaning_type']; ?> cleaning<br>
                <?php echo $row['service_address']; ?>
            </td>
            <td>
                <a class="btn" href="bill_generate.php?order_id=<?php echo $row['order_id']; ?>">Generate Bill</a>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
    <?php else: ?>
        <p>No orders yet.</p>
    <?php endif; ?>
</body>
</html>
