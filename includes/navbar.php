<?php
/**
 * Global Navigation Bar
 * DeskHero IT Help Desk System
 */
$currentPage = basename($_SERVER['PHP_SELF'] ?? '');
$isLoggedIn = is_logged_in();
$user = $isLoggedIn ? current_user() : null;
$role = $isLoggedIn ? current_user_role() : 'guest';
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top shadow-sm py-2">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center fw-bold text-white me-4" href="index.php">
            <span class="d-inline-flex align-items-center justify-content-center bg-primary rounded-3 text-white p-2 me-2" style="width: 36px; height: 36px;">
                <i class="bi bi-headset fs-5"></i>
            </span>
            <span>Desk<span class="text-primary">Hero</span></span>
        </a>

        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain" aria-controls="navbarMain" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarMain">
            <?php if ($isLoggedIn): ?>
                <ul class="navbar-nav me-auto mb-2 mb-lg-0 align-items-lg-center">
                    <li class="nav-item">
                        <a class="nav-link px-3 <?php echo ($currentPage === 'dashboard.php') ? 'active fw-semibold' : ''; ?>" href="dashboard.php">
                            <i class="bi bi-speedometer2 me-1"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link px-3 <?php echo ($currentPage === 'tickets.php' && empty($_GET['scope']) && empty($_GET['assigned'])) ? 'active fw-semibold' : ''; ?>" href="tickets.php">
                            <i class="bi bi-ticket-perforated me-1"></i> All Tickets
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link px-3 <?php echo ($currentPage === 'ticket-create.php') ? 'active fw-semibold' : ''; ?>" href="ticket-create.php">
                            <i class="bi bi-plus-circle me-1"></i> Submit Ticket
                        </a>
                    </li>

                    <?php if ($role === 'admin'): ?>
                        <li class="nav-item">
                            <a class="nav-link px-3 <?php echo ($currentPage === 'users.php' || $currentPage === 'user-form.php') ? 'active fw-semibold' : ''; ?>" href="users.php">
                                <i class="bi bi-people me-1"></i> Users
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link px-3 <?php echo ($currentPage === 'categories.php') ? 'active fw-semibold' : ''; ?>" href="categories.php">
                                <i class="bi bi-tags me-1"></i> Categories
                            </a>
                        </li>
                    <?php elseif ($role === 'it_staff'): ?>
                        <li class="nav-item">
                            <a class="nav-link px-3 <?php echo (isset($_GET['assigned']) && $_GET['assigned'] === 'my') ? 'active fw-semibold' : ''; ?>" href="tickets.php?assigned=my">
                                <i class="bi bi-person-check me-1"></i> My Assigned
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link px-3 <?php echo (isset($_GET['assigned']) && $_GET['assigned'] === 'unassigned') ? 'active fw-semibold' : ''; ?>" href="tickets.php?assigned=unassigned">
                                <i class="bi bi-inbox me-1"></i> Unassigned
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link px-3 <?php echo (isset($_GET['scope']) && $_GET['scope'] === 'my') ? 'active fw-semibold' : ''; ?>" href="tickets.php?scope=my">
                                <i class="bi bi-folder2 me-1"></i> My Tickets
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>

                <ul class="navbar-nav ms-auto align-items-lg-center">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center text-white" href="#" id="userMenu" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="d-inline-flex align-items-center justify-content-center bg-secondary bg-opacity-25 rounded-circle me-2" style="width: 32px; height: 32px;">
                                <i class="bi bi-person-fill text-light"></i>
                            </span>
                            <span class="fw-semibold me-2"><?php echo e($user['name']); ?></span>
                            <?php if ($role === 'admin'): ?>
                                <span class="badge bg-danger">Admin</span>
                            <?php elseif ($role === 'it_staff'): ?>
                                <span class="badge bg-primary">IT Staff</span>
                            <?php else: ?>
                                <span class="badge bg-success">User</span>
                            <?php endif; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2" aria-labelledby="userMenu">
                            <li class="px-3 py-2 border-bottom">
                                <div class="fw-bold text-dark"><?php echo e($user['name']); ?></div>
                                <div class="small text-muted"><?php echo e($user['email']); ?></div>
                                <?php if (!empty($user['department'])): ?>
                                    <div class="small text-muted"><i class="bi bi-building me-1"></i><?php echo e($user['department']); ?></div>
                                <?php endif; ?>
                            </li>
                            <li><a class="dropdown-item py-2" href="profile.php"><i class="bi bi-person-gear me-2 text-primary"></i>My Profile</a></li>
                            <li><a class="dropdown-item py-2" href="setup_check.php"><i class="bi bi-heart-pulse me-2 text-info"></i>System Health</a></li>
                            <li><hr class="dropdown-divider my-1"></li>
                            <li><a class="dropdown-item py-2 text-danger" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Sign Out</a></li>
                        </ul>
                    </li>
                </ul>
            <?php else: ?>
                <ul class="navbar-nav ms-auto align-items-lg-center">
                    <li class="nav-item">
                        <a class="nav-link px-3 <?php echo ($currentPage === 'login.php') ? 'active fw-semibold' : ''; ?>" href="login.php">
                            <i class="bi bi-box-arrow-in-right me-1"></i> Sign In
                        </a>
                    </li>
                    <li class="nav-item ms-lg-2">
                        <a class="btn btn-primary btn-sm px-3 shadow-sm" href="register.php">
                            <i class="bi bi-person-plus me-1"></i> Register
                        </a>
                    </li>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</nav>
