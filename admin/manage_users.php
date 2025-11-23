<?php
ob_start();
session_start();
require_once '../config/config.php';
include_once '../includes/header.php';

if ($_SESSION['role'] !== 'admin') { header('Location: ../index.php'); exit; }

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    if ($id > 1) { // don't delete yourself
        $conn->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);
    }
    header('Location: manage_users.php');
}

$users = $conn->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll();
?>

<div class="container my-5">
    <h1 class="display-5 fw-bold mb-4 text-success">Manage Users</h1>
    
    <div class="card shadow-lg border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-success">
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Phone</th>
                            <th>Hours</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                        <tr>
                            <td><strong><?=htmlspecialchars($u['name'])?></strong></td>
                            <td><?=htmlspecialchars($u['email'])?></td>
                            <td><span class="badge bg-<?= $u['role']=='admin'?'danger':($u['role']=='organization'?'warning':'primary') ?>">
                                <?=ucfirst($u['role'])?>
                            </span></td>
                            <td><?=$u['phone'] ?: '—'?></td>
                            <td><?=$u['total_verified_hours'] ?: 0?></td>
                            <td>
                                <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                    <a href="?delete=<?=$u['id']?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this user?')">Delete</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ob_end_flush(); ?>