<?php
require("admin_guard.php");
require("db.php");
require("functions.php");

$albumId = isset($_GET["id"]) ? (int)$_GET["id"] : 0;
$album = null;
$tracks = [];

if ($albumId > 0) {
    $stmt = $conn->prepare("SELECT id, title, artist, genre_id, release_year, record_label, cover, description FROM albums WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $albumId);
    $stmt->execute();
    $album = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$album) {
        header("Location: index.php");
        exit;
    }

    $trackStmt = $conn->prepare("SELECT position, title, duration_seconds FROM tracks WHERE album_id = ? ORDER BY position ASC");
    $trackStmt->bind_param("i", $albumId);
    $trackStmt->execute();
    $trackResult = $trackStmt->get_result();
    while ($t = $trackResult->fetch_assoc()) {
        $tracks[] = $t;
    }
    $trackStmt->close();
}

$genres = $conn->query("SELECT id, name FROM genres ORDER BY name");
$isEdit = $album !== null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $isEdit ? "Edit album" : "Add album"; ?> &middot; Vinyl Crate</title>
    <link rel="stylesheet" href="styles/style.css">
</head>
<body>
<?php require("header.php"); ?>

<main class="container">
    <a class="back-link" href="<?php echo $isEdit ? "details.php?id=" . (int)$album["id"] : "index.php"; ?>">&larr; Cancel</a>
    <h1><?php echo $isEdit ? "Edit album" : "Add a new album"; ?></h1>

    <form action="album_save.php" method="post" enctype="multipart/form-data" class="simple-form album-editor">
        <?php if ($isEdit): ?>
            <input type="hidden" name="id" value="<?php echo (int)$album["id"]; ?>">
        <?php endif; ?>

        <div class="form-grid">
            <div>
                <label for="title">Title</label>
                <input type="text" name="title" id="title" value="<?php echo htmlspecialchars($album["title"] ?? ""); ?>" required>
            </div>
            <div>
                <label for="artist">Artist</label>
                <input type="text" name="artist" id="artist" value="<?php echo htmlspecialchars($album["artist"] ?? ""); ?>" required>
            </div>
            <div>
                <label for="genre_id">Genre</label>
                <select name="genre_id" id="genre_id">
                    <option value="">&mdash; none &mdash;</option>
                    <?php while ($g = $genres->fetch_assoc()): ?>
                        <option value="<?php echo (int)$g["id"]; ?>" <?php echo (isset($album["genre_id"]) && (int)$album["genre_id"] === (int)$g["id"]) ? "selected" : ""; ?>>
                            <?php echo htmlspecialchars($g["name"]); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div>
                <label for="release_year">Release year</label>
                <input type="number" name="release_year" id="release_year" min="1900" max="2100" value="<?php echo htmlspecialchars($album["release_year"] ?? ""); ?>" required>
            </div>
            <div>
                <label for="record_label">Record label</label>
                <input type="text" name="record_label" id="record_label" value="<?php echo htmlspecialchars($album["record_label"] ?? ""); ?>">
            </div>
        </div>

        <label for="description">Description</label>
        <textarea name="description" id="description" rows="4"><?php echo htmlspecialchars($album["description"] ?? ""); ?></textarea>

        <label for="cover">Cover image</label>
        <?php if ($isEdit && !empty($album["cover"])): ?>
            <div class="current-cover">
                <img src="<?php echo htmlspecialchars($album["cover"]); ?>" alt="Current cover">
                <span class="muted">Current cover &mdash; upload a new file to replace it.</span>
            </div>
        <?php endif; ?>
        <input type="file" name="cover" id="cover" accept="image/png, image/jpeg, image/webp">

        <div class="track-editor">
            <div class="panel-head">
                <label>Tracklist</label>
                <button type="button" class="btn small" id="add-track">+ Add track</button>
            </div>
            <div id="track-rows">
                <?php if ($isEdit && count($tracks) > 0): ?>
                    <?php foreach ($tracks as $t): ?>
                        <div class="track-row">
                            <input type="text" name="track_title[]" placeholder="Track title" value="<?php echo htmlspecialchars($t["title"]); ?>">
                            <input type="text" name="track_duration[]" class="duration-field" placeholder="m:ss" value="<?php echo $t["duration_seconds"] ? format_duration($t["duration_seconds"]) : ""; ?>">
                            <button type="button" class="btn small danger remove-track">&times;</button>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="track-row">
                        <input type="text" name="track_title[]" placeholder="Track title">
                        <input type="text" name="track_duration[]" class="duration-field" placeholder="m:ss">
                        <button type="button" class="btn small danger remove-track">&times;</button>
                    </div>
                <?php endif; ?>
            </div>
            <p class="muted">Leave a row empty to skip it. Duration format: minutes:seconds (e.g. 4:33).</p>
        </div>

        <button type="submit" class="btn primary"><?php echo $isEdit ? "Save changes" : "Create album"; ?></button>
    </form>
</main>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="scripts/app.js"></script>
</body>
</html>
