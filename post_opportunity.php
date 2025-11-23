<?php
session_start();
require_once 'config/config.php';
require_once 'includes/auth_guard.php';

if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['role'], ['organization', 'admin'])) {
    header('Location: index.php'); exit;
}

if ($_POST) {
    $title         = trim($_POST['title']);
    $description   = trim($_POST['description']);
    $date          = $_POST['date'];
    $time          = $_POST['time'] ?: null;
    $location_name = trim($_POST['location_name']);
    $slots         = (int)$_POST['slots'];

    // Only required fields
    if ($title && $description && $date && $slots > 0 && $location_name) {
        $stmt = $conn->prepare("INSERT INTO opportunities 
            (organization_id, title, description, date, time, location_name, slots, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");

        $stmt->execute([
            $_SESSION['user_id'],
            $title,
            $description,
            $date,
            $time,
            $location_name,
            $slots
        ]);

        header("Location: manage_events.php?success=1");
        exit;
    } else {
        $error = "Please fill all required fields and click on the map to set the location.";
    }
}

include_once 'includes/header.php';
?>

<link href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" rel="stylesheet">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <h1 class="display-5 fw-bold text-center mb-4">Post a New Opportunity</h1>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger text-center"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" class="card shadow-lg border-0">
                <div class="card-body p-5">
                    <div class="row g-4">
                        <div class="col-md-8">
                            <label class="form-label fw-bold">Event Title</label>
                            <input type="text" name="title" class="form-control form-control-lg" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Volunteer Slots</label>
                            <input type="number" name="slots" min="1" max="500" class="form-control form-control-lg" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Date</label>
                            <input type="date" name="date" class="form-control form-control-lg" required min="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Time (optional)</label>
                            <input type="time" name="time" class="form-control form-control-lg">
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-bold">Description</label>
                            <textarea name="description" rows="4" class="form-control form-control-lg" required></textarea>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-bold">Exact Location — Click on the map</label>
                            <div id="map" style="height: 420px; border-radius: 18px; overflow: hidden; box-shadow: 0 12px 40px rgba(0,0,0,0.12);"></div>
                            <div class="mt-3 p-3 bg-light rounded">
                                <strong>Selected Location:</strong> 
                                <span id="selected_address" class="text-success fw-bold">Click the map to set location</span>
                            </div>
                        </div>

                        <!-- Only store the address text -->
                        <input type="hidden" name="location_name" id="location_name">

                        <div class="col-12 text-center mt-4">
                            <button type="submit" class="btn btn-success btn-lg px-5" style="border-radius: 16px;">
                                Post Opportunity
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let map, marker;

function initMap() {
    map = L.map('map').setView([42.6629, 21.1655], 12);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    map.on('click', function(e) {
        if (marker) map.removeLayer(marker);
        marker = L.marker(e.latlng).addTo(map);

        // Reverse geocoding to get human-readable address
        fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${e.latlng.lat}&lon=${e.latlng.lng}&zoom=18`)
            .then(r => r.json())
            .then(data => {
                const addr = data.display_name || "Unknown location";
                document.getElementById('location_name').value = addr;
                document.getElementById('selected_address').textContent = addr.split(',').slice(0, 3).join(', ');
            })
            .catch(() => {
                const fallback = `Lat: ${e.latlng.lat.toFixed(6)}, Lng: ${e.latlng.lng.toFixed(6)}`;
                document.getElementById('location_name').value = fallback;
                document.getElementById('selected_address').textContent = fallback;
            });
    });
}

document.addEventListener('DOMContentLoaded', initMap);
</script>

<?php include 'includes/footer.php'; ?>