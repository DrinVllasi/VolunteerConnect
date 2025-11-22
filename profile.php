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

// Get total hours volunteered
$total_hours_stmt = $conn->prepare("
    SELECT COALESCE(SUM(hours_logged), 0) as total_hours
    FROM applications
    WHERE volunteer_id = ? AND status = 'confirmed' AND hours_logged > 0
");
$total_hours_stmt->execute([$user_id]);
$total_hours = $total_hours_stmt->fetchColumn();

// Get hours by month for chart
$hours_by_month_stmt = $conn->prepare("
    SELECT 
        DATE_FORMAT(o.date, '%Y-%m') as month,
        COALESCE(SUM(a.hours_logged), 0) as hours
    FROM applications a
    JOIN opportunities o ON a.opportunity_id = o.id
    WHERE a.volunteer_id = ? 
        AND a.status = 'confirmed' 
        AND a.hours_logged > 0
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

// Calculate badges/achievements
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

// Get skills/categories from events (infer from descriptions/titles)
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
    WHERE a.volunteer_id = ? AND a.status = 'confirmed'
    LIMIT 10
");
$skills_stmt->execute([$user_id]);
$skills = $skills_stmt->fetchAll(PDO::FETCH_COLUMN);
$skills = array_filter(array_unique($skills)); // Remove duplicates and empty values
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

    <!-- Stats Row -->
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="stat-card text-center">
                <div class="stat-number"><?= number_format($total_hours) ?></div>
                <div class="stat-label">Hours Volunteered</div>
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
                <h4 class="fw-bold mb-3">Hours Volunteered (Last 6 Months)</h4>
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

            <!-- Skills -->
            <div class="stat-card">
                <h4 class="fw-bold mb-3">Skills & Categories</h4>
                <?php if (empty($skills)): ?>
                    <p class="text-muted small">Your skills will appear here as you volunteer!</p>
                <?php else: ?>
                    <div>
                        <?php foreach ($skills as $skill): ?>
                            <span class="skill-tag"><?= htmlspecialchars($skill) ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Progress to Next Badge -->
            <div class="stat-card mt-4">
                <h5 class="fw-bold mb-3">Progress</h5>
                <?php
                $next_milestone = 25;
                if ($total_hours >= 100) {
                    $next_milestone = 200;
                } elseif ($total_hours >= 50) {
                    $next_milestone = 100;
                } elseif ($total_hours >= 25) {
                    $next_milestone = 50;
                }
                $progress = $next_milestone > 0 ? min(100, ($total_hours / $next_milestone) * 100) : 0;
                ?>
                <p class="small text-muted mb-2">Next milestone: <?= $next_milestone ?> hours</p>
                <div class="progress-bar-custom">
                    <div class="progress-fill" style="width: <?= $progress ?>%"></div>
                </div>
                <p class="small text-muted mt-2 mb-0"><?= number_format($total_hours) ?> / <?= $next_milestone ?> hours</p>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js for the hours chart -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    // Hours Chart
    const ctx = document.getElementById('hoursChart');
    if (ctx) {
        const hoursData = <?= json_encode($hours_by_month) ?>;
        const labels = hoursData.map(item => {
            const date = new Date(item.month + '-01');
            return date.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
        });
        const hours = hoursData.map(item => parseInt(item.hours));

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Hours',
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
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    }
</script>

<?php include 'includes/footer.php'; ?>

