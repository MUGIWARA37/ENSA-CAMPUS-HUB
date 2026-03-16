<?php
session_start();
require_once 'db.php';

// 1. ACCESS CONTROL: Strictly for Club Admins ('C')
$user_priv = $_SESSION['privileges'] ?? $_SESSION['privilege'] ?? '';
if ($user_priv !== 'C') {
    header("Location: home.php");
    exit();
}

$user_id = $_SESSION['etudiant_id'];
$message = ""; $error_message = "";

// 2. FETCH CLUB INFO
$stmtClub = $pdo->prepare("SELECT club_id, club_name FROM CLUB WHERE id_admin_etudiant = ?");
$stmtClub->execute([$user_id]);
$my_club = $stmtClub->fetch();
if (!$my_club) { header("Location: home.php?error=no_club_leader"); exit(); }
$club_id = $my_club['club_id'];

// 3. LOGIC SERVICES
if (isset($_POST['remove_member'])) {
    $target_student = $_POST['student_id'];
    if ($target_student === $user_id) {
        $error_message = "You cannot kick yourself.";
    } else {
        $pdo->prepare("DELETE FROM ETUDIANT_CLUB WHERE etudiant_id = ? AND club_id = ?")->execute([$target_student, $club_id]);
        $pdo->prepare("DELETE FROM EVENEMENT_ETUDIANT WHERE etudiant_id = ? AND event_id IN (SELECT event_id FROM EVENEMENT WHERE club_id = ?)")->execute([$target_student, $club_id]);
        $message = "Member has been removed.";
    }
}

if (isset($_POST['manage_club_join'])) {
    $is_approve = ($_POST['action'] === 'approve');
    $status = $is_approve ? 'accepted' : 'rejected';
    $sql = "UPDATE ETUDIANT_CLUB SET status = ?, registration_date = " . ($is_approve ? "NOW()" : "NULL") . " WHERE etudiant_id = ? AND club_id = ?";
    $pdo->prepare($sql)->execute([$status, $_POST['student_id'], $club_id]);
    $message = "Club request updated!";
}

if (isset($_POST['manage_event_join'])) {
    $is_approve = ($_POST['action'] === 'approve');
    $status = $is_approve ? 'accepted' : 'rejected';
    $sql = "UPDATE EVENEMENT_ETUDIANT SET status = ?, registration_date = " . ($is_approve ? "NOW()" : "NULL") . " WHERE etudiant_id = ? AND event_id = ?";
    $pdo->prepare($sql)->execute([$status, $_POST['student_id'], $_POST['event_id']]);
    $message = "Event participation updated!";
}

// UPDATED: Event Request Logic with Duplicate Check
if (isset($_POST['request_event'])) {
    $e_name = trim($_POST['e_name']);
    $e_fees = $_POST['e_fees'];
    $e_budget = $_POST['e_budget'];

    // CHECK FOR DUPLICATE NAME
    $stmtCheck = $pdo->prepare("SELECT event_id FROM EVENEMENT WHERE event_name = ?");
    $stmtCheck->execute([$e_name]);
    
    if ($stmtCheck->fetch()) {
        $error_message = "Rejected: An event named '" . htmlspecialchars($e_name) . "' already exists in the system.";
    } elseif ($e_fees < 0 || $e_budget < 0) {
        $error_message = "Fees and Capital Needed cannot be negative values.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO EVENEMENT (event_name, event_type, event_date_start, event_date_end, place, participation_fees, event_budget, club_id, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
            $stmt->execute([
                $e_name,
                $_POST['e_type'],
                $_POST['e_start'],
                $_POST['e_end'],
                $_POST['e_place'],
                $e_fees,
                $e_budget,
                $club_id
            ]);
            $new_id = $pdo->lastInsertId();
            // Club admin is automatically accepted into their own event
            $pdo->prepare("INSERT INTO EVENEMENT_ETUDIANT (etudiant_id, event_id, status, registration_date) VALUES (?, ?, 'accepted', NOW())")->execute([$user_id, $new_id]);
            $message = "Event request submitted successfully!";
        } catch (Exception $e) { $error_message = "Database Error: " . $e->getMessage(); }
    }
}

if (isset($_POST['transfer_leadership'])) {
    try {
        $pdo->beginTransaction();
        $new_admin = $_POST['new_admin_id'];
        $pdo->prepare("UPDATE CLUB SET id_admin_etudiant = ? WHERE club_id = ?")->execute([$new_admin, $club_id]);
        $pdo->prepare("UPDATE ETUDIANT SET privilege = 'C' WHERE etudiant_id = ?")->execute([$new_admin]);
        $pdo->prepare("UPDATE ETUDIANT SET privilege = 'S' WHERE etudiant_id = ?")->execute([$user_id]);
        $pdo->commit();
        $_SESSION['privileges'] = 'S';
        $_SESSION['privilege'] = 'S';
        header("Location: home.php?msg=leadership_transferred");
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $error_message = "Leadership transfer failed.";
    }
}

// 4. DATA FETCHING
$pending_members = $pdo->prepare("SELECT e.* FROM ETUDIANT e JOIN ETUDIANT_CLUB ec ON e.etudiant_id = ec.etudiant_id WHERE ec.club_id = ? AND ec.status = 'pending'");
$pending_members->execute([$club_id]); $all_pending_members = $pending_members->fetchAll();

$pending_events = $pdo->prepare("SELECT e.fist_name, e.last_name, e.etudiant_id, ev.event_name, ev.event_id FROM ETUDIANT e JOIN EVENEMENT_ETUDIANT ee ON e.etudiant_id = ee.etudiant_id JOIN EVENEMENT ev ON ee.event_id = ev.event_id WHERE ev.club_id = ? AND ee.status = 'pending'");
$pending_events->execute([$club_id]); $all_pending_events = $pending_events->fetchAll();

$rank_stmt = $pdo->prepare("
    SELECT e.etudiant_id, e.fist_name, e.last_name, e.classe, ec.registration_date,
    GROUP_CONCAT(ev.event_name SEPARATOR ', ') as attended_list,
    COUNT(ee.event_id) as count
    FROM ETUDIANT e
    JOIN ETUDIANT_CLUB ec ON e.etudiant_id = ec.etudiant_id
    LEFT JOIN EVENEMENT_ETUDIANT ee ON e.etudiant_id = ee.etudiant_id AND ee.status = 'accepted'
    LEFT JOIN EVENEMENT ev ON ee.event_id = ev.event_id AND ev.club_id = ?
    WHERE ec.club_id = ? AND ec.status = 'accepted'
    GROUP BY e.etudiant_id
");
$rank_stmt->execute([$club_id, $club_id]);
$rankings = $rank_stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Club Admin | Dashboard</title>
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;800&display=swap');
        :root { --primary: #4EA685; --secondary: #57B894; --danger: #ff4d4d; --glass: rgba(255, 255, 255, 0.08); --border: rgba(255, 255, 255, 0.15); }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background: linear-gradient(rgba(0,0,0,0.95), rgba(0,0,0,0.95)), url('https://images.unsplash.com/photo-1517245386807-bb43f82c33c4?q=80&w=2070'); background-size: cover; background-attachment: fixed; color: #fff; min-height: 100vh; padding-bottom: 50px; }
        .navbar { display: flex; justify-content: space-between; align-items: center; padding: 1.2rem 8%; background: rgba(0, 0, 0, 0.6); backdrop-filter: blur(20px); border-bottom: 1px solid rgba(255,255,255,0.05); position: sticky; top: 0; z-index: 1000; }
        .nav-links { display: flex; align-items: center; gap: 20px; }
        .nav-btn { text-decoration: none; color: #fff; padding: 10px 18px; border-radius: 12px; font-size: 0.85rem; font-weight: 600; transition: 0.3s; border: 1px solid transparent; }
        .nav-btn:hover { color: var(--secondary); background: rgba(78, 166, 133, 0.1); border-color: var(--secondary); box-shadow: 0 0 15px var(--primary); }
        .logout-btn:hover { border-color: var(--danger) !important; color: var(--danger) !important; box-shadow: 0 0 15px var(--danger) !important; background: rgba(255, 77, 77, 0.1) !important; }
        .container { max-width: 1250px; margin: 3rem auto; padding: 0 20px; }
        .admin-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 25px; }
        .row-full { grid-column: span 2; }
        .card { background: var(--glass); backdrop-filter: blur(20px); border: 1px solid var(--border); border-radius: 28px; padding: 30px; box-shadow: 0 15px 35px rgba(0,0,0,0.4); }
        h3 { color: var(--secondary); margin-bottom: 20px; display: flex; align-items: center; gap: 12px; font-size: 1.3rem; }
        input, select { width: 100%; padding: 12px; background: rgba(0,0,0,0.4); border: 1px solid var(--border); border-radius: 14px; color: #fff; outline: none; margin-bottom: 15px; }
        .btn { background: var(--primary); color: white; border: none; padding: 14px; border-radius: 16px; cursor: pointer; font-weight: 700; width: 100%; transition: 0.4s; }
        .btn:hover { background: var(--secondary); box-shadow: 0 0 20px var(--primary); transform: translateY(-2px); }
        .btn-danger-glow { background: var(--danger); color: white; border: none; padding: 14px; border-radius: 16px; cursor: pointer; font-weight: 700; width: 100%; transition: 0.4s; }
        .btn-danger-glow:hover { box-shadow: 0 0 20px var(--danger); transform: translateY(-2px); }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 15px 10px; border-bottom: 1px solid rgba(255,255,255,0.05); font-size: 0.85rem; }
        .btn-action { background: none; border: none; font-weight: 800; cursor: pointer; transition: 0.2s; padding: 0 5px; font-size: 0.75rem; }
        .approve { color: var(--primary); } .deny { color: var(--danger); }
        #resultBox { margin-top: 20px; background: rgba(0,0,0,0.3); padding: 20px; border-radius: 20px; border: 1px dashed var(--secondary); display: none; }
        .res-item { margin-bottom: 8px; font-size: 0.9rem; }
        .res-label { color: var(--secondary); font-weight: 600; margin-right: 5px; }
        .alert { padding: 15px; border-radius: 15px; margin-bottom: 20px; font-weight: 600; border: 1px solid; }
        .alert-success { background: rgba(78, 166, 133, 0.2); color: var(--secondary); border-color: var(--primary); }
        .alert-error { background: rgba(255, 77, 77, 0.2); color: var(--danger); border-color: var(--danger); }
    </style>
</head>
<body>

    <nav class="navbar">
        <div style="font-weight: 800; color: var(--secondary); font-size: 1.5rem;"><i class='bx bxs-shield-crown'></i> CLUB CONSOLE</div>
        <div class="nav-links">
            <a href="home.php" class="nav-btn">Dashboard</a>
            <a href="logout.php" class="nav-btn logout-btn" style="color:var(--danger);">Logout</a>
        </div>
    </nav>

    <div class="container">
        <?php if($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        <?php if($error_message): ?>
            <div class="alert alert-error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <h2 style="margin-bottom: 30px; font-weight: 800;">CLUB: <?php echo strtoupper(htmlspecialchars($my_club['club_name'])); ?></h2>

        <div class="admin-grid">

            <div class="card row-full">
                <h3><i class='bx bx-party'></i> Request New Event</h3>
                <form method="POST" style="display:grid; grid-template-columns: repeat(4, 1fr); gap:15px;">
                    <input type="text" name="e_name" placeholder="Event Name" required>
                    <input type="text" name="e_type" placeholder="Category" required>
                    <input type="text" name="e_place" placeholder="Location" required>

                    <input type="number" step="0.01" min="0" name="e_budget" placeholder="Capital Needed (DH)" required>
                    <input type="number" min="0" name="e_fees" placeholder="Fee Per Person (DH)" required>

                    <input type="datetime-local" name="e_start" required title="Start Date">
                    <input type="datetime-local" name="e_end" required title="End Date">
                    <button type="submit" name="request_event" class="btn">SUBMIT REQUEST</button>
                </form>
            </div>

            <div class="card">
                <h3><i class='bx bx-user-plus'></i> Join Requests</h3>
                <table>
                    <?php foreach($all_pending_members as $m): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($m['fist_name']." ".$m['last_name']); ?></td>
                        <td style="text-align:right;">
                            <form method="POST">
                                <input type="hidden" name="student_id" value="<?php echo $m['etudiant_id']; ?>">
                                <button type="submit" name="manage_club_join" onclick="this.form.act1.value='approve'" class="btn-action approve">ACCEPT</button>
                                <button type="submit" name="manage_club_join" onclick="this.form.act1.value='deny'" class="btn-action deny">DENY</button>
                                <input type="hidden" name="action" id="act1">
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>

            <div class="card">
                <h3><i class='bx bx-id-card'></i> Participation Requests</h3>
                <table>
                    <?php foreach($all_pending_events as $pe): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($pe['fist_name']); ?></strong><br><small><?php echo htmlspecialchars($pe['event_name']); ?></small></td>
                        <td style="text-align:right;">
                            <form method="POST">
                                <input type="hidden" name="student_id" value="<?php echo $pe['etudiant_id']; ?>"><input type="hidden" name="event_id" value="<?php echo $pe['event_id']; ?>">
                                <button type="submit" name="manage_event_join" onclick="this.form.act2.value='approve'" class="btn-action approve">ACCEPT</button>
                                <button type="submit" name="manage_event_join" onclick="this.form.act2.value='deny'" class="btn-action deny">DENY</button>
                                <input type="hidden" name="action" id="act2">
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>

            <div class="card row-full">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h3 style="margin-bottom: 0;"><i class='bx bx-trophy'></i> Members & Rankings</h3>
                    <select id="memberFilter" style="width: 200px; margin-bottom: 0; padding: 8px;" onchange="sortMembers()">
                        <option value="events">Events Attended</option>
                        <option value="date">Subscription Date</option>
                        <option value="name">Name</option>
                    </select>
                </div>
                <table id="memberTable">
                    <thead><tr style="color:var(--secondary); font-weight:800;"><td>Name</td><td>Classe</td><td>Joined On</td><td>Events</td><td style="text-align:right;">Action</td></tr></thead>
                    <tbody id="memberBody">
                        <?php foreach($rankings as $r): ?>
                        <tr class="member-row" data-name="<?php echo strtolower($r['fist_name']." ".$r['last_name']); ?>" data-id="<?php echo $r['etudiant_id']; ?>" data-date="<?php echo $r['registration_date'] ?? ''; ?>" data-events="<?php echo $r['count']; ?>" data-classe="<?php echo $r['classe']; ?>" data-attended="<?php echo htmlspecialchars($r['attended_list'] ?: 'None'); ?>">
                            <td><?php echo $r['fist_name']." ".$r['last_name']; ?></td>
                            <td><?php echo $r['classe']; ?></td>
                            <td><?php echo $r['registration_date'] ? date('M d, Y', strtotime($r['registration_date'])) : 'N/A'; ?></td>
                            <td><?php echo $r['count']; ?></td>
                            <td style="text-align:right;">
                                <?php if($r['etudiant_id'] !== $user_id): ?>
                                <form method="POST" onsubmit="return confirm('Kick this member?')"><input type="hidden" name="student_id" value="<?php echo $r['etudiant_id']; ?>"><button type="submit" name="remove_member" class="btn-action deny">Kick</button></form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="card">
                <h3><i class='bx bx-transfer'></i> Transfer Leadership</h3>
                <form method="POST" onsubmit="return confirm('Make this person the new admin? You will stay in the club as a student.')">
                    <select name="new_admin_id" required>
                        <option value="">Select Member...</option>
                        <?php foreach($rankings as $m) if($m['etudiant_id'] !== $user_id) echo "<option value='{$m['etudiant_id']}'>{$m['fist_name']} {$m['last_name']}</option>"; ?>
                    </select>
                    <button type="submit" name="transfer_leadership" class="btn-danger-glow">TRANSFER LEADERSHIP</button>
                </form>
            </div>

            <div class="card">
                <h3><i class='bx bx-search-alt'></i> Members Lookup</h3>
                <input type="text" id="searchInput" placeholder="Search by name or ID..." onkeyup="searchMember()">
                <div id="resultBox">
                    <div class="res-item"><span class="res-label">Full Name:</span> <span id="resName"></span></div>
                    <div class="res-item"><span class="res-label">Classe:</span> <span id="resClasse"></span></div>
                    <div class="res-item"><span class="res-label">Club History:</span> <span id="resEvents"></span></div>
                    <div class="res-item"><span class="res-label">Joined On:</span> <span id="resDate"></span></div>
                    <form method="POST" onsubmit="return confirm('Remove this member?')" id="resKickForm">
                        <input type="hidden" name="student_id" id="resId">
                        <button type="submit" name="remove_member" class="btn-danger-glow" style="padding: 8px; font-size: 0.8rem; margin-top: 10px;">REMOVE FROM CLUB</button>
                    </form>
                </div>
            </div>

        </div>
    </div>

    <script>
        function searchMember() {
            let input = document.getElementById("searchInput").value.toLowerCase();
            let resultBox = document.getElementById("resultBox");
            let rows = document.getElementsByClassName("member-row");
            let found = false;
            if (input.length < 2) { resultBox.style.display = "none"; return; }
            for (let row of rows) {
                let name = row.getAttribute('data-name');
                let id = row.getAttribute('data-id');
                if (name.includes(input) || id.includes(input)) {
                    document.getElementById("resName").innerText = row.cells[0].innerText;
                    document.getElementById("resClasse").innerText = row.getAttribute('data-classe');
                    document.getElementById("resEvents").innerText = row.getAttribute('data-attended');
                    document.getElementById("resDate").innerText = row.cells[2].innerText;
                    document.getElementById("resId").value = id;
                    document.getElementById("resKickForm").style.display = (id == "<?php echo $user_id; ?>") ? "none" : "block";
                    resultBox.style.display = "block";
                    found = true;
                    break;
                }
            }
            if (!found) resultBox.style.display = "none";
        }

        function sortMembers() {
            const filter = document.getElementById('memberFilter').value;
            const body = document.getElementById('memberBody');
            const rows = Array.from(body.getElementsByClassName('member-row'));
            rows.sort((a, b) => {
                if (filter === 'events') return b.dataset.events - a.dataset.events;
                if (filter === 'date') return new Date(a.dataset.date) - new Date(b.dataset.date);
                return a.dataset.name.localeCompare(b.dataset.name);
            });
            rows.forEach(row => body.appendChild(row));
        }
        window.onload = sortMembers;
    </script>
</body>
</html>
