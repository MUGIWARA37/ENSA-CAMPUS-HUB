<?php
session_start();
require_once 'db.php';

// Check if user is logged in
if (!isset($_SESSION['etudiant_id'])) {
    header("Location: index.php");
    exit();
}

if (isset($_POST['event_id'])) {
    $etudiant_id = $_SESSION['etudiant_id'];
    $event_id = $_POST['event_id'];

    try {
        // 1. Check if already registered to prevent duplicates
        $check = $pdo->prepare("SELECT COUNT(*) FROM EVENEMENT_ETUDIANT WHERE etudiant_id = ? AND event_id = ?");
        $check->execute([$etudiant_id, $event_id]);

        if ($check->fetchColumn() == 0) {
            // 2. Insert into the many-to-many table
            $stmt = $pdo->prepare("INSERT INTO EVENEMENT_ETUDIANT (etudiant_id, event_id) VALUES (?, ?)");
            $stmt->execute([$etudiant_id, $event_id]);
            
            // Redirect back with success message
            header("Location: home.php?status=success");
        } else {
            header("Location: home.php?status=already_joined");
        }
    } catch (PDOException $e) {
        header("Location: home.php?status=error");
    }
} else {
    header("Location: home.php");
}
exit();
