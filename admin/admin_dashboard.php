<?php
session_start();
require_once '../config/config.php';
include 'includes/admin_header.php';

// Only allow admin
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}

/* ==========================
   FETCH STATS
========================== */
// Total volunteers
$total_volunteers = $conn->query("SELECT COUNT(*) FROM users WHERE role = 'volunteer'")->fetchColumn();

// Total organizations
$total_organizations = $conn->query("SELECT COUNT(*) FROM users WHERE role = 'organization'")->fetchColumn();

// Total events
$total_events = $conn->query("SELECT COUNT(*) FROM opportunities")->fetchColumn();

// Total verified hours
$total_hours = $conn->query("SELECT COALESCE(SUM(total_verified_hours),0) FROM users WHERE role='volunteer'")->fetchColumn();

// Users joined in last 7 days
$recent_users = $conn->query("SELECT COUNT(*) FROM users WHERE role='volunteer' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn();

// Organizations joined in last 7 days
$recent_orgs = $conn->query("SELECT COUNT(*) FROM users WHERE role='organization' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn();

/* ==========================
   FETCH TABLE DATA
========================== */
// Volunteers
$users = $conn->query("SELECT id, name, email, total_verified_hours, created_at FROM users WHERE role='volunteer' ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

// Organizations
$orgs = $conn->query("SELECT id, name, email, created_at FROM users WHERE role='organization' ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

?>

<style>
:root{
    --accent-1: #6a8e3a;
    --accent-2: #b27a4b;
    --card-radius: 18px;
    --muted: #6b6b6b;
}
body { font-family: 'Manrope', sans-serif; background: #f2efe9; }
.admin-container { max-width: 1200px; margin: 50px auto; }
.stat-card { border-radius: var(--card-radius); padding: 2rem; background: white; box-shadow: 0 8px 30px rgba(0,0,0,0.05); text-align: center; }
.stat-number { font-size: 2.5rem; font-weight: 800; color: var(--accent-1); }
.section-title { font-weight: 700; margin-bottom: 1rem; color: #2b2b2b; }
.table-container { margin-top: 2rem; background: #fff; padding: 1.5rem; border-radius: var(--card-radius); box-shadow: 0 8px 30px rgba(0,0,0,0.05); }
table { width: 100%; border-collapse: collapse; }
th, td { padding: 12px 15px; text-align: left; }
th { background: #f7f6f3; color: #2b2b2b; font-weight: 600; }
tr:nth-child(even) { background: #f9f9f9; }
.btn { padding: 0.4rem 0.8rem; border-radius: 8px; border: none; cursor: pointer; transition: 0.25s; }
.btn-edit { background: var(--accent-2); color: white; }
.btn-edit:hover { background: #a2693f; }
.btn-delete { background: #e53e3e; color: white; }
.btn-delete:hover { background: #c53030; }
</style>

<div class="admin-container">
    <h1 class="section-title">Admin Dashboard</h1>

    <!-- Stats -->
    <div class="row mb-5" style="display:flex; gap:2rem; flex-wrap:wrap;">
        <div class="stat-card" style="flex:1 1 200px;">
            <div class="stat-number"><?= $total_volunteers ?></div>
            <div>Total Volunteers</div>
        </div>
        <div class="stat-card" style="flex:1 1 200px;">
            <div class="stat-number"><?= $total_organizations ?></div>
            <div>Total Organizations</div>
        </div>
        <div class="stat-card" style="flex:1 1 200px;">
            <div class="stat-number"><?= $total_events ?></div>
            <div>Total Events</div>
        </div>
        <div class="stat-card" style="flex:1 1 200px;">
            <div class="stat-number"><?= $total_hours ?></div>
            <div>Total Verified Hours</div>
        </div>
        <div class="stat-card" style="flex:1 1 200px;">
            <div class="stat-number"><?= $recent_users ?></div>
            <div>New Volunteers (7d)</div>
        </div>
        <div class="stat-card" style="flex:1 1 200px;">
            <div class="stat-number"><?= $recent_orgs ?></div>
            <div>New Organizations (7d)</div>
        </div>
    </div>

    <!-- Volunteers Table -->
    <div class="table-container">
        <h2 class="section-title">Volunteers</h2>
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Verified Hours</th>
                    <th>Joined</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="volunteerTable">
                <?php foreach($users as $u): ?>
                    <tr id="userRow-<?= $u['id'] ?>">
                        <td><?= htmlspecialchars($u['name']) ?></td>
                        <td><?= htmlspecialchars($u['email']) ?></td>
                        <td><?= $u['total_verified_hours'] ?></td>
                        <td><?= date('Y-m-d', strtotime($u['created_at'])) ?></td>
                        <td>
                            <button class="btn btn-edit" onclick="editUser(<?= $u['id'] ?>)">Edit</button>
                            <button class="btn btn-delete" onclick="deleteUser(<?= $u['id'] ?>)">Delete</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Organizations Table -->
    <div class="table-container">
        <h2 class="section-title">Organizations</h2>
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Joined</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="orgTable">
                <?php foreach($orgs as $o): ?>
                    <tr id="orgRow-<?= $o['id'] ?>">
                        <td><?= htmlspecialchars($o['name']) ?></td>
                        <td><?= htmlspecialchars($o['email']) ?></td>
                        <td><?= date('Y-m-d', strtotime($o['created_at'])) ?></td>
                        <td>
                            <button class="btn btn-edit" onclick="editOrg(<?= $o['id'] ?>)">Edit</button>
                            <button class="btn btn-delete" onclick="deleteOrg(<?= $o['id'] ?>)">Delete</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function deleteUser(id){
    if(confirm("Delete this volunteer?")){
        fetch('admin_user_handler.php', {
            method:'POST',
            headers:{'Content-Type':'application/json'},
            body: JSON.stringify({action:'delete',id:id})
        }).then(res=>res.json()).then(data=>{
            if(data.success){
                document.getElementById('userRow-'+id).remove();
            } else alert(data.message);
        });
    }
}
function deleteOrg(id){
    if(confirm("Delete this organization?")){
        fetch('admin_user_handler.php', {
            method:'POST',
            headers:{'Content-Type':'application/json'},
            body: JSON.stringify({action:'delete',id:id})
        }).then(res=>res.json()).then(data=>{
            if(data.success){
                document.getElementById('orgRow-'+id).remove();
            } else alert(data.message);
        });
    }
}
function editUser(id){
    let newName = prompt("Enter new name for this volunteer:");
    if(newName){
        fetch('admin_user_handler.php', {
            method:'POST',
            headers:{'Content-Type':'application/json'},
            body: JSON.stringify({action:'edit',id:id,name:newName})
        }).then(res=>res.json()).then(data=>{
            if(data.success){
                document.querySelector('#userRow-'+id+' td:first-child').innerText = newName;
            } else alert(data.message);
        });
    }
}
function editOrg(id){
    let newName = prompt("Enter new name for this organization:");
    if(newName){
        fetch('admin_user_handler.php', {
            method:'POST',
            headers:{'Content-Type':'application/json'},
            body: JSON.stringify({action:'edit',id:id,name:newName})
        }).then(res=>res.json()).then(data=>{
            if(data.success){
                document.querySelector('#orgRow-'+id+' td:first-child').innerText = newName;
            } else alert(data.message);
        });
    }
}
</script>

<?php include_once '../includes/footer.php'; ?>
