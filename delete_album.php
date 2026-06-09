<?php
require("admin_guard.php");
require("db.php");

$albumId = isset($_GET["id"]) ? (int)$_GET["id"] : 0;

if ($albumId <= 0) {
    header("Location: index.php");
    exit;
}

$stmt = $conn->prepare("SELECT cover FROM albums WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $albumId);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Tracks, reviews and crate entries are removed automatically by ON DELETE CASCADE.
$del = $conn->prepare("DELETE FROM albums WHERE id = ?");
$del->bind_param("i", $albumId);
$del->execute();
$del->close();

if ($row && $row["cover"] && strpos($row["cover"], "covers/seed/") !== 0
    && strpos($row["cover"], "covers/") === 0 && is_file(__DIR__ . "/" . $row["cover"])) {
    @unlink(__DIR__ . "/" . $row["cover"]);
}

header("Location: index.php");
exit;
?>
