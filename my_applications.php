<?php
session_start();
require_once 'includes/auth_guard.php';
require_once 'config/config.php';
include_once 'includes/header.php';
?>

<style>
/* Your beautiful styling – untouched */
.app-card {
    border: none;
    border-radius: 18px;
    padding: 22px;
    background: #fff;
    box-shadow: 0 4px 14px rgba(0,0,0,0.08);
    transition: 0.25s ease;
}
.app-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 26px rgba(0,0,0,0.12);
}
.app-badge {
    font-size: 1rem;
    padding: 8px 18px;
    border-radius: 50px !important;
    font-weight: 600;
    box-shadow: 0 3px 6px rgba(0,0,0,0.12);
}
.empty-box {
    margin-top: 60px;
    text-align: center;
    color: #888;
}
</style>

<div class="container my-5">
    <h1 class="mb-4 fw-bold">My Volunteer Applications</h1>

    <?php
    // 1. Try to get real applications from DB (for future when you connect real events)
    $stmt = $conn->prepare("
        SELECT a.*, o.title, o.date, o.time, o.location_name, u.name AS org_name 
        FROM applications a 
        JOIN opportunities o ON a.opportunity_id = o.id 
        JOIN users u ON o.organization_id = u.id 
        WHERE a.volunteer_id = ? 
        ORDER BY a.applied_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $real_apps = $stmt->fetchAll();
    ?>
    <script>
        // If no real apps and no fake apps → show empty state
        if (!<?=count($real_apps)?> && !hasFake) {
            document.querySelector('.empty-box')?.classList.remove('d-none');
        }
    });
    </script>

    <div class="applications-container">
        <?php if (!empty($real_apps)): ?>
            <?php foreach ($real_apps as $app):
                $status = strtolower($app['status'] ?? 'pending');
                $badgeClass = $status === 'confirmed' ? 'bg-success text-white' :
                             ($status === 'pending' ? 'bg-warning text-dark' :
                             ($status === 'cancelled' ? 'bg-danger text-white' : 'bg-secondary text-white'));
                $badgeText = ucfirst($status);

                $hoursDisplay = '';
                if ($status === 'completed' && !empty($app['hours_approved'])) {
                    $hoursDisplay = '<small class="text-success fw-bold">Verified ' . $app['hours_worked'] . ' hours verified</small><br>';
                }
            ?>
                <div class="app-card mb-4">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <h4 class="fw-bold mb-1"><?=htmlspecialchars($app['title'])?></h4>
                            <p class="text-muted mb-2">
                                <i class="bi bi-building"></i> <?=htmlspecialchars($app['location_name'] ?? 'Prishtina')?> • 
                                <i class="bi bi-calendar"></i> <?=date('F j, Y', strtotime($app['date']))?>
                                <?php if ($app['time']): ?> at <?=date('g:i A', strtotime($app['time']))?><?php endif; ?>
                            </p>
                            <?=$hoursDisplay?>
                            <small class="text-muted">Applied on <?=date('M j, Y \a\t g:i A', strtotime($app['applied_at']))?></small>
                        </div>
                        <span class="app-badge <?=$badgeClass?>"><?=$badgeText?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Empty state -->
    <div class="empty-box d-none">
        <h4>No Applications Yet</h4>
        <p class="text-muted">Browse opportunities and apply to get started.</p>
        <a href="public_browse.php" class="btn btn-success mt-3 px-4 py-2">Browse Opportunities</a>
    </div>

    <div class="mt-4">
        <a href="public_browse.php" class="btn btn-outline-primary">
            Back to Opportunities
        </a>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
</body>
</html>