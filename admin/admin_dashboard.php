<?php
session_start();
require_once '../config/config.php';
include 'includes/admin_header.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}

/* ========================== FIXED & CORRECT STATS ========================== */
$total_volunteers     = $conn->query("SELECT COUNT(*) FROM users WHERE role IN ('user','volunteer')")->fetchColumn();
$total_organizations  = $conn->query("SELECT COUNT(*) FROM users WHERE role = 'organization'")->fetchColumn();
$total_events         = $conn->query("SELECT COUNT(*) FROM opportunities")->fetchColumn();

$total_hours = $conn->query("
    SELECT COALESCE(SUM(hours_worked), 0)
    FROM applications
    WHERE hours_approved = 1
")->fetchColumn();

$recent_users = $conn->query("
    SELECT COUNT(*) FROM users 
    WHERE role IN ('user','volunteer') 
    AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
")->fetchColumn();

$recent_orgs = $conn->query("
    SELECT COUNT(*) FROM users 
    WHERE role='organization' 
    AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
")->fetchColumn();

/* ========================== TABLE DATA ========================== */
$users = $conn->query("
    SELECT u.id, u.name, u.email, u.created_at,
           COALESCE(SUM(a.hours_worked), 0) AS verified_hours
    FROM users u
    LEFT JOIN applications a 
        ON u.id = a.volunteer_id AND a.hours_approved = 1
    WHERE u.role IN ('user','volunteer')
    GROUP BY u.id
    ORDER BY u.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

$orgs = $conn->query("
    SELECT id, name, email, created_at 
    FROM users
    WHERE role = 'organization'
    ORDER BY created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
:root{--accent-1:#6a8e3a;--accent-2:#b27a4b;--card-radius:18px;--muted:#6b6b6b}
body{font-family:'Manrope',sans-serif;background:#f2efe9}
.admin-container{max-width:1200px;margin:50px auto}

/* === GRID LAYOUT FOR CARDS === */
.stat-grid{
    display:grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap:1.5rem;
}

.stat-card{
    border-radius:var(--card-radius);
    padding:2rem;
    background:white;
    box-shadow:0 8px 30px rgba(0,0,0,0.05);
    text-align:center;
}
.stat-number{
    font-size:2.5rem;
    font-weight:800;
    color:var(--accent-1);
}
.stat-label{
    margin-top:8px;
    color:var(--muted);
}

.section-title{font-weight:700;margin:2rem 0 1rem;color:#2b2b2b}
.table-container{
    margin-top:2rem;background:#fff;padding:1.5rem;
    border-radius:var(--card-radius);
    box-shadow:0 8px 30px rgba(0,0,0,0.05)
}
table{width:100%;border-collapse:collapse}
th,td{padding:14px 16px;text-align:left}
th{background:#f7f6f3;color:#2b2b2b;font-weight:600}
tr:nth-child(even){background:#f9f9f9}

.btn{padding:0.5rem 1rem;border-radius:8px;border:none;cursor:pointer;font-size:0.9rem}
.btn-edit{background:var(--accent-2);color:white}
.btn-edit:hover{background:#a2693f}
.btn-delete{background:#e53e3e;color:white}
.btn-delete:hover{background:#c53030}
</style>

<div class="admin-container">
    <h1 class="section-title">Admin Dashboard</h1>

    <!-- === COLUMN GRID CARDS === -->
    <div class="stat-grid">

        <div class="stat-card">
            <div class="stat-number"><?= $total_volunteers ?></div>
            <div class="stat-label">Volunteers</div>
        </div>

        <div class="stat-card">
            <div class="stat-number"><?= $total_organizations ?></div>
            <div class="stat-label">Organizations</div>
        </div>

        <div class="stat-card">
            <div class="stat-number"><?= $total_events ?></div>
            <div class="stat-label">Events Posted</div>
        </div>

        <div class="stat-card">
            <div class="stat-number"><?= number_format($total_hours, 1) ?>h</div>
            <div class="stat-label">Verified Hours Given</div>
        </div>

        <div class="stat-card">
            <div class="stat-number"><?= $recent_users ?></div>
            <div class="stat-label">New Volunteers (7 days)</div>
        </div>

    </div>

    <!-- === TABLES === -->
    <div class="table-container">
        <h2 class="section-title">Volunteers</h2>
        <table>
            <thead><tr><th>Name</th><th>Email</th><th>Verified Hours</th><th>Joined</th><th>Actions</th></tr></thead>
            <tbody id="volunteerTable">
                <?php foreach($users as $u): ?>
                <tr id="userRow-<?= $u['id'] ?>">
                    <td><strong><?= htmlspecialchars($u['name']) ?></strong></td>
                    <td><?= htmlspecialchars($u['email']) ?></td>
                    <td><strong><?= number_format($u['verified_hours'],1) ?>h</strong></td>
                    <td><?= date('M j, Y', strtotime($u['created_at'])) ?></td>
                    <td>
                        <button class="btn btn-edit" onclick="editUser(<?= $u['id'] ?>)">Edit</button>
                        <button class="btn btn-delete" onclick="deleteUser(<?= $u['id'] ?>)">Delete</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="table-container">
        <h2 class="section-title">Organizations</h2>
        <table>
            <thead><tr><th>Name</th><th>Email</th><th>Joined</th><th>Actions</th></tr></thead>
            <tbody id="orgTable">
                <?php foreach($orgs as $o): ?>
                <tr id="orgRow-<?= $o['id'] ?>">
                    <td><strong><?= htmlspecialchars($o['name']) ?></strong></td>
                    <td><?= htmlspecialchars($o['email']) ?></td>
                    <td><?= date('M j, Y', strtotime($o['created_at'])) ?></td>
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
// NOTE: these functions expect admin_user_handler.php to accept JSON POSTs and return JSON { success: bool, message?: string }.
// Adjust the endpoint path if your handler is located elsewhere.

function deleteUser(id){
    if(!confirm("Delete this volunteer?")) return;
    fetch('admin_user_handler.php', {
        method:'POST',
        headers:{'Content-Type':'application/json'},
        body: JSON.stringify({action:'delete', id:id, type:'user'})
    }).then(res => res.json()).then(data => {
        if(data && data.success){
            const row = document.getElementById('userRow-'+id);
            if(row) row.remove();
        } else {
            alert(data && data.message ? data.message : 'Failed to delete user.');
        }
    }).catch(err=>{
        console.error(err);
        alert('Network error while deleting user.');
    });
}

function deleteOrg(id){
    if(!confirm("Delete this organization?")) return;
    fetch('admin_user_handler.php', {
        method:'POST',
        headers:{'Content-Type':'application/json'},
        body: JSON.stringify({action:'delete', id:id, type:'org'})
    }).then(res => res.json()).then(data => {
        if(data && data.success){
            const row = document.getElementById('orgRow-'+id);
            if(row) row.remove();
        } else {
            alert(data && data.message ? data.message : 'Failed to delete organization.');
        }
    }).catch(err=>{
        console.error(err);
        alert('Network error while deleting organization.');
    });
}

function editUser(id){
    const currentNameEl = document.querySelector('#userRow-'+id+' td:first-child strong');
    const currentName = currentNameEl ? currentNameEl.innerText.trim() : '';
    const newName = prompt("Enter new name for this volunteer:", currentName);
    if(!newName || newName.trim() === '' || newName === currentName) return;

    fetch('admin_user_handler.php', {
        method:'POST',
        headers:{'Content-Type':'application/json'},
        body: JSON.stringify({action:'edit', id:id, name:newName.trim(), type:'user'})
    }).then(res => res.json()).then(data => {
        if(data && data.success){
            if(currentNameEl) currentNameEl.innerText = newName.trim();
        } else {
            alert(data && data.message ? data.message : 'Failed to update volunteer.');
        }
    }).catch(err=>{
        console.error(err);
        alert('Network error while editing volunteer.');
    });
}

function editOrg(id){
    const currentNameEl = document.querySelector('#orgRow-'+id+' td:first-child strong');
    const currentName = currentNameEl ? currentNameEl.innerText.trim() : '';
    const newName = prompt("Enter new name for this organization:", currentName);
    if(!newName || newName.trim() === '' || newName === currentName) return;

    fetch('admin_user_handler.php', {
        method:'POST',
        headers:{'Content-Type':'application/json'},
        body: JSON.stringify({action:'edit', id:id, name:newName.trim(), type:'org'})
    }).then(res => res.json()).then(data => {
        if(data && data.success){
            if(currentNameEl) currentNameEl.innerText = newName.trim();
        } else {
            alert(data && data.message ? data.message : 'Failed to update organization.');
        }
    }).catch(err=>{
        console.error(err);
        alert('Network error while editing organization.');
    });
}
</script>

<?php include_once '../includes/footer.php'; ?>
