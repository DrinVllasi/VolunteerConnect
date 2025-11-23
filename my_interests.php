<?php
session_start();
require_once 'config/config.php';
include_once 'includes/header.php';
require_once 'includes/matching_engine.php';
require_once 'interest_handler.php';

// Only volunteers can access
if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['role'], ['user', 'volunteer'])) {
    header('Location: index.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Get all opportunities where volunteer expressed interest
$interests = getVolunteerInterests($conn, $user_id);

// Get all invites received
$invites_stmt = $conn->prepare("
    SELECT oi.*, o.title, o.description, o.location, o.date, o.time, u.name as org_name
    FROM organization_invites oi
    JOIN opportunities o ON oi.opportunity_id = o.id
    LEFT JOIN users u ON o.organization_id = u.id
    WHERE oi.volunteer_id = ? AND oi.status = 'pending' AND o.date >= CURDATE()
    ORDER BY oi.invited_at DESC
");
$invites_stmt->execute([$user_id]);
$invites = $invites_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
    :root{
        --accent-1: #6a8e3a;
        --accent-2: #b27a4b;
        --earth-1: #f2efe9;
    }
    
    .interest-card {
        background: white;
        border-radius: 14px;
        padding: 1.5rem;
        box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        margin-bottom: 1.5rem;
        transition: transform 0.2s ease;
    }
    
    .interest-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 6px 25px rgba(0,0,0,0.12);
    }
    
    .invite-badge {
        background: linear-gradient(135deg, var(--accent-1), var(--accent-2));
        color: white;
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-weight: 700;
        font-size: 0.85rem;
    }
</style>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="display-5 fw-bold">My Interests</h1>
        <a href="public_browse.php" class="btn btn-outline-primary">
            <i class="bi bi-search"></i> Browse Opportunities
        </a>
    </div>

    <?php if (isset($_SESSION['interest_message'])): ?>
        <div class="alert alert-<?= $_SESSION['interest_success'] ? 'success' : 'danger' ?>">
            <?= htmlspecialchars($_SESSION['interest_message']) ?>
        </div>
        <?php 
        unset($_SESSION['interest_message']);
        unset($_SESSION['interest_success']);
        ?>
    <?php endif; ?>

    <!-- Invites Section -->
    <?php if (!empty($invites)): ?>
        <div class="mb-5">
            <h3 class="fw-bold mb-3">
                <i class="bi bi-envelope-fill text-primary"></i> Invitations Received
            </h3>
            <p class="text-muted mb-4">Organizations have invited you to these opportunities!</p>
            <div class="row g-4">
                <?php foreach ($invites as $invite): ?>
                    <div class="col-md-6">
                        <div class="interest-card border border-primary">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="flex-grow-1">
                                    <h5 class="fw-bold mb-1"><?= htmlspecialchars($invite['title']) ?></h5>
                                    <p class="text-muted small mb-2">by <strong><?= htmlspecialchars($invite['org_name']) ?></strong></p>
                                </div>
                                <span class="invite-badge">
                                    <i class="bi bi-star-fill"></i> Invited!
                                </span>
                            </div>
                            <p class="small text-muted mb-3"><?= htmlspecialchars(substr($invite['description'], 0, 150)) ?><?= strlen($invite['description']) > 150 ? '...' : '' ?></p>
                            <div class="small text-muted mb-3">
                                <i class="bi bi-geo-alt-fill"></i> <?= htmlspecialchars($invite['location']) ?><br>
                                <i class="bi bi-calendar-event"></i> <?= date('F j, Y', strtotime($invite['date'])) ?>
                                <?php if ($invite['time']): ?>
                                    • <?= date('g:i A', strtotime($invite['time'])) ?>
                                <?php endif; ?>
                            </div>
                            <div class="d-flex gap-2">
                                <a href="events/event.php?id=<?= $invite['opportunity_id'] ?>" class="btn btn-primary flex-grow-1">
                                    View Details
                                </a>
                                <form method="POST" action="interest_handler.php" class="d-inline">
                                    <input type="hidden" name="action" value="accept_invite">
                                    <input type="hidden" name="invite_id" value="<?= $invite['id'] ?>">
                                    <button type="submit" class="btn btn-success">
                                        <i class="bi bi-check"></i> Accept
                                    </button>
                                </form>
                                <form method="POST" action="interest_handler.php" class="d-inline">
                                    <input type="hidden" name="action" value="decline_invite">
                                    <input type="hidden" name="invite_id" value="<?= $invite['id'] ?>">
                                    <button type="submit" class="btn btn-outline-danger">
                                        <i class="bi bi-x"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Interests Section -->
    <div>
        <h3 class="fw-bold mb-3">
            <i class="bi bi-heart-fill text-danger"></i> Opportunities I'm Interested In
        </h3>
        <?php if (empty($interests)): ?>
            <div class="text-center py-5">
                <div class="mb-4">
                    <i class="bi bi-heart" style="font-size: 4rem; color: #ccc;"></i>
                </div>
                <h4 class="text-muted">No interests yet</h4>
                <p class="text-muted">Start expressing interest in opportunities you like!</p>
                <a href="public_browse.php" class="btn btn-primary">
                    <i class="bi bi-search"></i> Browse Opportunities
                </a>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($interests as $interest): 
                    $has_invite = $interest['has_invite'] > 0;
                    $check_applied = $conn->prepare("SELECT status FROM applications WHERE opportunity_id = ? AND volunteer_id = ?");
                    $check_applied->execute([$interest['id'], $user_id]);
                    $applied = $check_applied->fetch();
                ?>
                    <div class="col-md-6">
                        <div class="interest-card <?= $has_invite ? 'border border-success' : '' ?>">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="flex-grow-1">
                                    <h5 class="fw-bold mb-1"><?= htmlspecialchars($interest['title']) ?></h5>
                                    <p class="text-muted small mb-2">by <strong><?= htmlspecialchars($interest['org_name']) ?></strong></p>
                                </div>
                                <?php if ($has_invite): ?>
                                    <span class="badge bg-success">
                                        <i class="bi bi-star-fill"></i> Invited!
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-info">
                                        <i class="bi bi-heart-fill"></i> Interested
                                    </span>
                                <?php endif; ?>
                            </div>
                            <p class="small text-muted mb-3"><?= htmlspecialchars(substr($interest['description'], 0, 150)) ?><?= strlen($interest['description']) > 150 ? '...' : '' ?></p>
                            <div class="small text-muted mb-3">
                                <i class="bi bi-geo-alt-fill"></i> <?= htmlspecialchars($interest['location']) ?><br>
                                <i class="bi bi-calendar-event"></i> <?= date('F j, Y', strtotime($interest['date'])) ?>
                                <?php if ($interest['time']): ?>
                                    • <?= date('g:i A', strtotime($interest['time'])) ?>
                                <?php endif; ?><br>
                                <i class="bi bi-clock"></i> Expressed interest on <?= date('M j, Y', strtotime($interest['interested_at'])) ?>
                            </div>
                            <div class="d-flex gap-2">
                                <a href="events/event.php?id=<?= $interest['id'] ?>" class="btn btn-primary flex-grow-1">
                                    View Details
                                </a>
                                <?php if (!$applied): ?>
                                    <form method="POST" action="interest_handler.php" class="d-inline">
                                        <input type="hidden" name="action" value="remove_interest">
                                        <input type="hidden" name="opportunity_id" value="<?= $interest['id'] ?>">
                                        <button type="submit" class="btn btn-outline-danger" title="Remove Interest">
                                            <i class="bi bi-heart-break"></i>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span class="badge bg-warning d-flex align-items-center">
                                        Applied
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

