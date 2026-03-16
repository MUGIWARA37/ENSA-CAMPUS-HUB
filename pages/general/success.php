<?php
$first_name = $_GET['name'] ?? 'User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Registration Successful</title>
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="success.css">
</head>
<body>
    <div class="success-container">
        <div class="success-row">

            <div class="success-col">
                <div class="success-content">
                    <div class="success-card">
                        <div class="success-icon">
                            <i class='bx bxs-party'></i>
                        </div>
                        <h1>🎉 Congratulations, <?php echo htmlspecialchars($first_name); ?>!</h1>
                        <p>Your registration was successful. You can now login to access your account.</p>
                        <button class="success-btn" onclick="window.location.href='../../index.php'">
                            <i class='bx bx-log-in'></i>
                            Go to Login Page
                        </button>
                    </div>
                </div>
            </div>

            <div class="success-col">
                <div class="success-text">
                    <h2>Welcome Aboard!</h2>
                    <p>Start your journey with us</p>
                </div>
            </div>

        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(() => { createConfetti(); }, 500);
        });

        function createConfetti() {
            const colors = ['#4EA685', '#57B894', '#ffeb3b', '#ff9800', '#e1306c'];
            const confettiCount = 50;

            for (let i = 0; i < confettiCount; i++) {
                const confetti = document.createElement('div');
                confetti.style.position = 'fixed';
                confetti.style.width = '10px';
                confetti.style.height = '10px';
                confetti.style.background = colors[Math.floor(Math.random() * colors.length)];
                confetti.style.borderRadius = '50%';
                confetti.style.left = Math.random() * 100 + 'vw';
                confetti.style.top = '-10px';
                confetti.style.opacity = '0.8';
                confetti.style.zIndex = '9999';
                confetti.style.pointerEvents = 'none';

                document.body.appendChild(confetti);

                const animation = confetti.animate([
                    { transform: 'translateY(0) rotate(0deg)', opacity: 1 },
                    { transform: `translateY(${window.innerHeight}px) rotate(${Math.random() * 360}deg)`, opacity: 0 }
                ], {
                    duration: Math.random() * 3000 + 2000,
                    easing: 'cubic-bezier(0.215, 0.610, 0.355, 1)'
                });

                animation.onfinish = () => confetti.remove();
            }
        }
    </script>
</body>
</html>