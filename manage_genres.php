<?php
require("admin_guard.php");
require("db.php");
require("functions.php");

$message = "";
$isError = false;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST["name"] ?? "");
    $slug = slugify($name);

    if ($name === "" || $slug === "") {
        $message = "Please enter a valid genre name.";
        $isError = true;
    } else {
        $stmt = $conn->prepare("INSERT INTO genres (name, slug) VALUES (?, ?)");
        $stmt->bind_param("ss", $name, $slug);
        if ($stmt->execute()) {
            $message = "Genre \"" . $name . "\" added.";
        } else {
            $message = "That genre already exists.";
            $isError = true;
        }
        $stmt->close();
    }
}

if (isset($_GET["delete"])) {
    $deleteId = (int)$_GET["delete"];
    if ($deleteId > 0) {
        $del = $conn->prepare("DELETE FROM genres WHERE id = ?");
        $del->bind_param("i", $deleteId);
        $del->execute();
        $del->close();
        header("Location: manage_genres.php");
        exit;
    }
}

$genres = $conn->query(
    "SELECT g.id, g.name, g.slug, COUNT(a.id) AS album_count
     FROM genres g
     LEFT JOIN albums a ON a.genre_id = g.id
     GROUP BY g.id
     ORDER BY g.name ASC"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage genres &middot; Vinyl Crate</title>
    <link rel="stylesheet" href="styles/style.css">
</head>
<body>
<?php require("header.php"); ?>

<main class="container">
    <a class="back-link" href="admin.php">&larr; Back to dashboard</a>
    <h1>Genres</h1>

    <?php if ($message !== ""): ?>
        <p class="message <?php echo $isError ? "error" : "ok"; ?>"><?php echo htmlspecialchars($message); ?></p>
    <?php endif; ?>

    <form action="manage_genres.php" method="post" class="inline-form">
        <input type="text" name="name" placeholder="New genre name" required>
        <button type="submit" class="btn primary">Add genre</button>
    </form>

    <table class="data-table">
        <thead>
        <tr>
            <th>Name</th>
            <th>Slug</th>
            <th>Albums</th>
            <th>Actions</th>
        </tr>
        </thead>
        <tbody>
        <?php while ($g = $genres->fetch_assoc()): ?>
            <tr>
                <td><a href="index.php?genre=<?php echo (int)$g["id"]; ?>"><?php echo htmlspecialchars($g["name"]); ?></a></td>
                <td><code><?php echo htmlspecialchars($g["slug"]); ?></code></td>
                <td><?php echo (int)$g["album_count"]; ?></td>
                <td class="row-actions">
                    <a class="btn small danger" href="manage_genres.php?delete=<?php echo (int)$g["id"]; ?>"
                       onclick="return confirm('Delete this genre? Albums will keep their data but lose this genre.');">Delete</a>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</main>
</body>
</html>
