<?php
session_start();
require("db.php");

if (!isset($_SESSION["id"])) {
    echo "error";
    exit;
}

$reviewId = (int)($_POST["review_id"] ?? 0);
$userId = (int)$_SESSION["id"];
$isAdmin = (int)($_SESSION["is_admin"] ?? 0) === 1;

if ($reviewId <= 0) {
    echo "error";
    exit;
}

// Administrators may delete any review; regular users only their own.
if ($isAdmin) {
    $del = $conn->prepare("DELETE FROM reviews WHERE id = ?");
    $del->bind_param("i", $reviewId);
} else {
    $del = $conn->prepare("DELETE FROM reviews WHERE id = ? AND user_id = ?");
    $del->bind_param("ii", $reviewId, $userId);
}

if ($del->execute() && $del->affected_rows > 0) {
    echo "ok";
} else {
    echo "error";
}
$del->close();
?>
