<?php
session_start();
require_once 'includes/auth_guard.php';
require_once 'config/config.php';

if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['role'], ['organization', 'admin'])) {
    header('Location: index.php');
    exit();
}

include_once 'includes/header.php';
?>

<style>
    :root { --accent: #6a8e3a; --accent2: #b27a4b; }
    .event-card {
        border: none;
        border-radius: 20px;
        padding: 28px;
        background: #fff;
        box-shadow: 0 4px 18px rgba(0,0,0,0.08);
        transition: all 0.3s ease;
        margin-bottom: 2rem;
    }
    .event-card:hover {
        transform: translateY(-6px);
        box-shadow: 0 16px 38px rgba(0,0,0,0.14);
    }
    .table-custom {
        font-size: 0.95rem;
    }
    .table-custom th {
        background: #f8f9fa;
        font-weight: 600;
        color: #444;
        border: none;
        padding: 16px;
    }
    .table-custom td {
        padding: 16px;
        vertical-align: middle;
        border-color: #eee;
    }
    .table-custom tr:hover {
        background: #f9fdf6 !important;
    }
    .badge {
        padding: 8px 14px;
        border-radius: 12px !important;
        font-weight: 600;
        font-size: 0.85rem;
    }
    .btn-sm {
        border-radius: 10px;
        font-weight: 600;
    }
    .hours-input {
        width: 90px;
        text-align: center;
        border-radius: 10px;
    }
</style>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-5">
        <h1 class="fw-bold display-6">Manage Events & Volunteers</h1>
        <a href="post_opportunity.php" class="btn btn-success btn-lg shadow-sm">
            Post New Event
        </a>
    </div>

    <?php
    $stmt = $conn->prepare("
        SELECT o.*, 
               (o.slots - COALESCE(COUNT(CASE WHEN a.status = 'confirmed' THEN 1 END), 0)) AS spots_left,
               COUNT(a.id) AS total_applications
        FROM opportunities o
        LEFT JOIN applications a ON o.id = a.opportunity_id AND a.status IN ('pending', 'confirmed')
        WHERE o.organization_id = ?
        GROUP BY o.id
        ORDER BY o.date DESC, o.created_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $events = $stmt->fetchAll();
    ?>

    <?php if (empty($events)): ?>
        <div class="text-center py-5">
            <div class="py-5">
                <h3 class="text-muted">No events posted yet</h3>
                <p class="lead text-muted mb-4">Start making an impact — create your first volunteer opportunity!</p>
                <a href="post_opportunity.php" class="btn btn-success btn-lg px-5">Post Your First Event</a>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($events as $event): ?>
            <div class="event-card">
                <div class="d-flex justify-content-between align-items-start mb-4">
                    <div>
                        <h3 class="fw-bold mb-2"><?= htmlspecialchars($event['title']) ?></h3>
                        <p class="text-muted mb-2">
                            <i class="bi bi-calendar-event"></i> 
                            <?= date('l, F j, Y', strtotime($event['date'])) ?>
                            <?= $event['time'] ? ' • ' . date('g:i A', strtotime($event['time'])) : '' ?>
                            <br>
                            <i class="bi bi-geo-alt"></i> <?= htmlspecialchars($event['location_name']) ?>
                        </p>
                    </div>
                    <div class="text-end">
                        <div class="badge bg-<?= $event['spots_left'] > 0 ? 'success' : 'danger' ?> fs-5 px-4 py-2">
                            <?= $event['spots_left'] ?> / <?= $event['slots'] ?> spots left
                        </div>
                        <div class="mt-2 text-muted small">
                            <?= $event['total_applications'] ?> application<?= $event['total_applications'] != 1 ? 's' : '' ?>
                        </div>
                    </div>
                </div>

                <hr class="my-4">

                <h5 class="fw-bold mb-4 text-success">Volunteers</h5>

                <?php
                $apps = $conn->prepare("
                    SELECT a.*, u.name, u.email, u.phone
                    FROM applications a
                    JOIN users u ON a.volunteer_id = u.id
                    WHERE a.opportunity_id = ?
                    ORDER BY 
                        a.status = 'pending' DESC,
                        a.applied_at DESC
                ");
                $apps->execute([$event['id']]);
                $volunteers = $apps->fetchAll();
                ?>

                <?php if (empty($volunteers)): ?>
                    <div class="text-center py-4 text-muted">
                        <p class="mb-0">No volunteers have applied yet.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-custom align-middle">
                            <thead>
                                <tr>
                                    <th>Volunteer</th>
                                    <th>Contact</th>
                                    <th>Applied</th>
                                    <th>Status</th>
                                    <th>Hours</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($volunteers as $v): ?>
                                    <tr id="row-<?= $v['id'] ?>">
                                        <td>
                                            <strong><?= htmlspecialchars($v['name']) ?></strong>
                                        </td>
                                        <td>
                                            <small>
                                                <?= htmlspecialchars($v['email']) ?><br>
                                                <?= $v['phone'] ? '<span class="text-muted">'.htmlspecialchars($v['phone']).'</span>' : '' ?>
                                            </small>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                <?= date('M j, Y', strtotime($v['applied_at'])) ?><br>
                                                <?= date('g:i A', strtotime($v['applied_at'])) ?>
                                            </small>
                                        </td>
                                        <td>
                                            <span class="badge 
                                                <?= $v['status'] == 'confirmed' ? 'bg-success' : 
                                                   ($v['status'] == 'cancelled' ? 'bg-secondary' : 
                                                   ($v['status'] == 'completed' ? 'bg-info' : 'bg-warning text-dark')) ?>"
                                                id="status-<?= $v['id'] ?>">
                                                <?= ucfirst($v['status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($v['status'] == 'completed'): ?>
                                                <strong class="text-success"><?= $v['hours_worked'] ?>h</strong>
                                            <?php else: ?>
                                                <input type="number" step="0.5" min="0" max="24" 
                                                       class="form-control form-control-sm hours-input"
                                                       id="hours-<?= $v['id'] ?>"
                                                       value="<?= $v['hours_worked'] ?? '' ?>"
                                                       <?= $v['status'] == 'pending' ? 'disabled' : '' ?>>
                                            <?php endif; ?>
                                        </td>
                                        <td id="actions-<?= $v['id'] ?>">
                                            <?php if ($v['status'] === 'pending'): ?>
                                                <button class="btn btn-sm btn-success me-2" onclick="updateApp(<?= $v['id'] ?>, 'confirm')">
                                                    Confirm
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger" onclick="updateApp(<?= $v['id'] ?>, 'cancel')">
                                                    Cancel
                                                </button>

                                            <?php elseif ($v['status'] === 'confirmed' && $v['hours_approved'] == 0): ?>
                                                <button class="btn btn-sm btn-primary" onclick="completeAndApprove(<?= $v['id'] ?>)">
                                                    Complete & Approve Hours
                                                </button>

                                            <?php elseif ($v['hours_approved'] == 1): ?>
                                                <span class="text-success fw-bold">Hours Approved</span>

                                            <?php else: ?>
                                                <span class="text-muted">—</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- AJAX Handler -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
function updateApp(appId, action) {
    $.post('ajax/org_update_application.php', {
        app_id: appId,
        action: action
    }, function(res) {
        if (res.success) {
            location.reload(); // simple & reliable
        } else {
            alert('Error: ' + (res.message || 'Try again'));
        }
    }, 'json');
}

function completeAndApprove(appId) {
    const hours = $(`#hours-${appId}`).val();
    if (!hours || hours <= 0) {
        alert('Please enter hours worked first!');
        return;
    }

    if (confirm(`Mark as completed and approve ${hours} hours?`)) {
        $.post('ajax/org_update_application.php', {
            app_id: appId,
            action: 'complete',
            hours: hours
        }, function(res) {
            if (res.success) {
                location.reload();
            } else {
                alert('Error: ' + res.message);
            }
        }, 'json');
    }
}
</script>

<?php include 'includes/footer.php'; ?>