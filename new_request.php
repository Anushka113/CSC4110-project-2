<?php
require_once 'db.php';

$success = "";
$error = "";

// Handle form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $client_id = intval($_POST['client_id'] ?? 0);
    $service_address = trim($_POST['service_address'] ?? '');
    $cleaning_type = $_POST['cleaning_type'] ?? '';
    $num_rooms = intval($_POST['num_rooms'] ?? 0);
    $preferred_datetime = $_POST['preferred_datetime'] ?? '';
    $proposed_budget = $_POST['proposed_budget'] ?? null;
    $notes = trim($_POST['notes'] ?? '');

    if ($client_id <= 0 || $service_address === '' || $cleaning_type === '' || $num_rooms <= 0 || $preferred_datetime === '') {
        $error = "Please fill in all required fields.";
    } else {
        // Insert into ServiceRequest
        $stmt = $conn->prepare("
            INSERT INTO ServiceRequest (client_id, service_address, cleaning_type, num_rooms, preferred_datetime, proposed_budget, notes)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param(
            "ississs",
            $client_id,
            $service_address,
            $cleaning_type,
            $num_rooms,
            $preferred_datetime,
            $proposed_budget,
            $notes
        );

        if ($stmt->execute()) {
            $request_id = $stmt->insert_id;
            $success = "Service request submitted! Request ID: " . $request_id;

            // Handle file uploads (up to 5 photos)
            if (!empty($_FILES['photos']['name'][0])) {
                $uploadDir = __DIR__ . '/uploads/requests/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                for ($i = 0; $i < count($_FILES['photos']['name']); $i++) {
                    if ($_FILES['photos']['error'][$i] === UPLOAD_ERR_OK) {
                        $tmpName = $_FILES['photos']['tmp_name'][$i];
                        $originalName = basename($_FILES['photos']['name'][$i]);
                        $ext = pathinfo($originalName, PATHINFO_EXTENSION);

                        $newFileName = 'req_' . $request_id . '_' . time() . '_' . $i . '.' . $ext;
                        $targetPath = $uploadDir . $newFileName;

                        if (move_uploaded_file($tmpName, $targetPath)) {
                            $relPath = 'uploads/requests/' . $newFileName;
                            $stmtPhoto = $conn->prepare("
                                INSERT INTO RequestPhoto (request_id, file_path)
                                VALUES (?, ?)
                            ");
                            $stmtPhoto->bind_param("is", $request_id, $relPath);
                            $stmtPhoto->execute();
                            $stmtPhoto->close();
                        }
                    }
                }
            }
        } else {
            $error = "Error saving request: " . $conn->error;
        }

        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>New Service Request</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 30px auto; }
        h1 { margin-bottom: 10px; }
        form { border: 1px solid #ccc; padding: 20px; border-radius: 5px; }
        label { display: block; margin-top: 10px; }
        input[type="text"], input[type="number"], input[type="datetime-local"], input[type="file"], select, textarea {
            width: 100%; padding: 8px; margin-top: 4px; box-sizing: border-box;
        }
        textarea { min-height: 80px; }
        .btn { margin-top: 15px; padding: 8px 16px; cursor: pointer; }
        .message { margin-top: 15px; padding: 10px; border-radius: 4px; }
        .success { background-color: #e0ffe0; border: 1px solid #5cb85c; }
        .error { background-color: #ffe0e0; border: 1px solid #d9534f; }
        a { text-decoration: none; color: #007bff; }
    </style>
</head>
<body>
    <h1>Submit a Service Request</h1>
    <p><a href="index.php">&larr; Back to Home</a></p>

    <p><strong>Note:</strong> Enter the Client ID you received after registration.</p>

    <?php if ($success): ?>
        <div class="message success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="message error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="post" action="new_request.php" enctype="multipart/form-data">
        <label>
            Client ID:
            <input type="number" name="client_id" required>
        </label>

        <label>
            Service Address:
            <input type="text" name="service_address" required>
        </label>

        <label>
            Type of Cleaning:
            <select name="cleaning_type" required>
                <option value="">-- Select --</option>
                <option value="basic">Basic</option>
                <option value="deep">Deep Cleaning</option>
                <option value="move-out">Move-out</option>
            </select>
        </label>

        <label>
            Number of Rooms:
            <input type="number" name="num_rooms" min="1" required>
        </label>

        <label>
            Preferred Date and Time:
            <input type="datetime-local" name="preferred_datetime" required>
        </label>

        <label>
            Proposed Budget (optional):
            <input type="text" name="proposed_budget">
        </label>

        <label>
            Notes (optional):
            <textarea name="notes" placeholder="Any special instructions, e.g., pet-friendly products only"></textarea>
        </label>

        <label>
            Photos of Home (optional, up to 5):
            <input type="file" name="photos[]" multiple accept="image/*">
        </label>

        <button type="submit" class="btn">Submit Request</button>
    </form>
</body>
</html>
