<?php
require_once 'db.php';

$client_id = intval($_GET['client_id'] ?? 0);
$success = "";
$error = "";

// Handle accept action
if (isset($_GET['action'], $_GET['quote_id'], $_GET['request_id'], $_GET['client_id']) 
    && $_GET['action'] === 'accept') {

    $client_id = intval($_GET['client_id']);
    $quote_id = intval($_GET['quote_id']);
    $request_id = intval($_GET['request_id']);

    if ($client_id > 0 && $quote_id > 0 && $request_id > 0) {
        // 1) Mark quote as accepted
        $stmtQ = $conn->prepare("UPDATE Quote SET status = 'accepted' WHERE quote_id = ?");
        $stmtQ->bind_param("i", $quote_id);
        if ($stmtQ->execute()) {
            $stmtQ->close();

            // 2) Create Order
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

// Reload quotes after actions
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
        body { font-family: Arial, sans-serif; max-width: 1000px; margin: 30px auto; }
        h1 { margin-bottom: 10px; }
        form.inline { display: inline-block; margin-bottom: 10px; }
        .box { border: 1px solid #ccc; padding: 15px; border-radius: 5px; margin-bottom: 10px; }
        .message { margin-top: 10px; padding: 10px; border-radius: 4px; }
        .success { background-color: #e0ffe0; border: 1px solid #5cb85c; }
        .error { background-color: #ffe0e0; border: 1px solid #d9534f; }
        a { text-decoration: none; color: #007bff; }
        .btn { padding: 6px 12px; border: 1px solid #007bff; border-radius: 4px; cursor: pointer; background: #fff; }
        .status-tag { font-size: 0.9em; padding: 2px 6px; border-radius: 3px; }
    </style>
</head>
<body>
    <h1>View Quotes</h1>
    <p><a href="index.php">&larr; Back to Home</a></p>

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
        <div class="box">
            <strong>Quote #<?php echo $q['quote_id']; ?></strong> 
            for Request #<?php echo $q['request_id']; ?> 
            <span class="status-tag">Status: <?php echo htmlspecialchars($q['status']); ?></span><br>
            Address: <?php echo htmlspecialchars($q['service_address']); ?>,
            Type: <?php echo htmlspecialchars($q['cleaning_type']); ?>,
            Rooms: <?php echo $q['num_rooms']; ?><br>
            Preferred: <?php echo $q['preferred_datetime']; ?><br>
            <strong>Price:</strong> $<?php echo $q['adjusted_price']; ?>  
            <strong>Time window:</strong> <?php echo htmlspecialchars($q['scheduled_time_window']); ?><br>
            Note from Anna: <?php echo nl2br(htmlspecialchars($q['note'])); ?><br><br>

            <?php if ($q['status'] === 'pending-client'): ?>
                <a class="btn" href="client_quotes.php?action=accept&client_id=<?php echo $client_id; ?>&quote_id=<?php echo $q['quote_id']; ?>&request_id=<?php echo $q['request_id']; ?>"
                   onclick="return confirm('Accept this quote and create an order?');">
                    Accept Quote
                </a>
            <?php else: ?>
                (Already <?php echo htmlspecialchars($q['status']); ?>)
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</body>
</html>
