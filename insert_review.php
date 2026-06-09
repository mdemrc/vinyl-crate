<?php
require("session.php");
require("db.php");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.php");
    exit;
}

$albumId = (int)($_POST["album_id"] ?? 0);
$rating = (int)($_POST["rating"] ?? 0);
$content = trim($_POST["content"] ?? "");
$userId = (int)$_SESSION["id"];

if ($albumId <= 0 || $rating < 1 || $rating > 5 || $content === "") {
    header("Location: details.php?id=" . $albumId);
    exit;
}

$stmt = $conn->prepare(
    "INSERT INTO reviews (album_id, user_id, rating, content) VALUES (?, ?, ?, ?)
     ON DUPLICATE KEY UPDATE rating = VALUES(rating), content = VALUES(content)"
);
$stmt->bind_param("iiis", $albumId, $userId, $rating, $content);
$stmt->execute();
$stmt->close();

header("Location: details.php?id=" . $albumId);
exit;
?>
