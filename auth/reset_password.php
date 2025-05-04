<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once 'auth_functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('/dashboard/index.php');
}

$error = '';
$success = '';

// Check if token is valid
if (!isset($_GET['token']) || empty($_GET['token'])) {
    $_SESSION['error'] = "Invalid or missing reset token. Please request a new password reset link.";
    redirect('/auth/forgot_password.php');
}

$token = sanitizeInput($_GET['token']);
$verifyResult = verifyResetToken($token);

if (!$verifyResult['success']) {
    $_SESSION['error'] = $verifyResult['message'];
    redirect('/auth/forgot_password.php');
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (empty($password) || empty($confirmPassword)) {
        $error = 'Please enter and confirm your new password.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } else {
        // Reset the password
        $resetResult = completePasswordReset($token, $password);
        
        if ($resetResult['success']) {
            $_SESSION['success'] = $resetResult['message'] . ' You can now log in with your new password.';
            redirect('/auth/login.php');
        } else {
            $error = $resetResult['message'];
        }
    }
}

// Page title
$pageTitle = 'Reset Password - EduQuest';
?>

<?php include_once '../includes/header.php'; ?>

<div class="row justify-content-center">
    <div class="col-lg-5 col-md-8">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white text-center py-3">
                <h4 class="mb-0"><i class="fas fa-key me-2"></i>Reset Password</h4>
            </div>
            <div class="card-body p-4">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <p class="text-muted mb-4">
                    Create a new password for your account.
                </p>
                
                <form method="POST" action="" class="needs-validation" novalidate>
                    <div class="mb-3">
                        <label for="password" class="form-label">New Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password" required>
                            <button type="button" class="btn btn-outline-secondary toggle-password" data-target="#password">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="invalid-feedback">Please enter a password.</div>
                        <small class="form-text text-muted">At least 6 characters long.</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            <button type="button" class="btn btn-outline-secondary toggle-password" data-target="#confirm_password">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="invalid-feedback">Please confirm your password.</div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Reset Password
                        </button>
                    </div>
                </form>
            </div>
            <div class="card-footer text-center py-3">
                <div class="small">
                    <a href="login.php"><i class="fas fa-arrow-left me-1"></i>Back to Login</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>
