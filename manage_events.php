<?php
session_start();
require_once 'includes/auth_guard.php';
require_once 'config/config.php';
include_once 'includes/header.php';
?>

<?php
// Only organization & admin can access
if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['role'], ['organization', 'admin'])) {
    header('Location: index.php');
    exit();
}

// Handle approve / reject
if (isset($_POST['action']) && in_array($_POST['action'], ['approve', 'reject'])) {
    $status = $_POST['action'] === 'approve' ? 'confirmed' : 'cancelled';
    $stmt = $conn->prepare("UPDATE applications SET status = ? WHERE id = ?");
    $stmt->execute([$status, $_POST['app_id']]);
    header("Location: manage_events.php");
    exit();
}

// Handle hours logging
if (isset($_POST['save_hours'])) {
    $hours = max(0, min(50, (int)$_POST['hours']));
    $stmt = $conn->prepare("UPDATE applications SET hours_logged = ? WHERE id = ?");
    $stmt->execute([$hours, $_POST['app_id']]);
    header("Location: manage_events.php");
    exit();
}
?>

<div class="container my-5">
    <h1 class="display-5 fw-bold mb-4">Manage Events</h1>

    <?php
    $stmt = $conn->prepare("SELECT o.*, 
        (o.slots - COUNT(CASE WHEN a.status='confirmed' THEN 1 END)) AS spots_left
        FROM opportunities o 
        LEFT JOIN applications a ON o.id = a.opportunity_id
        WHERE o.organization_id = ? 
        GROUP BY o.id ORDER BY o.date DESC");
    $stmt->execute([$_SESSION['user_id']]);
    $events = $stmt->fetchAll();
    ?>

    <?php if (empty($events)): ?>
        <div class="text-center py-5">
            <p class="lead text-muted">You haven't posted any events yet.</p>
            <a href="post_opportunity.php" class="btn btn-primary btn-lg">Post Your First Event</a>
        </div>
    <?php else: ?>
        <?php foreach ($events as $event): ?>
            <div class="card mb-4 shadow-sm border-0">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold"><?= htmlspecialchars($event['title']) ?></h5>
                    <span class="badge bg-<?= $event['spots_left'] > 0 ? 'success' : 'danger' ?> fs-6">
                        <?= $event['spots_left'] ?> / <?= $event['slots'] ?> spots
                    </span>
                </div>
                <div class="card-body">
                    <p><strong>Date:</strong> <?= date('l, F j, Y', strtotime($event['date'])) ?>
                        <?= $event['time'] ? ' at '.date('g:i A', strtotime($event['time'])) : '' ?></p>
                    <p><strong>Location:</strong> <?= htmlspecialchars($event['location']) ?></p>

                    <h6 class="mt-4">Applicants</h6>
                    <?php
                    $apps = $conn->prepare("SELECT a.*, u.name, u.email 
                                            FROM applications a 
                                            JOIN users u ON a.volunteer_id = u.id 
                                            WHERE a.opportunity_id = ? 
                                            ORDER BY a.applied_at DESC");
                    $apps->execute([$event['id']]);
                    $applicants = $apps->fetchAll();
                    ?>

                    <?php if (empty($applicants)): ?>
                        <p class="text-muted">No applicants yet.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead><tr><th>Name</th><th>Email</th><th>Status</th><th>Hours</th><th>Actions</th></tr></thead>
                                <tbody>
                                    <?php foreach ($applicants as $a): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($a['name']) ?></td>
                                            <td><?= htmlspecialchars($a['email']) ?></td>
                                            <td>
                                                <span class="badge bg-<?= $a['status']=='confirmed'?'success':($a['status']=='cancelled'?'secondary':'warning') ?>">
                                                    <?= ucfirst($a['status']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="app_id" value="<?= $a['id'] ?>">
                                                    <input type="number" name="hours" value="<?= $a['hours_logged'] ?? 0 ?>" 
                                                           min="0" max="50" class="form-control form-control-sm d-inline w-50">
                                                    <button name="save_hours" class="btn btn-sm btn-success">Save</button>
                                                </form>
                                            </td>
                                            <td>
                                                <?php if ($a['status'] === 'pending'): ?>
                                                    <form method="POST" class="d-inline">
                                                        <input type="hidden" name="app_id" value="<?= $a['id'] ?>">
                                                        <button name="action" value="approve" class="btn btn-sm btn-success">Approve</button>
                                                        <button name="action" value="reject" class="btn btn-sm btn-danger">Reject</button>
                                                    </form>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>