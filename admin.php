<?php
require("admin_guard.php");
require("db.php");
require("functions.php");

$stats = $conn->query(
    "SELECT
        (SELECT COUNT(*) FROM albums) AS albums,
        (SELECT COUNT(*) FROM users) AS users,
        (SELECT COUNT(*) FROM reviews) AS reviews,
        (SELECT COUNT(*) FROM genres) AS genres,
        (SELECT COUNT(*) FROM tracks) AS tracks"
)->fetch_assoc();

$albums = $conn->query(
    "SELECT a.id, a.title, a.artist, a.release_year, a.cover, g.name AS genre_name,
            COUNT(DISTINCT t.id) AS track_count,
            ROUND(AVG(r.rating), 1) AS avg_rating, COUNT(DISTINCT r.id) AS review_count
     FROM albums a
     LEFT JOIN genres g ON g.id = a.genre_id
     LEFT JOIN tracks t ON t.album_id = a.id
     LEFT JOIN reviews r ON r.album_id = a.id
     GROUP BY a.id
     ORDER BY a.created_at DESC"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin &middot; Vinyl Crate</title>
    <link rel="stylesheet" href="styles/style.css">
</head>
<body>
<?php require("header.php"); ?>

<main class="container">
    <div class="page-head">
        <h1>Admin dashboard</h1>
        <div class="head-actions">
            <a class="btn" href="manage_users.php">Manage users</a>
            <a class="btn" href="manage_genres.php">Manage genres</a>
            <a class="btn primary" href="album_form.php">+ Add album</a>
        </div>
    </div>

    <div class="stat-grid">
        <div class="stat-card"><span class="stat-num"><?php echo (int)$stats["albums"]; ?></span><span class="stat-label">Albums</span></div>
        <div class="stat-card"><span class="stat-num"><?php echo (int)$stats["tracks"]; ?></span><span class="stat-label">Tracks</span></div>
        <div class="stat-card"><span class="stat-num"><?php echo (int)$stats["genres"]; ?></span><span class="stat-label">Genres</span></div>
        <div class="stat-card"><span class="stat-num"><?php echo (int)$stats["reviews"]; ?></span><span class="stat-label">Reviews</span></div>
        <div class="stat-card"><span class="stat-num"><?php echo (int)$stats["users"]; ?></span><span class="stat-label">Users</span></div>
    </div>

    <h2>All albums</h2>
    <table class="data-table">
        <thead>
        <tr>
            <th></th>
            <th>Title</th>
            <th>Artist</th>
            <th>Genre</th>
            <th>Year</th>
            <th>Tracks</th>
            <th>Rating</th>
            <th>Actions</th>
        </tr>
        </thead>
        <tbody>
        <?php while ($album = $albums->fetch_assoc()): ?>
            <tr>
                <td>
                    <img class="table-thumb" src="<?php echo htmlspecialchars($album["cover"] ?: "covers/placeholder.svg"); ?>" alt="">
                </td>
                <td><a href="details.php?id=<?php echo (int)$album["id"]; ?>"><?php echo htmlspecialchars($album["title"]); ?></a></td>
                <td><?php echo htmlspecialchars($album["artist"]); ?></td>
                <td><?php echo $album["genre_name"] ? htmlspecialchars($album["genre_name"]) : "<span class='muted'>&mdash;</span>"; ?></td>
                <td><?php echo (int)$album["release_year"]; ?></td>
                <td><?php echo (int)$album["track_count"]; ?></td>
                <td><?php echo $album["review_count"] > 0 ? $album["avg_rating"] . "/5" : "<span class='muted'>&mdash;</span>"; ?></td>
                <td class="row-actions">
                    <a class="btn small" href="album_form.php?id=<?php echo (int)$album["id"]; ?>">Edit</a>
                    <a class="btn small danger" href="delete_album.php?id=<?php echo (int)$album["id"]; ?>" onclick="return confirm('Delete this album?');">Delete</a>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</main>
</body>
</html>
