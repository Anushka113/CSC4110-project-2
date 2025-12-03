<?php
require_once 'db.php';

$client_id = intval($_GET['client_id'] ?? 0);
$success = "";
$error = "";

if (isset($_GET['action'], $_GET['quote_id'], $_GET['request_id'], $_GET['client_id']) 
    && $_GET['action'] === 'accept') {

    $client_id = intval($_GET['client_id']);
    $quote_id = intval($_GET['quote_id']);
    $request_id = intval($_GET['request_id']);

    if ($client_id > 0 && $quote_id > 0 && $request_id > 0) {
        $stmtQ = $conn->prepare("UPDATE Quote SET status = 'accepted' WHERE quote_id = ?");
        $stmtQ->bind_param("i", $quote_id);
        if ($stmtQ->execute()) {
            $stmtQ->close();

            $stmtO = $conn->prepare("
                INSERT INTO `Order` (request_id, quote_id, client_id, status)
                VALUES (?, ?, ?, 'scheduled')
            ");
            $stmtO->bind_param("iii", $request_id, $quote_id, $client_id);

            if ($stmtO->execute()) {
                $order_id = $stmtO->insert_id;
                $success = "Quote accepted! Order #$order_id has been created.";
            } else {
                $error = "Error creating order: " . $conn->error;
            }
            $stmtO->close();
        } else {
            $error = "Error updating quote: " . $conn->error;
        }
    }
}

$quotes = [];
if ($client_id > 0) {
    $stmt = $conn->prepare("
        SELECT q.quote_id, q.request_id, q.status, q.created_at,
               sr.service_address, sr.cleaning_type, sr.num_rooms,
               sr.preferred_datetime,
               qm.adjusted_price, qm.scheduled_time_window, qm.note
        FROM Quote q
        JOIN ServiceRequest sr ON q.request_id = sr.request_id
        JOIN QuoteMessage qm ON q.quote_id = qm.quote_id
        WHERE sr.client_id = ?
          AND qm.sender_type = 'anna'
        ORDER BY q.created_at DESC
    ");
    $stmt->bind_param("i", $client_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $quotes[] = $row;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Client Quotes</title>
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
    .quote-card {
        border-radius: 10px;
        border: 1px solid #d0e6ff;
        background: #f4f9ff;
        padding: 12px 15px;
        margin-top: 12px;
    }
    .status-tag {
        display: inline-block;
        padding: 2px 8px;
        border-radius: 10px;
        font-size: 11px;
        background: #eef3ff;
        border: 1px solid #c3d7f2;
        margin-left: 5px;
    }
</style>
</head>
<body>
<header>
    <h1>Client - View Quotes</h1>
</header>

<div class="container">
    <p><a class="back" href="index.php">&larr; Back to Home</a></p>

    <form method="get" class="inline" action="client_quotes.php">
        <label>
            Enter your Client ID:
            <input type="number" name="client_id" value="<?php echo $client_id > 0 ? $client_id : ''; ?>" required>
        </label>
        <button type="submit" class="btn">View Quotes</button>
    </form>

    <?php if ($success): ?>
        <div class="message success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="message error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if ($client_id > 0 && empty($quotes)): ?>
        <p>No quotes found for this client.</p>
    <?php endif; ?>

    <?php foreach ($quotes as $q): ?>
        <div class="quote-card">
            <strong>Quote #<?php echo $q['quote_id']; ?></strong>
            <span class="status-tag">Status: <?php echo htmlspecialchars($q['status']); ?></span><br>
            <small>Request #<?php echo $q['request_id']; ?> • Address: <?php echo htmlspecialchars($q['service_address']); ?></small><br>
            Type: <?php echo htmlspecialchars($q['cleaning_type']); ?> • Rooms: <?php echo $q['num_rooms']; ?><br>
            Preferred: <?php echo $q['preferred_datetime']; ?><br><br>
            <strong>Price:</strong> $<?php echo $q['adjusted_price']; ?> <br>
            <strong>Time window:</strong> <?php echo htmlspecialchars($q['scheduled_time_window']); ?><br>
            <strong>Note from Anna:</strong> <?php echo nl2br(htmlspecialchars($q['note'])); ?><br><br>

            <?php if ($q['status'] === 'pending-client'): ?>
                <a class="btn-link" href="client_quotes.php?action=accept&client_id=<?php echo $client_id; ?>&quote_id=<?php echo $q['quote_id']; ?>&request_id=<?php echo $q['request_id']; ?>"
                   onclick="return confirm('Accept this quote and create an order?');">
                    Accept Quote
                </a>
            <?php else: ?>
                <em>Already <?php echo htmlspecialchars($q['status']); ?></em>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>
</body>
</html>
