<?php
session_start();
require_once 'includes/auth_guard.php';
require_once 'config/config.php';

// Only organization & admin can access - check BEFORE header
if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['role'], ['organization', 'admin'])) {
    header('Location: index.php');
    exit();
}

// Handle approve / reject - BEFORE header
if (isset($_POST['action']) && in_array($_POST['action'], ['approve', 'reject'])) {
    $status = $_POST['action'] === 'approve' ? 'confirmed' : 'cancelled';
    $stmt = $conn->prepare("UPDATE applications SET status = ? WHERE id = ?");
    $stmt->execute([$status, $_POST['app_id']]);
    header("Location: manage_events.php");
    exit();
}

// Handle hours logging - BEFORE header
if (isset($_POST['save_hours'])) {
    $hours = max(0, min(50, (int)$_POST['hours']));
    $stmt = $conn->prepare("UPDATE applications SET hours_logged = ? WHERE id = ?");
    $stmt->execute([$hours, $_POST['app_id']]);
    header("Location: manage_events.php");
    exit();
}

// Now include header after processing POST (if not redirecting)
include_once 'includes/header.php';
require_once 'includes/matching_engine.php';
require_once 'interest_handler.php';
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

                    <!-- Interested Volunteers -->
                    <?php
                    $interested_volunteers = getInterestedVolunteers($conn, $event['id']);
                    $mutual_matches = getMutualMatches($conn, $event['id']);
                    ?>
                    
                    <?php if (!empty($mutual_matches)): ?>
                        <div class="alert alert-success mb-3">
                            <h6 class="fw-bold mb-2">
                                <i class="bi bi-star-fill"></i> Mutual Matches!
                            </h6>
                            <p class="small mb-2">These volunteers expressed interest AND you invited them - perfect match!</p>
                            <div class="row g-2">
                                <?php foreach ($mutual_matches as $match): ?>
                                    <div class="col-md-6">
                                        <div class="card border-success">
                                            <div class="card-body p-2">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <strong><?= htmlspecialchars($match['name']) ?></strong>
                                                        <br><small class="text-muted"><?= htmlspecialchars($match['email']) ?></small>
                                                    </div>
                                                    <span class="badge bg-success">⭐ Mutual Match</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($interested_volunteers)): ?>
                        <div class="alert alert-info mb-3">
                            <h6 class="fw-bold mb-2">
                                <i class="bi bi-heart-fill"></i> <?= count($interested_volunteers) ?> Volunteer<?= count($interested_volunteers) > 1 ? 's' : '' ?> Expressed Interest
                            </h6>
                            <p class="small mb-2">These volunteers are interested in this opportunity. Consider inviting them!</p>
                            <div class="row g-2">
                                <?php foreach ($interested_volunteers as $vol): ?>
                                    <div class="col-md-6">
                                        <div class="card border-0 shadow-sm">
                                            <div class="card-body p-3">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div class="flex-grow-1">
                                                        <h6 class="mb-1 fw-bold"><?= htmlspecialchars($vol['name']) ?></h6>
                                                        <p class="small text-muted mb-2"><?= htmlspecialchars($vol['email']) ?></p>
                                                        <small class="text-muted">
                                                            <i class="bi bi-clock"></i> <?= date('M j, Y g:i A', strtotime($vol['interested_at'])) ?>
                                                        </small>
                                                    </div>
                                                    <div>
                                                        <?php if ($vol['has_invite']): ?>
                                                            <span class="badge bg-success">Invited</span>
                                                        <?php else: ?>
                                                            <form method="POST" action="interest_handler.php" class="d-inline">
                                                                <input type="hidden" name="action" value="invite_volunteer">
                                                                <input type="hidden" name="volunteer_id" value="<?= $vol['volunteer_id'] ?>">
                                                                <input type="hidden" name="opportunity_id" value="<?= $event['id'] ?>">
                                                                <button type="submit" class="btn btn-sm btn-primary">
                                                                    <i class="bi bi-envelope"></i> Invite
                                                                </button>
                                                            </form>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Top Matched Volunteers (for inviting) -->
                    <?php
                    $matched_volunteers = getMatchedVolunteers($conn, $event['id'], 10);
                    // Filter out those who already expressed interest or were invited
                    $matched_volunteers = array_filter($matched_volunteers, function($match) use ($interested_volunteers) {
                        foreach ($interested_volunteers as $iv) {
                            if ($iv['volunteer_id'] == $match['volunteer_id']) return false;
                        }
                        return true;
                    });
                    if (!empty($matched_volunteers)):
                    ?>
                        <div class="alert alert-light border mb-3">
                            <h6 class="fw-bold mb-2">
                                <i class="bi bi-search"></i> Suggested Volunteers to Invite
                            </h6>
                            <p class="small mb-2 text-muted">These volunteers match well with this opportunity. Consider inviting them!</p>
                            <div class="row g-2">
                                <?php foreach (array_slice($matched_volunteers, 0, 5) as $match): ?>
                                    <div class="col-md-6">
                                        <div class="card border-0 bg-light">
                                            <div class="card-body p-2">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <strong class="small"><?= htmlspecialchars($match['name']) ?></strong>
                                                        <div class="small">
                                                            <span class="badge bg-secondary">Level <?= $match['level'] ?></span>
                                                            <?php if ($match['category_experience'] > 0): ?>
                                                                <span class="badge bg-info"><?= $match['category_experience'] ?>h</span>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                        <form method="POST" action="interest_handler.php" class="d-inline">
                                                        <input type="hidden" name="action" value="invite_volunteer">
                                                        <input type="hidden" name="volunteer_id" value="<?= $match['volunteer_id'] ?>">
                                                        <input type="hidden" name="opportunity_id" value="<?= $event['id'] ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-primary">
                                                            <i class="bi bi-envelope"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
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