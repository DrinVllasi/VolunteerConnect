<?php
session_start();
require_once 'includes/auth_guard.php';
require_once 'config/config.php';
include_once 'includes/header.php';
?>


<div class="container my-5">
    <h1>My Volunteer Applications</h1>
    <?php
    $stmt = $conn->prepare("SELECT o.*, a.status, a.applied_at 
                            FROM applications a 
                            JOIN opportunities o ON a.opportunity_id = o.id 
                            WHERE a.volunteer_id = ? 
                            ORDER BY a.applied_at DESC");
    $stmt->execute([$_SESSION['user_id']]);
    $apps = $stmt->fetchAll();
    ?>
    <?php foreach ($apps as $app): ?>
        <div class="card mb-3 shadow-sm">
            <div class="card-body">
                <h5><?= htmlspecialchars($app['title']) ?> 
                    <span class="badge bg-<?= $app['status']=='confirmed'?'success':'warning' ?> float-end">
                        <?= ucfirst($app['status']) ?>
                    </span>
                </h5>
                <p><?= htmlspecialchars($app['location']) ?> • <?= date('M j, Y', strtotime($app['date'])) ?></p>
            </div>
        </div>
    <?php endforeach; ?>
    <a href="public_browse.php" class="btn btn-primary">← Dashboard</a>
</div>
<?php include 'includes/footer.php'; ?>
</body>
</html>
