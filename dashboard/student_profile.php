<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../achievements/achievement_functions.php';

// Ensure user is logged in
requireLogin();

$userId = $_SESSION['user_id'];
$userRole = $_SESSION['user_role'];

// If admin or teacher viewing a student profile
if (($userRole === 'admin' || $userRole === 'teacher') && isset($_GET['id'])) {
    $viewingUserId = (int)$_GET['id'];
    $viewingOtherUser = true;
} else {
    $viewingUserId = $userId;
    $viewingOtherUser = false;
}

// Get user profile data
$userQuery = "SELECT * FROM users WHERE id = $viewingUserId";
$userResult = executeQuery($userQuery);

if (!$userResult || numRows($userResult) === 0) {
    $_SESSION['error'] = "User not found.";
    redirect('/dashboard/index.php');
}

$user = fetchAssoc($userResult);

// Get user achievements
$achievements = getUserAchievements($viewingUserId);
$totalAchievements = countUserAchievements($viewingUserId);

// Update profile logic
$successMessage = '';
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$viewingOtherUser) {
    // Check which form was submitted
    if (isset($_POST['update_profile'])) {
        // Update profile details
        $name = sanitizeInput($_POST['name']);
        $bio = sanitizeInput($_POST['bio']);
        
        $updateSql = "UPDATE users SET name = '$name', bio = '$bio' WHERE id = $userId";
        $updateResult = executeQuery($updateSql);
        
        if ($updateResult) {
            $successMessage = "Profile updated successfully.";
            $_SESSION['user_name'] = $name;
            
            // Refresh user data
            $userResult = executeQuery($userQuery);
            $user = fetchAssoc($userResult);
        } else {
            $errorMessage = "Failed to update profile.";
        }
    } elseif (isset($_POST['change_password'])) {
        // Change password
        $currentPassword = $_POST['current_password'];
        $newPassword = $_POST['new_password'];
        $confirmPassword = $_POST['confirm_password'];
        
        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            $errorMessage = "All password fields are required.";
        } elseif ($newPassword !== $confirmPassword) {
            $errorMessage = "New passwords do not match.";
        } elseif (strlen($newPassword) < 6) {
            $errorMessage = "New password must be at least 6 characters long.";
        } else {
            // Verify current password
            $passwordSql = "SELECT password FROM users WHERE id = $userId";
            $passwordResult = executeQuery($passwordSql);
            $userData = fetchAssoc($passwordResult);
            
            if (password_verify($currentPassword, $userData['password'])) {
                // Hash new password
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                
                // Update password
                $updatePasswordSql = "UPDATE users SET password = '$hashedPassword' WHERE id = $userId";
                $updatePasswordResult = executeQuery($updatePasswordSql);
                
                if ($updatePasswordResult) {
                    $successMessage = "Password changed successfully.";
                } else {
                    $errorMessage = "Failed to update password.";
                }
            } else {
                $errorMessage = "Current password is incorrect.";
            }
        }
    }
}

// Page title
$pageTitle = 'Student Profile - EduQuest';
?>

<?php include_once '../includes/header.php'; ?>

<div class="row">
    <!-- Sidebar -->
    <div class="col-lg-3">
        <?php include_once '../includes/sidebar.php'; ?>
    </div>
    
    <!-- Main content -->
    <div class="col-lg-9">
        <?php if (!empty($successMessage)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($successMessage); ?></div>
        <?php endif; ?>
        
        <?php if (!empty($errorMessage)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($errorMessage); ?></div>
        <?php endif; ?>
        
        <div class="profile-header rounded">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-md-3 text-center text-md-start">
                        <div class="profile-img-container mx-auto mx-md-0">
                            <!-- Using Font Awesome for a user avatar -->
                            <div class="profile-img d-flex justify-content-center align-items-center bg-light text-primary">
                                <i class="fas fa-user fa-5x"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-9 mt-3 mt-md-0 text-center text-md-start">
                        <h2><?php echo htmlspecialchars($user['name']); ?></h2>
                        <p class="mb-1"><?php echo ucfirst(htmlspecialchars($user['role'])); ?></p>
                        <p class="mb-1">
                            <i class="fas fa-trophy me-2"></i>
                            <strong><?php echo $totalAchievements; ?></strong> Achievements
                        </p>
                        <p class="mb-1">
                            <i class="fas fa-calendar-alt me-2"></i>Member since <?php echo formatDate($user['created_at'], 'F Y'); ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
            <!-- Profile Information -->
            <div class="col-md-6">
                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-user me-2"></i>Profile Information
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (!$viewingOtherUser): ?>
                            <form method="POST" action="">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Full Name</label>
                                    <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email Address</label>
                                    <input type="email" class="form-control" id="email" value="<?php echo htmlspecialchars($user['email']); ?>" readonly>
                                    <small class="text-muted">Email address cannot be changed.</small>
                                </div>
                                <div class="mb-3">
                                    <label for="bio" class="form-label">Bio</label>
                                    <textarea class="form-control" id="bio" name="bio" rows="4"><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                                </div>
                                <button type="submit" name="update_profile" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Update Profile
                                </button>
                            </form>
                        <?php else: ?>
                            <div class="row mb-3">
                                <div class="col-md-4 fw-bold">Full Name:</div>
                                <div class="col-md-8"><?php echo htmlspecialchars($user['name']); ?></div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-4 fw-bold">Email:</div>
                                <div class="col-md-8"><?php echo htmlspecialchars($user['email']); ?></div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-4 fw-bold">Role:</div>
                                <div class="col-md-8"><?php echo ucfirst(htmlspecialchars($user['role'])); ?></div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-4 fw-bold">Member Since:</div>
                                <div class="col-md-8"><?php echo formatDate($user['created_at']); ?></div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-4 fw-bold">Last Login:</div>
                                <div class="col-md-8">
                                    <?php echo $user['last_login'] ? formatDate($user['last_login']) : 'Never'; ?>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-4 fw-bold">Bio:</div>
                                <div class="col-md-8">
                                    <?php echo !empty($user['bio']) ? nl2br(htmlspecialchars($user['bio'])) : 'No bio available.'; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if (!$viewingOtherUser): ?>
                    <div class="card shadow-sm mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-lock me-2"></i>Change Password
                            </h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <div class="mb-3">
                                    <label for="current_password" class="form-label">Current Password</label>
                                    <input type="password" class="form-control" id="current_password" name="current_password" required>
                                </div>
                                <div class="mb-3">
                                    <label for="new_password" class="form-label">New Password</label>
                                    <input type="password" class="form-control" id="new_password" name="new_password" required>
                                </div>
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Confirm New Password</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                </div>
                                <button type="submit" name="change_password" class="btn btn-primary">
                                    <i class="fas fa-key me-2"></i>Change Password
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Achievements -->
            <div class="col-md-6">
                <div class="card shadow-sm mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-trophy me-2"></i>Recent Achievements
                        </h5>
                        <?php if (($userRole === 'admin' || $userRole === 'teacher') && $viewingOtherUser): ?>
                            <a href="/achievements/add.php?student_id=<?php echo $viewingUserId; ?>" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus-circle me-1"></i>Add Achievement
                            </a>
                        <?php endif; ?>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($achievements)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-award fa-3x text-muted mb-3"></i>
                                <p>No achievements yet.</p>
                                <?php if (($userRole === 'admin' || $userRole === 'teacher') && $viewingOtherUser): ?>
                                    <a href="/achievements/add.php?student_id=<?php echo $viewingUserId; ?>" class="btn btn-primary btn-sm">
                                        <i class="fas fa-plus-circle me-1"></i>Add First Achievement
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($achievements as $achievement): ?>
                                    <a href="/achievements/view.php?id=<?php echo $achievement['id']; ?>" class="list-group-item list-group-item-action">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1"><?php echo htmlspecialchars($achievement['title']); ?></h6>
                                            <small><?php echo formatDate($achievement['achievement_date'], 'M j, Y'); ?></small>
                                        </div>
                                        <p class="mb-1 text-truncate"><?php echo htmlspecialchars($achievement['description']); ?></p>
                                        <small class="text-muted">
                                            <span class="badge bg-primary"><?php echo htmlspecialchars($achievement['category_name']); ?></span>
                                        </small>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer text-end">
                        <a href="/achievements/view.php<?php echo $viewingOtherUser ? '?student_id=' . $viewingUserId : ''; ?>" class="btn btn-link">View All Achievements</a>
                    </div>
                </div>
                
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-chart-pie me-2"></i>Achievement Statistics
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($achievements)): ?>
                            <div class="text-center py-4">
                                <p>No achievement statistics available yet.</p>
                            </div>
                        <?php else: ?>
                            <canvas id="achievementsChart" height="250"></canvas>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($achievements)): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Get achievement data for chart
    const achievementData = <?php
        $categoryData = [];
        foreach ($achievements as $achievement) {
            $category = $achievement['category_name'];
            if (!isset($categoryData[$category])) {
                $categoryData[$category] = 0;
            }
            $categoryData[$category]++;
        }
        
        $labels = array_keys($categoryData);
        $data = array_values($categoryData);
        
        echo json_encode(['labels' => $labels, 'data' => $data]);
    ?>;
    
    // Create chart
    const ctx = document.getElementById('achievementsChart').getContext('2d');
    const achievementsChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: achievementData.labels,
            datasets: [{
                data: achievementData.data,
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
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                },
                title: {
                    display: true,
                    text: 'Achievements by Category'
                }
            }
        }
    });
});
</script>
<?php endif; ?>

<?php include_once '../includes/footer.php'; ?>
