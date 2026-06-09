<?php
require("session.php");
require("db.php");
require("functions.php");

$userId = (int)$_SESSION["id"];

$stmt = $conn->prepare(
    "SELECT r.id, r.rating, r.content, r.created_at, a.id AS album_id, a.title, a.artist, a.cover,
            (SELECT COUNT(*) FROM review_likes rl WHERE rl.review_id = r.id) AS like_count
     FROM reviews r JOIN albums a ON a.id = r.album_id
     WHERE r.user_id = ?
     ORDER BY r.created_at DESC"
);
$stmt->bind_param("i", $userId);
$stmt->execute();
$reviews = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Reviews &middot; Vinyl Crate</title>
    <link rel="stylesheet" href="styles/style.css">
</head>
<body>
<?php require("header.php"); ?>

<main class="container">
    <h1>My Reviews</h1>

    <?php if ($reviews && $reviews->num_rows > 0): ?>
        <div id="reviews-list">
            <?php while ($row = $reviews->fetch_assoc()): ?>
                <article class="review-card wide" id="review-<?php echo (int)$row["id"]; ?>">
                    <a class="review-thumb" href="details.php?id=<?php echo (int)$row["album_id"]; ?>">
                        <img src="<?php echo htmlspecialchars($row["cover"] ?: "covers/placeholder.svg"); ?>" alt="<?php echo htmlspecialchars($row["title"]); ?> cover">
                    </a>
                    <div class="review-body">
                        <div class="review-head">
                            <span class="review-author">
                                <a href="details.php?id=<?php echo (int)$row["album_id"]; ?>"><?php echo htmlspecialchars($row["title"]); ?></a>
                                <span class="muted">&middot; <?php echo htmlspecialchars($row["artist"]); ?></span>
                            </span>
                            <?php echo render_stars($row["rating"]); ?>
                        </div>
                        <p class="review-date"><?php echo htmlspecialchars($row["created_at"]); ?></p>
                        <p class="review-text"><?php echo nl2br(htmlspecialchars($row["content"])); ?></p>
                        <div class="review-tools">
                            <span class="helpful-count">&#128077; <?php echo (int)$row["like_count"]; ?> helpful</span>
                            <a class="btn small" href="details.php?id=<?php echo (int)$row["album_id"]; ?>">Edit</a>
                            <button type="button" class="btn small danger delete-review" data-review-id="<?php echo (int)$row["id"]; ?>">Delete</button>
                        </div>
                    </div>
                </article>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <p class="empty-state">You have not written any reviews yet. <a href="index.php">Find an album</a> to review.</p>
    <?php endif; ?>
</main>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="scripts/app.js"></script>
</body>
</html>
