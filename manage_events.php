<?php
session_start();
require_once 'includes/auth_guard.php';
require_once 'config/config.php';

// Only organization & admin can access
if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['role'], ['organization', 'admin'])) {
    header('Location: index.php');
    exit();
}

include_once 'includes/header.php';
?>

<style>
/* INDEX STYLE CARD */
.event-card {
    border: none;
    border-radius: 20px;
    padding: 25px;
    background: #fff;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    transition: 0.25s ease;
}
.event-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 28px rgba(0,0,0,0.12);
}

/* TABLE STYLE */
.table-custom th {
    background: #f7f7f7;
    font-weight: 600;
}

.table-custom td, .table-custom th {
    padding: 14px 16px;
    vertical-align: middle;
}

.table-custom tr:hover {
    background: #fafafa;
}

/* Buttons & badges */
.badge {
    padding: 8px 12px;
    border-radius: 12px !important;
}
.btn-sm {
    padding: 5px 10px;
    border-radius: 10px;
}
</style>

<div class="container my-5">
    <h1 class="fw-bold mb-4">Manage Events & Volunteers</h1>

    <?php
    $stmt = $conn->prepare("
        SELECT o.*, 
        (o.slots - COUNT(CASE WHEN a.status='confirmed' THEN 1 END)) AS spots_left
        FROM opportunities o
        LEFT JOIN applications a ON o.id = a.opportunity_id
        WHERE o.organization_id = ?
        GROUP BY o.id
        ORDER BY o.date DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $events = $stmt->fetchAll();
    ?>

    <?php if (empty($events)): ?>
        <div class="text-center py-5">
            <p class="text-muted">You haven't posted any events yet.</p>
            <a href="post_opportunity.php" class="btn btn-primary btn-lg">Post Your First Event</a>
        </div>
    <?php else: ?>
        <?php foreach ($events as $event): ?>
            <div class="event-card mb-5">

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="fw-bold mb-0"><?= htmlspecialchars($event['title']) ?></h3>
                    <span class="badge bg-<?= $event['spots_left'] > 0 ? 'success' : 'danger' ?>">
                        <?= $event['spots_left'] ?>/<?= $event['slots'] ?> spots left
                    </span>
                </div>

                <p class="text-muted mb-1">
                    <strong>Date:</strong> 
                    <?= date('l, F j, Y', strtotime($event['date'])) ?>
                    <?= $event['time'] ? ' • '.date('g:i A', strtotime($event['time'])) : '' ?>
                </p>
                <p class="text-muted mb-4">
                    <strong>Location:</strong> <?= htmlspecialchars($event['location']) ?>
                </p>

                <hr>

                <h5 class="fw-bold mb-3">Volunteers</h5>

                <?php
                $apps = $conn->prepare("
                    SELECT a.*, u.name, u.email
                    FROM applications a
                    JOIN users u ON a.volunteer_id = u.id
                    WHERE a.opportunity_id = ?
                    ORDER BY a.applied_at DESC
                ");
                $apps->execute([$event['id']]);
                $vols = $apps->fetchAll();
                ?>

                <?php if (empty($vols)): ?>
                    <p class="text-muted">No volunteers yet.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-custom align-middle">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Status</th>
                                    <th>Hours</th>
                                    <th>Approve Hours</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($vols as $v): ?>
                                    <tr id="vol-<?= $v['id'] ?>">

                                        <td><strong><?= htmlspecialchars($v['name']) ?></strong></td>
                                        <td><?= htmlspecialchars($v['email']) ?></td>
                                        <td>
                                            <span class="badge 
                                                <?= $v['status']=='confirmed' ? 'bg-success' :
                                                   ($v['status']=='cancelled' ? 'bg-secondary' : 'bg-warning text-dark') ?>"
                                                id="status-<?= $v['id'] ?>">
                                                <?= ucfirst($v['status']) ?>
                                            </span>
                                        </td>

                                        <td>
                                            <input type="number" id="hours-<?= $v['id'] ?>" value="<?= $v['hours_worked'] ?>"
                                                   class="form-control form-control-sm" style="width:80px; display:inline-block">
                                            <button class="btn btn-sm btn-outline-primary" onclick="saveHours(<?= $v['id'] ?>)">Save</button>
                                        </td>

                                        <td>
                                            <span id="hours-status-<?= $v['id'] ?>">
                                            <?php if ($v['hours_approved']): ?>
                                                <span class="badge bg-success">Approved</span>
                                            <?php elseif ($v['hours_worked'] > 0): ?>
                                                <button class="btn btn-sm btn-success" onclick="approveHours(<?= $v['id'] ?>)">Approve</button>
                                            <?php else: ?>
                                                <span class="text-muted small">—</span>
                                            <?php endif; ?>
                                            </span>
                                        </td>

                                        <td>
                                            <?php if ($v['status'] === 'pending'): ?>
                                                <button class="btn btn-sm btn-success me-1" onclick="updateStatus(<?= $v['id'] ?>,'approve')">Approve</button>
                                                <button class="btn btn-sm btn-danger" onclick="updateStatus(<?= $v['id'] ?>,'reject')">Reject</button>
                                            <?php else: ?>
                                                <span class="text-muted small">—</span>
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

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
function updateStatus(appId, action){
    $.post('ajax_manage.php', {app_id: appId, action: action}, function(response){
        let statusText = action === 'approve' ? 'Confirmed' : 'Cancelled';
        let badgeClass = action === 'approve' ? 'bg-success' : 'bg-secondary';
        $('#status-'+appId).text(statusText).removeClass('bg-warning text-dark bg-success bg-secondary').addClass(badgeClass);
        // remove buttons
        $('#vol-'+appId+' td:last-child').html('<span class="text-muted small">—</span>');
    });
}

function saveHours(appId){
    let hours = $('#hours-'+appId).val();
    $.post('ajax_manage.php', {app_id: appId, hours: hours, save_hours: 1}, function(response){
        if(response.success){
            alert('Hours saved!');
        } else {
            alert(response.message || 'Failed to save hours.');
        }
    }, 'json');
}

function approveHours(appId){
    $.post('ajax_manage.php', {app_id: appId, approve_hours: 1}, function(response){
        if(response.success){
            $('#hours-status-'+appId).html('<span class="badge bg-success">Approved</span>');

            // Update the total hours counter dynamically
            let current = parseInt($('#hoursCounter').text().replace(/,/g,'')) || 0;
            $('#hoursCounter').text(current + response.hours_approved);
        } else {
            alert(response.message || 'Failed to approve hours.');
        }
    }, 'json');
}
</script>
</body>
</html>
