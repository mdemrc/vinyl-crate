<?php
require("admin_guard.php");
require("db.php");

$currentAdminId = (int)$_SESSION["id"];
$message = "";
$isError = false;

if (isset($_GET["toggle_admin"])) {
    $targetId = (int)$_GET["toggle_admin"];
    if ($targetId === $currentAdminId) {
        $message = "You cannot change your own role.";
        $isError = true;
    } elseif ($targetId > 0) {
        $stmt = $conn->prepare("UPDATE users SET is_admin = 1 - is_admin WHERE id = ?");
        $stmt->bind_param("i", $targetId);
        $stmt->execute();
        $stmt->close();
        header("Location: manage_users.php");
        exit;
    }
}

if (isset($_GET["delete"])) {
    $targetId = (int)$_GET["delete"];
    if ($targetId === $currentAdminId) {
        $message = "You cannot delete your own account.";
        $isError = true;
    } elseif ($targetId > 0) {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $targetId);
        $stmt->execute();
        $stmt->close();
        header("Location: manage_users.php");
        exit;
    }
}

$users = $conn->query(
    "SELECT u.id, u.username, u.email, u.is_admin, u.created_at,
            (SELECT COUNT(*) FROM reviews r WHERE r.user_id = u.id) AS review_count,
            (SELECT COUNT(*) FROM crate_items c WHERE c.user_id = u.id) AS crate_count
     FROM users u
     ORDER BY u.is_admin DESC, u.id ASC"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage users &middot; Vinyl Crate</title>
    <link rel="stylesheet" href="styles/style.css">
</head>
<body>
<?php require("header.php"); ?>

<main class="container">
    <a class="back-link" href="admin.php">&larr; Back to dashboard</a>
    <h1>Users</h1>

    <?php if ($message !== ""): ?>
        <p class="message <?php echo $isError ? "error" : "ok"; ?>"><?php echo htmlspecialchars($message); ?></p>
    <?php endif; ?>

    <table class="data-table">
        <thead>
        <tr>
            <th>User</th>
            <th>Email</th>
            <th>Role</th>
            <th>Joined</th>
            <th>Reviews</th>
            <th>Records</th>
            <th>Actions</th>
        </tr>
        </thead>
        <tbody>
        <?php while ($u = $users->fetch_assoc()): $isSelf = (int)$u["id"] === $currentAdminId; ?>
            <tr>
                <td>
                    <?php echo htmlspecialchars($u["username"]); ?>
                    <?php if ($isSelf): ?><span class="muted">(you)</span><?php endif; ?>
                </td>
                <td class="muted"><?php echo htmlspecialchars($u["email"]); ?></td>
                <td>
                    <?php if ((int)$u["is_admin"] === 1): ?>
                        <span class="badge">Admin</span>
                    <?php else: ?>
                        <span class="badge ghost">User</span>
                    <?php endif; ?>
                </td>
                <td class="muted"><?php echo htmlspecialchars(substr($u["created_at"], 0, 10)); ?></td>
                <td><?php echo (int)$u["review_count"]; ?></td>
                <td><?php echo (int)$u["crate_count"]; ?></td>
                <td class="row-actions">
                    <?php if ($isSelf): ?>
                        <span class="muted">&mdash;</span>
                    <?php else: ?>
                        <a class="btn small" href="manage_users.php?toggle_admin=<?php echo (int)$u["id"]; ?>">
                            <?php echo (int)$u["is_admin"] === 1 ? "Remove admin" : "Make admin"; ?>
                        </a>
                        <a class="btn small danger" href="manage_users.php?delete=<?php echo (int)$u["id"]; ?>"
                           onclick="return confirm('Delete this user and all of their reviews and crate entries?');">Delete</a>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</main>
</body>
</html>
