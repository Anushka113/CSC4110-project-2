<?php
require_once 'db.php';

$client_id = intval($_GET['client_id'] ?? 0);
$success = "";
$error = "";

// Handle payment action
if (isset($_GET['action'], $_GET['bill_id'], $_GET['client_id']) && $_GET['action'] === 'pay') {
    $client_id = intval($_GET['client_id']);
    $bill_id = intval($_GET['bill_id']);

    // Get bill amount
    $stmt = $conn->prepare("SELECT amount FROM Bill WHERE bill_id = ?");
    $stmt->bind_param("i", $bill_id);
    $stmt->execute();
    $stmt->bind_result($amount);
    if ($stmt->fetch()) {
        $stmt->close();

        // Insert payment
        $stmtP = $conn->prepare("
            INSERT INTO Payment (bill_id, paid_amount, paid_at, payment_method, status)
            VALUES (?, ?, NOW(), 'credit_card', 'successful')
        ");
        $stmtP->bind_param("id", $bill_id, $amount);
        if ($stmtP->execute()) {
            $stmtP->close();

            // Mark bill as paid
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

// Handle dispute submit
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

// Load bills for a client (if client_id is set)
$bills = [];
if ($client_id > 0) {
    $stmt = $conn->prepare("
        SELECT b.bill_id, b.order_id, b.amount, b.generated_at, b.status,
               o.client_id,
               bm.note AS anna_note
        FROM Bill b
        JOIN `Order` o ON b.order_id = o.order_id
        LEFT JOIN BillMessage bm 
          ON b.bill_id = bm.bill_id 
         AND bm.sender_type = 'anna'
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
        body { font-family: Arial, sans-serif; max-width: 900px; margin: 30px auto; }
        .box { border: 1px solid #ccc; padding: 15px; margin-bottom: 10px; border-radius: 5px; }
        .success { background-color: #e0ffe0; border: 1px solid #5cb85c; padding: 10px; margin-top: 10px; }
        .error { background-color: #ffe0e0; border: 1px solid #d9534f; padding: 10px; margin-top: 10px; }
        .btn { padding: 6px 10px; border-radius: 4px; border: 1px solid #007bff; background: #fff; cursor: pointer; text-decoration: none; color: #007bff; }
        textarea { width: 100%; min-height: 60px; margin-top: 5px; }
    </style>
</head>
<body>
    <h1>Your Bills</h1>
    <p><a href="index.php">&larr; Back to Home</a></p>

    <form method="get" action="client_bills.php">
        <label>
            Enter your Client ID:
            <input type="number" name="client_id" value="<?php echo $client_id > 0 ? $client_id : ''; ?>" required>
        </label>
        <button type="submit" class="btn">View Bills</button>
    </form>

    <?php if ($success): ?>
        <div class="success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if ($client_id > 0): ?>
        <?php if (count($bills) === 0): ?>
            <p>No bills found for this client.</p>
        <?php else: ?>
            <?php foreach ($bills as $b): ?>
                <div class="box">
                    <h3>Bill #<?php echo $b['bill_id']; ?> (Status: <?php echo htmlspecialchars($b['status']); ?>)</h3>
                    <p>
                        Order ID: <?php echo $b['order_id']; ?><br>
                        Amount: $<?php echo $b['amount']; ?><br>
                        Generated at: <?php echo $b['generated_at']; ?><br>
                        Note from Anna: <?php echo nl2br(htmlspecialchars($b['anna_note'] ?? '')); ?>
                    </p>

                    <?php if ($b['status'] === 'unpaid'): ?>
                        <p>
                            <a class="btn" href="client_bills.php?action=pay&bill_id=<?php echo $b['bill_id']; ?>&client_id=<?php echo $client_id; ?>"
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
</body>
</html>
