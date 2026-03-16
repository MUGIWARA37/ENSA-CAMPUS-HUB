<?php
session_start();
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cne              = $_POST['cne'] ?? '';
    $first_name       = $_POST['first_name'] ?? '';
    $last_name        = $_POST['last_name'] ?? '';
    $email            = $_POST['email'] ?? '';
    $classe           = $_POST['classe'] ?? '';
    $password         = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Check password match
    if ($password !== $confirm_password) {
        die("❌ Passwords do not match! <a href='index.php'>Go back</a>");
    }

    // Hash the password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    try {
        // We set prevliges to 'S' (Student) by default
        $stmt = $pdo->prepare(
            "INSERT INTO ETUDIANT (etudiant_id, fist_name, last_name, email, classe, password, prevliges) 
             VALUES (?, ?, ?, ?, ?, ?, 'S')"
        );
        $stmt->execute([$cne, $first_name, $last_name, $email, $classe, $hashed_password]);

        // STORE CNE in session for the next step (setup_recovery.php)
        $_SESSION['temp_cne'] = $cne;
        $_SESSION['temp_name'] = $first_name;

        // REDIRECT to security setup instead of success
        header("Location: setup_recovery.php");
        exit;

    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            echo "❌ CNE or Email already exists! <a href='index.php'>Try again</a>";
        } else {
            echo "❌ Error: " . $e->getMessage();
        }
    }
} else {
    header("Location: index.php");
}
?>
