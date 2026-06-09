<?php
session_start();
require("db.php");
require("functions.php");

if (!isset($_SESSION["username"])) {
    echo "";
    exit;
}

$fraza = trim($_GET["fraza"] ?? "");

if (mb_strlen($fraza) < 2) {
    echo "";
    exit;
}

$like = "%" . $fraza . "%";

$stmt = $conn->prepare(
    "SELECT a.id, a.title, a.artist, a.release_year, a.cover,
            g.id AS genre_id, g.name AS genre_name,
            ROUND(AVG(r.rating), 1) AS avg_rating, COUNT(DISTINCT r.id) AS review_count
     FROM albums a
     LEFT JOIN genres g ON g.id = a.genre_id
     LEFT JOIN reviews r ON r.album_id = a.id
     WHERE a.title LIKE ? OR a.artist LIKE ?
     GROUP BY a.id
     ORDER BY a.title ASC
     LIMIT 24"
);
$stmt->bind_param("ss", $like, $like);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo '<p class="empty-state">No albums match &ldquo;' . htmlspecialchars($fraza) . '&rdquo;.</p>';
    $stmt->close();
    exit;
}
?>
<p class="search-summary"><?php echo (int)$result->num_rows; ?> result<?php echo $result->num_rows == 1 ? "" : "s"; ?> for &ldquo;<?php echo htmlspecialchars($fraza); ?>&rdquo;</p>
<div class="album-grid">
    <?php while ($album = $result->fetch_assoc()): ?>
        <article class="album-card">
            <a class="cover-link" href="details.php?id=<?php echo (int)$album["id"]; ?>">
                <div class="cover-wrap">
                    <span class="vinyl"></span>
                    <img class="cover" src="<?php echo htmlspecialchars($album["cover"] ?: "covers/placeholder.svg"); ?>" alt="<?php echo htmlspecialchars($album["title"]); ?> cover">
                </div>
            </a>
            <div class="card-body">
                <h3 class="card-title"><a href="details.php?id=<?php echo (int)$album["id"]; ?>"><?php echo htmlspecialchars($album["title"]); ?></a></h3>
                <p class="card-artist"><a href="index.php?artist=<?php echo urlencode($album["artist"]); ?>"><?php echo htmlspecialchars($album["artist"]); ?></a></p>
                <div class="card-meta">
                    <?php if ($album["genre_name"]): ?>
                        <a class="badge" href="index.php?genre=<?php echo (int)$album["genre_id"]; ?>"><?php echo htmlspecialchars($album["genre_name"]); ?></a>
                    <?php endif; ?>
                    <span class="year"><?php echo (int)$album["release_year"]; ?></span>
                </div>
                <div class="card-rating">
                    <?php if ($album["review_count"] > 0): ?>
                        <?php echo render_stars($album["avg_rating"]); ?>
                        <span class="rating-count"><?php echo $album["avg_rating"]; ?> (<?php echo (int)$album["review_count"]; ?>)</span>
                    <?php else: ?>
                        <span class="rating-count muted">No reviews yet</span>
                    <?php endif; ?>
                </div>
            </div>
        </article>
    <?php endwhile; ?>
</div>
<?php
$stmt->close();
?>
