<?php
session_start();

// If user is already logged in, send them to the main dashboard
if (isset($_SESSION['client_id'])) {
    header("Location: index.php");
    exit;
}

require_once 'db.php';

$error = "";  // <-- IMPORTANT: define this before we use it

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = "Please enter both email and password.";
    } else {
        $stmt = $conn->prepare("
            SELECT client_id, first_name, last_name, password_hash
            FROM Client
            WHERE email = ?
        ");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->bind_result($client_id, $first_name, $last_name, $password_hash);

        if ($stmt->fetch()) {
            // We got a client; verify password
            if ($password_hash !== null && password_verify($password, $password_hash)) {
                // Login success
                $_SESSION['client_id'] = $client_id;
                $_SESSION['client_name'] = $first_name . " " . $last_name;

                $stmt->close();
                header("Location: index.php");
                exit;
            } else {
                $error = "Incorrect password.";
            }
        } else {
            $error = "No account found with that email.";
        }

        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Client Login</title>
<style>
    body { font-family: 'Segoe UI', Arial, sans-serif; background: #f4f7fb; margin: 0; }
    header {
        background: #4a90e2;
        color: white;
        padding: 18px 30px;
        box-shadow: 0 3px 8px rgba(0,0,0,0.15);
    }
    header h1 { margin: 0; font-size: 24px; }
    .container {
        max-width: 500px;
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
    label {
        display: block;
        margin-top: 10px;
        font-size: 14px;
        color: #333;
    }
    input[type="email"], input[type="password"] {
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
    .btn:hover { background: #3b7ccc; }
    .message {
        margin-top: 15px;
        padding: 10px 12px;
        border-radius: 6px;
        font-size: 14px;
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
    <h1>Client Login</h1>
</header>

<div class="container">
    <p><a class="back" href="home.php">&larr; Back to Welcome Page</a></p>

    <?php if (!empty($error)): ?>
        <div class="message error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="post" action="login.php">
        <label>Email:
            <input type="email" name="email" required>
        </label>
        <label>Password:
            <input type="password" name="password" required>
        </label>

        <button type="submit" class="btn">Login</button>
    </form>

    <p style="margin-top:15px; font-size:13px;">
        New user? <a href="register.php" style="color:#4a90e2;">Click here to register</a>
    </p>
</div>
</body>
</html>
