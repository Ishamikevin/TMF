<?php

session_start();

require_once "../config/database.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    header("Location: index.php");
    exit;
}

$suit_id = (int) $_GET["id"];

$user_id = $_SESSION["user_id"];

// Make sure the suit belongs to the logged-in user
$stmt = $conn->prepare(
    "SELECT id FROM suits
     WHERE id = ? AND user_id = ?"
);

$stmt->execute([
    $suit_id,
    $user_id
]);

$suit = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$suit) {
    die("Suit not found.");
}

// Delete the suit
$delete = $conn->prepare(
    "DELETE FROM suits
     WHERE id = ? AND user_id = ?"
);

$delete->execute([
    $suit_id,
    $user_id
]);

header("Location: index.php");
exit;