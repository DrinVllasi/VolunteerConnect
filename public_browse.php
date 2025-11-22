<?php
session_start();
require_once 'config/config.php';
include_once 'includes/header.php';

$is_logged_in = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
$user_id      = $_SESSION['user_id'] ?? null;
$user_role    = $_SESSION['role'] ?? null;

// Block admins & organizations
define('IS_BLOCKED_USER', ($is_logged_in && in_array($user_role, ['admin', 'organization'])));
?>

<style>
    :root{
        --earth-1: #f2efe9;
        --earth-2: #e8ded1;
        --accent-1: #6a8e3a; /* olive green */
        --accent-2: #b27a4b; /* warm clay */
        --muted: #6b6b6b;
        --card-radius: 18px;
    }
    body { 
        font-family: 'Manrope', 'Inter', sans-serif; 
        background: var(--earth-1); 
        color: #2b2b2b; 
        overflow-x: hidden; 
    }

    .page-title {
        font-size: 2.4rem;
        font-weight: 800;
        margin-bottom: 0.4rem;
        color: #2b2b2b;
    }

    .page-subtitle {
        color: var(--muted);
        font-size: 1.1rem;
    }

    /* --- Card Styling (matching index.php) --- */
    .opp-card {
        border-radius: 14px;
        overflow: hidden;
        background: linear-gradient(180deg,#fff,#fcfbf8);
        box-shadow: 0 12px 30px rgba(30,30,30,0.06);
        padding: 1.5rem;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .opp-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 16px 40px rgba(30,30,30,0.1);
    }

    .card-title {
        font-size: 1.25rem;
        font-weight: 700;
        margin-bottom: 0.2rem;
        color: #2b2b2b;
    }

    .org-name {
        color: var(--muted);
        font-size: 0.9rem;
        margin-bottom: 1rem;
    }

    .org-badge { 
        background: var(--accent-2); 
        color: white; 
        padding: .25rem .6rem; 
        border-radius: 8px; 
        font-weight: 700; 
        font-size: .85rem;
        white-space: nowrap;
        display: inline-block;
    }

    .card-text {
        color: #4b5563;
        font-size: 0.95rem;
    }

    /* --- Badges --- */
    .badge-spots {
        background: #e0f9ea;
        color: var(--accent-1);
        padding: 4px 10px;
        border-radius: 12px;
        font-weight: 600;
        font-size: 0.8rem;
    }
    .badge-full {
        background: #fde2e1;
        color: #ef4444;
        padding: 4px 10px;
        border-radius: 12px;
        font-weight: 600;
        font-size: 0.8rem;
    }

    /* --- Details List --- */
    .details li {
        margin-bottom: 6px;
        color: var(--muted);
        font-size: 0.92rem;
    }

    /* --- Buttons --- */
    .btn-main {
        background: var(--accent-1);
        font-weight: 600;
        border-radius: 10px;
        padding: .75rem 1.8rem;
        color: white;
        border: none;
        font-size: 0.95rem;
        letter-spacing: 0.2px;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 0 2px 8px rgba(106, 142, 58, 0.25);
    }
    .btn-main:hover {
        background: #5a7d32;
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(106, 142, 58, 0.35);
    }
    .btn-main:active {
        transform: translateY(0);
        box-shadow: 0 2px 6px rgba(106, 142, 58, 0.3);
    }

    .btn-disabled {
        background: #e5e7eb;
        border-radius: 10px;
        padding: .75rem 1.8rem;
        cursor: not-allowed;
        color: #9ca3af;
        font-weight: 500;
        font-size: 0.95rem;
        border: 1px solid #d1d5db;
    }

    .btn-success-custom {
        background: var(--accent-1);
        border-radius: 10px;
        padding: .75rem 1.8rem;
        font-weight: 600;
        color: white;
        border: none;
        font-size: 0.95rem;
        box-shadow: 0 2px 8px rgba(106, 142, 58, 0.2);
    }

    .btn-warning-custom {
        background: #d97706;
        border-radius: 10px;
        padding: .75rem 1.8rem;
        font-weight: 600;
        color: white;
        border: none;
        font-size: 0.95rem;
        box-shadow: 0 2px 8px rgba(217, 119, 6, 0.2);
    }

    .btn-danger-custom {
        background: #dc2626;
        border-radius: 10px;
        padding: .75rem 1.8rem;
        font-weight: 600;
        color: white;
        border: none;
        font-size: 0.95rem;
        box-shadow: 0 2px 8px rgba(220, 38, 38, 0.2);
    }

    .progress-spot { 
        height: 10px; 
        border-radius: 10px; 
    }
</style>

<body>

<div class="container my-5">

    <div class="text-center mb-5 py-4">
        <h1 class="page-title">Volunteer Opportunities</h1>
        <p class="page-subtitle">Find something meaningful to do in your community</p>
    </div>

    <?php
    $stmt = $conn->prepare("
        SELECT o.*, u.name AS org_name,
               (o.slots - IFNULL(COUNT(a.id),0)) AS spots_left
        FROM opportunities o
        LEFT JOIN users u ON o.organization_id = u.id
        LEFT JOIN applications a ON o.id = a.opportunity_id AND a.status = 'confirmed'
        WHERE o.date >= CURDATE()
        GROUP BY o.id ORDER BY o.date ASC
    ");
    $stmt->execute();
    $opportunities = $stmt->fetchAll();
    ?>

    <?php if (empty($opportunities)): ?>
        <div class="text-center py-5">
            <p class="lead text-muted">No upcoming opportunities yet — check back soon!</p>
        </div>

    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($opportunities as $opp): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="opp-card h-100 d-flex flex-column">

                        <div class="d-flex align-items-start gap-3">
                            <div style="width:78px; height:78px; border-radius:12px; background: linear-gradient(120deg, #f7f4ef, #eef6ea); display:flex; align-items:center; justify-content:center; flex-shrink: 0;">
                                <svg width="34" height="34" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 2L12 22" stroke="#6A8E3A" stroke-width="1.6" stroke-linecap="round"/><path d="M2 12L22 12" stroke="#B27A4B" stroke-width="1.6" stroke-linecap="round"/></svg>
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div style="min-width: 0; flex: 1;">
                                        <h5 class="card-title mb-1"><?= htmlspecialchars($opp['title']) ?></h5>
                                        <div class="small text-muted" style="white-space: nowrap;">by <span class="org-badge"><?= htmlspecialchars($opp['org_name'] ?? 'Unknown') ?></span></div>
                                    </div>
                                    <div class="text-end small text-muted"><?= date('M j, Y', strtotime($opp['date'])) ?></div>
                                </div>

                                <p class="card-text small text-muted mb-3"><?= htmlspecialchars(substr($opp['description'], 0, 150)) ?><?= strlen($opp['description']) > 150 ? '...' : '' ?></p>

                                <ul class="list-unstyled mb-3 details">
                                    <li><i class="bi bi-geo-alt-fill"></i> <?= htmlspecialchars($opp['location']) ?></li>
                                    <li>
                                        <i class="bi bi-calendar-event"></i>
                                        <?= date('F j, Y', strtotime($opp['date'])) ?>
                                        <?= $opp['time'] ? ' • '.date('g:i A', strtotime($opp['time'])) : '' ?>
                                    </li>
                                </ul>

                                <?php
                                $spots_left = max(0, (int)$opp['spots_left']);
                                $slots = (int)$opp['slots'];
                                $filled = max(0, $slots - $spots_left);
                                $percent = $slots > 0 ? round(($filled / $slots) * 100) : 0;
                                ?>
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div style="width:60%;">
                                        <div class="progress progress-spot bg-light" style="height:10px;">
                                            <div class="progress-bar" role="progressbar" style="width: <?= $percent ?>%; background-color: var(--accent-1);" aria-valuenow="<?= $percent ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                        <div class="small text-muted mt-1">
                                            <?php if ($spots_left > 0): ?>
                                                <?= $spots_left ?>/<?= $slots ?> spots left
                                            <?php else: ?>
                                                <span class="badge-full">Full</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-auto pt-3 border-top">

                            <?php if (!$is_logged_in): ?>

                                <a href="auth/login.php" class="btn btn-main w-100">Login to Sign Up</a>

                            <?php elseif (IS_BLOCKED_USER): ?>

                                <button class="btn-disabled w-100" disabled>Admins & Organizations Cannot Apply</button>

                            <?php else: ?>

                                <?php
                                $check = $conn->prepare("SELECT status FROM applications WHERE opportunity_id = ? AND volunteer_id = ?");
                                $check->execute([$opp['id'], $user_id]);
                                $applied = $check->fetch();
                                ?>

                                <?php if ($applied && $applied['status'] === 'confirmed'): ?>
                                    <button class="btn-success-custom w-100" disabled>Confirmed</button>

                                <?php elseif ($applied): ?>
                                    <button class="btn-warning-custom w-100" disabled>Pending Approval</button>

                                <?php elseif ($opp['spots_left'] <= 0): ?>
                                    <button class="btn-danger-custom w-100" disabled>Event Full</button>

                                <?php else: ?>
                                    <form method="POST" action="includes/apply_handler.php" class="apply-form" data-opp-id="<?= $opp['id'] ?>">
                                        <input type="hidden" name="opp_id" value="<?= $opp['id'] ?>">
                                        <button type="submit" class="btn btn-main w-100 apply-btn">Sign Me Up!</button>
                                    </form>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>

                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

<script>
    // Handle AJAX form submissions for applying to opportunities
    document.addEventListener('DOMContentLoaded', function() {
        const applyForms = document.querySelectorAll('.apply-form');
        
        applyForms.forEach(function(form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(form);
                const button = form.querySelector('.apply-btn');
                const originalText = button.textContent;
                
                // Disable button and show loading state
                button.disabled = true;
                button.textContent = 'Applying...';
                
                // Send AJAX request
                const xhr = new XMLHttpRequest();
                xhr.open('POST', form.action, true);
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                
                xhr.onload = function() {
                    if (xhr.status === 200) {
                        try {
                            const response = JSON.parse(xhr.responseText);
                            
                            if (response.success) {
                                // Update button to show applied state
                                button.textContent = 'You applied! Waiting for response...';
                                button.classList.remove('btn-main');
                                button.classList.add('btn-warning-custom');
                                button.disabled = true;
                                
                                // Remove form functionality
                                form.onsubmit = function(e) { e.preventDefault(); return false; };
                            } else {
                                // Show error but keep button enabled
                                alert(response.message);
                                button.disabled = false;
                                button.textContent = originalText;
                            }
                        } catch (e) {
                            console.error('Error parsing response:', e);
                            alert('An error occurred. Please try again.');
                            button.disabled = false;
                            button.textContent = originalText;
                        }
                    } else {
                        alert('An error occurred. Please try again.');
                        button.disabled = false;
                        button.textContent = originalText;
                    }
                };
                
                xhr.onerror = function() {
                    alert('Network error. Please try again.');
                    button.disabled = false;
                    button.textContent = originalText;
                };
                
                xhr.send(formData);
            });
        });
    });
</script>

<?php include 'includes/footer.php'; ?>
