<?php
require_once 'db.php';

$message = "";

// Handle reject action (simple for now)
if (isset($_GET['action'], $_GET['request_id']) && $_GET['action'] === 'reject') {
    $req_id = intval($_GET['request_id']);
    if ($req_id > 0) {
        $stmt = $conn->prepare("UPDATE ServiceRequest SET status = 'rejected' WHERE request_id = ?");
        $stmt->bind_param("i", $req_id);
        if ($stmt->execute()) {
            $message = "Request #$req_id has been rejected.";
        } else {
            $message = "Error rejecting request: " . $conn->error;
        }
        $stmt->close();
    }
}

// Load all pending requests
$sql = "
    SELECT sr.request_id, sr.service_address, sr.cleaning_type, sr.num_rooms,
           sr.preferred_datetime, sr.proposed_budget, sr.notes,
           c.client_id, c.first_name, c.last_name
    FROM ServiceRequest sr
    JOIN Client c ON sr.client_id = c.client_id
    WHERE sr.status = 'pending'
    ORDER BY sr.created_at DESC
";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Anna - Pending Service Requests</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1000px; margin: 30px auto; }
        h1 { margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; vertical-align: top; }
        th { background-color: #f0f0f0; }
        .message { margin-top: 10px; padding: 8px; border-radius: 4px; background: #e0ffe0; border: 1px solid #5cb85c; }
        a { color: #007bff; text-decoration: none; }
        a.btn { padding: 4px 8px; border: 1px solid #007bff; border-radius: 4px; margin-right: 5px; }
    </style>
</head>
<body>
    <h1>Anna - Pending Service Requests</h1>
    <p><a href="index.php">&larr; Back to Home</a></p>

    <?php if ($message): ?>
        <div class="message"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <?php if ($result && $result->num_rows > 0): ?>
        <table>
            <tr>
                <th>Request ID</th>
                <th>Client</th>
                <th>Details</th>
                <th>Actions</th>
            </tr>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td>#<?php echo $row['request_id']; ?></td>
                    <td>
                        ID: <?php echo $row['client_id']; ?><br>
                        <?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?>
                    </td>
                    <td>
                        Address: <?php echo htmlspecialchars($row['service_address']); ?><br>
                        Type: <?php echo htmlspecialchars($row['cleaning_type']); ?><br>
                        Rooms: <?php echo $row['num_rooms']; ?><br>
                        Preferred: <?php echo $row['preferred_datetime']; ?><br>
                        Budget: <?php echo $row['proposed_budget']; ?><br>
                        Notes: <?php echo nl2br(htmlspecialchars($row['notes'])); ?>
                    </td>
<td>
    <a class="btn" href="quote_anna.php?request_id=<?php echo $row['request_id']; ?>">Send Quote</a>
    <a class="btn" href="?action=reject&request_id=<?php echo $row['request_id']; ?>"
       onclick="return confirm('Reject this request?');">Reject</a>
</td>
                </tr>
            <?php endwhile; ?>
        </table>
    <?php else: ?>
        <p>No pending requests.</p>
    <?php endif; ?>
</body>
</html>
