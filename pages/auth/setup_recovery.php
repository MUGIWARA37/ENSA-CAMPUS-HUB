<?php
session_start();
if (!isset($_SESSION['temp_cne'])) {
    header("Location: index.php");
    exit();
}
$first_name = $_SESSION['temp_name'] ?? 'Student';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Security Setup | StudentHub</title>
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <style>
        body {
            background-color: var(--white);
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
            overflow: hidden;
        }

        .setup-container {
            width: 100%;
            max-width: 480px;
            padding: 2.5rem;
            background: var(--white);
            border-radius: 2rem;
            box-shadow: rgba(0, 0, 0, 0.15) 0px 10px 30px; 
            z-index: 10;
        }

        h2 { color: #000000; margin-bottom: 0.5rem; font-weight: 800; text-align: center; }

        .instruction {
            color: #000000;
            font-size: 0.95rem;
            margin-bottom: 1.5rem;
            text-align: center;
            font-weight: 500;
        }

        .question-label {
            display: block;
            text-align: left;
            font-weight: 700;
            color: #000000;
            font-size: 0.95rem;
            margin-bottom: 8px;
            margin-top: 15px;
        }

        .input-group { position: relative; margin-bottom: 1rem; }

        .input-group i {
            position: absolute;
            top: 50%;
            left: 1rem;
            transform: translateY(-50%);
            color: var(--primary-color);
            z-index: 2;
        }

        .input-group select, 
        .input-group input {
            width: 100%;
            padding: 1rem 1rem 1rem 3rem;
            background-color: #fcfcfc;
            border: 2px solid #d1d1d1;
            border-radius: 0.8rem;
            color: #000000 !important;
            font-size: 1rem;
            font-weight: 600;
            outline: none;
        }

        .input-group select:focus, 
        .input-group input:focus { border-color: var(--primary-color); }

        .btn-finalize {
            background-color: var(--primary-color);
            color: var(--white);
            border: none;
            padding: 1rem;
            width: 100%;
            border-radius: 0.8rem;
            font-size: 1.2rem;
            font-weight: 600;
            cursor: pointer;
            margin-top: 1.5rem;
            transition: 0.3s;
        }

        .btn-finalize:hover {
            background-color: var(--secondary-color);
            box-shadow: 0 0 15px var(--primary-color);
        }
    </style>
</head>
<body>
    <div class="setup-container">
        <form method="POST" action="save_recovery.php" id="recoveryForm">
            <h2>Security Setup</h2>
            <p class="instruction">Choose 3 unique questions to secure your account.</p>
            
            <?php for($i = 1; $i <= 3; $i++): ?>
                <label class="question-label">Question <?php echo $i; ?></label>
                <div class="input-group">
                    <i class='bx bx-help-circle'></i>
                    <select name="q<?php echo $i; ?>" class="q-select" required>
                        <option value="">-- Pick an option --</option>
                        <option value="pet">1. What was your first pet's name?</option>
                        <option value="city">2. In what city were you born?</option>
                        <option value="car">3. What was your first car model?</option>
                        <option value="school">4. What was your elementary school name?</option>
                        <option value="mother">5. What is your mother's maiden name?</option>
                    </select>
                </div>
                <div class="input-group">
                    <i class='bx bx-comment-dots'></i>
                    <input type="text" name="ans<?php echo $i; ?>" placeholder="Type your answer here" required>
                </div>
            <?php endfor; ?>
            
            <button type="submit" class="btn-finalize">Complete Registration</button>
        </form>
    </div>

    <script>
        // Select all dropdowns
        const selects = document.querySelectorAll('.q-select');

        function updateOptions() {
            // Get all currently selected values (excluding empty ones)
            const selectedValues = Array.from(selects)
                .map(select => select.value)
                .filter(value => value !== "");

            selects.forEach(select => {
                const options = select.querySelectorAll('option');
                options.forEach(option => {
                    if (option.value === "") return; // Skip the placeholder

                    // If this option is selected in ANOTHER dropdown, hide/disable it
                    const isSelectedElsewhere = selectedValues.includes(option.value) && select.value !== option.value;
                    
                    option.disabled = isSelectedElsewhere;
                    option.style.color = isSelectedElsewhere ? "#ccc" : "#000";
                });
            });
        }

        // Add event listeners to all dropdowns
        selects.forEach(select => {
            select.addEventListener('change', updateOptions);
        });
    </script>
</body>
</html>
