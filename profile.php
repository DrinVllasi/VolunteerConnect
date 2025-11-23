<?php
session_start();
require_once 'config/config.php';
include_once 'includes/header.php';

// Only logged-in users can view profiles
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: auth/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['name'] ?? 'User';

// CHANGE 1: Get verified hours directly from users table (fast + accurate + approved only)
$stmt = $conn->prepare("SELECT total_verified_hours FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$total_hours = $stmt->fetchColumn() ?: 0;

// CHANGE 2: Hours by month — only approved hours
$hours_by_month_stmt = $conn->prepare("
    SELECT 
        DATE_FORMAT(o.date, '%Y-%m') as month,  
        COALESCE(SUM(a.hours_worked), 0) as hours
    FROM applications a
    JOIN opportunities o ON a.opportunity_id = o.id
    WHERE a.volunteer_id = ? 
        AND a.status = 'confirmed' 
        AND a.hours_approved = 1
        AND o.date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(o.date, '%Y-%m')
    ORDER BY month ASC
");
$hours_by_month_stmt->execute([$user_id]);
$hours_by_month = $hours_by_month_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get total events participated
$events_count_stmt = $conn->prepare("
    SELECT COUNT(*) as total_events
    FROM applications
    WHERE volunteer_id = ? AND status = 'confirmed'
");
$events_count_stmt->execute([$user_id]);
$total_events = $events_count_stmt->fetchColumn();

// Get upcoming events
$upcoming_stmt = $conn->prepare("
    SELECT o.*, u.name AS org_name, a.status
    FROM applications a
    JOIN opportunities o ON a.opportunity_id = o.id
    LEFT JOIN users u ON o.organization_id = u.id
    WHERE a.volunteer_id = ? 
        AND o.date >= CURDATE()
        AND a.status IN ('pending', 'confirmed')
    ORDER BY o.date ASC
    LIMIT 5
");
$upcoming_stmt->execute([$user_id]);
$upcoming_events = $upcoming_stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate badges/achievements (now based on real verified hours)
$badges = [];
if ($total_hours >= 100) {
    $badges[] = ['name' => 'Century Volunteer', 'icon' => 'bi-trophy-fill', 'color' => '#FFD700'];
}
if ($total_hours >= 50) {
    $badges[] = ['name' => 'Half Century', 'icon' => 'bi-star-fill', 'color' => '#C0C0C0'];
}
if ($total_hours >= 25) {
    $badges[] = ['name' => 'Quarter Century', 'icon' => 'bi-award-fill', 'color' => '#CD7F32'];
}
if ($total_events >= 10) {
    $badges[] = ['name' => 'Event Master', 'icon' => 'bi-calendar-check-fill', 'color' => '#6a8e3a'];
}
if ($total_events >= 5) {
    $badges[] = ['name' => 'Regular Volunteer', 'icon' => 'bi-heart-fill', 'color' => '#b27a4b'];
}
if ($total_events >= 1) {
    $badges[] = ['name' => 'First Step', 'icon' => 'bi-check-circle-fill', 'color' => '#4CAF50'];
}

// Get volunteer preferences
$pref_stmt = $conn->prepare("SELECT * FROM volunteer_preferences WHERE volunteer_id = ?");
$pref_stmt->execute([$user_id]);
$preferences = $pref_stmt->fetch(PDO::FETCH_ASSOC);

$preferred_categories = $preferences ? json_decode($preferences['preferred_categories'] ?? '[]', true) : [];
$preferred_skills     = $preferences ? json_decode($preferences['skills'] ?? '[]', true) : [];

// CHANGE 3: Infer categories only from approved events
$skills_stmt = $conn->prepare("
    SELECT DISTINCT 
        CASE 
            WHEN LOWER(o.title) LIKE '%clean%' OR LOWER(o.title) LIKE '%environment%' THEN 'Environment'
            WHEN LOWER(o.title) LIKE '%teach%' OR LOWER(o.title) LIKE '%education%' THEN 'Education'
            WHEN LOWER(o.title) LIKE '%food%' OR LOWER(o.title) LIKE '%kitchen%' THEN 'Food Service'
            WHEN LOWER(o.title) LIKE '%health%' OR LOWER(o.title) LIKE '%care%' THEN 'Healthcare'
            WHEN LOWER(o.title) LIKE '%community%' OR LOWER(o.title) LIKE '%event%' THEN 'Community'
            ELSE 'General'
        END as skill
    FROM applications a
    JOIN opportunities o ON a.opportunity_id = o.id
    WHERE a.volunteer_id = ? AND a.status = 'confirmed' AND a.hours_approved = 1
    LIMIT 10
");
$skills_stmt->execute([$user_id]);
$event_categories = array_filter(array_unique($skills_stmt->fetchAll(PDO::FETCH_COLUMN)));

$all_categories = array_unique(array_merge($preferred_categories, $event_categories));
$all_categories = array_filter($all_categories);

// Level system — unchanged
function getVolunteerLevel($hours) {
    if ($hours >= 100) {
        return ['level' => 4, 'name' => 'Master Volunteer', 'hours_required' => 100, 'next_level_hours' => null];
    } elseif ($hours >= 50) {
        return ['level' => 3, 'name' => 'Experienced Volunteer', 'hours_required' => 50, 'next_level_hours' => 100];
    } elseif ($hours >= 25) {
        return ['level' => 2, 'name' => 'Active Volunteer', 'hours_required' => 25, 'next_level_hours' => 50];
    } else {
        return ['level' => 1, 'name' => 'New Volunteer', 'hours_required' => 0, 'next_level_hours' => 25];
    }
}

$level_info = getVolunteerLevel($total_hours);
$level_images = [
    1 => 'img/engineers.jpg',
    2 => 'img/speedsters.jpg',
    3 => 'img/shadows.jpg',
    4 => 'img/hipster.jpg'
];
?>

<style>
    :root{
        --earth-1: #f2efe9;
        --accent-1: #6a8e3a;
        --accent-2: #b27a4b;
        --muted: #6b6b6b;
    }
    
    .profile-header {
        background: linear-gradient(135deg, var(--accent-1), var(--accent-2));
        border-radius: 18px;
        padding: 3rem 2rem;
        color: white;
        margin-bottom: 2rem;
    }
    
    .profile-avatar {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.2);
        backdrop-filter: blur(10px);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 3rem;
        font-weight: bold;
        border: 4px solid rgba(255, 255, 255, 0.3);
        margin: 0 auto 1rem;
    }
    
    .stat-card {
        background: white;
        border-radius: 14px;
        padding: 1.5rem;
        box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        transition: transform 0.2s ease;
    }
    
    .stat-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 6px 25px rgba(0,0,0,0.12);
    }
    
    .stat-number {
        font-size: 2.5rem;
        font-weight: 800;
        color: var(--accent-1);
        margin-bottom: 0.5rem;
    }
    
    .stat-label {
        color: var(--muted);
        font-size: 0.95rem;
        font-weight: 600;
    }
    
    .badge-item {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        background: white;
        padding: 0.75rem 1.25rem;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        margin: 0.5rem;
    }
    
    .badge-icon {
        font-size: 1.5rem;
    }
    
    .skill-tag {
        display: inline-block;
        background: var(--accent-1);
        color: white;
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-size: 0.9rem;
        font-weight: 600;
        margin: 0.25rem;
    }
    
    .progress-bar-custom {
        height: 12px;
        border-radius: 10px;
        background: #e5e7eb;
        overflow: hidden;
    }
    
    .progress-fill {
        height: 100%;
        background: linear-gradient(90deg, var(--accent-1), var(--accent-2));
        border-radius: 10px;
        transition: width 0.3s ease;
    }
    
    .event-card {
        background: white;
        border-radius: 12px;
        padding: 1.25rem;
        box-shadow: 0 2px 12px rgba(0,0,0,0.06);
        margin-bottom: 1rem;
    }
    
    .chart-container {
        background: white;
        border-radius: 14px;
        padding: 1.5rem;
        box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        height: 300px;
    }
    
    .level-display {
        background: white;
        border-radius: 18px;
        padding: 2rem;
        box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        text-align: center;
        margin-bottom: 2rem;
        position: relative;
        overflow: hidden;
    }
    
    .level-display::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 6px;
        background: linear-gradient(90deg, var(--accent-1), var(--accent-2));
    }
    
    .level-image {
        width: 180px;
        height: 180px;
        object-fit: contain;
        margin: 0 auto 1.5rem;
        display: block;
        border-radius: 12px;
        background: var(--earth-1);
        padding: 1rem;
    }
    
    .level-badge {
        display: inline-block;
        background: linear-gradient(135deg, var(--accent-1), var(--accent-2));
        color: white;
        padding: 0.5rem 1.5rem;
        border-radius: 25px;
        font-weight: 700;
        font-size: 1.1rem;
        margin-bottom: 0.5rem;
    }
    
    .level-name {
        font-size: 1.5rem;
        font-weight: 800;
        color: var(--accent-1);
        margin-bottom: 0.5rem;
    }
    
    .level-progress-info {
        color: var(--muted);
        font-size: 0.95rem;
        margin-top: 1rem;
    }
</style>

<div class="container my-5">
    <!-- Profile Header -->
    <div class="profile-header text-center">
        <div class="profile-avatar">
            <?= strtoupper(substr($user_name, 0, 2)) ?>
        </div>
        <h1 class="display-5 fw-bold mb-2"><?= htmlspecialchars($user_name) ?></h1>
        <p class="lead mb-0">Volunteer Profile</p>
    </div>

    <!-- Level Display -->
    <div class="level-display">
        <?php if (file_exists($level_images[$level_info['level']])): ?>
            <img src="<?= htmlspecialchars($level_images[$level_info['level']]) ?>" alt="Level <?= $level_info['level'] ?>" class="level-image">
        <?php else: ?>
            <div class="level-image d-flex align-items-center justify-content-center" style="background: linear-gradient(135deg, var(--accent-1), var(--accent-2)); color: white; font-size: 4rem; font-weight: bold;">
                L<?= $level_info['level'] ?>
            </div>
        <?php endif; ?>
        <div class="level-badge">Level <?= $level_info['level'] ?></div>
        <div class="level-name"><?= htmlspecialchars($level_info['name']) ?></div>
        <?php if ($level_info['next_level_hours'] !== null): 
            $hours_needed = $level_info['next_level_hours'] - $total_hours;
            $progress_to_next = (($total_hours - $level_info['hours_required']) / ($level_info['next_level_hours'] - $level_info['hours_required'])) * 100;
        ?>
            <div class="level-progress-info">
                <p class="mb-2"><strong><?= number_format($hours_needed) ?></strong> more hours to reach Level <?= $level_info['level'] + 1 ?></p>
                <div class="progress-bar-custom" style="max-width: 400px; margin: 0 auto;">
                    <div class="progress-fill" style="width: <?= min(100, max(0, $progress_to_next)) ?>%"></div>
                </div>
                <p class="small mt-2 mb-0"><?= number_format($total_hours) ?> / <?= $level_info['next_level_hours'] ?> hours</p>
            </div>
        <?php else: ?>
            <div class="level-progress-info">
                <p class="mb-0"><strong>Maximum Level Achieved!</strong></p>
                <p class="small mt-1 mb-0">You've reached the highest volunteer level with <?= number_format($total_hours) ?> hours!</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Stats Row -->
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="stat-card text-center">
                <div class="stat-number"><?= number_format($total_hours, 1) ?></div>
                <div class="stat-label">Verified Hours</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card text-center">
                <div class="stat-number"><?= $total_events ?></div>
                <div class="stat-label">Events Completed</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card text-center">
                <div class="stat-number"><?= count($badges) ?></div>
                <div class="stat-label">Badges Earned</div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Left Column -->
        <div class="col-lg-8">
            <!-- Hours Chart -->
            <div class="chart-container mb-4">
                <h4 class="fw-bold mb-3">Verified Hours (Last 6 Months)</h4>
                <canvas id="hoursChart" height="250"></canvas>
            </div>

            <!-- Upcoming Events -->
            <div class="stat-card">
                <h4 class="fw-bold mb-3">Upcoming Events</h4>
                <?php if (empty($upcoming_events)): ?>
                    <p class="text-muted">No upcoming events. <a href="public_browse.php">Browse opportunities</a> to get started!</p>
                <?php else: ?>
                    <?php foreach ($upcoming_events as $event): ?>
                        <div class="event-card">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <h6 class="fw-bold mb-1"><?= htmlspecialchars($event['title']) ?></h6>
                                    <p class="small text-muted mb-2"><?= htmlspecialchars($event['org_name'] ?? 'Unknown') ?></p>
                                    <div class="small text-muted">
                                        <i class="bi bi-calendar-event"></i> <?= date('F j, Y', strtotime($event['date'])) ?>
                                        <?php if ($event['time']): ?>
                                            • <?= date('g:i A', strtotime($event['time'])) ?>
                                        <?php endif; ?>
                                        <br>
                                        <i class="bi bi-geo-alt-fill"></i> <?= htmlspecialchars($event['location']) ?>
                                    </div>
                                </div>
                                <div>
                                    <span class="badge bg-<?= $event['status'] === 'confirmed' ? 'success' : 'warning' ?>">
                                        <?= ucfirst($event['status']) ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Right Column -->
        <div class="col-lg-4">
            <!-- Badges -->
            <div class="stat-card mb-4">
                <h4 class="fw-bold mb-3">Badges & Achievements</h4>
                <?php if (empty($badges)): ?>
                    <p class="text-muted small">Complete events to earn badges!</p>
                <?php else: ?>
                    <div>
                        <?php foreach ($badges as $badge): ?>
                            <div class="badge-item">
                                <i class="bi <?= $badge['icon'] ?> badge-icon" style="color: <?= $badge['color'] ?>;"></i>
                                <span><?= htmlspecialchars($badge['name']) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Skills & Categories -->
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="fw-bold mb-0">Skills & Categories</h4>
                    <a href="volunteer_preferences.php" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-gear"></i> Edit
                    </a>
                </div>
                
                <?php if (empty($all_categories) && empty($preferred_skills)): ?>
                    <p class="text-muted small">Your skills and categories will appear here!</p>
                    <a href="volunteer_preferences.php" class="btn btn-sm btn-primary">Set Preferences</a>
                <?php else: ?>
                    <?php if (!empty($all_categories)): ?>
                        <div class="mb-3">
                            <h6 class="fw-bold mb-2 small text-muted">Categories</h6>
                            <div>
                                <?php foreach ($all_categories as $cat): ?>
                                    <span class="skill-tag" style="<?= in_array($cat, $preferred_categories) ? 'border: 2px solid var(--accent-1);' : '' ?>">
                                        <?= htmlspecialchars($cat) ?>
                                        <?php if (in_array($cat, $preferred_categories)): ?>
                                            <i class="bi bi-star-fill ms-1" style="font-size: 0.7rem;"></i>
                                        <?php endif; ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($preferred_skills)): ?>
                        <div>
                            <h6 class="fw-bold mb-2 small text-muted">Skills</h6>
                            <div>
                                <?php foreach ($preferred_skills as $skill): ?>
                                    <span class="skill-tag" style="background: var(--accent-2);">
                                        <?= htmlspecialchars($skill) ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <!-- All Levels Preview -->
            <div class="stat-card mt-4">
                <h5 class="fw-bold mb-3">Level Progression</h5>
                <div class="small">
                    <?php for ($lvl = 1; $lvl <= 4; $lvl++): 
                        $lvl_hours = [1 => 0, 2 => 25, 3 => 50, 4 => 100];
                        $lvl_names = [1 => 'New Volunteer', 2 => 'Active Volunteer', 3 => 'Experienced Volunteer', 4 => 'Master Volunteer'];
                        $is_unlocked = $total_hours >= $lvl_hours[$lvl];
                        $is_current = $level_info['level'] == $lvl;
                    ?>
                        <div class="d-flex align-items-center mb-2 p-2 rounded <?= $is_current ? 'bg-light' : '' ?>" style="<?= $is_current ? 'border: 2px solid var(--accent-1);' : '' ?>">
                            <div class="me-2">
                                <?php if ($is_unlocked): ?>
                                    <i class="bi bi-check-circle-fill text-success"></i>
                                <?php else: ?>
                                    <i class="bi bi-circle text-muted"></i>
                                <?php endif; ?>
                            </div>
                            <div class="flex-grow-1">
                                <strong>Level <?= $lvl ?>:</strong> <?= $lvl_names[$lvl] ?>
                                <span class="text-muted">(<?= $lvl_hours[$lvl] ?>+ hours)</span>
                                <?php if ($is_current): ?>
                                    <span class="badge bg-success ms-2">Current</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endfor; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    const ctx = document.getElementById('hoursChart');
    if (ctx) {
        const hoursData = <?= json_encode($hours_by_month) ?>;
        const labels = hoursData.map(item => {
            const date = new Date(item.month + '-01');
            return date.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
        });
        const hours = hoursData.map(item => parseFloat(item.hours));

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Verified Hours',
                    data: hours,
                    borderColor: '#6a8e3a',
                    backgroundColor: 'rgba(106, 142, 58, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true } }
            }
        });
    }
</script>

<?php include 'includes/footer.php'; ?>