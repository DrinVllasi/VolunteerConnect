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

// ==================== AJAX: APPLY ====================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $opp_id = (int)($input['opp_id'] ?? 0);

    ob_clean();
    header('Content-Type: application/json');

    if ($opp_id <= 0 || !$is_logged_in || !CAN_APPLY || !$user_id) {
        echo json_encode(['success' => false]); exit;
    }

    $check = $conn->prepare("SELECT 1 FROM applications WHERE opportunity_id = ? AND volunteer_id = ?");
    $check->execute([$opp_id, $user_id]);
    if ($check->fetchColumn()) {
        echo json_encode(['success' => false]); exit;
    }

    $stmt = $conn->prepare("INSERT INTO applications (opportunity_id, volunteer_id, status, applied_at) VALUES (?, ?, 'pending', NOW())");
    $stmt->execute([$opp_id, $user_id]);
    echo json_encode(['success' => true]);
    exit;
}

// ==================== FETCH OPPORTUNITIES ====================
$stmt = $conn->prepare("
    SELECT o.*, u.name AS org_name,
           (o.slots - COALESCE(c.confirmed,0)) AS spots_left
    FROM opportunities o
    LEFT JOIN users u ON o.organization_id = u.id
    LEFT JOIN (
        SELECT opportunity_id, COUNT(*) AS confirmed 
        FROM applications WHERE status = 'confirmed' 
        GROUP BY opportunity_id
    ) c ON o.id = c.opportunity_id
    WHERE o.date >= CURDATE()
    ORDER BY o.date ASC
");
$stmt->execute();
$opportunities = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Smart location fallback
foreach ($opportunities as &$opp) {
    if (!empty($opp['latitude']) && !empty($opp['longitude'])) {
        $opp['final_lat'] = $opp['latitude'];
        $opp['final_lng'] = $opp['longitude'];
        $opp['final_location'] = $opp['location_name'] ?? $opp['location'] ?? 'Prishtina';
    } else {
        $text = strtolower($opp['title'] . ' ' . ($opp['description'] ?? ''));
        $map = [
            'gërmi|park|tree|clean' => ['Parku i Gërmisë', 42.659167, 21.156944],
            'badovc|lake'           => ['Liqeni i Badovcit', 42.627500, 21.246400],
            'blood|donation'        => ['Sheshi Nëna Terezë', 42.662778, 21.165556],
            'iftar|mosque'          => ['Xhamia e Madhe', 42.659444, 21.162778],
            'university|workshop'   => ['Universiteti i Prishtinës', 42.648611, 21.167222],
            'newborn|paint'         => ['Monumenti NEWBORN', 42.664167, 21.162222],
        ];
        $opp['final_location'] = 'Sheshi Nëna Terezë';
        $opp['final_lat'] = 42.662778;
        $opp['final_lng'] = 21.165556;
        foreach ($map as $keys => $data) {
            if (preg_match("/$keys/i", $text)) {
                $opp['final_location'] = $data[0];
                $opp['final_lat'] = $data[1];
                $opp['final_lng'] = $data[2];
                break;
            }
        }
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
</style>

<div class="container my-5">
    <h1 class="display-5 fw-bold text-center mb-3">Volunteer Opportunities</h1>
    <p class="lead text-muted text-center mb-5">Join real events in Prishtina and make a difference</p>

    <div class="row g-4">
        <?php foreach ($opportunities as $opp):
            $id = $opp['id'];
            $spotsLeft = max(0, (int)$opp['spots_left']);
            $isFull = $spotsLeft === 0;

            $status = null;
            if ($is_logged_in && $user_id && CAN_APPLY) {
                $q = $conn->prepare("SELECT status FROM applications WHERE opportunity_id = ? AND volunteer_id = ?");
                $q->execute([$id, $user_id]);
                $status = $q->fetchColumn();
            }
        ?>
            <div class="col-md-6 col-lg-4">
                <div class="opp-card h-100 d-flex flex-column">
                    <div class="d-flex gap-3 flex-grow-1">
                        <div style="width:80px;height:80px;background:linear-gradient(120deg,#f0f9f0,#e6f4ea);border-radius:16px;display:flex;align-items:center;justify-content:center">
                            <i class="bi bi-heart-pulse-fill text-success" style="font-size:2.3rem"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h5 class="fw-bold mb-1"><?=htmlspecialchars($opp['title'])?></h5>
                            <p class="small text-muted mb-2">by <span class="org-badge"><?=htmlspecialchars($opp['org_name']??'Community')?></span></p>
                            <p class="small text-muted mb-3"><?=htmlspecialchars(substr($opp['description'],0,100))?>...</p>
                            <ul class="list-unstyled small text-muted mb-2">
                                <li><strong><?=htmlspecialchars($opp['final_location'])?></strong></li>
                                <li><?=date('F j, Y', strtotime($opp['date']))?></li>
                            </ul>
                            <div class="progress mb-2" style="height:8px">
                                <div class="progress-bar bg-success" style="width:<?=($opp['slots']>0)?round((($opp['slots']-$spotsLeft)/$opp['slots'])*100):100?>%"></div>
                            </div>
                            <small class="text-muted"><?=$spotsLeft?> of <?=$opp['slots']?> spots left</small>
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-top">
                        <?php if (!$is_logged_in): ?>
                            <a href="auth/login.php" class="btn btn-outline-secondary w-100">Login to Apply</a>
                        <?php elseif (IS_BLOCKED_USER): ?>
                            <button class="btn btn-secondary w-100" disabled>Cannot Apply</button>
                        <?php elseif ($status === 'pending'): ?>
                            <div class="state pending">Application Pending</div>
                        <?php elseif ($status === 'confirmed'): ?>
                            <div class="state pending text-success">You're Confirmed!</div>
                        <?php elseif ($isFull): ?>
                            <div class="state full">Event is Full</div>
                        <?php else: ?>
                            <div class="d-grid gap-2 d-md-flex">
                                <button class="btn btn-details flex-fill view-details-btn"
                                        data-opp-id="<?= $id ?>"
                                        data-lat="<?= $opp['final_lat'] ?>"
                                        data-lng="<?= $opp['final_lng'] ?>"
                                        data-location="<?= htmlspecialchars($opp['final_location'], ENT_QUOTES) ?>"
                                        data-bs-toggle="modal" data-bs-target="#oppModal">
                                    View Details
                                </button>
                                <button class="btn btn-apply flex-fill apply-btn" data-opp-id="<?= $id ?>">
                                    Sign Me Up!
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- MODAL -->
<div class="modal fade" id="oppModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content rounded-4 shadow-lg">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold" id="modalTitle">Loading...</h5>
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
<script>
let currentMap = null;

// APPLY BUTTON
document.querySelectorAll('.apply-btn').forEach(btn => {
    btn.addEventListener('click', async () => {
        if (btn.disabled) return;
        const oppId = btn.dataset.oppId;
        const box = btn.closest('.border-top');
        btn.disabled = true;
        btn.textContent = 'Sending...';

        try {
            const res = await fetch('public_browse.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ opp_id: oppId })
            });
            const data = await res.json();
            if (data.success) {
                box.innerHTML = `<div class="state pending">Application Sent!<br><small>We'll contact you soon</small></div>`;
            } else {
                btn.disabled = false;
                btn.textContent = 'Sign Me Up!';
                alert('Already applied');
            }
        } catch (e) {
            btn.disabled = false;
            btn.textContent = 'Sign Me Up!';
            alert('Connection failed');
        }
    });
});

// VIEW DETAILS + MAP — 100% FIXED (NO GREY TILES)
document.querySelectorAll('.view-details-btn').forEach(btn => {
    btn.addEventListener('click', function () {
        const id = this.dataset.oppId;
        const lat = parseFloat(this.dataset.lat);
        const lng = parseFloat(this.dataset.lng);
        const loc = this.dataset.location;

        document.getElementById('modalTitle').textContent = 'Loading...';
        document.getElementById('modalBody').innerHTML = '<div class="text-center py-5"><div class="spinner-border text-success" style="width:3rem;height:3rem"></div></div>';
        if (currentMap) { currentMap.remove(); currentMap = null; }

        fetch(`get_opportunity.php?id=${id}`)
            .then(r => r.json())
            .then(o => {
                document.getElementById('modalTitle').textContent = o.title;
                document.getElementById('modalBody').innerHTML = `
                    <div class="row g-4">
                        <div class="col-md-5 text-center">
                            <div style="height:220px;background:linear-gradient(120deg,#f7f4ef,#eef6ea);border-radius:20px;display:flex;align-items:center;justify-content:center">
                                <i class="bi bi-heart-pulse-fill text-success" style="font-size:5rem;opacity:0.8"></i>
                            </div>
                        </div>
                        <div class="col-md-7">
                            <p><strong>Organizer:</strong> ${o.org_name || 'Community'}</p>
                            <p><strong>Date:</strong> ${new Date(o.date).toLocaleDateString('en-US', {weekday:'long', year:'numeric', month:'long', day:'numeric'})}</p>
                            <p><strong>Location:</strong> <strong>${loc}</strong></p>
                            <p><strong>Spots Left:</strong> ${o.spots_left} of ${o.slots}</p>
                            <hr>
                            <h6 class="fw-bold">Description</h6>
                            <p class="lh-lg">${o.description.replace(/\n/g, '<br>')}</p>
                        </div>
                    </div>
                    <div class="mt-5">
                        <h6 class="fw-bold mb-3">Exact Location</h6>
                        <div id="detailMap" style="height:400px;border-radius:16px;overflow:hidden;box-shadow:0 10px 30px rgba(0,0,0,0.15);"></div>
                    </div>
                `;

                const modal = document.getElementById('oppModal');
                const initMap = () => {
                    setTimeout(() => {
                        if (currentMap) currentMap.remove();
                        currentMap = L.map('detailMap').setView([lat, lng], 17);
                        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                            attribution: '&copy; OpenStreetMap contributors'
                        }).addTo(currentMap);

                        L.marker([lat, lng], {
                            icon: L.divIcon({
                                html: '<i class="bi bi-geo-alt-fill text-danger" style="font-size:36px;"></i>',
                                iconSize: [36, 36],
                                iconAnchor: [18, 36]
                            })
                        }).addTo(currentMap)
                          .bindPopup(`<strong>${o.title}</strong><br>${loc}`)
                          .openPopup();

                        currentMap.invalidateSize(); // THIS FIXES GREY TILES FOREVER
                    }, 150);
                };

                if (modal.classList.contains('show')) {
                    initMap();
                } else {
                    modal.addEventListener('shown.bs.modal', function handler() {
                        initMap();
                        modal.removeEventListener('shown.bs.modal', handler);
                    });
                }
            })
            .catch(() => {
                document.getElementById('modalBody').innerHTML = '<p class="text-danger text-center py-5">Failed to load details.</p>';
            });
    });
});
</script>

<?php include 'includes/footer.php'; ob_end_flush(); ?> 