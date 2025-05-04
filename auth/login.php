<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once 'auth_functions.php';

// Ensure proper port binding
if (php_sapi_name() === 'cli-server') {
    $_SERVER['SERVER_PORT'] = APP_PORT;
}

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('/dashboard/index.php');
}

$error = '';
$email = '';

// Process login form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate input with improved sanitization
    $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';
    
    error_log("Login attempt for email: " . $email);
    
    error_log("Login attempt for email: $email");
    
    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password.';
    } else {
        // Attempt to login
        $loginResult = attemptLogin($email, $password);
        
        if ($loginResult['success']) {
            $_SESSION['success'] = 'Login successful. Welcome back!';
            redirect('/dashboard/index.php');
        } else {
            $error = $loginResult['message'];
        }
    }
}

// Page title
$pageTitle = 'Login - EduQuest';
?>

<?php include_once '../includes/header.php'; ?>

<div class="row justify-content-center">
    <div class="col-lg-5 col-md-8">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white text-center py-3">
                <h4 class="mb-0"><i class="fas fa-user-lock me-2"></i>Login to EduQuest</h4>
            </div>
            <div class="card-body p-4">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <form method="POST" action="" class="needs-validation" novalidate>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required autofocus>
                        </div>
                        <div class="invalid-feedback">Please enter a valid email address.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password" required>
                            <button type="button" class="btn btn-outline-secondary toggle-password" data-target="#password">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="invalid-feedback">Please enter your password.</div>
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="remember" name="remember">
                        <label class="form-check-label" for="remember">Remember me</label>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-sign-in-alt me-2"></i>Login
                        </button>
                    </div>
                </form>
            </div>
            <div class="card-footer text-center py-3">
                <div class="small mb-2">
                    <a href="forgot_password.php">Forgot password?</a>
                </div>
                <div class="small">
                    Don't have an account? <a href="register.php">Register now</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>
