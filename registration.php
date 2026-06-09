<?php
require("db.php");

$message = "";
$isSuccess = false;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirm = $_POST["confirm"] ?? "";
    $email = trim($_POST["email"] ?? "");

    if ($username === "" || $password === "" || $email === "") {
        $message = "All fields are required.";
    } elseif (strlen($username) < 3) {
        $message = "Username must be at least 3 characters long.";
    } elseif (strlen($password) < 6) {
        $message = "Password must be at least 6 characters long.";
    } elseif ($password !== $confirm) {
        $message = "The passwords do not match.";
    } else {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("INSERT INTO users (username, password, email) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $username, $hashedPassword, $email);

        if ($stmt->execute()) {
            $isSuccess = true;
            $message = "Account created. You can sign in now.";
        } else {
            $message = "Registration failed. This username may already be taken.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create account &middot; Vinyl Crate</title>
    <link rel="stylesheet" href="styles/style.css">
</head>
<body class="auth-page">
<div class="auth-box">
    <div class="auth-brand"><span class="brand-disc"></span> Vinyl Crate</div>
    <h1>Create account</h1>

    <?php if ($message !== ""): ?>
        <p class="message <?php echo $isSuccess ? "ok" : "error"; ?>"><?php echo htmlspecialchars($message); ?></p>
    <?php endif; ?>

    <?php if ($isSuccess): ?>
        <p class="auth-switch"><a href="login.php">Go to sign in</a></p>
    <?php else: ?>
        <form action="" method="post" class="simple-form">
            <label for="username">Username</label>
            <input type="text" name="username" id="username" value="<?php echo htmlspecialchars($_POST["username"] ?? ""); ?>" required>

            <label for="email">Email</label>
            <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($_POST["email"] ?? ""); ?>" required>

            <label for="password">Password</label>
            <input type="password" name="password" id="password" required>

            <label for="confirm">Confirm password</label>
            <input type="password" name="confirm" id="confirm" required>

            <button type="submit">Create account</button>
        </form>
        <p class="auth-switch">Already have an account? <a href="login.php">Sign in</a></p>
    <?php endif; ?>
</div>
</body>
</html>
