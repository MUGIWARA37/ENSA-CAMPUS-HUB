<?php
session_start();
require_once '../../includes/db.php';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $cne = trim($_POST['cne']);
    $password = $_POST['password'];
    try {
        $stmt = $pdo->prepare("SELECT etudiant_id, fist_name, password, privilege FROM ETUDIANT WHERE etudiant_id = ?");
        $stmt->execute([$cne]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id();
            $_SESSION['etudiant_id'] = $user['etudiant_id'];
            $_SESSION['first_name'] = $user['fist_name'];
            $_SESSION['privilege'] = $user['privilege'];
            header("Location: ../../pages/general/home.php");
            exit();
        } else {
            header("Location: ../../index.php?error=invalid");
            exit();
        }
    } catch (PDOException $e) {
        die("Error: " . $e->getMessage());
    }
}
?>
