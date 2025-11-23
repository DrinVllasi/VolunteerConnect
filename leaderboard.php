<?php
session_start();
require_once 'config/config.php';
include_once 'includes/header.php';

function getVolunteerLevel($hours) {
    if ($hours >= 100) return ['level' => 4, 'name' => 'Master Volunteer', 'hours_required' => 100, 'next_level_hours' => null];
    if ($hours >= 50)  return ['level' => 3, 'name' => 'Experienced Volunteer', 'hours_required' => 50, 'next_level_hours' => 100];
    if ($hours >= 25)  return ['level' => 2, 'name' => 'Active Volunteer', 'hours_required' => 25, 'next_level_hours' => 50];
    return                     ['level' => 1, 'name' => 'New Volunteer', 'hours_required' => 0, 'next_level_hours' => 25];
}

$level_images = [
    1 => 'img/engineers.jpg',
    2 => 'img/speedsters.jpg',
    3 => 'img/shadows.jpg',
    4 => 'img/hipster.jpg'
];

// CORRECT QUERY — gets real verified hours from applications table
$stmt = $conn->prepare("
    SELECT 
        u.name,
        COALESCE(SUM(a.hours_worked), 0) AS total_hours
    FROM users u
    LEFT JOIN applications a ON u.id = a.volunteer_id 
        AND a.status = 'completed' 
        AND a.hours_approved = 1
    WHERE u.role IN ('volunteer', 'user')
    GROUP BY u.id, u.name
    HAVING total_hours > 0
    ORDER BY total_hours DESC, u.name ASC
    LIMIT 15
");
$stmt->execute();
$leaders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container py-5">
    <div class="text-center mb-5">
        <h1 class="display-4 fw-bold mb-3">Leaderboard</h1>
        <p class="lead text-muted">Top volunteers of all time — verified & approved hours</p>
    </div>

    <?php if (empty($leaders)): ?>
        <div class="text-center py-5">
            <div class="bg-light rounded-4 p-5">
                <h3 class="text-muted">No verified hours yet</h3>
                <p class="lead text-muted">Be the first legend!</p>
            </div>
        </div>
    <?php else: ?>
        <div class="row g-4 justify-content-center">
            <?php $rank = 1; foreach ($leaders as $vol): 
                $level = getVolunteerLevel($vol['total_hours']);
                $level_img = $level_images[$level['level']] ?? 'img/engineers.jpg';
            ?>
                <div class="col-md-6 col-lg-4 col-xl-3">
                    <div class="card h-100 shadow-lg border-0 position-relative overflow-hidden 
                        <?= $rank === 1 ? 'border-warning border-3' : ($rank === 2 ? 'border-secondary border-2' : ($rank === 3 ? 'border-danger border-2' : 'border-light')) ?> 
                        transform-hover">

                        <?php if ($rank === 1): ?>
                            <i class="bi bi-trophy-fill text-warning position-absolute top-0 end-0 fs-1 opacity-75"></i>
                        <?php elseif ($rank === 2): ?>
                            <i class="bi bi-trophy-fill text-secondary position-absolute top-0 end-0 fs-2 opacity-70"></i>
                        <?php elseif ($rank === 3): ?>
                            <i class="bi bi-trophy-fill text-danger position-absolute top-0 end-0 fs-3 opacity-65"></i>
                        <?php endif; ?>

                        <div class="card-body text-center p-4">
                            <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3
                                <?= $rank <= 3 ? 'bg-warning text-dark' : 'bg-primary text-white' ?>"
                                 style="width: 70px; height: 70px; font-size: 2rem; font-weight: 800;">
                                #<?= $rank ?>
                            </div>

                            <?php if (file_exists($level_img)): ?>
                                <img src="<?= htmlspecialchars($level_img) ?>" alt="Level <?= $level['level'] ?>" class="leaderboard-level-img">
                            <?php else: ?>
                                <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold shadow-lg"
                                     style="width: 120px; height: 120px; font-size: 2.5rem; background: linear-gradient(135deg, #8b5cf6, #ec4899);">
                                    <?= strtoupper(substr($vol['name'], 0, 2)) ?>
                                </div>
                            <?php endif; ?>

                            <div class="position-absolute bottom-0 start-50 translate-middle-x mb-3">
                                <span class="badge rounded-pill px-3 py-2" 
                                      style="background: linear-gradient(135deg, #6a8e3a, #b27a4b); color: white; font-weight: 700; font-size: 0.85rem;">
                                    L<?= $level['level'] ?> • <?= $level['name'] ?>
                                </span>
                            </div>

                            <h4 class="fw-bold mb-1 mt-4"><?= htmlspecialchars($vol['name']) ?></h4>
                            <p class="text-muted small mb-3">Lifetime Legend</p>

                            <div class="bg-light rounded-pill overflow-hidden mb-3" style="height: 28px;">
                                <div class="h-100 d-flex align-items-center justify-content-center text-white fw-bold"
                                     style="width: <?= min(100, ($vol['total_hours'] / 300) * 100) ?>%; 
                                            background: linear-gradient(90deg, #667eea, #764ba2);">
                                    <?= number_format($vol['total_hours'], 1) ?> hrs
                                </div>
                            </div>

                            <p class="fw-bold text-primary fs-3 mb-0"><?= number_format($vol['total_hours'], 1) ?></p>
                            <small class="text-muted">verified hours</small>
                        </div>
                    </div>
                </div>
            <?php $rank++; endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="text-center mt-5">
        <p class="text-muted small">Based on organization-approved hours • All time ranking</p>
    </div>
</div>

<style>
    :root{ --accent-1: #6a8e3a; --accent-2: #b27a4b; }
    .transform-hover { transition: all 0.4s ease; }
    .transform-hover:hover {
        transform: translateY(-12px) scale(1.02);
        box-shadow: 0 30px 60px rgba(0,0,0,0.18) !important;
    }
    .leaderboard-level-img {
        width: 120px; height: 120px; object-fit: cover; border-radius: 50%;
        border: 4px solid white; box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        transition: transform 0.3s ease;
    }
    .transform-hover:hover .leaderboard-level-img { transform: scale(1.1); }
</style>

<?php include 'includes/footer.php'; ?>
</body>
</html>