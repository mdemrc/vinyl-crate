<?php
require("admin_guard.php");
require("db.php");
require("functions.php");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.php");
    exit;
}

$albumId = (int)($_POST["id"] ?? 0);
$isEdit = $albumId > 0;

$title = trim($_POST["title"] ?? "");
$artist = trim($_POST["artist"] ?? "");
$genreId = ($_POST["genre_id"] ?? "") === "" ? null : (int)$_POST["genre_id"];
$year = (int)($_POST["release_year"] ?? 0);
$label = trim($_POST["record_label"] ?? "");
$description = trim($_POST["description"] ?? "");

if ($title === "" || $artist === "" || $year <= 0) {
    header("Location: " . ($isEdit ? "album_form.php?id=" . $albumId : "album_form.php"));
    exit;
}

// Keep the current cover when editing unless a valid new file is uploaded.
$existingCover = null;
if ($isEdit) {
    $stmt = $conn->prepare("SELECT cover FROM albums WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $albumId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$row) {
        header("Location: index.php");
        exit;
    }
    $existingCover = $row["cover"];
}

$cover = $existingCover;

if (isset($_FILES["cover"]) && $_FILES["cover"]["error"] === UPLOAD_ERR_OK) {
    $tmp = $_FILES["cover"]["tmp_name"];
    $size = (int)$_FILES["cover"]["size"];
    $info = @getimagesize($tmp);
    $allowed = [
        IMAGETYPE_JPEG => "jpg",
        IMAGETYPE_PNG => "png",
        IMAGETYPE_WEBP => "webp",
    ];

    if ($info !== false && isset($allowed[$info[2]]) && $size > 0 && $size <= 4 * 1024 * 1024) {
        $fileName = "covers/" . uniqid("album_", true) . "." . $allowed[$info[2]];
        if (move_uploaded_file($tmp, __DIR__ . "/" . $fileName)) {
            $cover = $fileName;
            // Remove the previously uploaded file (but never the shared seed covers).
            if ($existingCover && strpos($existingCover, "covers/seed/") !== 0
                && strpos($existingCover, "covers/") === 0 && is_file(__DIR__ . "/" . $existingCover)) {
                @unlink(__DIR__ . "/" . $existingCover);
            }
        }
    }
}

if ($isEdit) {
    $stmt = $conn->prepare("UPDATE albums SET title = ?, artist = ?, genre_id = ?, release_year = ?, record_label = ?, cover = ?, description = ? WHERE id = ?");
    $stmt->bind_param("ssiisssi", $title, $artist, $genreId, $year, $label, $cover, $description, $albumId);
    $stmt->execute();
    $stmt->close();
} else {
    $stmt = $conn->prepare("INSERT INTO albums (title, artist, genre_id, release_year, record_label, cover, description) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssiisss", $title, $artist, $genreId, $year, $label, $cover, $description);
    $stmt->execute();
    $albumId = $conn->insert_id;
    $stmt->close();
}

// Rebuild the tracklist from the submitted rows.
if ($isEdit) {
    $del = $conn->prepare("DELETE FROM tracks WHERE album_id = ?");
    $del->bind_param("i", $albumId);
    $del->execute();
    $del->close();
}

$titles = $_POST["track_title"] ?? [];
$durations = $_POST["track_duration"] ?? [];
$trackStmt = $conn->prepare("INSERT INTO tracks (album_id, position, title, duration_seconds) VALUES (?, ?, ?, ?)");
$position = 1;
foreach ($titles as $index => $trackTitle) {
    $trackTitle = trim($trackTitle);
    if ($trackTitle === "") {
        continue;
    }
    $seconds = parse_duration($durations[$index] ?? "");
    $trackStmt->bind_param("iisi", $albumId, $position, $trackTitle, $seconds);
    $trackStmt->execute();
    $position++;
}
$trackStmt->close();

header("Location: details.php?id=" . $albumId);
exit;
?>
