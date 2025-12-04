<?php
require_once 'db.php';

$success = "";
$error = "";

// Handle Anna's revision / response
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bill_id'])) {
    $bill_id = intval($_POST['bill_id']);
    $new_amount = trim($_POST['new_amount'] ?? '');
    $note = trim($_POST['note'] ?? '');

    if ($bill_id <= 0) {
        $error = "Invalid bill ID.";
    } else {
        // If Anna entered new amount, update the bill amount
        if ($new_amount !== '') {
            $amt = floatval($new_amount);
            $stmtU = $conn->prepare("UPDATE Bill SET amount = ?, status = 'unpaid' WHERE bill_id = ?");
            $stmtU->bind_param("di", $amt, $bill_id);
            if (!$stmtU->execute()) {
                $error = "Error updating amount: " . $conn->error;
            }
            $stmtU->close();
        } else {
            // If no amount change, we still might want to reset from disputed to unpaid
            if ($note !== '') {
                $stmtU = $conn->prepare("UPDATE Bill SET status = 'unpaid' WHERE bill_id = ?");
                $stmtU->bind_param("i", $bill_id);
                $stmtU->execute();
                $stmtU->close();
            }
        }

        // Add Anna's response as BillMessage (if note given)
        if ($note !== '' && $error === "") {
            $stmtM = $conn->prepare("
                INSERT INTO BillMessage (bill_id, sender_type, note)
                VALUES (?, 'anna', ?)
            ");
            $stmtM->bind_param("is", $bill_id, $note);
            if (!$stmtM->execute()) {
                $error = "Error saving Anna's message: " . $conn->error;
            }
            $stmtM->close();
        }

        if ($error === "") {
            $success = "Bill #$bill_id updated. Status set to UNPAID so the client can review and pay.";
        }
    }
}

// Load all disputed bills
$sql = "
    SELECT b.bill_id, b.order_id, b.amount, b.generated_at, b.status,
           c.client_id, c.first_name, c.last_name
    FROM Bill b
    JOIN `Order` o ON b.order_id = o.order_id
    JOIN Client c ON o.client_id = c.client_id
    WHERE b.status = 'disputed'
    ORDER BY b.generated_at ASC
";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Anna - Billing (Disputed Bills)</title>
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
    .message { margin-top: 10px; padding: 10px 12px; border-radius: 6px; font-size: 14px; }
    .success { background: #e0ffe0; border: 1px solid #5cb85c; color: #2f6b2f; }
    .error { background: #ffe0e0; border: 1px solid #d9534f; color: #8a1a1a; }
    .bill-card {
        border-radius: 10px;
        border: 1px solid #d0e6ff;
        background: #f4f9ff;
        padding: 12px 15px;
        margin-top: 15px;
    }
    .conversation {
        background: #ffffff;
        border-radius: 8px;
        border: 1px solid #d0e6ff;
        padding: 10px 12px;
        margin-top: 10px;
        max-height: 220px;
        overflow-y: auto;
        font-size: 13px;
    }
    .msg-anna { color: #1f3a93; }
    .msg-client { color: #b33a3a; }
    .msg-meta { font-size: 11px; color: #777; }
    label { display: block; margin-top: 8px; font-size: 14px; color: #333; }
    input[type="number"], textarea {
        width: 100%;
        padding: 8px 10px;
        margin-top: 3px;
        border-radius: 6px;
        border: 1px solid #c3d7f2;
        box-sizing: border-box;
    }
    textarea { min-height: 70px; }
    .btn {
        margin-top: 10px;
        padding: 8px 16px;
        border-radius: 6px;
        border: none;
        background: #4a90e2;
        color: #fff;
        cursor: pointer;
        font-size: 13px;
    }
    .btn:hover { background: #3b7ccc; }
</style>
</head>
<body>
<header>
    <h1>Anna - Billing (Disputed Bills)</h1>
</header>

<div class="container">
    <p><a class="back" href="index.php">&larr; Back to Home</a></p>

    <?php if ($success): ?>
        <div class="message success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="message error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if ($result && $result->num_rows > 0): ?>
        <?php while ($b = $result->fetch_assoc()): ?>
            <div class="bill-card">
                <h3>Bill #<?php echo $b['bill_id']; ?> (Order #<?php echo $b['order_id']; ?>)</h3>
                <p>
                    Client: <?php echo htmlspecialchars($b['first_name'] . ' ' . $b['last_name']); ?> (ID: <?php echo $b['client_id']; ?>)<br>
                    Current Amount: $<?php echo $b['amount']; ?><br>
                    Generated At: <?php echo $b['generated_at']; ?><br>
                    Status: <?php echo htmlspecialchars($b['status']); ?>
                </p>

                <div class="conversation">
                    <strong>Conversation:</strong><br>
                    <?php
                    $stmtM = $conn->prepare("
                        SELECT sender_type, note, created_at
                        FROM BillMessage
                        WHERE bill_id = ?
                        ORDER BY created_at ASC
                    ");
                    $stmtM->bind_param("i", $b['bill_id']);
                    $stmtM->execute();
                    $resM = $stmtM->get_result();
                    if ($resM->num_rows === 0) {
                        echo "<em>No messages yet.</em>";
                    } else {
                        while ($m = $resM->fetch_assoc()) {
                            $cls = $m['sender_type'] === 'anna' ? 'msg-anna' : 'msg-client';
                            echo "<div class='{$cls}'>[" . htmlspecialchars($m['sender_type']) . "] "
                               . nl2br(htmlspecialchars($m['note']))
                               . "<br><span class='msg-meta'>at {$m['created_at']}</span></div><hr>";
                        }
                    }
                    $stmtM->close();
                    ?>
                </div>

                <form method="post" action="anna_billing.php">
                    <input type="hidden" name="bill_id" value="<?php echo $b['bill_id']; ?>">
                    <label>New Amount (optional – leave blank to keep same amount):</label>
                    <input type="number" step="0.01" name="new_amount" placeholder="<?php echo $b['amount']; ?>">

                    <label>Anna's Response / Explanation (optional):</label>
                    <textarea name="note" placeholder="Explain adjustments, give discount, or respond to dispute..."></textarea>

                    <button type="submit" class="btn">Update Bill & Add Response</button>
                </form>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p>No disputed bills right now.</p>
    <?php endif; ?>
</div>
</body>
</html>
