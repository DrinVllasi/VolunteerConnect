<?php
session_start();
require_once 'config/config.php';
include_once 'includes/header.php';

if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: auth/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['name'] ?? 'Volunteer';

// ============= VERIFIED HOURS =============
$stmt = $conn->prepare("
    SELECT COALESCE(SUM(a.hours_worked), 0)
    FROM applications a
    WHERE a.volunteer_id = ? AND a.status = 'completed' AND a.hours_approved = 1
");
$stmt->execute([$user_id]);
$total_hours = (float)$stmt->fetchColumn();

// ============= HOURS BY MONTH =============
$hours_by_month_stmt = $conn->prepare("
    SELECT DATE_FORMAT(o.date, '%Y-%m') as month, COALESCE(SUM(a.hours_worked), 0) as hours
    FROM applications a
    JOIN opportunities o ON a.opportunity_id = o.id
    WHERE a.volunteer_id = ? AND a.status = 'completed' AND a.hours_approved = 1
      AND o.date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY month ORDER BY month ASC
");
$hours_by_month_stmt->execute([$user_id]);
$hours_by_month = $hours_by_month_stmt->fetchAll(PDO::FETCH_ASSOC);

// ============= EVENTS & BADGES =============
$events_stmt = $conn->prepare("SELECT COUNT(*) FROM applications WHERE volunteer_id = ? AND status IN ('confirmed', 'completed')");
$events_stmt->execute([$user_id]);
$total_events = (int)$events_stmt->fetchColumn();

$badges = [];
if ($total_hours >= 100) $badges[] = ['name' => 'Century Volunteer', 'icon' => 'bi-trophy-fill', 'color' => '#FFD700'];
if ($total_hours >= 50)  $badges[] = ['name' => 'Half Century', 'icon' => 'bi-star-fill', 'color' => '#C0C0C0'];
if ($total_hours >= 25)  $badges[] = ['name' => 'Quarter Century', 'icon' => 'bi-award-fill', 'color' => '#CD7F32'];
if ($total_events >= 10) $badges[] = ['name' => 'Event Master', 'icon' => 'bi-calendar-check-fill', 'color' => '#6a8e3a'];
if ($total_events >= 5)  $badges[] = ['name' => 'Regular Volunteer', 'icon' => 'bi-heart-fill', 'color' => '#b27a4b'];
if ($total_events >= 1)  $badges[] = ['name' => 'First Step', 'icon' => 'bi-check-circle-fill', 'color' => '#4CAF50'];

// ============= LEVEL SYSTEM WITH YOUR IMAGES =============
function getVolunteerLevel($hours) {
    if ($hours >= 100) return ['level' => 4, 'name' => 'Master Volunteer', 'req' => 100, 'next' => null];
    if ($hours >= 50)  return ['level' => 3, 'name' => 'Experienced Volunteer', 'req' => 50, 'next' => 100];
    if ($hours >= 25)  return ['level' => 2, 'name' => 'Active Volunteer', 'req' => 25, 'next' => 50];
    return ['level' => 1, 'name' => 'New Volunteer', 'req' => 0, 'next' => 25];
}
$level_info = getVolunteerLevel($total_hours);

$level_images = [1 => 'img/engineers.jpg', 2 => 'img/speedsters.jpg', 3 => 'img/shadows.jpg', 4 => 'img/hipster.jpg'];
$level_names = [1 => 'New Volunteer', 2 => 'Active Volunteer', 3 => 'Experienced Volunteer', 4 => 'Master Volunteer'];
?>

<style>
    :root{--accent:#6a8e3a;--accent2:#b27a4b}
    .profile-header{background:linear-gradient(135deg,var(--accent),var(--accent2));color:white;padding:3rem;border-radius:18px;text-align:center}
    .level-display{background:white;border-radius:18px;padding:2rem;box-shadow:0 8px 30px rgba(0,0,0,.12);text-align:center;position:relative;overflow:hidden;margin-bottom:2rem}
    .level-display::before{content:'';position:absolute;top:0;left:0;right:0;height:8px;background:linear-gradient(90deg,var(--accent),var(--accent2))}
    .level-image{width:180px;height:180px;object-fit:cover;border-radius:16px;margin:0 auto 1rem;display:block;border:4px solid #fff;box-shadow:0 4px 15px rgba(0,0,0,.2)}
    .progress-bar-custom{height:14px;background:#e9ecef;border-radius:10px;overflow:hidden;max-width:420px;margin:1rem auto}
    .progress-fill{height:100%;background:linear-gradient(90deg,var(--accent),var(--accent2));border-radius:10px}
    .stat-card{background:white;padding:1.5rem;border-radius:14px;box-shadow:0 4px 20px rgba(0,0,0,.08)}
    .stat-number{font-size:2.8rem;font-weight:800;color:var(--accent)}
    .badge-item{display:inline-flex;gap:.6rem;align-items:center;background:white;padding:.8rem 1.4rem;border-radius:14px;box-shadow:0 3px 12px rgba(0,0,0,.1);margin:.4rem}
    .level-item.unlocked{box-shadow:0 6px 25px rgba(106,142,58,.2);border:2px solid var(--accent)}
    .level-item.locked{opacity:0.6;border:2px dashed #ddd}
    .level-thumb{width:80px;height:80px;object-fit:cover;border-radius:12px}
    .hover-lift:hover{transform:translateY(-6px);box-shadow:0 12px 35px rgba(0,0,0,.18)!important}
</style>

<div class="container my-5">

    <!-- Header + Current Level -->
    <div class="profile-header">
        <div style="width:120px;height:120px;background:rgba(255,255,255,.25);border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-size:3.8rem;font-weight:bold">
            <?= strtoupper(substr($user_name,0,2)) ?>
        </div>
        <h1 class="display-5 fw-bold mt-3"><?= htmlspecialchars($user_name) ?></h1>
        <p class="lead">Volunteer Profile • Level <?= $level_info['level'] ?></p>
    </div>

    <div class="level-display">
        <?php if (file_exists($level_images[$level_info['level']])): ?>
            <img src="<?= $level_images[$level_info['level']] ?>" alt="Level <?= $level_info['level'] ?>" class="level-image">
        <?php else: ?>
            <div class="level-image d-flex align-items-center justify-content-center text-white" style="background:linear-gradient(135deg,var(--accent),var(--accent2));font-size:5rem;font-weight:bold">L<?= $level_info['level'] ?></div>
        <?php endif; ?>
        <h3 class="fw-bold mt-2"><?= $level_info['name'] ?></h3>
        <?php if ($level_info['next'] !== null):
            $needed = $level_info['next'] - $total_hours;
            $progress = (($total_hours - $level_info['req']) / ($level_info['next'] - $level_info['req'])) * 100;
        ?>
            <p class="mt-3"><strong><?= number_format($needed) ?></strong> more hours to Level <?= $level_info['level'] + 1 ?></p>
            <div class="progress-bar-custom"><div class="progress-fill" style="width:<?= min(100, $progress) ?>%"></div></div>
            <small class="text-muted"><?= number_format($total_hours) ?> / <?= $level_info['next'] ?> hours</small>
        <?php else: ?>
            <p class="mt-3 text-success fw-bold fs-4">Maximum Level Achieved!</p>
        <?php endif; ?>
    </div>

    <!-- Stats -->
    <div class="row g-4 mb-5">
        <div class="col-md-4"><div class="stat-card text-center"><div class="stat-number"><?= number_format($total_hours,1) ?></div><div>Verified Hours</div></div></div>
        <div class="col-md-4"><div class="stat-card text-center"><div class="stat-number"><?= $total_events ?></div><div>Events Joined</div></div></div>
        <div class="col-md-4"><div class="stat-card text-center"><div class="stat-number"><?= count($badges) ?></div><div>Badges Earned</div></div></div>
    </div>

    <div class="row g-4">
        <!-- LEFT: Chart + RECOMMENDED EVENTS -->
        <div class="col-lg-8">
            <div class="stat-card p-4">
                <h4 class="mb-4">Your Progress (Last 6 Months)</h4>
                <canvas id="hoursChart" height="220"></canvas>
            </div>

            <!-- RECOMMENDED FOR YOU (THE KILLER FEATURE) -->
            <div class="stat-card mt-4 p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="fw-bold mb-0">Recommended For You</h4>
                    <a href="public_browse.php" class="small text-decoration-none">See all →</a>
                </div>

                <?php
                $top_cats_stmt = $conn->prepare("
                    SELECT o.category, COUNT(*) as c
                    FROM applications a
                    JOIN opportunities o ON a.opportunity_id = o.id
                    WHERE a.volunteer_id = ? AND a.status IN ('confirmed','completed')
                    GROUP BY o.category ORDER BY c DESC LIMIT 2
                ");
                $top_cats_stmt->execute([$user_id]);
                $top_cats = $top_cats_stmt->fetchAll(PDO::FETCH_COLUMN);
                $where_cats = !empty($top_cats) ? "AND o.category IN ('" . implode("','", array_map('addslashes', $top_cats)) . "')" : "";

                $rec_stmt = $conn->prepare("
                    SELECT o.*, u.name AS org_name, (o.slots - o.filled_slots) AS spots_left
                    FROM opportunities o
                    LEFT JOIN users u ON o.organization_id = u.id
                    WHERE o.date >= CURDATE()
                      $where_cats
                      AND o.id NOT IN (SELECT opportunity_id FROM applications WHERE volunteer_id = ?)
                    ORDER BY 
                      CASE WHEN o.category IN ('" . implode("','", array_map('addslashes', $top_cats)) . "') THEN 0 ELSE 1 END,
                      o.date ASC
                    LIMIT 3
                ");
                $rec_stmt->execute([$user_id]);
                $recommended = $rec_stmt->fetchAll(PDO::FETCH_ASSOC);
                ?>

                <?php if (empty($recommended)): ?>
                    <p class="text-muted text-center py-5">
                        New events are added daily — check back soon!
                    </p>
                <?php else: ?>
                    <div class="row g-3">
                        <?php foreach ($recommended as $event): ?>
                            <div class="col-md-4">
                                <div class="border rounded-3 overflow-hidden shadow-sm h-100 position-relative hover-lift"
                                     style="cursor:pointer" onclick="location.href='opportunity_detail.php?id=<?= $event['id'] ?>'">
                                    <div class="bg-light text-center py-4">
                                        <i class="bi bi-calendar-heart fs-1 text-success"></i>
                                    </div>
                                    <div class="p-3">
                                        <h6 class="fw-bold mb-1 text-truncate"><?= htmlspecialchars($event['title']) ?></h6>
                                        <small class="text-muted d-block mb-2">
                                            <?= htmlspecialchars($event['location_name']) ?>
                                        </small>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="badge bg-success"><?= ucfirst($event['category']) ?></span>
                                            <small class="text-success fw-bold"><?= $event['spots_left'] ?> spots</small>
                                        </div>
                                        <small class="text-muted d-block mt-2">
                                            <?= date('M j', strtotime($event['date'])) ?>
                                            <?= $event['time'] ? ' • '.date('g:i A', strtotime($event['time'])) : '' ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- RIGHT: Badges + All Levels Showcase -->
        <div class="col-lg-4">
            <div class="stat-card mb-4">
                <h4 class="mb-4">Your Badges</h4>
                <?php foreach ($badges as $b): ?>
                    <div class="badge-item">
                        <i class="bi <?= $b['icon'] ?>" style="color:<?= $b['color'] ?>;font-size:1.6rem"></i>
                        <strong><?= $b['name'] ?></strong>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($badges)): ?><p class="text-muted small">Volunteer more to unlock badges!</p><?php endif; ?>
            </div>

            <div class="stat-card">
                <h4 class="mb-4">Level Progression</h4>
                <?php for ($i = 1; $i <= 4; $i++):
                    $unlocked = $total_hours >= ($i == 1 ? 0 : ($i == 2 ? 25 : ($i == 3 ? 50 : 100)));
                    $is_current = $level_info['level'] == $i;
                ?>
                    <div class="d-flex align-items-center p-3 rounded mb-3 level-item <?= $unlocked ? 'unlocked' : 'locked' ?> <?= $is_current ? 'border-primary' : '' ?>">
                        <?php if ($unlocked): ?><i class="bi bi-check-circle-fill text-success me-3 fs-3"></i><?php endif; ?>
                        <img src="<?= $level_images[$i] ?>" alt="Level <?= $i ?>" class="level-thumb me-3">
                        <div>
                            <strong>Level <?= $i ?>: <?= $level_names[$i] ?></strong><br>
                            <small class="text-muted"><?= $i == 1 ? '0' : ($i == 2 ? '25' : ($i == 3 ? '50' : '100')) ?>+ hours</small>
                            <?php if ($is_current): ?><span class="badge bg-success ms-2">CURRENT</span><?php endif; ?>
                        </div>
                    </div>
                <?php endfor; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('hoursChart');
    const data = <?= json_encode($hours_by_month) ?>;
    const labels = data.map(m => new Date(m.month + '-01').toLocaleDateString('en-US', { month: 'short', year: 'numeric' }));
    const values = data.map(m => parseFloat(m.hours));
    new Chart(ctx, {
        type: 'line',
        data: { labels: labels.length ? labels : ['No data'], datasets: [{ label: 'Hours', data: values, borderColor: '#6a8e3a', backgroundColor: 'rgba(106,142,58,0.1)', tension: 0.4, fill: true }] },
        options: { responsive: true, plugins: { legend: { display: false } } }
    });
</script>

<?php include 'includes/footer.php'; ?>