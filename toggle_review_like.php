<?php
session_start();
require("db.php");

if (!isset($_SESSION["id"])) {
    echo "error";
    exit;
}

$reviewId = (int)($_POST["review_id"] ?? 0);
$userId = (int)$_SESSION["id"];

if ($reviewId <= 0) {
    echo "error";
    exit;
}

// A user cannot mark their own review as helpful.
$own = $conn->prepare("SELECT user_id FROM reviews WHERE id = ? LIMIT 1");
$own->bind_param("i", $reviewId);
$own->execute();
$review = $own->get_result()->fetch_assoc();
$own->close();

if (!$review || (int)$review["user_id"] === $userId) {
    echo "error";
    exit;
}

$check = $conn->prepare("SELECT id FROM review_likes WHERE review_id = ? AND user_id = ? LIMIT 1");
$check->bind_param("ii", $reviewId, $userId);
$check->execute();
$exists = $check->get_result()->fetch_assoc();
$check->close();

if ($exists) {
    $del = $conn->prepare("DELETE FROM review_likes WHERE review_id = ? AND user_id = ?");
    $del->bind_param("ii", $reviewId, $userId);
    $del->execute();
    $del->close();
    $state = "unliked";
} else {
    $ins = $conn->prepare("INSERT INTO review_likes (review_id, user_id) VALUES (?, ?)");
    $ins->bind_param("ii", $reviewId, $userId);
    $ins->execute();
    $ins->close();
    $state = "liked";
}

$cnt = $conn->prepare("SELECT COUNT(*) AS n FROM review_likes WHERE review_id = ?");
$cnt->bind_param("i", $reviewId);
$cnt->execute();
$count = (int)$cnt->get_result()->fetch_assoc()["n"];
$cnt->close();

echo $state . ":" . $count;
?>
