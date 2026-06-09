<?php
require("db.php");
session_start();

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";

    $stmt = $conn->prepare("SELECT id, username, password, is_admin FROM users WHERE username = ? LIMIT 1");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if ($user && password_verify($password, $user["password"])) {
        $_SESSION["id"] = (int)$user["id"];
        $_SESSION["username"] = $user["username"];
        $_SESSION["is_admin"] = (int)$user["is_admin"];
        header("Location: index.php");
        exit;
    }

    $message = "Invalid username or password.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign in &middot; Vinyl Crate</title>
    <link rel="stylesheet" href="styles/style.css">
</head>
<body class="auth-page">
<div class="auth-box">
    <div class="auth-brand"><span class="brand-disc"></span> Vinyl Crate</div>
    <h1>Sign in</h1>

    <?php if ($message !== ""): ?>
        <p class="message error"><?php echo htmlspecialchars($message); ?></p>
    <?php endif; ?>

    <form action="" method="post" class="simple-form">
        <label for="username">Username</label>
        <input type="text" name="username" id="username" required autofocus>

        <label for="password">Password</label>
        <input type="password" name="password" id="password" required>

        <button type="submit">Sign in</button>
    </form>

    <p class="auth-switch">No account yet? <a href="registration.php">Create one</a></p>
</div>
</body>
</html>
