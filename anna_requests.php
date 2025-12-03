<?php
require_once 'db.php';

$message = "";

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
<title>Anna - Pending Requests</title>
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
    table { width: 100%; border-collapse: collapse; margin-top: 15px; }
    th, td { border: 1px solid #d0e6ff; padding: 8px 10px; text-align: left; }
    th { background: #bee3ff; }
    tr:nth-child(even) td { background: #f9fcff; }
    tr:hover td { background: #eef7ff; }
    .message {
        margin-top: 10px; padding: 8px 10px;
        border-radius: 6px; background: #e0ffe0;
        border: 1px solid #5cb85c; color: #2f6b2f;
    }
    .btn {
        display: inline-block;
        padding: 5px 10px;
        border-radius: 5px;
        border: 1px solid #4a90e2;
        text-decoration: none;
        color: #4a90e2;
        font-size: 13px;
        background: #fff;
        margin-right: 5px;
    }
    .btn:hover { background: #e7f2ff; }
    .btn-danger {
        border-color: #d9534f;
        color: #d9534f;
    }
    .btn-danger:hover { background: #ffe0e0; }
</style>
</head>
<body>
<header>
    <h1>Anna - Pending Service Requests</h1>
</header>

<div class="container">
    <p><a class="back" href="index.php">&larr; Back to Home</a></p>

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
                        <a class="btn btn-danger" href="?action=reject&request_id=<?php echo $row['request_id']; ?>"
                           onclick="return confirm('Reject this request?');">Reject</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </table>
    <?php else: ?>
        <p>No pending requests.</p>
    <?php endif; ?>
</div>
</body>
</html>
