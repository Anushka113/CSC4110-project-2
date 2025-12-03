<?php
require_once 'db.php';

$order_id = intval($_GET['order_id'] ?? 0);
$success = "";
$error = "";

if ($order_id <= 0) {
    die("Invalid order ID.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = $_POST['amount'] ?? '';
    $note = trim($_POST['note'] ?? '');

    if ($amount === '') {
        $error = "Amount is required.";
    } else {
        // Create bill
$stmt = $conn->prepare("
    INSERT INTO Bill (order_id, status, amount)
    VALUES (?, 'unpaid', ?)
");
$stmt->bind_param("id", $order_id, $amount);

        if ($stmt->execute()) {
            $bill_id = $stmt->insert_id;
            $stmt->close();

            // Add first BillMessage from Anna
$stmt2 = $conn->prepare("
    INSERT INTO BillMessage (bill_id, sender_type, note)
    VALUES (?, 'anna', ?)
");
$stmt2->bind_param("is", $bill_id, $note);
            $stmt2->execute();
            $stmt2->close();

            $success = "Bill created successfully! Bill ID: " . $bill_id;
        } else {
            $error = "Error creating bill: " . $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Generate Bill</title>
    <style>
        body { font-family: Arial; max-width: 800px; margin: 30px auto; }
        input, textarea { width: 100%; padding: 8px; margin-top: 4px; }
        .btn { padding: 8px 16px; }
        .success { background: #e0ffe0; padding: 10px; }
        .error { background: #ffe0e0; padding: 10px; }
    </style>
</head>
<body>
    <h1>Generate Bill for Order #<?php echo $order_id; ?></h1>
    <p><a href="anna_orders.php">&larr; Back to Orders</a></p>

    <?php if ($success): ?>
        <div class="success"><?php echo $success; ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="error"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="post">
        <label>Amount:
            <input type="number" step="0.01" name="amount" required>
        </label>
        <label>Note (optional):
            <textarea name="note"></textarea>
        </label>
        <button class="btn" type="submit">Create Bill</button>
    </form>
</body>
</html>
