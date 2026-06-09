<?php
session_start();
require("db.php");

if (!isset($_SESSION["id"])) {
    echo "error";
    exit;
}

$albumId = (int)($_POST["album_id"] ?? 0);
$target = $_POST["status"] ?? "";
$userId = (int)$_SESSION["id"];

if ($albumId <= 0 || !in_array($target, ["owned", "wishlist"], true)) {
    echo "error";
    exit;
}

$stmt = $conn->prepare("SELECT status FROM crate_items WHERE user_id = ? AND album_id = ? LIMIT 1");
$stmt->bind_param("ii", $userId, $albumId);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();
$current = $row ? $row["status"] : "";

if ($current === $target) {
    $del = $conn->prepare("DELETE FROM crate_items WHERE user_id = ? AND album_id = ?");
    $del->bind_param("ii", $userId, $albumId);
    $del->execute();
    $del->close();
    echo "removed";
    exit;
}

$up = $conn->prepare(
    "INSERT INTO crate_items (user_id, album_id, status) VALUES (?, ?, ?)
     ON DUPLICATE KEY UPDATE status = VALUES(status)"
);
$up->bind_param("iis", $userId, $albumId, $target);
$up->execute();
$up->close();

echo $target;
?>
