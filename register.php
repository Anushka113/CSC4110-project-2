<?php
require_once 'db.php';

$success = "";
$error = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $credit_card = trim($_POST['credit_card'] ?? '');

    // Basic validation
    if ($first_name === '' || $last_name === '' || $address === '' || $phone === '' || $email === '' || $credit_card === '') {
        $error = "All fields are required.";
    } else {
        // Store only last 4 digits for safety
        $credit_last4 = substr(preg_replace('/\D/', '', $credit_card), -4);

        $stmt = $conn->prepare("
            INSERT INTO Client (first_name, last_name, address, phone, email, credit_card_last4)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("ssssss", $first_name, $last_name, $address, $phone, $email, $credit_last4);

        if ($stmt->execute()) {
            $new_id = $stmt->insert_id;
            $success = "Registration successful! Your Client ID is: " . $new_id;
        } else {
            if ($conn->errno === 1062) { // duplicate email
                $error = "This email is already registered.";
            } else {
                $error = "Error: " . $conn->error;
            }
        }

        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Client Registration - Home Cleaning Service</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 700px; margin: 30px auto; }
        h1 { margin-bottom: 10px; }
        form { border: 1px solid #ccc; padding: 20px; border-radius: 5px; }
        label { display: block; margin-top: 10px; }
        input[type="text"], input[type="email"] {
            width: 100%; padding: 8px; margin-top: 4px; box-sizing: border-box;
        }
        .btn {
            margin-top: 15px; padding: 8px 16px; cursor: pointer;
        }
        .message { margin-top: 15px; padding: 10px; border-radius: 4px; }
        .success { background-color: #e0ffe0; border: 1px solid #5cb85c; }
        .error { background-color: #ffe0e0; border: 1px solid #d9534f; }
        a { text-decoration: none; color: #007bff; }
    </style>
</head>
<body>
    <h1>Client Registration</h1>
    <p><a href="index.php">&larr; Back to Home</a></p>

    <?php if ($success): ?>
        <div class="message success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="message error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="post" action="register.php">
        <label>
            First Name:
            <input type="text" name="first_name" required>
        </label>

        <label>
            Last Name:
            <input type="text" name="last_name" required>
        </label>

        <label>
            Address:
            <input type="text" name="address" required>
        </label>

        <label>
            Phone:
            <input type="text" name="phone" required>
        </label>

        <label>
            Email:
            <input type="email" name="email" required>
        </label>

        <label>
            Credit Card Number:
            <input type="text" name="credit_card" required>
        </label>

        <button type="submit" class="btn">Register</button>
    </form>
</body>
</html>
