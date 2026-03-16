<?php
session_start();
require_once '../../includes/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_SESSION['temp_cne'])) {

    $cne  = $_SESSION['temp_cne'];
    $name = $_SESSION['temp_name'] ?? 'Student';

    $ans1 = password_hash(strtolower(trim($_POST['ans1'])), PASSWORD_DEFAULT);
    $ans2 = password_hash(strtolower(trim($_POST['ans2'])), PASSWORD_DEFAULT);
    $ans3 = password_hash(strtolower(trim($_POST['ans3'])), PASSWORD_DEFAULT);

    $q1 = $_POST['q1'];
    $q2 = $_POST['q2'];
    $q3 = $_POST['q3'];

    try {
        $stmt = $pdo->prepare("INSERT INTO PASSWORD_RESET (
            etudiant_id, question1, answer1_hash, question2, answer2_hash, question3, answer3_hash
        ) VALUES (?, ?, ?, ?, ?, ?, ?)");

        $stmt->execute([$cne, $q1, $ans1, $q2, $ans2, $q3, $ans3]);

        unset($_SESSION['temp_cne']);
        unset($_SESSION['temp_name']);

        header("Location: ../general/success.php?name=" . urlencode($name));
        exit();

    } catch (PDOException $e) {
        die("Error finalizing registration: " . $e->getMessage());
    }

} else {
    header("Location: ../../index.php");
    exit();
}
?>