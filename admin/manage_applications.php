<?php
session_start();
require_once '../config/config.php';
require_once '../includes/auth_guard.php'; // Ensure admin/org only

// Only organization & admin can access
if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['role'], ['organization', 'admin'])) {
    header('Location: ../index.php');
    exit();
}

include_once '../includes/header.php';
?>

<style>
/* Card style consistent with index.php */
.app-card {
    border-radius: 18px;
    padding: 20px;
    background: #fff;
    box-shadow: 0 4px 14px rgba(0,0,0,0.08);
    margin-bottom: 16px;
}
.app-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 26px rgba(0,0,0,0.12);
}
.vol-info {
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.vol-info .vol-name {
    font-weight: 600;
}
.badge {
    padding: 5px 10px;
    border-radius: 12px;
    font-size: 0.85rem;
}
.action-btn {
    margin-right: 6px;
}
.hours-input {
    width: 80px;
}
</style>

<div class="container my-5">
    <h1 class="mb-4 fw-bold">Manage Applications</h1>

    <?php
    // Fetch all applications (with user and opportunity)
    $stmt = $conn->prepare("
        SELECT a.id AS app_id, a.status, a.hours_worked, a.hours_approved,
               u.name AS volunteer_name, u.email AS volunteer_email,
               o.title AS event_title
        FROM applications a
        JOIN users u ON a.volunteer_id = u.id
        JOIN opportunities o ON a.opportunity_id = o.id
        ORDER BY a.id DESC
    ");
    $stmt->execute();
    $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    ?>

    <?php if(empty($applications)): ?>
        <p class="text-muted">No applications found.</p>
    <?php else: ?>
        <?php foreach($applications as $app): ?>
            <div class="app-card" data-app-id="<?= $app['app_id'] ?>">
                <div class="vol-info mb-2">
                    <div>
                        <div class="vol-name"><?= htmlspecialchars($app['volunteer_name']) ?></div>
                        <small class="text-muted"><?= htmlspecialchars($app['volunteer_email']) ?></small>
                        <div class="text-muted small">Event: <?= htmlspecialchars($app['event_title']) ?></div>
                    </div>
                    <span class="badge bg-<?= $app['status']=='confirmed'?'success':($app['status']=='pending'?'warning text-dark':'danger') ?>">
                        <?= ucfirst($app['status']) ?>
                    </span>
                </div>

                <div class="d-flex align-items-center gap-2 mt-2">
                    <input type="number" class="form-control form-control-sm hours-input" min="0" max="100" step="0.25" value="<?= $app['hours_worked'] ?? 0 ?>" data-app-id="<?= $app['app_id'] ?>">
                    <?php if($app['hours_approved']): ?>
                        <span class="badge bg-success">Hours Approved</span>
                    <?php elseif($app['hours_worked']>0): ?>
                        <button class="btn btn-sm btn-success approve-hours-btn" data-app-id="<?= $app['app_id'] ?>">Approve Hours</button>
                    <?php else: ?>
                        <span class="text-muted small">No hours logged</span>
                    <?php endif; ?>
                </div>

                <?php if($app['status']=='pending'): ?>
                    <div class="mt-3">
                        <button class="btn btn-sm btn-success action-btn approve-btn" data-app-id="<?= $app['app_id'] ?>">Approve</button>
                        <button class="btn btn-sm btn-danger action-btn reject-btn" data-app-id="<?= $app['app_id'] ?>">Reject</button>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
// AJAX logic
document.addEventListener('DOMContentLoaded', function() {
    function sendAction(appId, action, callback) {
        fetch('manage_applications_handler.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ app_id: appId, action: action })
        })
        .then(res => res.json())
        .then(data => callback(data))
        .catch(err => console.error(err));
    }

    // Approve/Reject buttons
    document.querySelectorAll('.approve-btn').forEach(btn=>{
        btn.addEventListener('click', ()=> {
            const appId = btn.dataset.appId;
            sendAction(appId, 'approve', (res)=>{
                if(res.success){
                    const card = document.querySelector(`.app-card[data-app-id="${appId}"]`);
                    card.querySelector('.badge').className = 'badge bg-success';
                    card.querySelector('.badge').textContent = 'Confirmed';
                    btn.remove(); card.querySelector('.reject-btn')?.remove();
                } else alert(res.message);
            });
        });
    });

    document.querySelectorAll('.reject-btn').forEach(btn=>{
        btn.addEventListener('click', ()=> {
            const appId = btn.dataset.appId;
            sendAction(appId, 'reject', (res)=>{
                if(res.success){
                    const card = document.querySelector(`.app-card[data-app-id="${appId}"]`);
                    card.querySelector('.badge').className = 'badge bg-danger';
                    card.querySelector('.badge').textContent = 'Cancelled';
                    btn.remove(); card.querySelector('.approve-btn')?.remove();
                } else alert(res.message);
            });
        });
    });

    // Approve hours buttons
    document.querySelectorAll('.approve-hours-btn').forEach(btn=>{
        btn.addEventListener('click', ()=> {
            const appId = btn.dataset.appId;
            sendAction(appId, 'approve_hours', (res)=>{
                if(res.success){
                    btn.replaceWith(document.createElement('span')).textContent='Hours Approved';
                    const span = document.createElement('span');
                    span.className='badge bg-success';
                    span.textContent='Hours Approved';
                    btn.parentNode.appendChild(span);
                } else alert(res.message);
            });
        });
    });

    // Update hours on input blur
    document.querySelectorAll('.hours-input').forEach(input=>{
        input.addEventListener('change', ()=>{
            const appId = input.dataset.appId;
            const hours = parseFloat(input.value);
            sendAction(appId, 'update_hours', (res)=>{
                if(!res.success) alert(res.message);
            });
        });
    });
});
</script>

<?php include_once '../includes/footer.php'; ?>
