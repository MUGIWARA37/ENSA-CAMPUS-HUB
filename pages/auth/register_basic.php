<?php
session_start();
require_once '../../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cne              = $_POST['cne'] ?? '';
    $first_name       = $_POST['first_name'] ?? '';
    $last_name        = $_POST['last_name'] ?? '';
    $email            = $_POST['email'] ?? '';
    $classe           = $_POST['classe'] ?? '';
    $password         = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($password !== $confirm_password) {
        die("❌ Passwords do not match! <a href='../../index.php'>Go back</a>");
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    try {
        $stmt = $pdo->prepare(
            "INSERT INTO ETUDIANT (etudiant_id, fist_name, last_name, email, classe, password, prevliges) 
             VALUES (?, ?, ?, ?, ?, ?, 'S')"
        );
        $stmt->execute([$cne, $first_name, $last_name, $email, $classe, $hashed_password]);

        $_SESSION['temp_cne']  = $cne;
        $_SESSION['temp_name'] = $first_name;

        header("Location: setup_recovery.php");
        exit;

    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            echo "❌ CNE or Email already exists! <a href='../../index.php'>Try again</a>";
        } else {
            echo "❌ Error: " . $e->getMessage();
        }
    }
} else {
    header("Location: ../../index.php");
}
?>