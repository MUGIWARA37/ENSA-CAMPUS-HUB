<?php
session_start();
if (!isset($_SESSION['temp_cne'])) {
    header("Location: ../../index.php");
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
    <link rel="stylesheet" href="../../assets/css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="setup_recovery.css">
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
        const selects = document.querySelectorAll('.q-select');

        function updateOptions() {
            const selectedValues = Array.from(selects)
                .map(select => select.value)
                .filter(value => value !== "");

            selects.forEach(select => {
                const options = select.querySelectorAll('option');
                options.forEach(option => {
                    if (option.value === "") return;
                    const isSelectedElsewhere = selectedValues.includes(option.value) && select.value !== option.value;
                    option.disabled = isSelectedElsewhere;
                    option.style.color = isSelectedElsewhere ? "#ccc" : "#000";
                });
            });
        }

        selects.forEach(select => {
            select.addEventListener('change', updateOptions);
        });
    </script>
</body>
</html>