<?php
require_once 'db.php';

$client_id = intval($_GET['client_id'] ?? 0);
$success = "";
$error = "";

// Accept quote -> creates order
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

// Client sends counter note (negotiation)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['counter_quote_id'])) {
    $quote_id = intval($_POST['counter_quote_id']);
    $client_id = intval($_POST['client_id']);
    $note = trim($_POST['counter_note'] ?? '');

    if ($quote_id <= 0 || $client_id <= 0) {
        $error = "Invalid quote or client ID.";
    } elseif ($note === '') {
        $error = "Please type your message.";
    } else {
        $stmtM = $conn->prepare("
            INSERT INTO QuoteMessage (quote_id, sender_type, adjusted_price, scheduled_time_window, note)
            VALUES (?, 'client', NULL, NULL, ?)
        ");
        $stmtM->bind_param("is", $quote_id, $note);
        if ($stmtM->execute()) {
            $stmtM->close();

            // Mark quote as counter from client (so Anna knows)
            $stmtU = $conn->prepare("UPDATE Quote SET status = 'counter-from-client' WHERE quote_id = ?");
            $stmtU->bind_param("i", $quote_id);
            $stmtU->execute();
            $stmtU->close();

            $success = "Your message has been sent to Anna for this quote.";
        } else {
            $error = "Error sending message: " . $conn->error;
        }
    }
}

// Load quotes for this client
$quotes = [];
if ($client_id > 0) {
    $stmt = $conn->prepare("
        SELECT q.quote_id, q.request_id, q.status, q.created_at,
               sr.service_address, sr.cleaning_type, sr.num_rooms,
               sr.preferred_datetime,
               qm.adjusted_price, qm.scheduled_time_window, qm.note AS anna_note
        FROM Quote q
        JOIN ServiceRequest sr ON q.request_id = sr.request_id
        JOIN QuoteMessage qm 
          ON q.quote_id = qm.quote_id 
         AND qm.sender_type = 'anna'
        WHERE sr.client_id = ?
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
    .conversation {
        background: #ffffff;
        border-radius: 8px;
        border: 1px solid #d0e6ff;
        padding: 8px 10px;
        margin-top: 10px;
        max-height: 200px;
        overflow-y: auto;
        font-size: 13px;
    }
    .msg-anna { color: #1f3a93; }
    .msg-client { color: #b33a3a; }
    .msg-meta { font-size: 11px; color: #777; }
    textarea {
        width: 100%;
        min-height: 60px;
        padding: 8px 10px;
        border-radius: 6px;
        border: 1px solid #c3d7f2;
        margin-top: 5px;
        box-sizing: border-box;
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

            <strong>Offer from Anna:</strong><br>
            Price: $<?php echo $q['adjusted_price']; ?><br>
            Time window: <?php echo htmlspecialchars($q['scheduled_time_window']); ?><br>
            Note: <?php echo nl2br(htmlspecialchars($q['anna_note'])); ?><br>

            <div class="conversation">
                <strong>Conversation:</strong><br>
                <?php
                $stmtC = $conn->prepare("
                    SELECT sender_type, adjusted_price, scheduled_time_window, note, created_at
                    FROM QuoteMessage
                    WHERE quote_id = ?
                    ORDER BY created_at ASC
                ");
                $stmtC->bind_param("i", $q['quote_id']);
                $stmtC->execute();
                $resC = $stmtC->get_result();
                if ($resC->num_rows === 0) {
                    echo "<em>No messages yet.</em>";
                } else {
                    while ($m = $resC->fetch_assoc()) {
                        $cls = $m['sender_type'] === 'anna' ? 'msg-anna' : 'msg-client';
                        echo "<div class='{$cls}'>[" . htmlspecialchars($m['sender_type']) . "]";
                        if ($m['adjusted_price'] !== null) {
                            echo " Price: $" . $m['adjusted_price'];
                        }
                        if ($m['scheduled_time_window'] !== null && $m['scheduled_time_window'] !== '') {
                            echo " • Time: " . htmlspecialchars($m['scheduled_time_window']);
                        }
                        echo "<br>" . nl2br(htmlspecialchars($m['note']))
                           . "<br><span class='msg-meta'>at {$m['created_at']}</span></div><hr>";
                    }
                }
                $stmtC->close();
                ?>
            </div>

            <?php if ($q['status'] !== 'accepted'): ?>
                <br>
                <a class="btn-link" href="client_quotes.php?action=accept&client_id=<?php echo $client_id; ?>&quote_id=<?php echo $q['quote_id']; ?>&request_id=<?php echo $q['request_id']; ?>"
                   onclick="return confirm('Accept this quote and create an order?');">
                    Accept Quote
                </a>

                <form method="post" action="client_quotes.php?client_id=<?php echo $client_id; ?>">
                    <input type="hidden" name="counter_quote_id" value="<?php echo $q['quote_id']; ?>">
                    <input type="hidden" name="client_id" value="<?php echo $client_id; ?>">
                    <label>Send a message to Anna (e.g., "Too expensive", "Can you do morning instead?"):</label>
                    <textarea name="counter_note" required></textarea>
                    <button type="submit" class="btn">Send Message / Counter</button>
                </form>
            <?php else: ?>
                <p><em>This quote has already been accepted.</em></p>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>
</body>
</html>
