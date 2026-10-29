<?php
/**
 * Global Navigation Bar - Radiant Glowing Edition
 * DeskHero IT Help Desk System
 */
$currentPage = basename($_SERVER['PHP_SELF'] ?? '');
$isLoggedIn = is_logged_in();
$user = $isLoggedIn ? current_user() : null;
$role = $isLoggedIn ? current_user_role() : 'guest';
?>
<nav class="navbar navbar-expand-lg sticky-top glow-navbar py-2">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center fw-bold me-4" href="index.php">
            <span class="brand-glow-icon d-inline-flex align-items-center justify-content-center rounded-3 text-white me-2">
                <i class="bi bi-headset fs-5"></i>
            </span>
            <span class="brand-title">Desk<span class="brand-gradient-text">Hero</span></span>
            <span class="live-status-dot ms-2" title="System Online"></span>
        </a>

        <div class="d-flex align-items-center d-lg-none gap-2">
            <!-- Mobile Theme Toggle -->
            <button class="btn btn-sm btn-theme-toggle rounded-pill px-2 py-1" id="themeToggleBtnMobile" type="button" aria-label="Toggle theme">
                <i class="bi bi-moon-stars-fill"></i>
            </button>
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain" aria-controls="navbarMain" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
        </div>

        <div class="collapse navbar-collapse" id="navbarMain">
            <?php if ($isLoggedIn): ?>
                <ul class="navbar-nav me-auto mb-2 mb-lg-0 align-items-lg-center">
                    <li class="nav-item">
                        <a class="nav-link px-3 nav-glow-link <?php echo ($currentPage === 'dashboard.php') ? 'active' : ''; ?>" href="dashboard.php">
                            <i class="bi bi-speedometer2 me-1"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link px-3 nav-glow-link <?php echo ($currentPage === 'tickets.php' && empty($_GET['scope']) && empty($_GET['assigned'])) ? 'active' : ''; ?>" href="tickets.php">
                            <i class="bi bi-ticket-perforated me-1"></i> All Tickets
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link px-3 nav-glow-link <?php echo ($currentPage === 'ticket-create.php') ? 'active' : ''; ?>" href="ticket-create.php">
                            <i class="bi bi-plus-circle me-1"></i> Submit Ticket
                        </a>
                    </li>

                    <?php if ($role === 'admin'): ?>
                        <li class="nav-item">
                            <a class="nav-link px-3 nav-glow-link <?php echo ($currentPage === 'users.php' || $currentPage === 'user-form.php') ? 'active' : ''; ?>" href="users.php">
                                <i class="bi bi-people me-1"></i> Users
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link px-3 nav-glow-link <?php echo ($currentPage === 'categories.php') ? 'active' : ''; ?>" href="categories.php">
                                <i class="bi bi-tags me-1"></i> Categories
                            </a>
                        </li>
                    <?php elseif ($role === 'it_staff'): ?>
                        <li class="nav-item">
                            <a class="nav-link px-3 nav-glow-link <?php echo (isset($_GET['assigned']) && $_GET['assigned'] === 'my') ? 'active' : ''; ?>" href="tickets.php?assigned=my">
                                <i class="bi bi-person-check me-1"></i> My Assigned
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link px-3 nav-glow-link <?php echo (isset($_GET['assigned']) && $_GET['assigned'] === 'unassigned') ? 'active' : ''; ?>" href="tickets.php?assigned=unassigned">
                                <i class="bi bi-inbox me-1"></i> Unassigned
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link px-3 nav-glow-link <?php echo (isset($_GET['scope']) && $_GET['scope'] === 'my') ? 'active' : ''; ?>" href="tickets.php?scope=my">
                                <i class="bi bi-folder2 me-1"></i> My Tickets
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>

                <ul class="navbar-nav ms-auto align-items-lg-center gap-2">
                    <!-- Desktop Theme Switcher Button -->
                    <li class="nav-item d-none d-lg-block">
                        <button class="btn btn-sm btn-theme-toggle rounded-pill px-3 py-1" id="themeToggleBtn" type="button" title="Switch Theme">
                            <i class="bi bi-moon-stars-fill me-1" id="themeIcon"></i>
                            <span id="themeText">Glow Dark</span>
                        </button>
                    </li>

                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center glow-user-pill px-3 py-1 rounded-pill" href="#" id="userMenu" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="d-inline-flex align-items-center justify-content-center user-avatar-circle me-2">
                                <i class="bi bi-person-fill"></i>
                            </span>
                            <span class="fw-semibold me-2"><?php echo e($user['name']); ?></span>
                            <?php if ($role === 'admin'): ?>
                                <span class="badge badge-role-glow badge-role-admin">Admin</span>
                            <?php elseif ($role === 'it_staff'): ?>
                                <span class="badge badge-role-glow badge-role-staff">IT Staff</span>
                            <?php else: ?>
                                <span class="badge badge-role-glow badge-role-user">User</span>
                            <?php endif; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 glow-dropdown mt-2" aria-labelledby="userMenu">
                            <li class="px-3 py-2 border-bottom border-secondary border-opacity-25">
                                <div class="fw-bold text-white"><?php echo e($user['name']); ?></div>
                                <div class="small text-muted"><?php echo e($user['email']); ?></div>
                                <?php if (!empty($user['department'])): ?>
                                    <div class="small text-muted"><i class="bi bi-building me-1 text-primary"></i><?php echo e($user['department']); ?></div>
                                <?php endif; ?>
                            </li>
                            <li><a class="dropdown-item py-2" href="profile.php"><i class="bi bi-person-gear me-2 text-primary"></i>My Profile</a></li>
                            <li><a class="dropdown-item py-2" href="setup_check.php"><i class="bi bi-heart-pulse me-2 text-info"></i>System Health</a></li>
                            <li><hr class="dropdown-divider my-1 border-secondary border-opacity-25"></li>
                            <li><a class="dropdown-item py-2 text-danger" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Sign Out</a></li>
                        </ul>
                    </li>
                </ul>
            <?php else: ?>
                <ul class="navbar-nav ms-auto align-items-lg-center gap-2">
                    <li class="nav-item d-none d-lg-block">
                        <button class="btn btn-sm btn-theme-toggle rounded-pill px-3 py-1" id="themeToggleBtn" type="button" title="Switch Theme">
                            <i class="bi bi-moon-stars-fill me-1" id="themeIcon"></i>
                            <span id="themeText">Glow Dark</span>
                        </button>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link px-3 nav-glow-link <?php echo ($currentPage === 'login.php') ? 'active' : ''; ?>" href="login.php">
                            <i class="bi bi-box-arrow-in-right me-1"></i> Sign In
                        </a>
                    </li>
                    <li class="nav-item ms-lg-2">
                        <a class="btn btn-glow-primary btn-sm px-4 shadow" href="register.php">
                            <i class="bi bi-person-plus me-1"></i> Register
                        </a>
                    </li>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</nav>
