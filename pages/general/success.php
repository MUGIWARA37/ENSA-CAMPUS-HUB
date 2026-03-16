<?php
$first_name = $_GET['name'] ?? 'User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Registration Successful</title>
    
    <!-- BOXICONS -->
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    
    <!-- CSS -->
    <link rel="stylesheet" href="style.css">
    
    <style>
        /* Success page specific styles */
        .success-container {
            position: relative;
            min-height: 100vh;
            overflow: hidden;
        }
        
        .success-container::before {
            content: "";
            position: absolute;
            top: 0;
            right: 0;
            height: 100vh;
            width: 300vw;
            transform: translate(35%, 0);
            background-image: linear-gradient(-45deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            z-index: 6;
            box-shadow: rgba(0, 0, 0, 0.35) 0px 5px 15px;
            border-bottom-right-radius: max(50vw, 50vh);
            border-top-left-radius: max(50vw, 50vh);
        }
        
        .success-row {
            display: flex;
            flex-wrap: wrap;
            height: 100vh;
        }
        
        .success-col {
            width: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            flex-direction: column;
        }
        
        .success-content {
            position: relative;
            z-index: 7;
            width: 100%;
            max-width: 28rem;
        }
        
        .success-card {
            padding: 3rem 2rem;
            background-color: var(--white);
            border-radius: 1.5rem;
            width: 100%;
            box-shadow: rgba(0, 0, 0, 0.35) 0px 5px 15px;
            text-align: center;
            animation: fadeInScale 0.8s ease;
        }
        
        @keyframes fadeInScale {
            from {
                opacity: 0;
                transform: scale(0.9) translateY(20px);
            }
            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }
        
        .success-icon {
            font-size: 5rem;
            color: var(--primary-color);
            margin-bottom: 1.5rem;
            animation: bounce 1s ease infinite alternate;
        }
        
        @keyframes bounce {
            from { transform: translateY(0); }
            to { transform: translateY(-10px); }
        }
        
        .success-card h1 {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--primary-color);
            margin-bottom: 1rem;
            line-height: 1.3;
        }
        
        .success-card p {
            color: var(--gray-2);
            font-size: 1.1rem;
            margin-bottom: 2rem;
            line-height: 1.6;
        }
        
        .success-btn {
            cursor: pointer;
            width: 100%;
            padding: 1rem 0;
            border-radius: .5rem;
            border: none;
            background-color: var(--primary-color);
            color: var(--white);
            font-size: 1.2rem;
            font-weight: 600;
            outline: none;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.8rem;
            margin-top: 1rem;
        }
        
        .success-btn:hover {
            background-color: var(--secondary-color);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        .success-text {
            margin: 4rem;
            color: var(--white);
            position: relative;
            z-index: 7;
        }
        
        .success-text h2 {
            font-size: 3.5rem;
            font-weight: 800;
            margin: 2rem 0;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
        }
        
        .success-text p {
            font-weight: 600;
            font-size: 1.2rem;
        }
        
        /* Responsive */
        @media only screen and (max-width: 768px) {
            .success-col {
                width: 100%;
                height: 50vh;
            }
            
            .success-text h2 {
                font-size: 2.5rem;
            }
            
            .success-card {
                max-width: 90%;
                padding: 2rem 1.5rem;
            }
            
            .success-card h1 {
                font-size: 2rem;
            }
            
            .success-icon {
                font-size: 4rem;
            }
            
            .success-container::before {
                border-radius: 0;
            }
        }
        
        @media only screen and (max-width: 425px) {
            .success-text h2 {
                font-size: 2rem;
                margin: 1rem 0;
            }
            
            .success-card h1 {
                font-size: 1.8rem;
            }
            
            .success-icon {
                font-size: 3.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="success-container">
        <div class="success-row">
            <!-- Right side - Success card -->
            <div class="success-col">
                <div class="success-content">
                    <div class="success-card">
                        <div class="success-icon">
                            <i class='bx bxs-party'></i>
                        </div>
                        <h1>🎉 Congratulations, <?php echo htmlspecialchars($first_name); ?>!</h1>
                        <p>Your registration was successful. You can now login to access your account.</p>
                        
                        <button class="success-btn" onclick="window.location.href='index.php'">
                            <i class='bx bx-log-in'></i>
                            Go to Login Page
                        </button>
                    </div>
                </div>
            </div>

            <!-- Left side - Welcome text -->
            <div class="success-col">
                <div class="success-text">
                    <h2>Welcome Aboard!</h2>
                    <p>Start your journey with us</p>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Add confetti effect
        document.addEventListener('DOMContentLoaded', function() {
            // Simple confetti effect
            setTimeout(() => {
                createConfetti();
            }, 500);
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
                
                // Animation
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
