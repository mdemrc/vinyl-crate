<?php
require("session.php");
require("db.php");
require("functions.php");

$userId = (int)$_SESSION["id"];
$status = $_GET["status"] ?? "all";
if (!in_array($status, ["all", "owned", "wishlist"], true)) {
    $status = "all";
}

$counts = ["owned" => 0, "wishlist" => 0];
$countStmt = $conn->prepare("SELECT status, COUNT(*) AS n FROM crate_items WHERE user_id = ? GROUP BY status");
$countStmt->bind_param("i", $userId);
$countStmt->execute();
$countResult = $countStmt->get_result();
while ($c = $countResult->fetch_assoc()) {
    $counts[$c["status"]] = (int)$c["n"];
}
$countStmt->close();
$total = $counts["owned"] + $counts["wishlist"];

$sql = "SELECT c.status, a.id, a.title, a.artist, a.release_year, a.cover, g.name AS genre_name, g.id AS genre_id
        FROM crate_items c
        JOIN albums a ON a.id = c.album_id
        LEFT JOIN genres g ON g.id = a.genre_id
        WHERE c.user_id = ?";
if ($status !== "all") {
    $sql .= " AND c.status = ?";
}
$sql .= " ORDER BY c.added_at DESC";

$stmt = $conn->prepare($sql);
if ($status !== "all") {
    $stmt->bind_param("is", $userId, $status);
} else {
    $stmt->bind_param("i", $userId);
}
$stmt->execute();
$items = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Crate &middot; Vinyl Crate</title>
    <link rel="stylesheet" href="styles/style.css">
</head>
<body>
<?php require("header.php"); ?>

<main class="container">
    <h1>My Crate</h1>

    <div class="decade-bar">
        <a href="crate.php?status=all" class="<?php echo $status === "all" ? "active" : ""; ?>">All (<?php echo $total; ?>)</a>
        <a href="crate.php?status=owned" class="<?php echo $status === "owned" ? "active" : ""; ?>">Collection (<?php echo $counts["owned"]; ?>)</a>
        <a href="crate.php?status=wishlist" class="<?php echo $status === "wishlist" ? "active" : ""; ?>">Wishlist (<?php echo $counts["wishlist"]; ?>)</a>
    </div>

    <?php if ($items && $items->num_rows > 0): ?>
        <div class="album-grid">
            <?php while ($album = $items->fetch_assoc()): ?>
                <article class="album-card">
                    <a class="cover-link" href="details.php?id=<?php echo (int)$album["id"]; ?>">
                        <div class="cover-wrap">
                            <span class="vinyl"></span>
                            <img class="cover" src="<?php echo htmlspecialchars($album["cover"] ?: "covers/placeholder.svg"); ?>" alt="<?php echo htmlspecialchars($album["title"]); ?> cover">
                            <span class="status-tag <?php echo $album["status"]; ?>"><?php echo $album["status"] === "owned" ? "Owned" : "Wishlist"; ?></span>
                        </div>
                    </a>
                    <div class="card-body">
                        <h3 class="card-title"><a href="details.php?id=<?php echo (int)$album["id"]; ?>"><?php echo htmlspecialchars($album["title"]); ?></a></h3>
                        <p class="card-artist"><a href="index.php?artist=<?php echo urlencode($album["artist"]); ?>"><?php echo htmlspecialchars($album["artist"]); ?></a></p>
                        <div class="crate-controls" data-album-id="<?php echo (int)$album["id"]; ?>" data-removable="1">
                            <button type="button" class="btn small crate-btn" data-target="owned"
                                    data-on="&#10003; Owned" data-off="Own it"
                                    data-active="<?php echo $album["status"] === "owned" ? "1" : "0"; ?>">
                                <?php echo $album["status"] === "owned" ? "&#10003; Owned" : "Own it"; ?>
                            </button>
                            <button type="button" class="btn small ghost crate-btn" data-target="wishlist"
                                    data-on="&#10003; Wishlist" data-off="Want it"
                                    data-active="<?php echo $album["status"] === "wishlist" ? "1" : "0"; ?>">
                                <?php echo $album["status"] === "wishlist" ? "&#10003; Wishlist" : "Want it"; ?>
                            </button>
                        </div>
                    </div>
                </article>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <p class="empty-state">
            <?php echo $status === "wishlist" ? "Your wishlist is empty." : ($status === "owned" ? "You have not marked any records as owned yet." : "Your crate is empty."); ?>
            <a href="index.php">Browse albums</a> to start filling it.
        </p>
    <?php endif; ?>
</main>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="scripts/app.js"></script>
</body>
</html>
