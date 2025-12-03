<?php
require_once 'db.php';

$success = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name  = trim($_POST['last_name'] ?? '');
    $address    = trim($_POST['address'] ?? '');
    $phone      = trim($_POST['phone'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $credit_card = trim($_POST['credit_card'] ?? '');

    if ($first_name === '' || $last_name === '' || $address === '' ||
        $phone === '' || $email === '' || $credit_card === '') {
        $error = "All fields are required.";
    } else {
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
            if ($conn->errno === 1062) {
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
<title>Client Registration</title>
<style>
    body {
        font-family: 'Segoe UI', Arial, sans-serif;
        background: #f4f7fb;
        margin: 0;
    }
    header {
        background: #4a90e2;
        color: white;
        padding: 18px 30px;
        box-shadow: 0 3px 8px rgba(0,0,0,0.15);
    }
    header h1 { margin: 0; font-size: 24px; }
    .container {
        max-width: 700px;
        margin: 30px auto;
        background: #fff;
        padding: 25px 30px;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    a.back {
        text-decoration: none;
        color: #4a90e2;
        font-size: 13px;
    }
    form {
        margin-top: 15px;
    }
    label {
        display: block;
        margin-top: 10px;
        font-size: 14px;
        color: #333;
    }
    input[type="text"], input[type="email"] {
        width: 100%;
        padding: 8px 10px;
        margin-top: 3px;
        border-radius: 6px;
        border: 1px solid #c3d7f2;
        box-sizing: border-box;
    }
    .btn {
        margin-top: 15px;
        padding: 9px 18px;
        border-radius: 6px;
        border: none;
        background: #4a90e2;
        color: white;
        cursor: pointer;
        font-size: 14px;
    }
    .btn:hover {
        background: #3b7ccc;
    }
    .message {
        margin-top: 15px;
        padding: 10px 12px;
        border-radius: 6px;
        font-size: 14px;
    }
    .success {
        background: #e0ffe0;
        border: 1px solid #5cb85c;
        color: #2f6b2f;
    }
    .error {
        background: #ffe0e0;
        border: 1px solid #d9534f;
        color: #8a1a1a;
    }
</style>
</head>
<body>
<header>
    <h1>Client Registration</h1>
</header>

<div class="container">
    <p><a class="back" href="index.php">&larr; Back to Home</a></p>

    <?php if ($success): ?>
        <div class="message success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="message error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="post" action="register.php">
        <label>First Name:
            <input type="text" name="first_name" required>
        </label>

        <label>Last Name:
            <input type="text" name="last_name" required>
        </label>

        <label>Address:
            <input type="text" name="address" required>
        </label>

        <label>Phone:
            <input type="text" name="phone" required>
        </label>

        <label>Email:
            <input type="email" name="email" required>
        </label>

        <label>Credit Card Number:
            <input type="text" name="credit_card" required>
        </label>

        <button type="submit" class="btn">Register</button>
    </form>
</div>
</body>
</html>
