<?php if (session_status() === PHP_SESSION_NONE) session_start(); ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VolunteerConnect - <?= isset($page_title) ? htmlspecialchars($page_title) : 'Admin Dashboard' ?></title>

    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@300;400;600;800&family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

    <style>
        :root{
            --earth-1: #f2efe9;
            --accent-1: #6a8e3a;
            --accent-2: #b27a4b;
            --muted: #6b6b6b;
        }
        body { 
            font-family: 'Manrope', 'Inter', sans-serif; 
            background: var(--earth-1); 
            color: #2b2b2b; 
            margin: 0;
        }
        .navbar {
            height: 86px;
            background: rgba(255, 255, 255, 0.94) !important;
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(0,0,0,0.06);
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 8px 32px rgba(0,0,0,0.04);
        }
        .navbar-brand {
            font-weight: 800 !important;
            font-size: 1.95rem !important;
            color: var(--accent-1) !important;
            display: flex;
            align-items: center;
            gap: 12px;
            letter-spacing: -0.5px;
            white-space: nowrap;
        }
        .navbar-brand i {
            font-size: 2.7rem !important;
            background: linear-gradient(135deg, #6a8e3a, #b27a4b);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .user-avatar {
            width: 42px; height: 42px; border-radius: 50%;
            background: linear-gradient(135deg, #6a8e3a, #b27a4b);
            display: flex; align-items: center; justify-content: center;
            color: white; font-weight: bold; font-size: 1.2rem;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        a:hover .user-avatar {
            transform: scale(1.1);
            box-shadow: 0 4px 12px rgba(106, 142, 58, 0.4);
        }
        .btn-logout { 
            font-weight: 600; padding: 0.5rem 1rem; border-radius: 10px;
            background: #dc2626; color: white; border: none;
            transition: all 0.3s ease;
        }
        .btn-logout:hover { 
            background: #b91c1c; transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(220,38,38,0.3);
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg">
    <div class="container-fluid px-4 px-lg-5">
        <a class="navbar-brand" href="admin_dashboard.php">
            <i class="bi bi-speedometer2"></i>
            VolunteerConnect Admin
        </a>

        <div class="d-flex align-items-center gap-3 ms-auto">
            <?php if (isset($_SESSION['logged_in']) && ($_SESSION['role'] ?? '') === 'admin'): ?>
                <div class="d-flex align-items-center gap-3">
                    <div class="text-end">
                        <div class="fw-bold"><?= htmlspecialchars($_SESSION['name'] ?? 'Admin') ?></div>
                        <div class="small text-muted">Administrator</div>
                    </div>
                    <div class="user-avatar"><?= strtoupper(substr($_SESSION['name'] ?? 'A', 0, 2)) ?></div>
                    <a href="../auth/logout.php" class="btn btn-logout">Logout</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</nav>
