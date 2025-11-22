<?php
session_start();
require_once 'config/config.php';
include_once 'includes/header.php';
?>

<div class="container py-5">
    <div class="text-center mb-5">
        <h1 class="display-4 fw-bold mb-3">Leaderboard</h1>
        <p class="lead text-muted">Top volunteers making the biggest impact this month</p>
    </div>

    <?php
    // Safe query — works even if you don't have updated_at column
    $stmt = $conn->prepare("
        SELECT u.name, 
               COALESCE(SUM(a.hours_logged), 0) as total_hours,
               COUNT(a.id) as events_done
        FROM users u
        LEFT JOIN applications a ON a.volunteer_id = u.id 
            AND a.status = 'confirmed' 
            AND a.hours_logged > 0
            AND (
                a.applied_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
                OR a.applied_at IS NULL
            )
        WHERE u.role IN ('user', 'volunteer')
        GROUP BY u.id, u.name
        HAVING total_hours > 0
        ORDER BY total_hours DESC 
        LIMIT 15
    ");
    $stmt->execute();
    $leaders = $stmt->fetchAll();
    ?>

    <?php if (empty($leaders)): ?>
        <div class="text-center py-5">
            <div class="bg-light rounded-4 p-5">
                <h3 class="text-muted">No hours logged yet this month</h3>
                <p class="lead text-muted">Be the first to make an impact!</p>
            </div>
        </div>
    <?php else: ?>
        <div class="row g-4 justify-content-center">
            <?php $rank = 1; foreach ($leaders as $vol): ?>
                <div class="col-md-6 col-lg-4 col-xl-3">
                    <div class="card h-100 shadow-lg border-0 position-relative overflow-hidden 
                        <?php 
                        echo $rank === 1 ? 'border-warning border-3' : 
                             ($rank === 2 ? 'border-secondary border-2' : 
                             ($rank === 3 ? 'border-danger border-2' : 'border-light')); 
                        ?> transform-hover">
                        
                        <div class="card-body text-center p-4">
                            <!-- Trophy Icons -->
                            <?php if ($rank === 1): ?>
                                <i class="bi bi-trophy-fill text-warning position-absolute top-0 end-0 fs-1 opacity-75"></i>
                            <?php elseif ($rank === 2): ?>
                                <i class="bi bi-trophy-fill text-secondary position-absolute top-0 end-0 fs-2 opacity-70"></i>
                            <?php elseif ($rank === 3): ?>
                                <i class="bi bi-trophy-fill text-danger position-absolute top-0 end-0 fs-3 opacity-65"></i>
                            <?php endif; ?>

                            <!-- Rank Badge -->
                            <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3
                                <?= $rank <= 3 ? 'bg-warning text-dark' : 'bg-primary text-white' ?>"
                                 style="width: 70px; height: 70px; font-size: 2rem; font-weight: 800;">
                                #<?= $rank ?>
                            </div>

                            <!-- Avatar -->
                            <div class="mx-auto mb-3 rounded-circle d-flex align-items-center justify-content-center text-white fw-bold shadow-lg"
                                 style="width: 90px; height: 90px; font-size: 2.5rem;
                                        background: linear-gradient(135deg, #8b5cf6, #ec4899);">
                                <?= strtoupper(substr($vol['name'], 0, 2)) ?>
                            </div>

                            <h4 class="fw-bold mb-1"><?= htmlspecialchars($vol['name']) ?></h4>
                            <p class="text-muted small mb-3">
                                <?= $vol['events_done'] ?> event<?= $vol['events_done'] == 1 ? '' : 's' ?>
                            </p>

                            <!-- Progress Bar -->
                            <div class="bg-light rounded-pill overflow-hidden mb-3" style="height: 28px;">
                                <div class="h-100 d-flex align-items-center justify-content-center text-white fw-bold"
                                     style="width: <?= min(100, ($vol['total_hours'] / 200) * 100) ?>%;
                                            background: linear-gradient(90deg, #667eea, #764ba2);">
                                    <?= $vol['total_hours'] ?> hrs
                                </div>
                            </div>

                            <p class="fw-bold text-primary fs-3 mb-0"><?= $vol['total_hours'] ?></p>
                            <small class="text-muted">hours logged</small>
                        </div>
                    </div>
                </div>
            <?php $rank++; endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="text-center mt-5">
        <p class="text-muted small">Based on confirmed & logged hours • Resets monthly</p>
    </div>
</div>

<style>
    .transform-hover {
        transition: all 0.4s ease;
    }
    .transform-hover:hover {
        transform: translateY(-12px) scale(1.02);
        box-shadow: 0 30px 60px rgba(0,0,0,0.18) !important;
    }
</style>

<?php include 'includes/footer.php'; ?>
</body>
</html>