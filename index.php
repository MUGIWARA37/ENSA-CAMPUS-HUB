<?php
// Start session at the VERY TOP
session_start();

// If already logged in, redirect to home
if (isset($_SESSION['etudiant_id'])) {
    header("Location: pages/general/home.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login / Register | StudentHub</title>
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">
</head>
<body>

<div id="container" class="container sign-in">

    <div class="row">
        <div class="col align-items-center flex-col sign-up">
            <div class="form-wrapper align-items-center">
                <form class="form sign-up" method="POST" action="pages/auth/register_basic.php">
                    <div class="input-group">
                        <i class='bx bxs-user'></i>
                        <input type="text" name="first_name" placeholder="First Name" required>
                    </div>
                    <div class="input-group">
                        <i class='bx bxs-user'></i>
                        <input type="text" name="last_name" placeholder="Last Name" required>
                    </div>
                    <div class="input-group">
                        <i class='bx bx-mail-send'></i>
                        <input type="email" name="email" placeholder="Email" required>
                    </div>
                    <div class="input-group">
                        <i class='bx bx-id-card'></i>
                        <input type="text" name="cne" placeholder="CNE (Student ID)" required>
                    </div>
                    
                    <div class="input-group">
                        <i class='bx bxs-buildings'></i>
                        <select name="classe" id="sector" required>
                            <option value="">Select Your Field</option>
                            <option value="GI1">GI1</option>
                            <option value="GI2">GI2</option>
                            <option value="GI3">GI3</option>
                            <option value="IID1">IID1</option>
                            <option value="IID2">IID2</option>
                            <option value="IID3">IID3</option>
                            <option value="MGSI1">MGSI1</option>
                            <option value="MGSI2">MGSI2</option>
                            <option value="MGSI3">MGSI3</option>
                            <option value="IRIC1">IRIC1</option>
                            <option value="IRIC2">IRIC2</option>
                            <option value="IRIC3">IRIC3</option>
                            <option value="GE1">GE1</option>
                            <option value="GE2">GE2</option>
                            <option value="GE3">GE3</option>
                            <option value="GPEE1">GPEE1</option>
                            <option value="GPEE2">GPEE2</option>
                            <option value="GPEE3">GPEE3</option>
                            <option value="API1">API1</option>
                            <option value="API2">API2</option>
                            <option value="MASTER">MASTER</option>
                        </select>
                    </div>

                    <div class="input-group">
                        <i class='bx bxs-lock-alt'></i>
                        <input type="password" name="password" placeholder="Password" required>
                    </div>
                    <div class="input-group">
                        <i class='bx bxs-lock-alt'></i>
                        <input type="password" name="confirm_password" placeholder="Confirm Password" required>
                    </div>

                    <button type="submit">Continue to Security Setup</button>
                    <p>Already have an account? <b class="pointer" onclick="toggle()">Sign in here</b></p>
                </form>
            </div>
        </div>

        <div class="col align-items-center flex-col sign-in">
            <div class="form-wrapper align-items-center">
                <form class="form sign-in" method="POST" action="pages/auth/login.php">
                    <div class="input-group">
                        <i class='bx bx-id-card'></i>
                        <input type="text" name="cne" placeholder="CNE" required>
                    </div>
                    <div class="input-group">
                        <i class='bx bxs-lock-alt'></i>
                        <input type="password" name="password" placeholder="Password" required>
                    </div>

		   <?php if(isset($_GET['error']) && $_GET['error'] === 'invalid'): ?>
    		    <div style="color:#ff4d4d; font-size:0.85rem; text-align:center; margin-bottom:10px; font-weight:600;">
        		<i class='bx bx-error-circle'></i> Incorrect CNE or Password.
    		    </div>
		    <?php endif; ?> 
                    <button type="submit">Sign In</button>

                    <div class="forgot-password-box">
                        <a href="pages/auth/forgot_password.php" class="forgot-password-link">Forgot password?</a>
                    </div>

                    <p>Don't have an account? <b class="pointer" onclick="toggle()">Sign up here</b></p>
                </form>
            </div>
        </div>
    </div>

    <div class="row content-row">
        <div class="col align-items-center flex-col">
            <div class="text sign-in">
                <h2>Welcome Back</h2>
            </div>
        </div>
        <div class="col align-items-center flex-col">
            <div class="text sign-up">
                <h2>Join the Hub</h2>
            </div>
        </div>
    </div>

</div>

<script src="assets/js/script.js"></script>
</body>
</html>
