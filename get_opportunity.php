<?php
require_once 'config/config.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    exit(json_encode(['error' => 'Invalid ID']));
}

$id = (int)$_GET['id'];

// REAL PRISHTINA LOCATIONS — NAME → COORDS
$realLocations = [
    'Sheshi Nëna Terezë'          => ['lat' => 42.66290, 'lng' => 21.16550],
    'Parku i Gërmisë'             => ['lat' => 42.65780, 'lng' => 21.15730],
    'Monumenti NEWBORN'           => ['lat' => 42.66420, 'lng' => 21.16210],
    'Universiteti i Prishtinës'   => ['lat' => 42.65010, 'lng' => 21.15340],
    'Parku i Qytetit'             => ['lat' => 42.67120, 'lng' => 21.16670],
    'Pazari i Vjetër'             => ['lat' => 42.64650, 'lng' => 21.14980],
    'Xhamia e Madhe'              => ['lat' => 42.65930, 'lng' => 21.16280],
    'Teatri Kombëtar'             => ['lat' => 42.66510, 'lng' => 21.15990],
    'Qendra e Qytetit'            => ['lat' => 42.66200, 'lng' => 21.16400],
    'Lago e Liqenit'              => ['lat' => 42.65300, 'lng' => 21.14800],
];

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
    WHERE o.id = ?
");
$stmt->execute([$id]);
$opp = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$opp) {
    http_response_code(404);
    exit(json_encode(['error' => 'Opportunity not found']));
}

// Determine correct location name and coordinates
$locText = trim($opp['location_name'] ?? $opp['location'] ?? '');
$finalName = 'Sheshi Nëna Terezë'; // default fallback

foreach ($realLocations as $name => $coords) {
    if (strcasecmp($locText, $name) === 0 || 
        stripos($locText, str_replace(['ë','Ë'], 'e', $name)) !== false ||
        stripos($name, str_replace(['ë','Ë'], 'e', $locText)) !== false) {
        $finalName = $name;
        break;
    }
}

// Fallback cycle
if (!isset($realLocations[$finalName])) {
    $keys = array_keys($realLocations);
    $finalName = $keys[$id % count($keys)];
}

header('Content-Type: application/json');
echo json_encode([
    'title'          => $opp['title'],
    'description'    => $opp['description'],
    'date'           => $opp['date'],
    'location_name'  => $finalName,
    'latitude'       => $realLocations[$finalName]['lat'],
    'longitude'      => $realLocations[$finalName]['lng'],
    'slots'          => (int)$opp['slots'],
    'spots_left'     => (int)$opp['spots_left'],
    'org_name'       => $opp['org_name'] ?? 'Community'
]);