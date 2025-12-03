<?php
require_once 'db.php';

$request_id = intval($_GET['request_id'] ?? 0);
$success = "";
$error = "";
$request = null;

if ($request_id <= 0) {
    die("Invalid request ID.");
}

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $price = $_POST['adjusted_price'] ?? '';
    $time_window = trim($_POST['time_window'] ?? '');
    $note = trim($_POST['note'] ?? '');

    if ($price === '' || $time_window === '') {
        $error = "Price and time window are required.";
    } else {
        $stmtQ = $conn->prepare("
            INSERT INTO Quote (request_id, status)
            VALUES (?, 'pending-client')
        ");
        $stmtQ->bind_param("i", $request_id);

        if ($stmtQ->execute()) {
            $quote_id = $stmtQ->insert_id;
            $stmtQ->close();

            $stmtM = $conn->prepare("
                INSERT INTO QuoteMessage (quote_id, sender_type, adjusted_price, scheduled_time_window, note)
                VALUES (?, 'anna', ?, ?, ?)
            ");
            $stmtM->bind_param("idss", $quote_id, $price, $time_window, $note);

            if ($stmtM->execute()) {
                $success = "Quote sent successfully! Quote ID: " . $quote_id;
            } else {
                $error = "Error saving quote message: " . $conn->error;
            }
            $stmtM->close();
        } else {
            $error = "Error creating quote: " . $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Send Quote</title>
<style>
    body { font-family: 'Segoe UI', Arial, sans-serif; background: #f4f7fb; margin: 0; }
    header { background: #4a90e2; color: #fff; padding: 18px 30px; box-shadow: 0 3px 8px rgba(0,0,0,0.15); }
    header h1 { margin: 0; font-size: 24px; }
    .container {
        max-width: 800px;
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
    <h1>Send Quote for Request #<?php echo $request_id; ?></h1>
</header>

<div class="container">
    <p><a class="back" href="anna_requests.php">&larr; Back to Pending Requests</a></p>

    <div class="box">
        <h3>Request Details</h3>
        <p>
            <strong>Client:</strong> <?php echo htmlspecialchars($request['first_name'] . ' ' . $request['last_name']); ?> (ID: <?php echo $request['client_id']; ?>)<br>
            <strong>Address:</strong> <?php echo htmlspecialchars($request['service_address']); ?><br>
            <strong>Type:</strong> <?php echo htmlspecialchars($request['cleaning_type']); ?><br>
            <strong>Rooms:</strong> <?php echo $request['num_rooms']; ?><br>
            <strong>Preferred:</strong> <?php echo $request['preferred_datetime']; ?><br>
            <strong>Proposed Budget:</strong> <?php echo $request['proposed_budget']; ?><br>
            <strong>Notes:</strong> <?php echo nl2br(htmlspecialchars($request['notes'])); ?>
        </p>
    </div>

    <?php if ($success): ?>
        <div class="message success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="message error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="box">
        <h3>Quote to Client</h3>
        <form method="post">
            <label>Adjusted Price:
                <input type="number" step="0.01" name="adjusted_price" required>
            </label>

            <label>Time Window (e.g., "Dec 10, 2–4 PM"):
                <input type="text" name="time_window" required>
            </label>

            <label>Note to Client (optional):
                <textarea name="note"></textarea>
            </label>

            <button type="submit" class="btn">Send Quote</button>
        </form>
    </div>
</div>
</body>
</html>
