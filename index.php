<?php
require("session.php");
require("db.php");
require("functions.php");

$genreId = isset($_GET["genre"]) ? (int)$_GET["genre"] : 0;
$decade = isset($_GET["decade"]) ? (int)$_GET["decade"] : 0;
$artist = trim($_GET["artist"] ?? "");
$sort = $_GET["sort"] ?? "newest";

$conditions = [];
$params = [];
$types = "";

if ($genreId > 0) {
    $conditions[] = "a.genre_id = ?";
    $params[] = $genreId;
    $types .= "i";
}
if ($decade > 0) {
    $conditions[] = "a.release_year BETWEEN ? AND ?";
    $params[] = $decade;
    $params[] = $decade + 9;
    $types .= "ii";
}
if ($artist !== "") {
    $conditions[] = "a.artist = ?";
    $params[] = $artist;
    $types .= "s";
}

$where = $conditions ? "WHERE " . implode(" AND ", $conditions) : "";

$sortOptions = [
    "newest" => "a.created_at DESC",
    "rating" => "avg_rating DESC",
    "year" => "a.release_year DESC",
    "title" => "a.title ASC",
];
$orderBy = $sortOptions[$sort] ?? $sortOptions["newest"];

$sql = "SELECT a.id, a.title, a.artist, a.release_year, a.cover,
               g.id AS genre_id, g.name AS genre_name,
               ROUND(AVG(r.rating), 1) AS avg_rating, COUNT(DISTINCT r.id) AS review_count
        FROM albums a
        LEFT JOIN genres g ON g.id = a.genre_id
        LEFT JOIN reviews r ON r.album_id = a.id
        $where
        GROUP BY a.id
        ORDER BY $orderBy, a.title ASC";

$stmt = $conn->prepare($sql);
if ($types !== "") {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$albums = $stmt->get_result();

$genres = $conn->query("SELECT id, name FROM genres ORDER BY name");
$decades = $conn->query("SELECT DISTINCT FLOOR(release_year / 10) * 10 AS d FROM albums ORDER BY d");

$activeLabel = "";
if ($genreId > 0) {
    $g = $conn->query("SELECT name FROM genres WHERE id = " . $genreId)->fetch_assoc();
    $activeLabel = $g ? "Genre: " . $g["name"] : "";
} elseif ($decade > 0) {
    $activeLabel = "Decade: " . $decade . "s";
} elseif ($artist !== "") {
    $activeLabel = "Artist: " . $artist;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse &middot; Vinyl Crate</title>
    <link rel="stylesheet" href="styles/style.css">
</head>
<body>
<?php require("header.php"); ?>

<main class="container">
    <div class="page-head">
        <h1>Browse the crate</h1>
        <?php if ((int)$_SESSION["is_admin"] === 1): ?>
            <a class="btn primary" href="album_form.php">+ Add album</a>
        <?php endif; ?>
    </div>

    <div class="toolbar">
        <div class="search-box">
            <input type="text" id="search-input" placeholder="Search by album or artist...">
        </div>
        <form class="filter-form" method="get" action="index.php">
            <select name="genre" onchange="this.form.submit()">
                <option value="0">All genres</option>
                <?php while ($g = $genres->fetch_assoc()): ?>
                    <option value="<?php echo (int)$g["id"]; ?>" <?php echo $genreId === (int)$g["id"] ? "selected" : ""; ?>>
                        <?php echo htmlspecialchars($g["name"]); ?>
                    </option>
                <?php endwhile; ?>
            </select>
            <select name="sort" onchange="this.form.submit()">
                <option value="newest" <?php echo $sort === "newest" ? "selected" : ""; ?>>Newest first</option>
                <option value="rating" <?php echo $sort === "rating" ? "selected" : ""; ?>>Top rated</option>
                <option value="year" <?php echo $sort === "year" ? "selected" : ""; ?>>Release year</option>
                <option value="title" <?php echo $sort === "title" ? "selected" : ""; ?>>Title A&ndash;Z</option>
            </select>
        </form>
    </div>

    <div class="decade-bar">
        <span class="decade-label">Decades:</span>
        <a href="index.php" class="<?php echo $decade === 0 && $genreId === 0 && $artist === "" ? "active" : ""; ?>">All</a>
        <?php while ($d = $decades->fetch_assoc()): $dv = (int)$d["d"]; ?>
            <a href="index.php?decade=<?php echo $dv; ?>" class="<?php echo $decade === $dv ? "active" : ""; ?>"><?php echo $dv; ?>s</a>
        <?php endwhile; ?>
    </div>

    <div id="search-results"></div>

    <div id="catalog">
        <?php if ($activeLabel !== ""): ?>
            <p class="active-filter"><?php echo htmlspecialchars($activeLabel); ?> <a href="index.php">&times; clear</a></p>
        <?php endif; ?>

        <?php if ($albums && $albums->num_rows > 0): ?>
            <div class="album-grid">
                <?php while ($album = $albums->fetch_assoc()): ?>
                    <article class="album-card">
                        <a class="cover-link" href="details.php?id=<?php echo (int)$album["id"]; ?>">
                            <div class="cover-wrap">
                                <span class="vinyl"></span>
                                <img class="cover"
                                     src="<?php echo htmlspecialchars($album["cover"] ?: "covers/placeholder.svg"); ?>"
                                     alt="<?php echo htmlspecialchars($album["title"]); ?> cover">
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
        <?php else: ?>
            <p class="empty-state">No albums match this filter.</p>
        <?php endif; ?>
    </div>
</main>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="scripts/app.js"></script>
</body>
</html>
