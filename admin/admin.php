<?php
session_start();
require_once '../includes/db.php';

// 1. ROBUST ACCESS CONTROL
$user_id = $_SESSION['etudiant_id'] ?? null;
$user_priv = $_SESSION['privilege'] ?? null;

if ($user_id && (!$user_priv || $user_priv !== 'A')) {
    $stmt = $pdo->prepare("SELECT privilege FROM ETUDIANT WHERE etudiant_id = ?");
    $stmt->execute([$user_id]);
    $db_priv = $stmt->fetchColumn();
    if ($db_priv) {
        $user_priv = $db_priv;
        $_SESSION['privilege'] = $db_priv;
    }
}

if ($user_priv !== 'A') {
    header("Location: ../pages/general/home.php");
    exit();
}

$message = ""; $error_message = "";

// 2. MANAGEMENT LOGIC
try {
    if (isset($_POST['promote_student'])) {
        $pdo->prepare("UPDATE ETUDIANT SET privilege = 'A', classe = 'ADMIN' WHERE etudiant_id = ?")->execute([$_POST['student_id']]);
        $message = "Student promoted to Super Admin!";
    }
    if (isset($_POST['demote_admin'])) {
        $pdo->prepare("UPDATE ETUDIANT SET privilege = 'S', classe = 'STUDENT' WHERE etudiant_id = ?")->execute([$_POST['admin_id_demote']]);
        $message = "Admin access revoked.";
    }

    if (isset($_POST['create_club'])) {
        $club_name = trim($_POST['club_name']);
        $stmt_check = $pdo->prepare("SELECT club_id FROM CLUB WHERE club_name = ?");
        $stmt_check->execute([$club_name]);
        if ($stmt_check->fetch()) {
            $error_message = "Rejected: A club named '" . htmlspecialchars($club_name) . "' already exists.";
        } else {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("INSERT INTO CLUB (club_name, descriptoin, incs_fees, id_admin_etudiant) VALUES (?, ?, ?, ?)");
            $stmt->execute([$club_name, $_POST['description'], $_POST['fees'], $_POST['club_admin_cne']]);
            $pdo->prepare("UPDATE ETUDIANT SET privilege = 'C' WHERE etudiant_id = ?")->execute([$_POST['club_admin_cne']]);

            // Auto-join the assigned leader as accepted member if not already in the club
            $stmt_check_member = $pdo->prepare("SELECT 1 FROM ETUDIANT_CLUB WHERE etudiant_id = ? AND club_id = ?");
            $stmt_check_member->execute([$_POST['club_admin_cne'], $pdo->lastInsertId()]);
            if (!$stmt_check_member->fetch()) {
                $new_club_id = $pdo->query("SELECT LAST_INSERT_ID()")->fetchColumn();
                $pdo->prepare("INSERT INTO ETUDIANT_CLUB (etudiant_id, club_id, status, registration_date) VALUES (?, ?, 'accepted', NOW())")
                    ->execute([$_POST['club_admin_cne'], $new_club_id]);
            }

            $pdo->commit();
            $message = "Club created and Admin assigned!";
        }
    }

    if (isset($_POST['delete_club'])) {
        $c_id = $_POST['club_id_del'];
        $pdo->prepare("DELETE FROM ETUDIANT_CLUB WHERE club_id = ?")->execute([$c_id]);
        $pdo->prepare("DELETE FROM EVENEMENT_ETUDIANT WHERE event_id IN (SELECT event_id FROM EVENEMENT WHERE club_id = ?)")->execute([$c_id]);
        $pdo->prepare("DELETE FROM EVENEMENT WHERE club_id = ?")->execute([$c_id]);
        $pdo->prepare("DELETE FROM CLUB WHERE club_id = ?")->execute([$c_id]);
        $message = "Club deleted successfully.";
    }
    if (isset($_POST['delete_approved_event'])) {
        $e_id = $_POST['event_id_del_approved'];
        $pdo->prepare("DELETE FROM EVENEMENT_ETUDIANT WHERE event_id = ?")->execute([$e_id]);
        $pdo->prepare("DELETE FROM EVENEMENT WHERE event_id = ?")->execute([$e_id]);
        $message = "Approved event removed.";
    }
    if (isset($_POST['approve_event'])) {
        $pdo->prepare("UPDATE EVENEMENT SET status = 'approved' WHERE event_id = ?")->execute([$_POST['event_id']]);
        $message = "Event approved successfully!";
    }
    if (isset($_POST['delete_event'])) {
        $pdo->prepare("DELETE FROM EVENEMENT_ETUDIANT WHERE event_id = ?")->execute([$_POST['event_id_del']]);
        $pdo->prepare("DELETE FROM EVENEMENT WHERE event_id = ?")->execute([$_POST['event_id_del']]);
        $message = "Request rejected.";
    }
} catch (PDOException $e) { if($pdo->inTransaction()) $pdo->rollBack(); $error_message = $e->getMessage(); }

// 3. DATA FETCHING
$all_clubs = $pdo->query("SELECT club_id, club_name FROM CLUB ORDER BY club_name ASC")->fetchAll();
$all_admins = $pdo->query("SELECT etudiant_id, fist_name, last_name FROM ETUDIANT WHERE privilege = 'A'")->fetchAll();
$approved_events = $pdo->query("SELECT event_id, event_name FROM EVENEMENT WHERE status = 'approved' AND event_date_start > DATE_ADD(NOW(), INTERVAL 3 DAY) ORDER BY event_name ASC")->fetchAll();
$pending_events = $pdo->query("SELECT e.*, c.club_name FROM EVENEMENT e JOIN CLUB c ON e.club_id = c.club_id WHERE e.status = 'pending' ORDER BY e.event_date_start DESC")->fetchAll();

// 4. ANALYTICS & RANKING
$rank_type = $_GET['rank_events_by'] ?? 'participants';
$pop_clubs = $pdo->query("SELECT c.club_name, COUNT(ec.etudiant_id) as count FROM CLUB c LEFT JOIN ETUDIANT_CLUB ec ON c.club_id = ec.club_id GROUP BY c.club_id ORDER BY count DESC LIMIT 5")->fetchAll();

if ($rank_type === 'rating') {
    $pop_events = $pdo->query("SELECT e.event_name, AVG(ee.student_rating) as count FROM EVENEMENT e JOIN EVENEMENT_ETUDIANT ee ON e.event_id = ee.event_id WHERE e.status='approved' AND ee.student_rating IS NOT NULL GROUP BY e.event_id ORDER BY count DESC LIMIT 5")->fetchAll();
    $rank_unit = "Avg Stars"; $max_val = 5;
} elseif ($rank_type === 'budget') {
    $pop_events = $pdo->query("SELECT event_name, event_budget as count FROM EVENEMENT WHERE status='approved' ORDER BY event_budget DESC LIMIT 5")->fetchAll();
    $rank_unit = "DH"; $max_val = (!empty($pop_events)) ? $pop_events[0]['count'] : 1;
} else {
    $pop_events = $pdo->query("SELECT e.event_name, COUNT(ee.etudiant_id) as count FROM EVENEMENT e LEFT JOIN EVENEMENT_ETUDIANT ee ON e.event_id = ee.event_id WHERE e.status='approved' GROUP BY e.event_id ORDER BY count DESC LIMIT 5")->fetchAll();
    $rank_unit = "Participants"; $max_val = (!empty($pop_events)) ? $pop_events[0]['count'] : 1;
}

// 5. ATTENDANCE & LOOKUP
$view_event_id = $_GET['view_event_id'] ?? null;
$view_club_id = $_GET['view_club_id'] ?? null;
$event_participants = []; $club_members = [];

if ($view_event_id) {
    $stmt = $pdo->prepare("SELECT s.* FROM ETUDIANT s JOIN EVENEMENT_ETUDIANT ee ON s.etudiant_id = ee.etudiant_id WHERE ee.event_id = ? AND ee.status = 'accepted'");
    $stmt->execute([$view_event_id]); $event_participants = $stmt->fetchAll();
}
if ($view_club_id) {
    $stmt = $pdo->prepare("SELECT s.* FROM ETUDIANT s JOIN ETUDIANT_CLUB ec ON s.etudiant_id = ec.etudiant_id WHERE ec.club_id = ? AND ec.status = 'accepted'");
    $stmt->execute([$view_club_id]); $club_members = $stmt->fetchAll();
}

$search_student = null; $search_clubs = []; $search_events = [];
if (!empty($_GET['search_cne'])) {
    $stmt = $pdo->prepare("SELECT * FROM ETUDIANT WHERE etudiant_id = ?");
    $stmt->execute([$_GET['search_cne']]); $search_student = $stmt->fetch();
    if($search_student) {
        $stmt = $pdo->prepare("SELECT c.club_name FROM CLUB c JOIN ETUDIANT_CLUB ec ON c.club_id = ec.club_id WHERE ec.etudiant_id = ? AND ec.status = 'accepted'");
        $stmt->execute([$_GET['search_cne']]); $search_clubs = $stmt->fetchAll();
        $stmt = $pdo->prepare("SELECT e.event_name, c.club_name as organized_by, e.event_date_start FROM EVENEMENT e JOIN EVENEMENT_ETUDIANT ee ON e.event_id = ee.event_id JOIN CLUB c ON e.club_id = c.club_id WHERE ee.etudiant_id = ? AND ee.status = 'accepted' ORDER BY e.event_date_start DESC");
        $stmt->execute([$_GET['search_cne']]); $search_events = $stmt->fetchAll();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>AdminHUB | Dashboard</title>
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="admin.css">
</head>
<body>
    <nav class="navbar">
        <div style="font-weight: 800; color: var(--secondary); font-size: 1.5rem;"><i class='bx bxs-shield-crown'></i>ADMIN-HUB</div>
        <div style="display: flex; gap: 20px;">
            <a href="../pages/general/home.php" class="nav-btn">Home</a>
            <a href="../pages/auth/logout.php" class="nav-btn logout-btn">Logout</a>
        </div>
    </nav>

    <div class="container">
        <?php if($message): ?>
            <div style="padding:15px; background:rgba(78,166,133,0.2); border:1px solid var(--primary); border-radius:15px; margin-bottom:20px; color:var(--secondary);"><?php echo $message; ?></div>
        <?php endif; ?>
        <?php if($error_message): ?>
            <div style="padding:15px; background:rgba(255,77,77,0.2); border:1px solid var(--danger); border-radius:15px; margin-bottom:20px; color:var(--danger); font-weight: 600;">⚠️ <?php echo $error_message; ?></div>
        <?php endif; ?>

        <h3 class="section-title">Administration</h3>
        <div class="admin-grid">
            <div class="card">
                <h3>Role Management</h3>
                <form method="POST"><input type="text" name="student_id" placeholder="CNE" required><button type="submit" name="promote_student" class="btn">Promote to Admin</button></form>
                <form method="POST" style="margin-top:15px;"><select name="admin_id_demote" required><option value="">Demote Admin</option><?php foreach($all_admins as $a) echo "<option value='{$a['etudiant_id']}'>{$a['fist_name']} {$a['last_name']}</option>"; ?></select><button type="submit" name="demote_admin" class="btn btn-del" style="width:100%;">Revoke Access</button></form>
            </div>
            <div class="card" style="grid-column: span 2;">
                <h3>Initialize Club</h3>
                <form method="POST" style="display:grid; grid-template-columns: 1fr 1fr; gap:15px;">
                    <input type="text" name="club_name" placeholder="Name" required>
                    <input type="text" name="club_admin_cne" placeholder="Admin CNE" required>
                    <input type="number" name="fees" placeholder="Annual Fee">
                    <textarea name="description" placeholder="Description" style="grid-column: span 2;"></textarea>
                    <button type="submit" name="create_club" class="btn" style="grid-column: span 2;">Create Club</button>
                </form>
            </div>
        </div>

        <h3 class="section-title">Club & Event Termination</h3>
        <div class="admin-grid">
            <div class="card">
                <h3 style="color:var(--danger);"><i class='bx bx-trash'></i> Delete a Club</h3>
                <form method="POST" onsubmit="return confirm('Delete this club and all its data?')">
                    <select name="club_id_del" required>
                        <option value="">Select Club...</option>
                        <?php foreach($all_clubs as $c) echo "<option value='{$c['club_id']}'>{$c['club_name']}</option>"; ?>
                    </select>
                    <button type="submit" name="delete_club" class="btn btn-del" style="width:100%;">PERMANENTLY DELETE CLUB</button>
                </form>
            </div>
            <div class="card">
                <h3 style="color:var(--danger);"><i class='bx bx-calendar-x'></i> Delete Approved Event</h3>
                <form method="POST" onsubmit="return confirm('Permanently remove this event?')">
                    <select name="event_id_del_approved" required>
                        <option value="">Select Approved Event...</option>
                        <?php foreach($approved_events as $ae) echo "<option value='{$ae['event_id']}'>{$ae['event_name']}</option>"; ?>
                    </select>
                    <button type="submit" name="delete_approved_event" class="btn btn-del" style="width:100%;">REMOVE EVENT</button>
                </form>
            </div>
        </div>

        <h3 class="section-title">Scheduling Queue</h3>
        <div class="card">
            <table>
                <thead>
                    <tr>
                        <th class="col-event">Event</th>
                        <th class="col-club">Club</th>
                        <th class="col-budget">Budget Required</th>
                        <th class="col-actions" style="text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach($pending_events as $e): ?>
                <tr>
                    <td class="col-event"><strong><?php echo htmlspecialchars($e['event_name']); ?></strong></td>
                    <td class="col-club"><?php echo htmlspecialchars($e['club_name']); ?></td>
                    <td class="col-budget"><span style="color:var(--secondary); font-weight:700;"><?php echo number_format($e['event_budget'], 2); ?> DH</span></td>
                    <td class="col-actions">
                        <div class="action-flex">
                            <form method="POST" style="margin-bottom:0;"><input type="hidden" name="event_id" value="<?php echo $e['event_id']; ?>"><button type="submit" name="approve_event" class="btn" style="width:auto; padding:5px 15px;">Approve</button></form>
                            <form method="POST" style="margin-bottom:0;"><input type="hidden" name="event_id_del" value="<?php echo $e['event_id']; ?>"><button type="submit" name="delete_event" class="btn btn-del">Deny</button></form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; if(empty($pending_events)) echo "<tr><td colspan='4' style='text-align:center;'>No requests.</td></tr>"; ?>
                </tbody>
            </table>
        </div>

        <h3 class="section-title">Performance Analytics</h3>
        <div class="analytics-stack">

            <!-- Club Popularity -->
            <div class="analytics-card">
                <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px; margin-bottom:15px;">
                    <h3 style="margin-bottom:0;">Club Popularity</h3>
                    <div class="chart-toggle">
                        <button onclick="switchChart('club', 'bars')" id="club-btn-bars">Bars</button>
                        <button onclick="switchChart('club', 'doughnut')" id="club-btn-doughnut">Doughnut</button>
                        <button onclick="switchChart('club', 'line')" id="club-btn-line">Line</button>
                    </div>
                </div>
                <div class="chart-area"><canvas id="clubChart"></canvas></div>
            </div>

            <!-- Event Ranking -->
            <div class="analytics-card">
                <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px; margin-bottom:15px;">
                    <h3 style="margin-bottom:0;">Event Ranking</h3>
                    <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
                        <form method="GET" style="margin:0;">
                            <select name="rank_events_by" onchange="this.form.submit()" style="width:auto; margin-bottom:0; font-size:0.75rem;">
                                <option value="participants" <?php if($rank_type == 'participants') echo 'selected'; ?>>By Participants</option>
                                <option value="rating" <?php if($rank_type == 'rating') echo 'selected'; ?>>By Avg Rating</option>
                                <option value="budget" <?php if($rank_type == 'budget') echo 'selected'; ?>>By Event Budget</option>
                            </select>
                        </form>
                        <div class="chart-toggle" style="margin-bottom:0;">
                            <button onclick="switchChart('event', 'bars')" id="event-btn-bars">Bars</button>
                            <button onclick="switchChart('event', 'doughnut')" id="event-btn-doughnut">Doughnut</button>
                            <button onclick="switchChart('event', 'line')" id="event-btn-line">Line</button>
                        </div>
                    </div>
                </div>
                <div class="chart-area"><canvas id="eventChart"></canvas></div>
            </div>

        </div>

        <h3 class="section-title">Student Profile Lookup</h3>
        <div class="card">
            <form method="GET" style="display:flex; gap:15px; margin-bottom:20px;">
                <input type="text" name="search_cne" placeholder="Search by CNE..." required>
                <button type="submit" class="btn" style="width:auto; padding:0 30px;">SEARCH PROFILE</button>
            </form>
            <?php if($search_student): ?>
            <div class="admin-grid">
                <div class="card" style="background: rgba(255,255,255,0.05);">
                    <h3>Identity</h3>
                    <p><strong>Name:</strong> <?php echo htmlspecialchars($search_student['fist_name']." ".$search_student['last_name']); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($search_student['email']); ?></p>
                </div>
                <div class="card" style="background: rgba(255,255,255,0.05);">
                    <h3>Clubs</h3>
                    <ul><?php foreach($search_clubs as $sc) echo "<li>" . htmlspecialchars($sc['club_name']) . "</li>"; ?></ul>
                </div>
                <div class="card" style="grid-column: span 2; background: rgba(255,255,255,0.05);">
                    <h3>Event History</h3>
                    <table style="table-layout: auto;">
                        <thead><tr><th>Event</th><th>Organizer</th><th>Date</th></tr></thead>
                        <tbody><?php foreach($search_events as $se) echo "<tr><td>" . htmlspecialchars($se['event_name']) . "</td><td>" . htmlspecialchars($se['organized_by']) . "</td><td>" . date('Y-m-d', strtotime($se['event_date_start'])) . "</td></tr>"; ?></tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <h3 class="section-title">Attendance & Registry</h3>
        <div class="admin-grid">
            <div class="card">
                <h3>Event Attendance <?php if($view_event_id) echo "<span class='badge-count'>".count($event_participants)."</span>"; ?></h3>
                <form method="GET"><select name="view_event_id" onchange="saveScroll(); this.form.submit()"><option value="">Select Event</option><?php
                    $ev_s = $pdo->query("SELECT event_id, event_name FROM EVENEMENT WHERE status = 'approved'");
                    foreach($ev_s->fetchAll() as $e) echo "<option value='{$e['event_id']}' ".($view_event_id==$e['event_id']?'selected':'').">{$e['event_name']}</option>";
                    ?></select></form>
                <?php if($event_participants): ?><table><?php foreach($event_participants as $p) echo "<tr><td>{$p['fist_name']} {$p['last_name']}</td></tr>"; ?></table><?php endif; ?>
                </div>
                <div class="card">
                    <h3>Club Membership <?php if($view_club_id) echo "<span class='badge-count'>".count($club_members)."</span>"; ?></h3>
                    <form method="GET"><select name="view_club_id" onchange="saveScroll(); this.form.submit()"><option value="">Select Club</option><?php foreach($all_clubs as $c) echo "<option value='{$c['club_id']}' ".($view_club_id==$c['club_id']?'selected':'').">{$c['club_name']}</option>"; ?></select></form>
                    <?php if($club_members): ?><table><?php foreach($club_members as $m) echo "<tr><td>{$m['fist_name']} {$m['last_name']}</td></tr>"; ?></table><?php endif; ?>
                    </div>
                </div>
            </div>
            
        <footer class="footer">
            <p>Designed & Developed by <span>MUGIWARA37</span></p>
            <div class="footer-links">
                <a href="https://github.com/MUGIWARA37" target="_blank">
                    <i class='bx bxl-github'></i> GitHub
                </a>
                <a href="https://www.linkedin.com/in/rida-hlou-581b4a36a/" target="_blank">
                    <i class='bx bxl-linkedin'></i> LinkedIn
                </a>
                <a href="mailto:hloureda@gmail.com">
                    <i class='bx bx-envelope'></i> hloureda@gmail.com
                </a>
            </div>
        </footer>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
    <script>
        function saveScroll() { sessionStorage.setItem("admY", window.scrollY); }
        window.onload = function() { var y = sessionStorage.getItem("admY"); if (y) { window.scrollTo(0, y); sessionStorage.removeItem("admY"); } };

        const colors = ['#4EA685','#57B894','#f1c40f','#ff4d4d','#3498db','#9b59b6'];
        const borderColors = colors.map(c => c);

        const clubLabels  = <?php echo json_encode(array_column($pop_clubs, 'club_name')); ?>;
        const clubData    = <?php echo json_encode(array_column($pop_clubs, 'count')); ?>;
        const eventLabels = <?php echo json_encode(array_column($pop_events, 'event_name')); ?>;
        const eventData   = <?php echo json_encode(array_column($pop_events, 'count')); ?>;
        const rankUnit    = "<?php echo $rank_unit; ?>";

        const sharedOptions = (unit) => ({
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { color: '#fff', font: { family: 'Poppins', size: 12 }, padding: 15 } },
                tooltip: { callbacks: { label: ctx => ` ${ctx.label}: ${parseFloat(ctx.raw).toFixed(1)} ${unit}` } }
            }
        });

        const barOptions = (unit) => ({
            ...sharedOptions(unit),
            scales: {
                x: { ticks: { color: '#aaa', font: { family: 'Poppins' } }, grid: { color: 'rgba(255,255,255,0.05)' } },
                y: { ticks: { color: '#aaa', font: { family: 'Poppins' } }, grid: { color: 'rgba(255,255,255,0.05)' } }
            }
        });

        const lineOptions = (unit) => ({
            ...sharedOptions(unit),
            scales: {
                x: { ticks: { color: '#aaa', font: { family: 'Poppins' } }, grid: { color: 'rgba(255,255,255,0.05)' } },
                y: { ticks: { color: '#aaa', font: { family: 'Poppins' } }, grid: { color: 'rgba(255,255,255,0.05)' } }
            }
        });

        // Build dataset per type
        function buildDataset(labels, data, type) {
            if (type === 'doughnut') {
                return { labels, datasets: [{ data, backgroundColor: colors, borderColor: 'rgba(0,0,0,0.3)', borderWidth: 2 }] };
            } else if (type === 'line') {
                return { labels, datasets: [{ label: 'Value', data, borderColor: '#4EA685', backgroundColor: 'rgba(78,166,133,0.15)', pointBackgroundColor: colors, borderWidth: 3, pointRadius: 6, fill: true, tension: 0.4 }] };
            } else {
                return { labels, datasets: [{ label: 'Value', data, backgroundColor: colors, borderColor: borderColors, borderWidth: 2, borderRadius: 8 }] };
            }
        }

        function getOptions(type, unit) {
            if (type === 'doughnut') return sharedOptions(unit);
            if (type === 'line') return lineOptions(unit);
            return barOptions(unit);
        }

        // Chart instances
        let clubChart  = null;
        let eventChart = null;
        let clubType   = localStorage.getItem('clubChartType')  || 'bars';
        let eventType  = localStorage.getItem('eventChartType') || 'bars';

        function renderChart(id, labels, data, type, unit) {
            const canvas = document.getElementById(id);
            const chartType = type === 'bars' ? 'bar' : type;
            return new Chart(canvas, {
                type: chartType,
                data: buildDataset(labels, data, type),
                options: getOptions(type, unit)
            });
        }

        function setActive(prefix, type) {
            ['bars','doughnut','line'].forEach(t => {
                const btn = document.getElementById(`${prefix}-btn-${t}`);
                if (btn) btn.classList.toggle('active', t === type);
            });
        }

        function switchChart(which, type) {
            if (which === 'club') {
                if (clubChart) clubChart.destroy();
                clubType = type;
                localStorage.setItem('clubChartType', type);
                clubChart = renderChart('clubChart', clubLabels, clubData, type, 'members');
                setActive('club', type);
            } else {
                if (eventChart) eventChart.destroy();
                eventType = type;
                localStorage.setItem('eventChartType', type);
                eventChart = renderChart('eventChart', eventLabels, eventData, type, rankUnit);
                setActive('event', type);
            }
        }

        // Init
        clubChart  = renderChart('clubChart',  clubLabels,  clubData,  clubType,  'members');
        eventChart = renderChart('eventChart', eventLabels, eventData, eventType, rankUnit);
        setActive('club',  clubType);
        setActive('event', eventType);
    </script>

</body>
</html>
