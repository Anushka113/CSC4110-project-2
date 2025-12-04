<?php
require_once 'db.php';

$request_id = intval($_GET['request_id'] ?? 0);
$success = "";
$error = "";

if ($request_id <= 0) {
    die("Invalid request ID.");
}

// Load request + client info
$stmt = $conn->prepare("
    SELECT sr.*, c.first_name, c.last_name
    FROM ServiceRequest sr
    JOIN Client c ON sr.client_id = c.client_id
    WHERE sr.request_id = ?
");
$stmt->bind_param("i", $request_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    die("Service request not found.");
}
$request = $result->fetch_assoc();
$stmt->close();

// Check if there is already a quote for this request
$quote_id = null;
$stmtQ = $conn->prepare("
    SELECT quote_id, status, created_at
    FROM Quote
    WHERE request_id = ?
    ORDER BY created_at DESC
    LIMIT 1
");
$stmtQ->bind_param("i", $request_id);
$stmtQ->execute();
$resQ = $stmtQ->get_result();
$existingQuote = $resQ->fetch_assoc();
if ($existingQuote) {
    $quote_id = $existingQuote['quote_id'];
}
$stmtQ->close();

// Handle Anna's form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $price = $_POST['adjusted_price'] ?? '';
    $time_window = trim($_POST['time_window'] ?? '');
    $note = trim($_POST['note'] ?? '');

    if ($price === '' && $note === '' && $time_window === '') {
        $error = "Please enter at least a note or price/time window.";
    } else {
        if ($quote_id === null) {
            // No quote yet -> create quote + first message
            $stmtQ2 = $conn->prepare("
                INSERT INTO Quote (request_id, status)
                VALUES (?, 'pending-client')
            ");
            $stmtQ2->bind_param("i", $request_id);
            if ($stmtQ2->execute()) {
                $quote_id = $stmtQ2->insert_id;
                $stmtQ2->close();

                $stmtM = $conn->prepare("
                    INSERT INTO QuoteMessage (quote_id, sender_type, adjusted_price, scheduled_time_window, note)
                    VALUES (?, 'anna', ?, ?, ?)
                ");
                $stmtM->bind_param("idss", $quote_id, $price, $time_window, $note);
                if ($stmtM->execute()) {
                    $success = "Quote created and sent to client. Quote ID: " . $quote_id;
                } else {
                    $error = "Error saving quote message: " . $conn->error;
                }
                $stmtM->close();
            } else {
                $error = "Error creating quote: " . $conn->error;
            }
        } else {
            // Existing quote -> add another Anna message and set status back to pending-client
            $stmtM = $conn->prepare("
                INSERT INTO QuoteMessage (quote_id, sender_type, adjusted_price, scheduled_time_window, note)
                VALUES (?, 'anna', ?, ?, ?)
            ");
            $stmtM->bind_param("idss", $quote_id, $price === '' ? null : $price, $time_window, $note);
            if ($stmtM->execute()) {
                $stmtM->close();

                $stmtU = $conn->prepare("UPDATE Quote SET status = 'pending-client' WHERE quote_id = ?");
                $stmtU->bind_param("i", $quote_id);
                $stmtU->execute();
                $stmtU->close();

                $success = "Your new quote message was sent to the client (Quote ID: $quote_id).";
            } else {
                $error = "Error saving message: " . $conn->error;
            }
        }
    }
}

// Load conversation messages (if quote exists)
$messages = [];
if ($quote_id !== null) {
    $stmtM2 = $conn->prepare("
        SELECT sender_type, adjusted_price, scheduled_time_window, note, created_at
        FROM QuoteMessage
        WHERE quote_id = ?
        ORDER BY created_at ASC
    ");
    $stmtM2->bind_param("i", $quote_id);
    $stmtM2->execute();
    $resM2 = $stmtM2->get_result();
    while ($row = $resM2->fetch_assoc()) {
        $messages[] = $row;
    }
    $stmtM2->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Send / Revise Quote</title>
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
    .box {
        border-radius: 10px;
        border: 1px solid #d0e6ff;
        background: #f4f9ff;
        padding: 15px 18px;
        margin-top: 10px;
    }
    .conversation {
        background: #ffffff;
        border-radius: 8px;
        border: 1px solid #d0e6ff;
        padding: 10px 12px;
        margin-top: 10px;
        max-height: 240px;
        overflow-y: auto;
        font-size: 13px;
    }
    .msg-anna { color: #1f3a93; }
    .msg-client { color: #b33a3a; }
    .msg-meta { font-size: 11px; color: #777; }
    label { display: block; margin-top: 10px; font-size: 14px; color: #333; }
    input[type="text"], input[type="number"], textarea {
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
    <h1>Send / Revise Quote for Request #<?php echo $request_id; ?></h1>
</header>

<div class="container">
    <p><a class="back" href="anna_requests.php">&larr; Back to Pending Requests</a></p>

    <div class="box">
        <h3>Request Details</h3>
        <p>
            <strong>Client:</strong> <?php echo htmlspecialchars($request['first_name'] . ' ' . $request['last_name']); ?>
            (ID: <?php echo $request['client_id']; ?>)<br>
            <strong>Address:</strong> <?php echo htmlspecialchars($request['service_address']); ?><br>
            <strong>Type:</strong> <?php echo htmlspecialchars($request['cleaning_type']); ?><br>
            <strong>Rooms:</strong> <?php echo $request['num_rooms']; ?><br>
            <strong>Preferred:</strong> <?php echo $request['preferred_datetime']; ?><br>
            <strong>Proposed Budget:</strong> <?php echo $request['proposed_budget']; ?><br>
            <strong>Notes:</strong> <?php echo nl2br(htmlspecialchars($request['notes'])); ?>
        </p>
    </div>

    <?php if ($quote_id !== null): ?>
        <div class="box">
            <h3>Existing Quote Conversation (Quote ID: <?php echo $quote_id; ?>)</h3>
            <div class="conversation">
                <?php if (empty($messages)): ?>
                    <em>No messages yet.</em>
                <?php else: ?>
                    <?php foreach ($messages as $m): ?>
                        <?php $cls = $m['sender_type'] === 'anna' ? 'msg-anna' : 'msg-client'; ?>
                        <div class="<?php echo $cls; ?>">
                            [<?php echo htmlspecialchars($m['sender_type']); ?>]
                            <?php if ($m['adjusted_price'] !== null): ?>
                                Price: $<?php echo $m['adjusted_price']; ?>
                            <?php endif; ?>
                            <?php if ($m['scheduled_time_window'] !== null && $m['scheduled_time_window'] !== ''): ?>
                                • Time: <?php echo htmlspecialchars($m['scheduled_time_window']); ?>
                            <?php endif; ?>
                            <br>
                            <?php echo nl2br(htmlspecialchars($m['note'])); ?>
                            <br><span class="msg-meta">at <?php echo $m['created_at']; ?></span>
                        </div>
                        <hr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="message success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="message error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="box">
        <h3><?php echo $quote_id === null ? 'Create First Quote' : 'Send New Quote Response'; ?></h3>
        <form method="post">
            <label>Adjusted Price (optional, e.g., 120.00):
                <input type="number" step="0.01" name="adjusted_price">
            </label>

            <label>Time Window (optional, e.g., "Dec 10, 2–4 PM"):
                <input type="text" name="time_window">
            </label>

            <label>Note to Client:</label>
            <textarea name="note" placeholder="Explain your offer, address their concerns, etc."></textarea>

            <button type="submit" class="btn">
                <?php echo $quote_id === null ? 'Create Quote' : 'Send Response to Client'; ?>
            </button>
        </form>
    </div>
</div>
</body>
</html>
