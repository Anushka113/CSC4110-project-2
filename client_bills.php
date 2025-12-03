<?php
require_once 'db.php';

$client_id = intval($_GET['client_id'] ?? 0);
$success = "";
$error = "";

// Pay action
if (isset($_GET['action'], $_GET['bill_id'], $_GET['client_id']) && $_GET['action'] === 'pay') {
    $client_id = intval($_GET['client_id']);
    $bill_id = intval($_GET['bill_id']);

    $stmt = $conn->prepare("SELECT amount FROM Bill WHERE bill_id = ?");
    $stmt->bind_param("i", $bill_id);
    $stmt->execute();
    $stmt->bind_result($amount);
    if ($stmt->fetch()) {
        $stmt->close();

        $stmtP = $conn->prepare("
            INSERT INTO Payment (bill_id, paid_amount, paid_at, payment_method, status)
            VALUES (?, ?, NOW(), 'credit_card', 'successful')
        ");
        $stmtP->bind_param("id", $bill_id, $amount);
        if ($stmtP->execute()) {
            $stmtP->close();

            $stmtU = $conn->prepare("UPDATE Bill SET status = 'paid' WHERE bill_id = ?");
            $stmtU->bind_param("i", $bill_id);
            $stmtU->execute();
            $stmtU->close();

            $success = "Bill #$bill_id has been paid successfully.";
        } else {
            $error = "Error processing payment: " . $conn->error;
        }
    } else {
        $error = "Bill not found.";
        $stmt->close();
    }
}

// Dispute action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['dispute_bill_id'])) {
    $bill_id = intval($_POST['dispute_bill_id']);
    $note = trim($_POST['note'] ?? '');

    if ($note === '') {
        $error = "Please enter a dispute reason.";
    } else {
        $stmt = $conn->prepare("
            INSERT INTO BillMessage (bill_id, sender_type, note)
            VALUES (?, 'client', ?)
        ");
        $stmt->bind_param("is", $bill_id, $note);
        if ($stmt->execute()) {
            $stmt->close();

            $stmtU = $conn->prepare("UPDATE Bill SET status = 'disputed' WHERE bill_id = ?");
            $stmtU->bind_param("i", $bill_id);
            $stmtU->execute();
            $stmtU->close();

            $success = "Dispute submitted for Bill #$bill_id.";
        } else {
            $error = "Error submitting dispute: " . $conn->error;
        }
    }
}

$bills = [];
if ($client_id > 0) {
    $stmt = $conn->prepare("
        SELECT b.bill_id, b.order_id, b.amount, b.generated_at, b.status,
               bm.note AS anna_note
        FROM Bill b
        JOIN `Order` o ON b.order_id = o.order_id
        LEFT JOIN BillMessage bm
          ON b.bill_id = bm.bill_id AND bm.sender_type = 'anna'
        WHERE o.client_id = ?
        ORDER BY b.generated_at DESC
    ");
    $stmt->bind_param("i", $client_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $bills[] = $row;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Client Bills</title>
<style>
    body { font-family: 'Segoe UI', Arial, sans-serif; background: #f4f7fb; margin: 0; }
    header { background: #4a90e2; color: #fff; padding: 18px 30px; box-shadow: 0 3px 8px rgba(0,0,0,0.15); }
    header h1 { margin: 0; font-size: 24px; }
    .container {
        max-width: 900px;
        margin: 30px auto;
        background: #fff;
        padding: 25px 30px;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    a.back { text-decoration: none; color: #4a90e2; font-size: 13px; }
    form.inline { margin-top: 15px; }
    input[type="number"] {
        padding: 6px 10px;
        border-radius: 6px;
        border: 1px solid #c3d7f2;
    }
    .btn {
        padding: 7px 14px;
        border-radius: 6px;
        border: none;
        background: #4a90e2;
        color: #fff;
        cursor: pointer;
        font-size: 13px;
        margin-left: 5px;
    }
    .btn:hover { background: #3b7ccc; }
    .btn-link {
        display: inline-block;
        padding: 6px 12px;
        border-radius: 6px;
        border: 1px solid #4a90e2;
        color: #4a90e2;
        text-decoration: none;
        font-size: 13px;
        background: #fff;
    }
    .btn-link:hover { background: #e7f2ff; }
    .message { margin-top: 15px; padding: 10px 12px; border-radius: 6px; font-size: 14px; }
    .success { background: #e0ffe0; border: 1px solid #5cb85c; color: #2f6b2f; }
    .error { background: #ffe0e0; border: 1px solid #d9534f; color: #8a1a1a; }
    .bill-card {
        border-radius: 10px;
        border: 1px solid #d0e6ff;
        background: #f4f9ff;
        padding: 12px 15px;
        margin-top: 12px;
    }
    textarea {
        width: 100%;
        min-height: 60px;
        padding: 8px 10px;
        border-radius: 6px;
        border: 1px solid #c3d7f2;
        margin-top: 5px;
        box-sizing: border-box;
    }
    .status-tag {
        display: inline-block;
        padding: 2px 8px;
        border-radius: 10px;
        font-size: 11px;
        margin-left: 5px;
    }
    .status-unpaid { background: #ffeecf; border: 1px solid #ffb84d; }
    .status-disputed { background: #ffd4d4; border: 1px solid #d9534f; }
    .status-paid { background: #d4ffd4; border: 1px solid #5cb85c; }
</style>
</head>
<body>
<header>
    <h1>Your Bills</h1>
</header>

<div class="container">
    <p><a class="back" href="index.php">&larr; Back to Home</a></p>

    <form method="get" class="inline" action="client_bills.php">
        <label>Enter Client ID:
            <input type="number" name="client_id" value="<?php echo $client_id > 0 ? $client_id : ''; ?>" required>
        </label>
        <button type="submit" class="btn">View Bills</button>
    </form>

    <?php if ($success): ?>
        <div class="message success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="message error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if ($client_id > 0): ?>
        <?php if (count($bills) === 0): ?>
            <p>No bills found for this client.</p>
        <?php else: ?>
            <?php foreach ($bills as $b): ?>
                <?php
                    $statusClass = 'status-unpaid';
                    if ($b['status'] === 'paid') $statusClass = 'status-paid';
                    elseif ($b['status'] === 'disputed') $statusClass = 'status-disputed';
                ?>
                <div class="bill-card">
                    <h3>
                        Bill #<?php echo $b['bill_id']; ?>
                        <span class="status-tag <?php echo $statusClass; ?>">
                            <?php echo htmlspecialchars($b['status']); ?>
                        </span>
                    </h3>
                    <p>
                        Order ID: <?php echo $b['order_id']; ?><br>
                        Amount: $<?php echo $b['amount']; ?><br>
                        Generated at: <?php echo $b['generated_at']; ?><br>
                        Note from Anna: <?php echo nl2br(htmlspecialchars($b['anna_note'] ?? '')); ?>
                    </p>

                    <?php if ($b['status'] === 'unpaid'): ?>
                        <p>
                            <a class="btn-link" href="client_bills.php?action=pay&bill_id=<?php echo $b['bill_id']; ?>&client_id=<?php echo $client_id; ?>"
                               onclick="return confirm('Pay this bill now?');">
                                Pay Bill
                            </a>
                        </p>
                        <form method="post" action="client_bills.php?client_id=<?php echo $client_id; ?>">
                            <input type="hidden" name="dispute_bill_id" value="<?php echo $b['bill_id']; ?>">
                            <label>Dispute this bill (reason):</label>
                            <textarea name="note" required></textarea>
                            <button type="submit" class="btn">Submit Dispute</button>
                        </form>
                    <?php elseif ($b['status'] === 'disputed'): ?>
                        <p><strong>This bill is under dispute.</strong></p>
                    <?php elseif ($b['status'] === 'paid'): ?>
                        <p><strong>Paid ✔</strong></p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    <?php endif; ?>
</div>
</body>
</html>
