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
$email = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizeInput($_POST['email'] ?? '');
    
    if (empty($email)) {
        $error = 'Please enter your email address.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        // Attempt to send password reset email
        $resetResult = resetPassword($email);
        
        if ($resetResult['success']) {
            $success = $resetResult['message'];
            
            // In a real application, we would send an email with the reset link
            // For development purposes, display the token (this would be removed in production)
            $resetToken = $resetResult['token'] ?? '';
            $resetLink = '/auth/reset_password.php?token=' . $resetToken;
            
            // This is just for development/testing - would be removed in production
            $success .= ' For development: <a href="' . $resetLink . '">Reset link</a>';
        } else {
            $error = $resetResult['message'];
        }
    }
}

// Page title
$pageTitle = 'Forgot Password - EduQuest';
?>

<?php include_once '../includes/header.php'; ?>

<div class="row justify-content-center">
    <div class="col-lg-5 col-md-8">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white text-center py-3">
                <h4 class="mb-0"><i class="fas fa-key me-2"></i>Forgot Password</h4>
            </div>
            <div class="card-body p-4">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php else: ?>
                    <p class="text-muted mb-4">
                        Enter your email address and we'll send you a link to reset your password.
                    </p>
                    
                    <form method="POST" action="" class="needs-validation" novalidate>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required autofocus>
                            </div>
                            <div class="invalid-feedback">Please enter a valid email address.</div>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane me-2"></i>Send Reset Link
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
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
