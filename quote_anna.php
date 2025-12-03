<?php
require_once 'db.php';

$request_id = intval($_GET['request_id'] ?? 0);
$success = "";
$error = "";
$request = null;

if ($request_id <= 0) {
    die("Invalid request ID.");
}

// Load request details
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

// Handle quote submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $price = $_POST['adjusted_price'] ?? '';
    $time_window = trim($_POST['time_window'] ?? '');
    $note = trim($_POST['note'] ?? '');

    if ($price === '' || $time_window === '') {
        $error = "Price and time window are required.";
    } else {
        // 1) Create Quote
        $stmtQ = $conn->prepare("
            INSERT INTO Quote (request_id, status)
            VALUES (?, 'pending-client')
        ");
        $stmtQ->bind_param("i", $request_id);

        if ($stmtQ->execute()) {
            $quote_id = $stmtQ->insert_id;
            $stmtQ->close();

            // 2) Add first QuoteMessage from Anna
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
    <title>Send Quote - Anna</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 30px auto; }
        h1 { margin-bottom: 10px; }
        .box { border: 1px solid #ccc; padding: 15px; border-radius: 5px; margin-bottom: 15px; }
        label { display: block; margin-top: 10px; }
        input[type="text"], input[type="number"], textarea {
            width: 100%; padding: 8px; margin-top: 4px; box-sizing: border-box;
        }
        textarea { min-height: 80px; }
        .btn { margin-top: 15px; padding: 8px 16px; cursor: pointer; }
        .message { margin-top: 10px; padding: 10px; border-radius: 4px; }
        .success { background-color: #e0ffe0; border: 1px solid #5cb85c; }
        .error { background-color: #ffe0e0; border: 1px solid #d9534f; }
        a { text-decoration: none; color: #007bff; }
    </style>
</head>
<body>
    <h1>Send Quote for Request #<?php echo $request_id; ?></h1>
    <p><a href="anna_requests.php">&larr; Back to Pending Requests</a></p>

    <div class="box">
        <h3>Request Details</h3>
        <p>
            Client: <?php echo htmlspecialchars($request['first_name'] . ' ' . $request['last_name']); ?> (ID: <?php echo $request['client_id']; ?>)<br>
            Address: <?php echo htmlspecialchars($request['service_address']); ?><br>
            Type: <?php echo htmlspecialchars($request['cleaning_type']); ?><br>
            Rooms: <?php echo $request['num_rooms']; ?><br>
            Preferred: <?php echo $request['preferred_datetime']; ?><br>
            Proposed Budget: <?php echo $request['proposed_budget']; ?><br>
            Notes: <?php echo nl2br(htmlspecialchars($request['notes'])); ?>
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
        <form method="post" action="">
            <label>
                Adjusted Price:
                <input type="number" step="0.01" name="adjusted_price" required>
            </label>

            <label>
                Time Window (e.g., "Dec 5, 2–4 PM"):
                <input type="text" name="time_window" required>
            </label>

            <label>
                Note to Client (optional):
                <textarea name="note" placeholder="Any details or explanations"></textarea>
            </label>

            <button type="submit" class="btn">Send Quote</button>
        </form>
    </div>
</body>
</html>
