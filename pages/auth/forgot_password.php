<?php
session_start();
require_once '../../includes/db.php';

$step  = 1;
$error = "";

// STEP 1: FIND STUDENT & LOAD QUESTIONS
if (isset($_POST['find_user'])) {
    $cne  = trim($_POST['cne']);
    $stmt = $pdo->prepare("SELECT * FROM PASSWORD_RESET WHERE etudiant_id = ?");
    $stmt->execute([$cne]);
    $questions = $stmt->fetch();

    if ($questions) {
        $_SESSION['reset_cne'] = $cne;
        $_SESSION['q1']        = $questions['question1'];
        $_SESSION['q2']        = $questions['question2'];
        $_SESSION['q3']        = $questions['question3'];
        $step = 2;
    } else {
        $error = "No security questions found for this CNE.";
    }
}

// STEP 2: VERIFY ANSWERS
if (isset($_POST['verify_answers'])) {
    $cne  = $_SESSION['reset_cne'];
    $stmt = $pdo->prepare("SELECT * FROM PASSWORD_RESET WHERE etudiant_id = ?");
    $stmt->execute([$cne]);
    $db = $stmt->fetch();

    $ans1 = strtolower(trim($_POST['ans1']));
    $ans2 = strtolower(trim($_POST['ans2']));
    $ans3 = strtolower(trim($_POST['ans3']));

    if (password_verify($ans1, $db['answer1_hash']) &&
        password_verify($ans2, $db['answer2_hash']) &&
        password_verify($ans3, $db['answer3_hash'])) {
        $step = 3;
    } else {
        $error = "One or more answers are incorrect.";
        $step  = 2;
    }
}

// STEP 3: UPDATE PASSWORD
if (isset($_POST['update_password'])) {
    $new_pass     = $_POST['new_pass'];
    $confirm_pass = $_POST['confirm_pass'];

    if ($new_pass === $confirm_pass) {
        $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
        $stmt   = $pdo->prepare("UPDATE ETUDIANT SET password = ? WHERE etudiant_id = ?");
        $stmt->execute([$hashed, $_SESSION['reset_cne']]);

        session_destroy();
        header("Location: ../../index.php?status=reset_success");
        exit();
    } else {
        $error = "Passwords do not match.";
        $step  = 3;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password | StudentHub</title>
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="forgot_password.css">
</head>
<body>
<div class="card">
    <h2 style="margin-bottom: 10px;">Recovery</h2>

    <?php if($error): ?>
        <div class="error"><?php echo $error; ?></div>
    <?php endif; ?>

    <?php if($step == 1): ?>
        <p style="font-size: 0.8rem; opacity: 0.7; margin-bottom: 20px;">Enter your CNE to begin recovery.</p>
        <form method="POST">
            <div class="input-group">
                <i class='bx bx-id-card' style="margin-right:10px; color:#4EA685;"></i>
                <input type="text" name="cne" placeholder="CNE" required>
            </div>
            <button type="submit" name="find_user" class="btn">Next Step</button>
        </form>

    <?php elseif($step == 2): ?>
        <p style="font-size: 0.8rem; opacity: 0.7; margin-bottom: 20px;">Answer your security questions correctly.</p>
        <form method="POST">
            <div class="q-text"><?php echo htmlspecialchars($_SESSION['q1']); ?></div>
            <div class="input-group"><input type="text" name="ans1" placeholder="Your Answer" required></div>

            <div class="q-text"><?php echo htmlspecialchars($_SESSION['q2']); ?></div>
            <div class="input-group"><input type="text" name="ans2" placeholder="Your Answer" required></div>

            <div class="q-text"><?php echo htmlspecialchars($_SESSION['q3']); ?></div>
            <div class="input-group"><input type="text" name="ans3" placeholder="Your Answer" required></div>

            <button type="submit" name="verify_answers" class="btn">Verify Answers</button>
        </form>

    <?php elseif($step == 3): ?>
        <p style="font-size: 0.8rem; opacity: 0.7; margin-bottom: 20px;">Identity verified. Enter a new password.</p>
        <form method="POST">
            <div class="input-group">
                <i class='bx bx-lock-alt' style="margin-right:10px; color:#4EA685;"></i>
                <input type="password" name="new_pass" placeholder="New Password" required>
            </div>
            <div class="input-group">
                <i class='bx bx-check-shield' style="margin-right:10px; color:#4EA685;"></i>
                <input type="password" name="confirm_pass" placeholder="Confirm Password" required>
            </div>
            <button type="submit" name="update_password" class="btn">Update Password</button>
        </form>
    <?php endif; ?>

    <p class="back-link">
        <a href="../../index.php">Back to Login</a>
    </p>
</div>
</body>
</html>