<?php
require("session.php");

if (!isset($_SESSION["is_admin"]) || (int)$_SESSION["is_admin"] !== 1) {
    echo "You do not have permission to view this page.";
    exit;
}
?>
