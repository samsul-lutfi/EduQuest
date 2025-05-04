<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../achievements/achievement_functions.php';

// Ensure user is logged in
requireLogin();

$userId = $_SESSION['user_id'];
$userRole = $_SESSION['user_role'];

// Get user's achievements (or for teachers/admins, recent achievements in the system)
if ($userRole === 'student') {
    $achievements = getUserAchievements($userId, 5);
    $totalAchievements = countUserAchievements($userId);
    
    // Get category distribution data for charts
    $categoryData = getCategoryDistribution($userId);
    $categories = array_column($categoryData, 'name');
    $categoryCounts = array_column($categoryData, 'count');
    
    // Get monthly progress data for charts
    $monthlyData = getMonthlyProgress($userId);
    $months = array_column($monthlyData, 'month');
    $monthlyCounts = array_column($monthlyData, 'count');
    
} else {
    // For teachers and admins, show recent system achievements
    $achievements = getRecentAchievements(10);
    $totalStudents = countStudents();
    $totalAchievements = countAllAchievements();
    
    // Get top students
    $topStudents = getTopStudents(5);
}

// Page title
$pageTitle = 'Dashboard - EduQuest';
?>

<?php include_once '../includes/header.php'; ?>

<div class="row">
    <!-- Sidebar -->
    <div class="col-lg-3">
        <?php include_once '../includes/sidebar.php'; ?>
    </div>
    
    <!-- Main content -->
    <div class="col-lg-9">
        <h1 class="mb-4"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</h1>
        
        <!-- Student Dashboard -->
        <?php if ($userRole === 'student'): ?>
        
        <div class="row">
            <div class="col-md-6 col-lg-4">
                <div class="dashboard-stat primary">
                    <h3><?php echo $totalAchievements; ?></h3>
                    <p>Total Achievements</p>
                    <div class="stat-icon">
                        <i class="fas fa-trophy"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="dashboard-stat info">
                    <h3><?php echo count($categories); ?></h3>
                    <p>Achievement Categories</p>
                    <div class="stat-icon">
                        <i class="fas fa-tags"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="dashboard-stat success">
                    <h3><?php echo $achievements ? $achievements[0]['title'] ?? 'None' : 'None'; ?></h3>
                    <p>Latest Achievement</p>
                    <div class="stat-icon">
                        <i class="fas fa-award"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card shadow-sm h-100">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-chart-line me-2"></i>Achievement Progress
                        </h5>
                    </div>
                    <div class="card-body">
                        <canvas id="progressChart" height="250"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card shadow-sm h-100">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-chart-pie me-2"></i>Achievement Categories
                        </h5>
                    </div>
                    <div class="card-body">
                        <canvas id="categoryChart" height="250"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Teacher/Admin Dashboard -->
        <?php else: ?>
        
        <div class="row">
            <div class="col-md-6 col-lg-3">
                <div class="dashboard-stat primary">
                    <h3><?php echo $totalStudents; ?></h3>
                    <p>Total Students</p>
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="dashboard-stat info">
                    <h3><?php echo $totalAchievements; ?></h3>
                    <p>Total Achievements</p>
                    <div class="stat-icon">
                        <i class="fas fa-trophy"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="dashboard-stat success">
                    <h3><?php echo round($totalAchievements / ($totalStudents ?: 1), 1); ?></h3>
                    <p>Avg. Achievements/Student</p>
                    <div class="stat-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="dashboard-stat warning">
                    <h3><?php echo count(getAllCategories()); ?></h3>
                    <p>Achievement Categories</p>
                    <div class="stat-icon">
                        <i class="fas fa-tags"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-trophy me-2"></i>Recently Added Achievements
                        </h5>
                        <a href="/achievements/add.php" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus-circle me-1"></i>Add New
                        </a>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Achievement</th>
                                        <th>Category</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($achievements)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center py-3">No achievements found.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($achievements as $achievement): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($achievement['student_name']); ?></td>
                                                <td><?php echo htmlspecialchars($achievement['title']); ?></td>
                                                <td>
                                                    <span class="badge bg-primary">
                                                        <?php echo htmlspecialchars($achievement['category_name']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo formatDate($achievement['achievement_date']); ?></td>
                                                <td>
                                                    <a href="/achievements/edit.php?id=<?php echo $achievement['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="/achievements/delete.php?id=<?php echo $achievement['id']; ?>" class="btn btn-sm btn-outline-danger btn-delete-achievement">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer text-end">
                        <a href="/achievements/view.php" class="btn btn-link">View All Achievements</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-medal me-2"></i>Top Performing Students
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                            <?php if (empty($topStudents)): ?>
                                <li class="list-group-item text-center py-3">No data available.</li>
                            <?php else: ?>
                                <?php foreach ($topStudents as $index => $student): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <span class="badge bg-<?php echo $index === 0 ? 'warning' : ($index === 1 ? 'secondary' : 'info'); ?> me-2">
                                                #<?php echo $index + 1; ?>
                                            </span>
                                            <?php echo htmlspecialchars($student['name']); ?>
                                        </div>
                                        <span class="badge bg-primary rounded-pill">
                                            <?php echo $student['achievement_count']; ?> achievements
                                        </span>
                                    </li>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </ul>
                    </div>
                    <div class="card-footer text-end">
                        <a href="/dashboard/students.php" class="btn btn-link">View All Students</a>
                    </div>
                </div>
            </div>
        </div>
        
        <?php endif; ?>
        
        <!-- Recent Activity Section (for all users) -->
        <div class="card shadow-sm mt-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-history me-2"></i>Recent Activity
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="timeline p-3">
                    <?php if (empty($achievements)): ?>
                        <div class="text-center py-3">No recent activity found.</div>
                    <?php else: ?>
                        <?php foreach ($achievements as $achievement): ?>
                            <div class="timeline-item">
                                <div class="timeline-badge">
                                    <i class="fas fa-trophy"></i>
                                </div>
                                <div class="timeline-date">
                                    <?php echo formatDate($achievement['achievement_date']); ?>
                                </div>
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title">
                                            <?php if ($userRole !== 'student'): ?>
                                                <strong><?php echo htmlspecialchars($achievement['student_name']); ?></strong> earned 
                                            <?php endif; ?>
                                            <?php echo htmlspecialchars($achievement['title']); ?>
                                        </h5>
                                        <p class="card-text">
                                            <?php echo htmlspecialchars($achievement['description']); ?>
                                        </p>
                                        <span class="badge bg-primary">
                                            <?php echo htmlspecialchars($achievement['category_name']); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($userRole === 'student'): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Achievement Progress Chart
    const progressCtx = document.getElementById('progressChart').getContext('2d');
    const progressChart = new Chart(progressCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($months); ?>,
            datasets: [{
                label: 'Achievements',
                data: <?php echo json_encode($monthlyCounts); ?>,
                borderColor: '#007bff',
                backgroundColor: 'rgba(0, 123, 255, 0.1)',
                borderWidth: 2,
                tension: 0.3,
                fill: true
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
    
    // Achievement Categories Chart
    const categoryCtx = document.getElementById('categoryChart').getContext('2d');
    const categoryChart = new Chart(categoryCtx, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode($categories); ?>,
            datasets: [{
                data: <?php echo json_encode($categoryCounts); ?>,
                backgroundColor: [
                    'rgba(255, 99, 132, 0.7)',
                    'rgba(54, 162, 235, 0.7)',
                    'rgba(255, 206, 86, 0.7)',
                    'rgba(75, 192, 192, 0.7)',
                    'rgba(153, 102, 255, 0.7)',
                    'rgba(255, 159, 64, 0.7)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            plugins: {
                legend: {
                    position: 'right'
                }
            }
        }
    });
});
</script>
<?php endif; ?>

<?php include_once '../includes/footer.php'; ?>
