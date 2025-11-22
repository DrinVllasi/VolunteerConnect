<?php
session_start();
require_once 'includes/auth_guard.php';
require_once 'config/config.php';
include_once 'includes/header.php';
?>

<?php if ($_SESSION['role'] !== 'organization' && $_SESSION['role'] !== 'admin'): ?>
    <?php $_SESSION['login_errors'] = ["Only organizations can post opportunities."]; header('Location: public_browse.php'); exit(); ?>
<?php endif; ?>

<div class="container"><div class="card shadow p-4">
    <h2 class="text-center mb-4">Post a Volunteer Opportunity</h2>

    <?php if (isset($_SESSION['opp_success'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_SESSION['opp_success']) ?></div>
        <?php unset($_SESSION['opp_success']); ?>
    <?php endif; ?>

    <form action="post_opportunity.php" method="POST">
        <div class="mb-3"><input type="text" name="title" class="form-control form-control-lg" placeholder="Title (e.g. Food Drive)" required></div>
        <div class="mb-3"><textarea name="description" class="form-control" rows="5" placeholder="Describe the opportunity..." required></textarea></div>
        <div class="row">
            <div class="col-md-6 mb-3"><input type="text" name="location" class="form-control" placeholder="Location" required></div>
            <div class="col-md-4 mb-3"><input type="date" name="date" class="form-control" required></div>
            <div class="col-md-2 mb-3"><input type="time" name="time" class="form-control"></div>
        </div>
        <div class="mb-3"><input type="number" name="slots" class="form-control" placeholder="Available slots (default 10)" min="1" value="10"></div>
        <div class="d-grid"><button type="submit" name="submit" class="btn btn-primary btn-lg">Post Opportunity</button></div>
    </form>
    <div class="text-center mt-3"><a href="public_browse.php" class="btn btn-link">← Back to Dashboard</a></div>
</div></div>
</body>
</html>

<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    $title       = trim($_POST['title']);
    $description = trim($_POST['description']);
    $location    = trim($_POST['location']);
    $date        = $_POST['date'];
    $time        = $_POST['time'] ?? null;
    $slots       = (int)($_POST['slots'] ?? 10);

    $stmt = $conn->prepare("INSERT INTO opportunities (title, description, location, date, time, slots, organization_id) 
                            VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$title, $description, $location, $date, $time, $slots, $_SESSION['user_id']]);

    $_SESSION['opp_success'] = "Opportunity posted successfully!";
    exit();
}
?>
<?php include 'includes/footer.php'; ?>