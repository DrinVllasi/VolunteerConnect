<?php
session_start();
require_once 'includes/auth_guard.php';
require_once 'config/config.php';
include_once 'includes/header.php';
?>

<style>
/* Modern card styling */
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
    $stmt = $conn->prepare("
        SELECT o.*, a.status, a.applied_at 
        FROM applications a 
        JOIN opportunities o ON a.opportunity_id = o.id 
        WHERE a.volunteer_id = ? 
        ORDER BY a.applied_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $apps = $stmt->fetchAll();
    ?>

    <?php if (empty($apps)): ?>
        <div class="empty-box">
            <h4>No Applications Yet</h4>
            <p class="text-muted">Browse opportunities and apply to get started.</p>
            <a href="public_browse.php" class="btn btn-primary mt-3">Browse Opportunities</a>
        </div>
    <?php endif; ?>

    <?php foreach ($apps as $app): ?>

        <?php
        $status = strtolower($app['status']);
        $badgeClass = match ($status) {
            'confirmed' => 'bg-success text-white',
            'pending'   => 'bg-warning text-dark',
            'rejected'  => 'bg-danger text-white',
            default     => 'bg-secondary text-white'
        };
        ?>

        <div class="app-card mb-4">
            <div class="d-flex justify-content-between align-items-center">
                
                <div>
                    <h4 class="fw-bold mb-1"><?= htmlspecialchars($app['title']) ?></h4>
                    <p class="text-muted mb-0">
                        <?= htmlspecialchars($app['location']) ?> • 
                        <?= date('M j, Y', strtotime($app['date'])) ?>
                    </p>
                </div>

                <span class="app-badge <?= $badgeClass ?>">
                    <?= ucfirst($app['status']) ?>
                </span>

            </div>
        </div>

    <?php endforeach; ?>

    <a href="public_browse.php" class="btn btn-primary mt-4">← Dashboard</a>

</div>

<?php include 'includes/footer.php'; ?>
</body>
</html>
