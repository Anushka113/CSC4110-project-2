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
        $stmt = $conn->prepare("
            INSERT INTO Bill (order_id, status, amount, generated_at)
            VALUES (?, 'unpaid', ?, NOW())
        ");
        $stmt->bind_param("id", $order_id, $amount);

        if ($stmt->execute()) {
            $bill_id = $stmt->insert_id;
            $stmt->close();

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
    body { font-family: 'Segoe UI', Arial, sans-serif; background: #f4f7fb; margin: 0; }
    header { background: #4a90e2; color: #fff; padding: 18px 30px; box-shadow: 0 3px 8px rgba(0,0,0,0.15); }
    header h1 { margin: 0; font-size: 24px; }
    .container {
        max-width: 700px;
        margin: 30px auto;
        background: #fff;
        padding: 25px 30px;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    a.back { text-decoration: none; color: #4a90e2; font-size: 13px; }
    label { display: block; margin-top: 10px; font-size: 14px; color: #333; }
    input[type="number"], textarea {
        width: 100%;
        padding: 8px 10px;
        margin-top: 3px;
        border-radius: 6px;
        border: 1px solid #c3d7f2;
        box-sizing: border-box;
    }
    textarea { min-height: 80px; }
    .btn {
        margin-top: 15px;
        padding: 9px 18px;
        border-radius: 6px;
        border: none;
        background: #4a90e2;
        color: #fff;
        cursor: pointer;
        font-size: 14px;
    }
    .btn:hover { background: #3b7ccc; }
    .message { margin-top: 15px; padding: 10px 12px; border-radius: 6px; font-size: 14px; }
    .success { background: #e0ffe0; border: 1px solid #5cb85c; color: #2f6b2f; }
    .error { background: #ffe0e0; border: 1px solid #d9534f; color: #8a1a1a; }
</style>
</head>
<body>
<header>
    <h1>Generate Bill for Order #<?php echo $order_id; ?></h1>
</header>

<div class="container">
    <p><a class="back" href="anna_orders.php">&larr; Back to Orders</a></p>

    <?php if ($success): ?>
        <div class="message success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="message error"><?php echo htmlspecialchars($error); ?></div>
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
</div>
</body>
</html>
