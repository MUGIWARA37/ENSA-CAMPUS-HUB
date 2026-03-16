<?php
session_start();
require_once 'db.php';

/**
 * FINAL REGISTRATION STEP
 * Check if the user is authorized to be here
 */
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_SESSION['temp_cne'])) {
    
    $cne = $_SESSION['temp_cne'];
    $name = $_SESSION['temp_name'] ?? 'Student';

    // Normalize and Securely Hash the answers
    // Trim and lowercase ensures better user experience during recovery
    $ans1 = password_hash(strtolower(trim($_POST['ans1'])), PASSWORD_DEFAULT);
    $ans2 = password_hash(strtolower(trim($_POST['ans2'])), PASSWORD_DEFAULT);
    $ans3 = password_hash(strtolower(trim($_POST['ans3'])), PASSWORD_DEFAULT);

    $q1 = $_POST['q1'];
    $q2 = $_POST['q2'];
    $q3 = $_POST['q3'];

    try {
        // Insert questions and hashed answers into PASSWORD_RESET table
        $stmt = $pdo->prepare("INSERT INTO PASSWORD_RESET (
            etudiant_id, question1, answer1_hash, question2, answer2_hash, question3, answer3_hash
        ) VALUES (?, ?, ?, ?, ?, ?, ?)");

        $stmt->execute([$cne, $q1, $ans1, $q2, $ans2, $q3, $ans3]);

        // Cleanup: Remove temporary session variables
        unset($_SESSION['temp_cne']);
        unset($_SESSION['temp_name']);

        // Redirect to your existing success page
        header("Location: success.php?name=" . urlencode($name));
        exit();

    } catch (PDOException $e) {
        // Handle database errors
        die("Error finalizing registration: " . $e->getMessage());
    }
} else {
    // If accessed directly without Step 1, go back
    header("Location: index.php");
    exit();
}
?>
