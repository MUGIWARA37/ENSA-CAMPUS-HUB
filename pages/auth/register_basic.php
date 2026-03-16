<?php
session_start();
require_once '../../includes/db.php';

function showError($message) {
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Registration Error | StudentHub</title>
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="register_basic.css">
</head>
<body>
    <div class="error-box">
        <div class="error-icon"><i class='bx bx-error-circle'></i></div>
        <div class="error-title">Registration Failed</div>
        <div class="error-message"><?php echo htmlspecialchars($message); ?></div>
        <a href="../../index.php" class="btn-back"><i class='bx bx-arrow-back'></i> Go Back</a>
    </div>
</body>
</html>
<?php
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cne              = $_POST['cne'] ?? '';
    $first_name       = $_POST['first_name'] ?? '';
    $last_name        = $_POST['last_name'] ?? '';
    $email            = $_POST['email'] ?? '';
    $classe           = $_POST['classe'] ?? '';
    $password         = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($password !== $confirm_password) {
        showError("Passwords do not match. Please go back and try again.");
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    try {
        $stmt = $pdo->prepare(
            "INSERT INTO ETUDIANT (etudiant_id, fist_name, last_name, email, classe, password, privilege) 
             VALUES (?, ?, ?, ?, ?, ?, 'S')"
        );
        $stmt->execute([$cne, $first_name, $last_name, $email, $classe, $hashed_password]);

        $_SESSION['temp_cne']  = $cne;
        $_SESSION['temp_name'] = $first_name;

        header("Location: setup_recovery.php");
        exit;

    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            showError("This CNE or Email is already registered. Please use a different one.");
        } else {
            showError("A database error occurred. Please try again later.");
        }
    }
} else {
    header("Location: ../../index.php");
}
?>