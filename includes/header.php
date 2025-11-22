<?php if (session_status() === PHP_SESSION_NONE) session_start(); ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VolunteerConnect - <?= isset($page_title) ? htmlspecialchars($page_title) : 'Make a Difference' ?></title>

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
        /* FORCE LOGO TO BE EXACTLY THE SAME EVERYWHERE */
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
        .nav-link {
            display: inline-block !important;
            font-weight: 400 !important;
            color: var(--muted) !important;
            padding: 0.6rem 1.3rem !important;
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        .nav-link:hover, .nav-link.active {
            color: var(--accent-1) !important;
            background: rgba(106, 142, 58, 0.1);
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
        a[href="profile.php"]:hover {
            opacity: 0.8;
        }
        .btn-login { 
            font-weight: 600; 
            padding: 0.7rem 1.6rem; 
            border-radius: 10px; 
            border: 1.5px solid var(--accent-1); 
            color: var(--accent-1); 
            background: transparent;
            font-size: 0.95rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            letter-spacing: 0.2px;
        }
        .btn-login:hover {
            background: var(--accent-1);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(106, 142, 58, 0.25);
            border-color: var(--accent-1);
        }
        .btn-join { 
            font-weight: 600; 
            padding: 0.7rem 1.8rem; 
            border-radius: 10px;
            background: var(--accent-1); 
            color: white !important;
            border: none;
            font-size: 0.95rem;
            box-shadow: 0 2px 8px rgba(106, 142, 58, 0.25);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            letter-spacing: 0.2px;
        }
        .btn-join:hover { 
            background: #5a7d32;
            transform: translateY(-2px); 
            box-shadow: 0 4px 12px rgba(106, 142, 58, 0.35);
        }
        .btn-outline-danger.btn-sm {
            border-radius: 10px;
            font-weight: 600;
            padding: 0.5rem 1.2rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .btn-outline-danger.btn-sm:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.25);
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg">
    <div class="container-fluid px-4 px-lg-5">
        <!-- LOGO — FORCED TO BE IDENTICAL EVERYWHERE -->
        <a class="navbar-brand" href="index.php">
            <i class="bi bi-heart-pulse-fill"></i>
            VolunteerConnect
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav mx-auto">
                <li class="nav-item"><a class="nav-link <?= (basename($_SERVER['SCRIPT_NAME']) === 'index.php' ? 'active' : '') ?>" href="index.php">Home</a></li>
                <li class="nav-item"><a class="nav-link <?= (basename($_SERVER['SCRIPT_NAME']) === 'public_browse.php' ? 'active' : '') ?>" href="public_browse.php">Browse</a></li>
                
                <?php if (isset($_SESSION['logged_in'])): ?>
                    <?php if (in_array($_SESSION['role'] ?? '', ['user','volunteer'])): ?>
                        <li class="nav-item"><a class="nav-link" href="my_applications.php">My Applications</a></li>
                    <?php endif; ?>
                    <?php if (in_array($_SESSION['role'] ?? '', ['organization','admin'])): ?>
                        <li class="nav-item"><a class="nav-link" href="post_opportunity.php">Post Event</a></li>
                        <li class="nav-item"><a class="nav-link" href="manage_events.php">Manage Events</a></li>
                    <?php endif; ?>
                <?php endif; ?>

                <li class="nav-item"><a class="nav-link <?= (basename($_SERVER['SCRIPT_NAME']) === 'leaderboard.php' ? 'active' : '') ?>" href="leaderboard.php">Leaderboard</a></li>
            </ul>

            <div class="d-flex align-items-center gap-3">
                <?php if (isset($_SESSION['logged_in'])): ?>
                    <div class="d-flex align-items-center gap-3">
                        <a href="profile.php" class="d-flex align-items-center gap-3 text-decoration-none" style="color: inherit;" title="View Profile">
                            <div class="text-end">
                                <div class="fw-bold"><?= htmlspecialchars($_SESSION['name'] ?? 'User') ?></div>
                            </div>
                            <div class="user-avatar"><?= strtoupper(substr($_SESSION['name'] ?? 'U', 0, 2)) ?></div>
                        </a>
                        <a href="auth/logout.php" class="btn btn-outline-danger btn-sm">Logout</a>
                    </div>
                <?php else: ?>
                    <a href="auth/login.php" class="btn btn-login">Login</a>
                    <a href="auth/register.php" class="btn btn-join">Join Free</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>