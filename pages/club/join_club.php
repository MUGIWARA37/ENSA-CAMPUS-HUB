<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['etudiant_id']) || !isset($_POST['club_id'])) {
    header("Location: home.php");
    exit();
}

$etudiant_id = $_SESSION['etudiant_id'];
$club_id = $_POST['club_id'];

try {
    $stmt = $pdo->prepare("INSERT IGNORE INTO ETUDIANT_CLUB (etudiant_id, club_id) VALUES (?, ?)");
    $stmt->execute([$etudiant_id, $club_id]);
    header("Location: home.php?status=success");
} catch (PDOException $e) {
    header("Location: home.php?status=error");
}
