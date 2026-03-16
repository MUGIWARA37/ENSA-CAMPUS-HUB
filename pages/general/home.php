<?php
session_start();
require_once '../../includes/db.php';

if (!isset($_SESSION['etudiant_id'])) {
    header("Location: ../../index.php");
    exit();
}

$etudiant_id = $_SESSION['etudiant_id'];
$first_name = $_SESSION['first_name'] ?? 'User';
$user_priv = $_SESSION['privilege'] ?? 'S';
$is_super_admin = ($user_priv === 'A');
$is_club_admin = ($user_priv === 'C');

$admin_club_id = null;
if ($is_club_admin) {
    $stmt_c = $pdo->prepare("SELECT club_id FROM CLUB WHERE id_admin_etudiant = ?");
    $stmt_c->execute([$etudiant_id]);
    $admin_club_id = $stmt_c->fetchColumn();
}

if (isset($_POST['submit_student_rating'])) {
    $pdo->prepare("UPDATE EVENEMENT_ETUDIANT SET student_rating = ? WHERE etudiant_id = ? AND event_id = ?")
        ->execute([$_POST['rating'], $etudiant_id, $_POST['event_id']]);
    header("Location: " . $_SERVER['PHP_SELF']); exit();
}
if (isset($_POST['leave_event'])) {
    $pdo->prepare("DELETE FROM EVENEMENT_ETUDIANT WHERE etudiant_id = ? AND event_id = ?")
        ->execute([$etudiant_id, $_POST['event_id']]);
    header("Location: " . $_SERVER['PHP_SELF']); exit();
}
if (isset($_POST['leave_club'])) {
    $pdo->prepare("DELETE FROM ETUDIANT_CLUB WHERE etudiant_id = ? AND club_id = ?")
        ->execute([$etudiant_id, $_POST['club_id']]);
    header("Location: " . $_SERVER['PHP_SELF']); exit();
}
if (isset($_POST['request_join_club'])) {
    $pdo->prepare("INSERT INTO ETUDIANT_CLUB (etudiant_id, club_id, status) VALUES (?, ?, 'pending')")
        ->execute([$etudiant_id, $_POST['club_id']]);
    header("Location: " . $_SERVER['PHP_SELF']); exit();
}
if (isset($_POST['request_join_event'])) {
    $pdo->prepare("INSERT INTO EVENEMENT_ETUDIANT (etudiant_id, event_id, status) VALUES (?, ?, 'pending')")
        ->execute([$etudiant_id, $_POST['event_id']]);
    header("Location: " . $_SERVER['PHP_SELF']); exit();
}

try {
    $stmt2 = $pdo->prepare("SELECT c.*, e.fist_name, e.last_name FROM CLUB c
                            JOIN ETUDIANT_CLUB ec ON c.club_id = ec.club_id
                            JOIN ETUDIANT e ON c.id_admin_etudiant = e.etudiant_id
                            WHERE ec.etudiant_id = ? AND ec.status = 'accepted'");
    $stmt2->execute([$etudiant_id]);
    $my_clubs = $stmt2->fetchAll();

    $stmt1 = $pdo->prepare("SELECT e.*, c.club_name FROM EVENEMENT e
                            JOIN EVENEMENT_ETUDIANT ee ON e.event_id = ee.event_id
                            JOIN CLUB c ON e.club_id = c.club_id
                            WHERE ee.etudiant_id = ? 
                            AND ee.status = 'accepted'
                            AND e.event_date_end > NOW()
                            ORDER BY e.event_date_start ASC");
    $stmt1->execute([$etudiant_id]);
    $my_events = $stmt1->fetchAll();

    $stmt_r = $pdo->prepare("SELECT e.*, c.club_name, ee.student_rating 
                             FROM EVENEMENT e
                             JOIN EVENEMENT_ETUDIANT ee ON e.event_id = ee.event_id
                             JOIN CLUB c ON e.club_id = c.club_id
                             WHERE ee.etudiant_id = ? 
                             AND ee.status = 'accepted' 
                             AND e.event_date_end < NOW()
                             AND (ee.student_rating IS NULL OR ee.student_rating = 0)");
    $stmt_r->execute([$etudiant_id]);
    $past_events = $stmt_r->fetchAll();

    $stmt_p1 = $pdo->prepare("SELECT c.club_name FROM CLUB c JOIN ETUDIANT_CLUB ec ON c.club_id = ec.club_id WHERE ec.etudiant_id = ? AND ec.status = 'pending'");
    $stmt_p1->execute([$etudiant_id]);
    $pending_clubs = $stmt_p1->fetchAll();

    $stmt_p2 = $pdo->prepare("SELECT e.event_name FROM EVENEMENT e JOIN EVENEMENT_ETUDIANT ee ON e.event_id = ee.event_id WHERE ee.etudiant_id = ? AND ee.status = 'pending'");
    $stmt_p2->execute([$etudiant_id]);
    $pending_events = $stmt_p2->fetchAll();

    $query_discover = "SELECT e.*, c.club_name FROM EVENEMENT e
                       JOIN CLUB c ON e.club_id = c.club_id
                       WHERE e.event_date_start > NOW()
                       AND e.event_date_end > NOW()
                       AND e.status = 'approved'
                       AND e.event_id NOT IN (SELECT event_id FROM EVENEMENT_ETUDIANT WHERE etudiant_id = ?)";
    $stmt3 = $pdo->prepare($query_discover);
    $stmt3->execute([$etudiant_id]);
    $upcoming_events = $stmt3->fetchAll();

    $stmt4 = $pdo->prepare("SELECT c.*, e.fist_name, e.last_name FROM CLUB c
                            JOIN ETUDIANT e ON c.id_admin_etudiant = e.etudiant_id
                            WHERE c.club_id NOT IN (SELECT club_id FROM ETUDIANT_CLUB WHERE etudiant_id = ? AND status = 'accepted')");
    $stmt4->execute([$etudiant_id]);
    $other_clubs = $stmt4->fetchAll();

} catch (PDOException $e) { die("Database Error: " . $e->getMessage()); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>StudentHub | Dashboard</title>
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="home.css">
</head>
<body>
    <nav class="navbar">
        <div style="font-weight: 800; font-size: 1.4rem; color: #fff;">StudentHub</div>
        <div class="nav-links">
            <a href="home.php" class="nav-btn">Home</a>
            <?php if ($is_super_admin): ?>
                <a href="../../admin/admin.php" class="nav-btn admin-link" style="color:var(--admin); border-color:var(--admin);">Admin Panel</a>
            <?php endif; ?>
            <?php if ($is_club_admin): ?>
                <a href="../club/club_admin.php" class="nav-btn admin-link" style="color:var(--admin); border-color:var(--admin);">Club Mgmt</a>
            <?php endif; ?>
            <a href="../../pages/auth/logout.php" class="nav-btn logout-btn" style="color:var(--danger);">Logout</a>
        </div>
    </nav>
    <div class="container">
        <h1 style="font-size: 2.2rem; margin-bottom: 30px; font-weight: 800;">WELCOME, <?php echo strtoupper(htmlspecialchars($first_name)); ?></h1>

        <?php if(!empty($pending_clubs) || !empty($pending_events)): ?>
        <h3 class="section-title"><i class='bx bx-time-five'></i> Pending Requests</h3>
        <div class="grid">
            <?php foreach($pending_clubs as $pc): ?>
                <div class="card" style="min-height:150px; border-color:var(--admin)">
                    <div class="badge" style="color:var(--admin)">Club Application</div>
                    <h2><?php echo htmlspecialchars($pc['club_name']); ?></h2>
                </div>
            <?php endforeach; ?>
            <?php foreach($pending_events as $pe): ?>
                <div class="card" style="min-height:150px; border-color:var(--admin)">
                    <div class="badge" style="color:var(--admin)">Event Application</div>
                    <h2><?php echo htmlspecialchars($pe['event_name']); ?></h2>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <h3 class="section-title"><i class='bx bxs-group'></i> My Memberships</h3>
        <div class="grid">
            <?php foreach($my_clubs as $club): ?>
                <div class="card">
                    <div class="card-content">
                        <div class="badge">Accepted Member</div>
                        <h2><?php echo htmlspecialchars($club['club_name']); ?></h2>
                        <div class="info-row"><i class='bx bxs-user-voice'></i> Leader: <?php echo htmlspecialchars($club['fist_name'] . " " . $club['last_name']); ?></div>
                        <div class="price-tag">Annual Fee: <?php echo (!empty($club['incs_fees']) && $club['incs_fees'] > 0) ? $club['incs_fees'] . " DH" : "Free"; ?></div>
                    </div>
                    <form method="POST" onsubmit="return confirm('Leave this club?')">
                        <input type="hidden" name="club_id" value="<?php echo $club['club_id']; ?>">
                        <button type="submit" name="leave_club" class="btn btn-unreg">Leave Club</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if(!empty($my_events)): ?>
        <h3 class="section-title"><i class='bx bxs-calendar-check'></i> My Schedule</h3>
        <div class="grid">
            <?php foreach($my_events as $event): ?>
                <div class="card">
                    <div class="card-content">
                        <div class="badge">Attending</div>
                        <h2><?php echo htmlspecialchars($event['event_name']); ?></h2>
                        <div class="info-row"><i class='bx bxs-buildings'></i> Host Club: <?php echo htmlspecialchars($event['club_name']); ?></div>
                        <div class="info-row"><i class='bx bx-calendar'></i> Starts: <?php echo date('M d, Y H:i', strtotime($event['event_date_start'])); ?></div>
                    </div>
                    <form method="POST">
                        <input type="hidden" name="event_id" value="<?php echo $event['event_id']; ?>">
                        <button type="submit" name="leave_event" class="btn btn-unreg">Unregister</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if(!empty($upcoming_events)): ?>
        <h3 class="section-title"><i class='bx bx-rocket'></i> Discover New Events</h3>
        <div class="grid">
            <?php foreach($upcoming_events as $event): ?>
                <div class="card">
                    <div class="card-content">
                        <div class="badge" style="color:var(--secondary)">Upcoming</div>
                        <h2><?php echo htmlspecialchars($event['event_name']); ?></h2>
                        <div class="info-row"><i class='bx bxs-buildings'></i> Host Club: <?php echo htmlspecialchars($event['club_name']); ?></div>
                        <div class="info-row"><i class='bx bx-calendar'></i> Starts: <?php echo date('M d, Y H:i', strtotime($event['event_date_start'])); ?></div>
                    </div>
                    <form method="POST">
                        <input type="hidden" name="event_id" value="<?php echo $event['event_id']; ?>">
                        <button type="submit" name="request_join_event" class="btn">Register</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <h3 class="section-title"><i class='bx bx-compass'></i> Join New Clubs</h3>
        <div class="grid">
            <?php foreach($other_clubs as $club): ?>
                <div class="card">
                    <div class="card-content">
                        <div class="badge">Available</div>
                        <h2><?php echo htmlspecialchars($club['club_name']); ?></h2>
                        <div class="info-row"><i class='bx bxs-user-voice'></i> Leader: <?php echo htmlspecialchars($club['fist_name'] . " " . $club['last_name']); ?></div>
                        <div class="price-tag">Annual Fee: <?php echo ($club['incs_fees'] > 0) ? $club['incs_fees'] . " DH" : "Free"; ?></div>
                    </div>
                    <form method="POST">
                        <input type="hidden" name="club_id" value="<?php echo $club['club_id']; ?>">
                        <button type="submit" name="request_join_club" class="btn">Apply to Join</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if(!empty($past_events)): ?>
        <h3 class="section-title"><i class='bx bx-star'></i> Rate Past Events</h3>
        <div class="grid">
            <?php foreach($past_events as $re): ?>
                <div class="card" style="min-height: 250px;">
                    <div class="card-content">
                        <div class="badge">Completed</div>
                        <h2><?php echo htmlspecialchars($re['event_name']); ?></h2>
                        <div class="info-row"><i class='bx bxs-buildings'></i> Organiser: <?php echo htmlspecialchars($re['club_name']); ?></div>
                        <form method="POST">
                            <input type="hidden" name="event_id" value="<?php echo $re['event_id']; ?>">
                            <div class="rating-wrapper">
                                <input type="radio" name="rating" value="5" id="r5_<?php echo $re['event_id']; ?>" required><label for="r5_<?php echo $re['event_id']; ?>"></label>
                                <input type="radio" name="rating" value="4" id="r4_<?php echo $re['event_id']; ?>"><label for="r4_<?php echo $re['event_id']; ?>"></label>
                                <input type="radio" name="rating" value="3" id="r3_<?php echo $re['event_id']; ?>"><label for="r3_<?php echo $re['event_id']; ?>"></label>
                                <input type="radio" name="rating" value="2" id="r2_<?php echo $re['event_id']; ?>"><label for="r2_<?php echo $re['event_id']; ?>"></label>
                                <input type="radio" name="rating" value="1" id="r1_<?php echo $re['event_id']; ?>"><label for="r1_<?php echo $re['event_id']; ?>"></label>
                            </div>
                            <button type="submit" name="submit_student_rating" class="btn" style="padding: 10px; margin-top: 15px; font-size: 0.85rem;">Submit Rating</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <script>
        function saveScroll() { sessionStorage.setItem("homeY", window.scrollY); }
        window.onload = function() {
            var y = sessionStorage.getItem("homeY");
            if (y) { window.scrollTo(0, y); sessionStorage.removeItem("homeY"); }
        };
        document.querySelectorAll('form').forEach(f => f.onsubmit = saveScroll);
    </script>
</body>
</html>
