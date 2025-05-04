<div class="list-group mb-4">
    <!-- Main Menu Section -->
    <div class="menu-section">
                <div class="menu-header" data-target="main-menu">
            <span><i class="fas fa-bars me-2"></i>Menu Utama</span>
            <i class="fas fa-chevron-down menu-icon"></i>
        </div>
        <div class="menu-content show" id="main-menu">
            <a href="/dashboard/index.php" class="list-group-item list-group-item-action <?php echo strpos($_SERVER['PHP_SELF'], '/dashboard/index.php') !== false ? 'active' : ''; ?>">
                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
            </a>
            <a href="/activities/index.php" class="list-group-item list-group-item-action <?php echo strpos($_SERVER['PHP_SELF'], '/activities/index.php') !== false ? 'active' : ''; ?>">
                <i class="fas fa-th-list me-2"></i>Modul Aktifitas
            </a>
            <a href="/courses/index.php" class="list-group-item list-group-item-action <?php echo strpos($_SERVER['PHP_SELF'], '/courses/index.php') !== false ? 'active' : ''; ?>">
                <i class="fas fa-book me-2"></i>Courses
            </a>
            <a href="/courses/contents.php?action=list_all" class="list-group-item list-group-item-action <?php echo strpos($_SERVER['PHP_SELF'], '/courses/contents.php') !== false ? 'active' : ''; ?>">
                <i class="fas fa-graduation-cap me-2"></i>Nilai
            </a>
            <a href="/courses/video_conference.php?action=dashboard" class="list-group-item list-group-item-action <?php echo strpos($_SERVER['PHP_SELF'], '/courses/video_conference.php') !== false ? 'active' : ''; ?>">
                <i class="fas fa-video me-2"></i>Video Conference
            </a>
        </div>
    </div>
    <!-- Student Dashboard Section -->
    <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'student'): ?>
    <div class="menu-section">
        <div class="menu-header" data-target="student-menu">
            <span><i class="fas fa-graduation-cap me-2"></i>Student Dashboard</span>
            <i class="fas fa-chevron-down menu-icon"></i>
        </div>
        <div class="menu-content show" id="student-menu">
            <a href="/achievements/view.php" class="list-group-item list-group-item-action <?php echo strpos($_SERVER['PHP_SELF'], '/achievements/view.php') !== false ? 'active' : ''; ?>">
                <i class="fas fa-trophy me-2"></i>My Achievements
            </a>
            <a href="/dashboard/student_profile.php" class="list-group-item list-group-item-action <?php echo strpos($_SERVER['PHP_SELF'], '/dashboard/student_profile.php') !== false ? 'active' : ''; ?>">
                <i class="fas fa-user me-2"></i>My Profile
            </a>
        </div>
    </div>
    <?php endif; ?>

    <!-- Admin Section -->
    <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
    <div class="menu-section">
        <div class="menu-header" data-target="admin-menu">
            <span><i class="fas fa-lock me-2"></i>Admin Panel</span>
            <i class="fas fa-chevron-down menu-icon"></i>
        </div>
        <div class="menu-content show" id="admin-menu">
            <a href="/admin/users.php" class="list-group-item list-group-item-action <?php echo strpos($_SERVER['PHP_SELF'], '/admin/users.php') !== false ? 'active' : ''; ?>">
                <i class="fas fa-user-cog me-2"></i>Manage Users
            </a>
            <a href="/admin/categories.php" class="list-group-item list-group-item-action <?php echo strpos($_SERVER['PHP_SELF'], '/admin/categories.php') !== false ? 'active' : ''; ?>">
                <i class="fas fa-tags me-2"></i>Achievement Categories
            </a>
            <a href="/admin/reports.php" class="list-group-item list-group-item-action <?php echo strpos($_SERVER['PHP_SELF'], '/admin/reports.php') !== false ? 'active' : ''; ?>">
                <i class="fas fa-chart-bar me-2"></i>Reports & Analytics
            </a>
            <a href="/database/run_migration.php" class="list-group-item list-group-item-action <?php echo strpos($_SERVER['PHP_SELF'], '/database/run_migration.php') !== false ? 'active' : ''; ?>">
                <i class="fas fa-database me-2"></i>Database Migrations
            </a>
        </div>
    </div>

    <div class="alert alert-warning mt-2 mb-2 p-2">
        <i class="fas fa-exclamation-triangle me-1"></i>
        <small>Silakan jalankan migrasi database agar semua fitur berfungsi dengan baik</small>
    </div>
    <?php endif; ?>
</div>

<!-- Optional: Add this script at the bottom of your sidebar.php -->
<script>
    var coll = document.getElementsByClassName("collapsible");
    for (let i = 0; i < coll.length; i++) {
        coll[i].addEventListener("click", function() {
            this.classList.toggle("active");
            var content = this.nextElementSibling;
            if (content.style.display === "block") {
                content.style.display = "none";
            } else {
                content.style.display = "block";
            }
        });
    }
</script>

