<?php
ob_start();
session_start();
require_once 'config/config.php';
include_once 'includes/header.php';

$is_logged_in = !empty($_SESSION['logged_in']);
$user_id      = $_SESSION['user_id'] ?? null;
$user_role    = $_SESSION['role'] ?? null;

define('CAN_APPLY', $is_logged_in && in_array($user_role, ['user', 'volunteer']));
define('IS_BLOCKED_USER', $is_logged_in && in_array($user_role, ['admin', 'organization']));

// Fetch ALL real opportunities from DB
$stmt = $conn->prepare("
    SELECT o.*, u.name AS org_name,
           (o.slots - COALESCE(COUNT(CASE WHEN a.status = 'confirmed' THEN 1 END), 0)) AS spots_left
    FROM opportunities o
    LEFT JOIN users u ON o.organization_id = u.id
    LEFT JOIN applications a ON o.id = a.opportunity_id AND a.status = 'confirmed'
    WHERE o.date >= CURDATE()
    GROUP BY o.id
    ORDER BY o.date ASC, o.created_at DESC
");
$stmt->execute();
$opportunities = $stmt->fetchAll();

// Get which ones THIS user has already applied to
$applied_opps = [];
if ($is_logged_in && CAN_APPLY) {
    $ids = array_column($opportunities, 'id');
    if (!empty($ids)) {
        $placeholders = str_repeat('?,', count($ids) - 1) . '?';
        $check = $conn->prepare("SELECT opportunity_id FROM applications WHERE volunteer_id = ? AND opportunity_id IN ($placeholders)");
        $check->execute(array_merge([$user_id], $ids));
        $applied_opps = $check->fetchAll(PDO::FETCH_COLUMN);
    }
}
?>

<link href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" rel="stylesheet">
<style>
    :root{--accent:#6a8e3a;--accent2:#b27a4b}
    .opp-card{border-radius:16px;background:#fff;box-shadow:0 10px 30px rgba(0,0,0,.08);padding:1.5rem;transition:.3s}
    .opp-card:hover{transform:translateY(-8px);box-shadow:0 20px 40px rgba(0,0,0,.15)}
    .org-badge{background:var(--accent2);color:#fff;padding:.3rem .7rem;border-radius:8px;font-size:.85rem;font-weight:600}
    .btn-apply{background:var(--accent);color:#fff;border:none;border-radius:12px;padding:.8rem 1.2rem;font-weight:600}
    .btn-details{background:#fff;color:var(--accent);border:2px solid var(--accent);border-radius:12px;padding:.8rem 1.2rem;font-weight:600}
    .state{padding:1rem;border-radius:12px;text-align:center;font-weight:600}
    .pending{background:#d4edda;color:#155724;border:1px solid #c3e6cb}
    .full{background:#f8d7da;color:#721c24;border:1px solid #f5c6cb}
    #detailMap { height: 380px; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.15); }
</style>

<div class="container my-5">
    <h1 class="display-5 fw-bold text-center mb-3">Volunteer Opportunities in Prishtina</h1>
    <p class="lead text-muted text-center mb-5">Real events. Real impact. Join today.</p>

    <?php if (empty($opportunities)): ?>
        <div class="text-center py-5">
            <h4 class="text-muted">No opportunities available right now</h4>
            <p>Check back soon — new events are posted daily!</p>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($opportunities as $opp):
                $isFull = ($opp['spots_left'] <= 0);
                $alreadyApplied = in_array($opp['id'], $applied_opps);
                $lat = $opp['latitude'] ?? 42.6629;
                $lng = $opp['longitude'] ?? 21.1655;
            ?>
                <div class="col-md-6 col-lg-4">
                    <div class="opp-card h-100 d-flex flex-column">
                        <div class="d-flex gap-3 flex-grow-1">
                            <div style="width:80px;height:80px;background:linear-gradient(120deg,#f0f9f0,#e6f4ea);border-radius:16px;display:flex;align-items:center;justify-content:center">
                                <i class="bi bi-heart-pulse-fill text-success" style="font-size:2.3rem"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h5 class="fw-bold mb-1"><?=htmlspecialchars($opp['title'])?></h5>
                                <p class="small text-muted mb-2">by <span class="org-badge"><?=htmlspecialchars($opp['org_name'])?></span></p>
                                <p class="small text-muted mb-3"><?=htmlspecialchars(substr($opp['description'],0,100))?>...</p>
                                <ul class="list-unstyled small text-muted mb-2">
                                    <li><strong><?=htmlspecialchars($opp['location_name'])?></strong></li>
                                    <li><?=date('F j, Y', strtotime($opp['date']))?> <?= $opp['time'] ? 'at '.$opp['time'] : '' ?></li>
                                </ul>
                                <div class="progress mb-2" style="height:8px">
                                    <div class="progress-bar bg-success" style="width:<?=round((($opp['slots'] - $opp['spots_left']) / $opp['slots']) * 100)?>%"></div>
                                </div>
                                <small class="text-muted"><?=$opp['spots_left']?> of <?=$opp['slots']?> spots left</small>
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-top" id="apply-section-<?=$opp['id']?>">
                            <?php if (!$is_logged_in): ?>
                                <a href="auth/login.php" class="btn btn-outline-secondary w-100">Login to Apply</a>
                            <?php elseif (IS_BLOCKED_USER): ?>
                                <button class="btn btn-secondary w-100" disabled>Cannot Apply</button>
                            <?php elseif ($alreadyApplied): ?>
                                <div class="state pending">Application Sent!<br><small>We'll contact you soon</small></div>
                            <?php elseif ($isFull): ?>
                                <div class="state full">Event is Full</div>
                            <?php else: ?>
                                <div class="d-grid gap-2 d-md-flex">
                                    <button class="btn btn-details flex-fill view-details-btn"
                                            data-opp-title="<?=htmlspecialchars($opp['title'])?>"
                                            data-opp-desc="<?=htmlspecialchars($opp['description'])?>"
                                            data-opp-org="<?=htmlspecialchars($opp['org_name'])?>"
                                            data-location="<?=htmlspecialchars($opp['location_name'])?>"
                                            data-date="<?=$opp['date']?>"
                                            data-time="<?=$opp['time']?>"
                                            data-spots-left="<?=$opp['spots_left']?>"
                                            data-total-slots="<?=$opp['slots']?>"
                                            data-lat="<?=$lat?>"
                                            data-lng="<?=$lng?>"
                                            data-bs-toggle="modal" data-bs-target="#oppModal">
                                        View Details
                                    </button>
                                    <button class="btn btn-apply flex-fill apply-btn" data-opp-id="<?=$opp['id']?>">
                                        Sign Me Up!
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- FULLY WORKING MODAL WITH MAP -->
<div class="modal fade" id="oppModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content rounded-4 shadow-lg">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-success" id="modalTitle">Event Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modalBody">
                <div class="text-center py-5">
                    <div class="spinner-border text-success" style="width:3rem;height:3rem"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
let currentMap = null;

// Apply button
$('.apply-btn').on('click', function() {
    const btn = $(this);
    const oppId = btn.data('opp-id');
    const section = btn.closest('[id^="apply-section-"]');

    btn.prop('disabled', true).text('Sending...');

    $.post('includes/apply_handler.php', { opp_id: oppId }, function(res) {
        if (res.success) {
            section.html('<div class="state pending">Application Sent!<br><small>We\'ll contact you soon</small></div>');
        } else {
            alert(res.message || 'Error. Try again.');
            btn.prop('disabled', false).text('Sign Me Up!');
        }
    }, 'json');
});

// VIEW DETAILS + MAP — NOW 100% WORKING
$(document).on('click', '.view-details-btn', function () {
    const d = this.dataset;

    $('#modalTitle').text(d.oppTitle);
    $('#modalBody').html(`
        <div class="row g-4">
            <div class="col-md-5 text-center">
                <div style="height:220px;background:linear-gradient(120deg,#f7f4ef,#eef6ea);border-radius:20px;display:flex;align-items:center;justify-content:center">
                    <i class="bi bi-heart-pulse-fill text-success" style="font-size:5rem;opacity:0.8"></i>
                </div>
            </div>
            <div class="col-md-7">
                <p><strong>Organizer:</strong> ${d.oppOrg}</p>
                <p><strong>Date:</strong> ${new Date(d.date).toLocaleDateString('en-US', {weekday:'long', year:'numeric', month:'long', day:'numeric'})} ${d.time ? 'at ' + d.time : ''}</p>
                <p><strong>Location:</strong> <strong>${d.location}</strong></p>
                <p><strong>Spots Left:</strong> ${d.spotsLeft} of ${d.totalSlots}</p>
                <hr>
                <h6 class="fw-bold mb-3">Description</h6>
                <p class="lh-lg">${d.oppDesc.replace(/\n/g, '<br>')}</p>
            </div>
        </div>
        <div class="mt-5">
            <h6 class="fw-bold mb-3">Exact Location on Map</h6>
            <div id="detailMap"></div>
        </div>
    `);

    // Initialize map after modal content is loaded
    setTimeout(() => {
        if (currentMap) currentMap.remove();
        currentMap = L.map('detailMap').setView([d.lat, d.lng], 16);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(currentMap);
        L.marker([d.lat, d.lng], {
            icon: L.divIcon({
                html: '<i class="bi bi-geo-alt-fill text-danger" style="font-size:36px;"></i>',
                iconSize: [36, 36],
                iconAnchor: [18, 36]
            })
        }).addTo(currentMap)
          .bindPopup(`<strong>${d.oppTitle}</strong><br>${d.location}`)
          .openPopup();
        currentMap.invalidateSize();
    }, 150);
});
</script>

<?php include 'includes/footer.php'; ob_end_flush(); ?>