<?php
include '../config/config.php';
 include '../includes/header.php'; 

// Fetch all events
$stmt = $conn->prepare("SELECT * FROM events ORDER BY date ASC, time ASC");
$stmt->execute();
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch user signups if logged in
$user_signups = [];
if (isset($_SESSION['user_id'])) {
    $stmt2 = $conn->prepare("SELECT event_id FROM event_signups WHERE user_id = ?");
    $stmt2->execute([$_SESSION['user_id']]);
    $user_signups = $stmt2->fetchAll(PDO::FETCH_COLUMN);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Events - VolunteerConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .event-card { border: 1px solid #ddd; border-radius: 10px; padding: 20px; margin-bottom: 20px; background: #f9f9f9; }
        .event-card h3 { margin-bottom: 10px; }
        .event-card p { margin-bottom: 5px; }
    </style>
</head>
<body>


<div class="container">
    <h1 class="mb-4 text-center">Upcoming Volunteer Events</h1>

    <?php if (empty($events)): ?>
        <p class="text-center">No events available at the moment.</p>
    <?php else: ?>
        <?php foreach ($events as $event): ?>
            <div class="event-card">
                <h3><?= htmlspecialchars($event['title']); ?></h3>
                <p><strong>Date:</strong> <?= htmlspecialchars($event['date']); ?> <strong>Time:</strong> <?= htmlspecialchars($event['time']); ?></p>
                <p><strong>Location:</strong> <?= htmlspecialchars($event['location']); ?></p>
                <p><?= nl2br(htmlspecialchars($event['description'])); ?></p>

                <?php if (isset($_SESSION['user_id'])): ?>
                    <?php if (in_array($event['id'], $user_signups)): ?>
                        <button class="btn btn-secondary" disabled>Already Signed Up</button>
                    <?php else: ?>
                        <form action="signup.php" method="post" class="d-inline">
                            <input type="hidden" name="event_id" value="<?= $event['id']; ?>">
                            <button type="submit" class="btn btn-primary">Sign Up</button>
                        </form>
                    <?php endif; ?>
                <?php else: ?>
                    <a href="../auth/login.php" class="btn btn-warning">Login to Sign Up</a>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
</body>
</html>
