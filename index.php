<?php
session_start();
require_once 'config/config.php';
include_once 'includes/header.php';

// Real-time stats
$total_volunteers = $conn->query("SELECT COUNT(*) FROM users WHERE role IN ('user','volunteer')")->fetchColumn();

// FIXED LINE – REAL verified hours from applications (approved + completed)
$total_hours_result = $conn->query("
    SELECT COALESCE(SUM(a.hours_worked), 0) 
    FROM applications a 
    WHERE a.status = 'completed' AND a.hours_approved = 1
");
$total_hours = (float)$total_hours_result->fetchColumn();   // ← this is the only change (was fake total_hours column)

$total_events = $conn->query("SELECT COUNT(*) FROM opportunities")->fetchColumn();

// Upcoming opportunities
$stmt = $conn->prepare("
    SELECT o.*, u.name AS org_name,
           (o.slots - COUNT(CASE WHEN a.status = 'confirmed' THEN 1 END)) AS spots_left
    FROM opportunities o
    LEFT JOIN users u ON o.organization_id = u.id
    LEFT JOIN applications a ON o.id = a.opportunity_id AND a.status = 'confirmed'
    WHERE o.date >= CURDATE()
    GROUP BY o.id
    ORDER BY o.date ASC
    LIMIT 4
");
$stmt->execute();
$opportunities = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- Additional CSS for Leaflet and AOS -->
<link href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css" rel="stylesheet">

<style>
        :root{
            --earth-1: #f2efe9;
            --earth-2: #e8ded1;
            --accent-1: #6a8e3a; /* olive green */
            --accent-2: #b27a4b; /* warm clay */
            --muted: #6b6b6b;
            --card-radius: 18px;
        }
        body { font-family: 'Manrope', 'Inter', sans-serif; background: var(--earth-1); color: #2b2b2b; overflow-x: hidden; }
        .hero {
            background-image: linear-gradient(rgba(19,30,14,0.35), rgba(19,30,14,0.35)), url('img/volunteers.jpg');
            background-size: cover; background-position: center;
            min-height: 72vh; display:flex; align-items:center; border-bottom-left-radius: 36px; border-bottom-right-radius: 36px;
            color: white; padding: 5rem 0;
        }
        .hero .glass {
            backdrop-filter: blur(6px);
            background: linear-gradient(180deg, rgba(255,255,255,0.03), rgba(255,255,255,0.02));
            border-radius: 14px;
            padding: 2rem;
            box-shadow: 0 8px 30px rgba(0,0,0,0.25);
        }
        .tag {
            display:inline-block; padding:.35rem .8rem; border-radius:999px; background: rgba(255,255,255,0.08);
            font-weight:600; font-size:.9rem; margin-right:.5rem;
        }
        .feature-card { border-radius: var(--card-radius); padding: 2rem; background: white; box-shadow: 0 8px 30px rgba(15,15,15,0.05); }
        .opportunity-card { border-radius: 14px; overflow:hidden; background: linear-gradient(180deg,#fff,#fcfbf8); box-shadow: 0 12px 30px rgba(30,30,30,0.06); }
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
        .progress-spot { height:10px; border-radius:10px; }
        .stats { padding: 4rem 0; background: linear-gradient(180deg,#fff,#f7f6f3); border-top: 1px solid rgba(0,0,0,0.03); }
        .stat-number { font-size: 2.8rem; font-weight:800; color: var(--accent-1); }
        .cta { background: linear-gradient(90deg,var(--accent-1), #4b7a2e); color: white; padding: 3.5rem 0; border-radius: 14px; }
        .map-card { 
            height: 400px !important; 
            width: 100% !important; 
            border-radius: 15px; 
            overflow: hidden;
            background: #e5e7eb;
            position: relative;
            min-height: 400px;
        }
        #map {
            height: 100% !important;
            width: 100% !important;
            z-index: 1;
            min-height: 400px;
        }
        /* Ensure Leaflet tiles display correctly */
        .leaflet-container {
            height: 100%;
            width: 100%;
        }
        .story-card { background: linear Heal-gradient(180deg,#fff,#fbfaf7); border-radius:12px; padding:1rem; box-shadow: 0 8px 28px rgba(0,0,0,0.05); }
        a.btn-custom { 
            border-radius: 12px; 
            padding: .9rem 2.2rem; 
            font-weight: 600; 
            font-size: 0.95rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            letter-spacing: 0.3px;
        }
        .btn-light.btn-custom {
            background: rgba(255, 255, 255, 0.95);
            color: #2b2b2b;
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
        }
        .btn-light.btn-custom:hover {
            background: #ffffff;
            transform: translateY(-2px);
            box-shadow: 0 6px 25px rgba(0, 0, 0, 0.2);
            color: #2b2b2b;
        }
        .btn-outline-light.btn-custom {
            background: rgba(255, 255, 255, 0.1);
            color: #ffffff;
            border: 1.5px solid rgba(255, 255, 255, 0.4);
            backdrop-filter: blur(10px);
        }
        .btn-outline-light.btn-custom:hover {
            background: rgba(255, 255, 255, 0.2);
            border-color: rgba(255, 255, 255, 0.6);
            color: #ffffff;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }
        .btn-success {
            background: var(--accent-1);
            border: none;
            border-radius: 10px;
            padding: 0.6rem 1.2rem;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 2px 8px rgba(106, 142, 58, 0.25);
        }
        .btn-success:hover {
            background: #5a7d32;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(106, 142, 58, 0.35);
        }
        .btn-outline-success {
            border: 1.5px solid var(--accent-1);
            color: var(--accent-1);
            border-radius: 10px;
            padding: 0.6rem 1.2rem;
            font-weight: 600;
            font-size: 0.9rem;
            background: transparent;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .btn-outline-success:hover {
            background: var(--accent-1);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(106, 142, 58, 0.25);
        }
        .btn-outline-secondary {
            border-radius: 10px;
            padding: 0.7rem 1.5rem;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .btn-outline-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        .category-tag {
    display: inline-block;
    padding: 4px 10px;
    font-size: 0.875rem; /* same as btn-sm */
    font-weight: 400;
    line-height: 1.5;

    border: 1px solid #6c757d; /* outline-secondary border */
    color: #6c757d;            /* outline-secondary text */

    border-radius: 6px;
    cursor: default;

    margin-right: 6px;
    margin-bottom: 6px;

    user-select: none;
}

/* Optional: make them react on hover like a button */
.category-tag:hover {
    background-color: rgba(108, 117, 125, 0.1);
}

        footer { padding:2.5rem 0; color:var(--muted); font-size:.95rem; }
        .tagline { color: #f3f7ec; text-shadow: 0 3px 18px rgba(0,0,0,0.35); }
        @media (max-width: 767px) {
            .hero { padding: 3rem 0; min-height: 60vh; }
        }
</style>

<!-- HERO -->
<section class="hero">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-7 mb-4" data-aos="fade-up">
                <div class="glass p-4">
                    <div class="mb-3">
                        <span class="tag">Community · Local</span>
                        <span class="tag">Volunteer-Matching</span>
                    </div>

                    <h1 class="display-5 fw-extrabold mb-3">Help your community. Share your time.</h1>
                    <p class="lead tagline mb-4">Connect local volunteers with causes that need hands — in minutes. Join neighbors, build impact, and track the lives you change.</p>

                    <?php if (!isset($_SESSION['logged_in'])): ?>
                        <div class="d-flex gap-3 flex-wrap">
                            <a href="auth/register.php" class="btn btn-light btn-custom">Join as Volunteer</a>
                            <a href="auth/register.php" class="btn btn-outline-light btn-custom">Register Org</a>
                            <a href="auth/login.php" class="btn btn-outline-light btn-custom">Login</a>
                        </div>
                    <?php else: ?>
                        <a href="public_browse.php" class="btn btn-light btn-custom">Open Your Dashboard</a>
                    <?php endif; ?>
                    <div class="mt-4 text-muted small">Trusted by communities across the region.</div>
                </div>
            </div>

            <div class="col-lg-5" data-aos="fade-left">
                <div class="feature-card">
                    <h5 class="fw-bold">Quick Impact</h5>
                    <p class="small text-muted">Find nearby events by day, skill, and time — or post an opportunity in under 3 minutes.</p>

                    <div class="row text-center mt-3">
                        <div class="col-4">
                            <div class="fw-bold" style="font-size:1.15rem; color:var(--accent-1)" id="stat-volunteers"><?= number_format($total_volunteers) ?></div>
                            <div class="small text-muted">Volunteers</div>
                        </div>
                        <div class="col-4">
                            <div class="fw-bold" style="font-size:1.15rem; color:#b27a4b" id="stat-hours"><?= number_format($total_hours, 1) ?></div>
                            <div class="small text-muted">Hours</div>
                        </div>
                        <div class="col-4">
                            <div class="fw-bold" style="font-size:1.15rem; color:#7d7d7d" id="stat-events"><?= number_format($total_events) ?></div>
                            <div class="small text-muted">Events</div>
                        </div>
                    </div>

                    <hr class="my-3">
                    <div class="small text-muted">Choose from different categories like:</div>
                    <div class="mt-2 d-flex gap-2 flex-wrap">
                        <h3 class="category-tag">Environment</h3>
                        <h3 class="category-tag">Education</h3>
                        <h3 class="category-tag">Health</h3>
                        <h3 class="category-tag">Community</h3>
                        <h3 class="category-tag">Animal Care</h3>
                        <h3 class="category-tag">Arts</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- OPPORTUNITIES & MAP -->
<section class="py-5">
    <div class="container">
        <div class="row g-4 align-items-start">
            <div class="col-lg-7" data-aos="fade-up">
                <h2 class="fw-bold mb-3">Upcoming Opportunities</h2>

                <?php if (empty($opportunities)): ?>
                    <div class="text-center py-5 story-card">
                        <p class="mb-0">No upcoming events yet — be the first to create one and rally your community.</p>
                        <div class="mt-3">
                            <a href="auth/register.php" class="btn btn-outline-secondary">Post an Opportunity</a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="row row-cols-1 row-cols-md-2 g-4">
                        <?php foreach ($opportunities as $opp): ?>
                            <?php
                                $spots_left = max(0, (int)$opp['spots_left']);
                                $slots = (int)$opp['slots'];
                                $filled = max(0, $slots - $spots_left);
                                $percent = $slots > 0 ? round(($filled / $slots) * 100) : 0;
                            ?>
                            <div class="col">
                                <div class="p-3 opportunity-card h-100" data-aos="zoom-in">
                                    <div class="d-flex align-items-start gap-3">
                                        <div style="width:78px; height:78px; border-radius:12px; background: linear-gradient(120deg, #f7f4ef, #eef6ea); display:flex; align-items:center; justify-content:center;">
                                            <svg width="34" height="34" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 2L12 22" stroke="#6A8E3A" stroke-width="1.6" stroke-linecap="round"/><path d="M2 12L22 12" stroke="#B27A4B" stroke-width="1.6" stroke-linecap="round"/></svg>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div style="min-width: 0; flex: 1;">
                                                    <h5 class="mb-1 fw-bold"><?= htmlspecialchars($opp['title']) ?></h5>
                                                    <div class="small text-muted" style="white-space: nowrap;">by <span class="org-badge"><?= htmlspecialchars($opp['org_name'] ?? 'Unknown') ?></span></div>
                                                </div>
                                                <div class="text-end small text-muted"><?= date('M j, Y', strtotime($opp['date'])) ?></div>
                                            </div>

                                            <p class="small text-muted mt-2 mb-2"><?= htmlspecialchars(substr($opp['description'], 0, 120)) ?>...</p>

                                            <div class="d-flex justify-content-between align-items-center">
                                                <div style="width:60%;">
                                                    <div class="progress progress-spot bg-light" style="height:10px;">
                                                        <div class="progress-bar" role="progressbar" style="width: <?= $percent ?>%;" aria-valuenow="<?= $percent ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                                    </div>
                                                    <div class="small text-muted mt-1"><?= $spots_left ?>/<?= $slots ?> spots left</div>
                                                </div>

                                                <div style="width:36%">
                                                    <?php if (!isset($_SESSION['logged_in'])): ?>
                                                        <button class="btn btn-outline-success w-100" data-bs-toggle="modal" data-bs-target="#authModal">Login to Join</button>
                                                    <?php elseif (in_array($_SESSION['role'] ?? '', ['admin','organization'])): ?>
                                                        <button class="btn btn-secondary w-100" disabled>Admins Cannot Join</button>
                                                    <?php else: ?>
                                                        <form method="POST" action="includes/apply_handler.php" class="apply-form" data-opp-id="<?= $opp['id'] ?>">
                                                            <input type="hidden" name="opp_id" value="<?= $opp['id'] ?>">
                                                            <button type="submit" class="btn btn-success w-100 fw-bold apply-btn">Sign Me Up</button>
                                                        </form>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="text-center mt-4">
                    <a href="public_browse.php" class="btn btn-outline-success btn-lg">View All Opportunities</a>
                </div>
            </div>

            <div class="col-lg-5" data-aos="fade-left">
                <h5 class="fw-bold mb-3">Nearby Opportunities</h5>
                <div id="map" class="map-card mb-3" style="height: 400px; width: 100%; border-radius: 15px;"></div>

                <p class="small text-muted">Explore opportunities on the map. Click a pin to see details and sign up.</p>

                <div class="story-card mt-3">
                    <h6 class="fw-bold">Volunteer Story</h6>
                    <p class="mb-1 small text-muted">“I joined a park cleanup last month — met great neighbors and logged 10 hours. Worth every minute.”</p>
                    <div class="small text-muted">— Arben, Volunteer</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- STATS -->
<section class="stats">
    <div class="container">
        <div class="row text-center">
            <div class="col-md-4 mb-4" data-aos="fade-up">
                <div class="stat-number" id="volCounter"><?= number_format($total_volunteers) ?></div>
                <p class="fw-bold">Active Volunteers</p>
            </div>
            <div class="col-md-4 mb-4" data-aos="fade-up" data-aos-delay="100">
                <div class="stat-number" id="hoursCounter"><?= number_format($total_hours, 1) ?></div>
                <p class="fw-bold">Hours Contributed</p>
            </div>
            <div class="col-md-4 mb-4" data-aos="fade-up" data-aos-delay="200">
                <div class="stat-number" id="eventsCounter"><?= number_format($total_events) ?></div>
                <p class="fw-bold">Events Hosted</p>
            </div>
        </div>
    </div>
</section>

<!-- AUTH MODAL (shown when trying to apply while not logged in) -->
<div class="modal fade" id="authModal" tabindex="-1" aria-labelledby="authModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="border-radius:14px; overflow:hidden;">
      <div class="modal-body p-4">
        <h5 class="fw-bold mb-2">Please Sign Up to Join</h5>
        <p class="small text-muted mb-3">Create an account to save your profile, track hours, and join opportunities easily.</p>
        <div class="d-flex gap-2">
            <a href="auth/register.php" class="btn btn-success w-100">Sign Up</a>
            <a href="auth/login.php" class="btn btn-outline-secondary w-100">Login</a>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Scripts -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
<script src="https://cdn.jsdelivr.net/npm/countup.js@2.8.0/dist/countUp.umd.js"></script>

<script>
    AOS.init({ duration: 700, once: true });

    // Animated counters (CountUp)
    (function(){
        function initCounters() {
            let CountUp;
            if (typeof window !== 'undefined') {
                if (window.CountUp && window.CountUp.CountUp) {
                    CountUp = window.CountUp.CountUp;
                } else if (window.CountUp && typeof window.CountUp === 'function') {
                    CountUp = window.CountUp;
                } else if (window.countUp && window.countUp.CountUp) {
                    CountUp = window.countUp.CountUp;
                } else if (typeof countUp !== 'undefined' && countUp.CountUp) {
                    CountUp = countUp.CountUp;
                }
            }
            
            if (!CountUp) {
                if (typeof initCounters.retries === 'undefined') {
                    initCounters.retries = 0;
                }
                if (initCounters.retries < 10) {
                    initCounters.retries++;
                    setTimeout(initCounters, 200);
                } else {
                    console.warn('CountUp library failed to load. Available:', Object.keys(window).filter(k => k.toLowerCase().includes('count')));
                }
                return;
            }

            try {
                const vol = new CountUp('volCounter', <?= $total_volunteers ?>, { duration: 1.4, separator: ',' });
                const hrs = new CountUp('hoursCounter', <?= $total_hours ?>, { duration: 1.4, decimalPlaces: 1, separator: ',' });
                const evs = new CountUp('eventsCounter', <?= $total_events ?>, { duration: 1.4, separator: ',' });
                if (vol && !vol.error) vol.start();
                if (hrs && !hrs.error) hrs.start();
                if (evs && !evs.error) evs.start();

                const smallVol = new CountUp('stat-volunteers', <?= $total_volunteers ?>, { duration: 1.2, separator: ',' });
                const smallHrs = new CountUp('stat-hours', <?= $total_hours ?>, { duration: 1.2, decimalPlaces: 1, separator: ',' });
                const smallEvs = new CountUp('stat-events', <?= $total_events ?>, { duration: 1.2, separator: ',' });
                if (smallVol && !smallVol.error) smallVol.start();
                if (smallHrs && !smallHrs.error) smallHrs.start();
                if (smallEvs && !smallEvs.error) smallEvs.start();
            } catch (e) {
                console.error('CountUp initialization error:', e);
            }
        }
        
        window.addEventListener('load', function() {
            setTimeout(initCounters, 100);
        });
        
        if (document.readyState !== 'loading') {
            setTimeout(initCounters, 100);
        }
    })();

    // Initialize Leaflet map
    function initMap() {
        if (typeof L === 'undefined') {
            console.error('Leaflet library not loaded, retrying...');
            setTimeout(initMap, 100);
            return;
        }

        var mapElement = document.getElementById('map');
        if (!mapElement) {
            console.warn('Map element not found');
            return;
        }

        try {
            var map = L.map('map', { 
                scrollWheelZoom: false,
                zoomControl: true
            }).setView([42.6629, 21.1655], 12);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                maxZoom: 19
            }).addTo(map);

            var markers = [];
            <?php if (!empty($opportunities)): ?>
                <?php 
                $center_lat = 42.6629;
                $center_lng = 21.1655;
                $radius = 0.05;
                
                foreach ($opportunities as $i=>$opp): 
                    $seed = crc32($opp['id'] . $opp['title']);
                    mt_srand($seed);
                    
                    $angle = mt_rand(0, 360) * (M_PI / 180);
                    $distance = mt_rand(30, 100) / 100;
                    
                    $lat_offset = $distance * $radius * cos($angle);
                    $lng_offset = $distance * $radius * sin($angle);
                    
                    $lat = $center_lat + $lat_offset;
                    $lng = $center_lng + $lng_offset;
                ?>
                    var marker<?= $i ?> = L.marker([<?= $lat ?>, <?= $lng ?>]).addTo(map);
                    marker<?= $i ?>.bindPopup(
                        "<strong><?= htmlspecialchars(addslashes($opp['title'])) ?></strong><br>" +
                        "<?= htmlspecialchars(addslashes(substr($opp['description'],0,80))) ?>...<br>" +
                        "<small><?= htmlspecialchars(addslashes($opp['org_name'] ?? 'Unknown')) ?></small>"
                    );
                    markers.push(marker<?= $i ?>);
                <?php endforeach; ?>

                if (markers.length > 0) {
                    var group = new L.featureGroup(markers);
                    if (markers.length > 1) {
                        map.fitBounds(group.getBounds().pad(0.1));
                    }
                }
            <?php endif; ?>

            console.log('Map initialized successfully');
            window.mapInitialized = true;
        } catch (e) {
            console.error('Map initialization failed:', e);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(initMap, 200);
        });
    } else {
        setTimeout(initMap, 200);
    }
    
    window.addEventListener('load', function() {
        setTimeout(function() {
            if (document.getElementById('map') && typeof L !== 'undefined' && !window.mapInitialized) {
                initMap();
                window.mapInitialized = true;
            }
        }, 300);
    });

    // Handle AJAX form submissions for applying to opportunities
    document.addEventListener('DOMContentLoaded', function() {
        const applyForms = document.querySelectorAll('.apply-form');
        
        applyForms.forEach(function(form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(form);
                const button = form.querySelector('.apply-btn');
                const originalText = button.textContent;
                
                button.disabled = true;
                button.textContent = 'Applying...';
                
                const xhr = new XMLHttpRequest();
                xhr.open('POST', form.action, true);
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                
                xhr.onload = function() {
                    if (xhr.status === 200) {
                        try {
                            const response = JSON.parse(xhr.responseText);
                            
                            if (response.success) {
                                button.textContent = 'You applied! Waiting for response...';
                                button.classList.remove('btn-success', 'btn-main', 'fw-bold');
                                button.classList.add('btn-warning');
                                button.style.background = '#f59e0b';
                                button.style.border = 'none';
                                button.style.color = 'white';
                                button.disabled = true;
                                
                                form.onsubmit = function(e) { e.preventDefault(); return false; };
                            } else {
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

<?php include_once 'includes/footer.php'; ?>