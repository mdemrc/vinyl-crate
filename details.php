<?php
require("session.php");
require("db.php");
require("functions.php");

$albumId = isset($_GET["id"]) ? (int)$_GET["id"] : 0;
$userId = (int)$_SESSION["id"];

$albumStmt = $conn->prepare(
    "SELECT a.id, a.title, a.artist, a.release_year, a.record_label, a.cover, a.description,
            g.id AS genre_id, g.name AS genre_name,
            ROUND(AVG(r.rating), 1) AS avg_rating, COUNT(DISTINCT r.id) AS review_count
     FROM albums a
     LEFT JOIN genres g ON g.id = a.genre_id
     LEFT JOIN reviews r ON r.album_id = a.id
     WHERE a.id = ?
     GROUP BY a.id
     LIMIT 1"
);
$albumStmt->bind_param("i", $albumId);
$albumStmt->execute();
$album = $albumStmt->get_result()->fetch_assoc();
$albumStmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $album ? htmlspecialchars($album["title"]) : "Album"; ?> &middot; Vinyl Crate</title>
    <link rel="stylesheet" href="styles/style.css">
</head>
<body>
<?php require("header.php"); ?>

<main class="container">
<?php if (!$album): ?>
    <h1>Album not found</h1>
    <p><a href="index.php">Back to the crate</a></p>
<?php else:
    $trackRes = $conn->prepare("SELECT position, title, duration_seconds FROM tracks WHERE album_id = ? ORDER BY position ASC");
    $trackRes->bind_param("i", $albumId);
    $trackRes->execute();
    $trackResult = $trackRes->get_result();
    $tracks = [];
    $totalSeconds = 0;
    while ($t = $trackResult->fetch_assoc()) {
        $tracks[] = $t;
        $totalSeconds += (int)$t["duration_seconds"];
    }
    $trackRes->close();

    $crateStmt = $conn->prepare("SELECT status FROM crate_items WHERE user_id = ? AND album_id = ? LIMIT 1");
    $crateStmt->bind_param("ii", $userId, $albumId);
    $crateStmt->execute();
    $crateRow = $crateStmt->get_result()->fetch_assoc();
    $crateStmt->close();
    $crateStatus = $crateRow ? $crateRow["status"] : "";

    $myStmt = $conn->prepare("SELECT rating, content FROM reviews WHERE album_id = ? AND user_id = ? LIMIT 1");
    $myStmt->bind_param("ii", $albumId, $userId);
    $myStmt->execute();
    $myReview = $myStmt->get_result()->fetch_assoc();
    $myStmt->close();

    $reviewStmt = $conn->prepare(
        "SELECT r.id, r.rating, r.content, r.created_at, u.id AS user_id, u.username,
                (SELECT COUNT(*) FROM review_likes rl WHERE rl.review_id = r.id) AS like_count,
                EXISTS(SELECT 1 FROM review_likes rl2 WHERE rl2.review_id = r.id AND rl2.user_id = ?) AS liked_by_me
         FROM reviews r JOIN users u ON u.id = r.user_id
         WHERE r.album_id = ?
         ORDER BY like_count DESC, r.created_at DESC"
    );
    $reviewStmt->bind_param("ii", $userId, $albumId);
    $reviewStmt->execute();
    $reviews = $reviewStmt->get_result();
?>
    <a class="back-link" href="index.php">&larr; Back to the crate</a>

    <section class="album-hero">
        <div class="hero-cover">
            <div class="disc"></div>
            <img src="<?php echo htmlspecialchars($album["cover"] ?: "covers/placeholder.svg"); ?>" alt="<?php echo htmlspecialchars($album["title"]); ?> cover">
        </div>

        <div class="hero-info">
            <h1><?php echo htmlspecialchars($album["title"]); ?></h1>
            <p class="hero-artist">by <a href="index.php?artist=<?php echo urlencode($album["artist"]); ?>"><?php echo htmlspecialchars($album["artist"]); ?></a></p>

            <div class="hero-meta">
                <?php if ($album["genre_name"]): ?>
                    <a class="badge" href="index.php?genre=<?php echo (int)$album["genre_id"]; ?>"><?php echo htmlspecialchars($album["genre_name"]); ?></a>
                <?php endif; ?>
                <a class="badge ghost" href="index.php?decade=<?php echo (int)(floor($album["release_year"] / 10) * 10); ?>"><?php echo decade_label($album["release_year"]); ?></a>
                <span class="meta-item"><?php echo (int)$album["release_year"]; ?></span>
                <?php if ($album["record_label"]): ?>
                    <span class="meta-item"><?php echo htmlspecialchars($album["record_label"]); ?></span>
                <?php endif; ?>
            </div>

            <div class="hero-rating">
                <?php if ($album["review_count"] > 0): ?>
                    <?php echo render_stars($album["avg_rating"]); ?>
                    <span><?php echo $album["avg_rating"]; ?>/5 from <?php echo (int)$album["review_count"]; ?> review<?php echo $album["review_count"] == 1 ? "" : "s"; ?></span>
                <?php else: ?>
                    <span class="muted">Not rated yet</span>
                <?php endif; ?>
            </div>

            <?php if ($album["description"]): ?>
                <p class="hero-desc"><?php echo nl2br(htmlspecialchars($album["description"])); ?></p>
            <?php endif; ?>

            <div class="crate-controls" data-album-id="<?php echo (int)$album["id"]; ?>">
                <button type="button" class="btn crate-btn" data-target="owned"
                        data-on="&#10003; In collection" data-off="Add to collection"
                        data-active="<?php echo $crateStatus === "owned" ? "1" : "0"; ?>">
                    <?php echo $crateStatus === "owned" ? "&#10003; In collection" : "Add to collection"; ?>
                </button>
                <button type="button" class="btn crate-btn ghost" data-target="wishlist"
                        data-on="&#10003; On wishlist" data-off="Add to wishlist"
                        data-active="<?php echo $crateStatus === "wishlist" ? "1" : "0"; ?>">
                    <?php echo $crateStatus === "wishlist" ? "&#10003; On wishlist" : "Add to wishlist"; ?>
                </button>
            </div>

            <?php if ((int)$_SESSION["is_admin"] === 1): ?>
                <div class="admin-actions">
                    <a class="btn" href="album_form.php?id=<?php echo (int)$album["id"]; ?>">Edit album</a>
                    <a class="btn danger" href="delete_album.php?id=<?php echo (int)$album["id"]; ?>" onclick="return confirm('Delete this album and all of its tracks and reviews?');">Delete</a>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <section class="panel">
        <div class="panel-head">
            <h2>Tracklist</h2>
            <?php if (count($tracks) > 0): ?>
                <span class="muted"><?php echo count($tracks); ?> tracks &middot; <?php echo format_duration($totalSeconds); ?></span>
            <?php endif; ?>
        </div>
        <?php if (count($tracks) > 0): ?>
            <ol class="tracklist">
                <?php foreach ($tracks as $t): ?>
                    <li>
                        <span class="track-pos"><?php echo (int)$t["position"]; ?></span>
                        <span class="track-title"><?php echo htmlspecialchars($t["title"]); ?></span>
                        <span class="track-time"><?php echo $t["duration_seconds"] ? format_duration($t["duration_seconds"]) : ""; ?></span>
                    </li>
                <?php endforeach; ?>
            </ol>
        <?php else: ?>
            <p class="muted">No tracks listed for this album yet.</p>
        <?php endif; ?>
    </section>

    <section class="panel">
        <h2><?php echo $myReview ? "Your review" : "Write a review"; ?></h2>
        <form action="insert_review.php" method="post" class="review-form">
            <input type="hidden" name="album_id" value="<?php echo (int)$album["id"]; ?>">

            <div class="star-input">
                <?php for ($i = 5; $i >= 1; $i--): ?>
                    <input type="radio" name="rating" id="star<?php echo $i; ?>" value="<?php echo $i; ?>"
                        <?php echo ($myReview && (int)$myReview["rating"] === $i) ? "checked" : ""; ?> required>
                    <label for="star<?php echo $i; ?>" title="<?php echo $i; ?> star<?php echo $i === 1 ? "" : "s"; ?>">&#9733;</label>
                <?php endfor; ?>
            </div>

            <textarea name="content" rows="4" placeholder="Share your thoughts on this record..." required><?php echo $myReview ? htmlspecialchars($myReview["content"]) : ""; ?></textarea>
            <button type="submit" class="btn primary"><?php echo $myReview ? "Update review" : "Post review"; ?></button>
        </form>

        <div class="reviews">
            <h2>Reviews</h2>
            <?php if ($reviews && $reviews->num_rows > 0): ?>
                <?php while ($row = $reviews->fetch_assoc()): ?>
                    <article class="review-card" id="review-<?php echo (int)$row["id"]; ?>">
                        <?php if ((int)$_SESSION["is_admin"] === 1): ?>
                            <button type="button" class="delete-review review-x" data-review-id="<?php echo (int)$row["id"]; ?>" title="Delete review">&times;</button>
                        <?php endif; ?>
                        <div class="review-head">
                            <span class="review-author"><?php echo htmlspecialchars($row["username"]); ?><?php echo (int)$row["user_id"] === $userId ? " (you)" : ""; ?></span>
                            <?php echo render_stars($row["rating"]); ?>
                        </div>
                        <p class="review-date"><?php echo htmlspecialchars($row["created_at"]); ?></p>
                        <p class="review-text"><?php echo nl2br(htmlspecialchars($row["content"])); ?></p>
                        <div class="review-foot">
                            <?php if ((int)$row["user_id"] === $userId): ?>
                                <span class="like-static">&#128077; <?php echo (int)$row["like_count"]; ?> found this helpful</span>
                            <?php else: ?>
                                <button type="button" class="like-btn" data-review-id="<?php echo (int)$row["id"]; ?>" data-liked="<?php echo (int)$row["liked_by_me"] === 1 ? "1" : "0"; ?>">
                                    &#128077; Helpful <span class="like-count">(<?php echo (int)$row["like_count"]; ?>)</span>
                                </button>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endwhile; ?>
            <?php else: ?>
                <p class="muted">No reviews yet. Be the first to write one.</p>
            <?php endif; ?>
        </div>
    </section>
    <?php $reviewStmt->close(); ?>
<?php endif; ?>
</main>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="scripts/app.js"></script>
</body>
</html>
