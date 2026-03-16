<?php
session_start();
require_once '../../includes/db.php';

// 1. ACCESS CONTROL: Strictly for Club Admins ('C')
$user_priv = $_SESSION['privileges'] ?? $_SESSION['privilege'] ?? '';
if ($user_priv !== 'C') {
    header("Location: ../general/home.php");
    exit();
}

$user_id = $_SESSION['etudiant_id'];
$message = ""; $error_message = "";

// 2. FETCH CLUB INFO
$stmtClub = $pdo->prepare("SELECT club_id, club_name FROM CLUB WHERE id_admin_etudiant = ?");
$stmtClub->execute([$user_id]);
$my_club = $stmtClub->fetch();
if (!$my_club) { header("Location: ../general/home.php?error=no_club_leader"); exit(); }
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

if (isset($_POST['request_event'])) {
    $e_name   = trim($_POST['e_name']);
    $e_fees   = $_POST['e_fees'];
    $e_budget = $_POST['e_budget'];

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
        $_SESSION['privilege']  = 'S';
        header("Location: ../general/home.php?msg=leadership_transferred");
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
    <link rel="stylesheet" href="club_admin.css">
</head>
<body>
    <nav class="navbar">
        <div style="font-weight: 800; color: var(--secondary); font-size: 1.5rem;"><i class='bx bxs-shield-crown'></i> CLUB CONSOLE</div>
        <div class="nav-links">
            <a href="../general/home.php" class="nav-btn">Dashboard</a>
            <a href="../auth/logout.php" class="nav-btn logout-btn" style="color:var(--danger);">Logout</a>
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
                                <input type="hidden" name="student_id" value="<?php echo $pe['etudiant_id']; ?>">
                                <input type="hidden" name="event_id" value="<?php echo $pe['event_id']; ?>">
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
                        <tr class="member-row"
                            data-name="<?php echo strtolower($r['fist_name']." ".$r['last_name']); ?>"
                            data-id="<?php echo $r['etudiant_id']; ?>"
                            data-date="<?php echo $r['registration_date'] ?? ''; ?>"
                            data-events="<?php echo $r['count']; ?>"
                            data-classe="<?php echo $r['classe']; ?>"
                            data-attended="<?php echo htmlspecialchars($r['attended_list'] ?: 'None'); ?>">
                            <td><?php echo $r['fist_name']." ".$r['last_name']; ?></td>
                            <td><?php echo $r['classe']; ?></td>
                            <td><?php echo $r['registration_date'] ? date('M d, Y', strtotime($r['registration_date'])) : 'N/A'; ?></td>
                            <td><?php echo $r['count']; ?></td>
                            <td style="text-align:right;">
                                <?php if($r['etudiant_id'] !== $user_id): ?>
                                <form method="POST" onsubmit="return confirm('Kick this member?')">
                                    <input type="hidden" name="student_id" value="<?php echo $r['etudiant_id']; ?>">
                                    <button type="submit" name="remove_member" class="btn-action deny">Kick</button>
                                </form>
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