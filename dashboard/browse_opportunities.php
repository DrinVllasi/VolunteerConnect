<?php require_once '../includes/auth_guard.php'; ?>
<?php require_once '../config/config.php'; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Opportunities - VolunteerConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>body{background:#f8f9fa}.card-hover:hover{transform:translateY(-5px);transition:.3s}</style>
</head>
<body>
<div class="container my-5">
    <h1 class="text-center mb-5">Volunteer Opportunities</h1>

    <?php
    $stmt = $conn->query("SELECT o.*, u.name as org_name,
                          (o.slots - IFNULL(COUNT(a.id),0)) as spots_left
                          FROM opportunities o
                          LEFT JOIN users u ON o.organization_id = u.id
                          LEFT JOIN applications a ON o.id = a.opportunity_id AND a.status = 'confirmed'
                          WHERE o.date >= CURDATE()
                          GROUP BY o.id
                          ORDER BY o.date ASC");
    $opportunities = $stmt->fetchAll();
    ?>

    <?php if (empty($opportunities)): ?>
        <div class="alert alert-info text-center">No opportunities yet — check back soon!</div>
    <?php endif; ?>

    <div class="row g-4">
    <?php foreach ($opportunities as $opp): ?>
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 shadow card-hover">
                <div class="card-body d-flex flex-column">
                    <h5 class="card-title"><?= htmlspecialchars($opp['title']) ?></h5>
                    <p class="text-muted small">by <?= htmlspecialchars($opp['org_name']) ?></p>
                    <p class="card-text flex-grow-1"><?= nl2br(htmlspecialchars($opp['description'])) ?></p>
                    <ul class="list-unstyled mt-3">
                        <li>Location: <?= htmlspecialchars($opp['location']) ?></li>
                        <li>Date: <?= date('M j, Y', strtotime($opp['date'])) ?><?= $opp['time'] ? ' at '.date('g:i A', strtotime($opp['time'])) : '' ?></li>
                        <li>Spots left: <strong><?= max(0, $opp['spots_left']) ?>/<?= $opp['slots'] ?></strong></li>
                    </ul>

                    <?php
                    $check = $conn->prepare("SELECT status FROM applications WHERE opportunity_id = ? AND volunteer_id = ?");
                    $check->execute([$opp['id'], $_SESSION['user_id']]);
                    $applied = $check->fetch();
                    ?>

                    <?php if ($applied): ?>
                        <?php if ($applied['status'] === 'confirmed'): ?>
                            <span class="btn btn-success w-100">Confirmed!</span>
                        <?php else: ?>
                            <span class="btn btn-warning w-100">Pending</span>
                        <?php endif; ?>
                    <?php elseif ($opp['spots_left'] <= 0): ?>
                        <span class="btn btn-secondary w-100">Full</span>
                    <?php else: ?>
                        <form action="" method="POST" class="mt-auto">
                            <input type="hidden" name="opp_id" value="<?= $opp['id'] ?>">
                            <button type="submit" name="apply" class="btn btn-primary w-100">Sign Me Up!</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    </div>

    <div class="text-center mt-5">
        <a href="../public_browse.php" class="btn btn-outline-primary">← Back to Dashboard</a>
    </div>
</div>

<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply'])) {
    $opp_id = (int)$_POST['opp_id'];
    try {
        $conn->prepare("INSERT INTO applications (opportunity_id, volunteer_id) VALUES (?, ?)")
            ->execute([$opp_id, $_SESSION['user_id']]);
        $_SESSION['opp_success'] = "Successfully applied!";
    } catch (PDOException $e) {
        // Duplicate or full → ignore silently
    }
    exit();
}
?>
</body>
</html>