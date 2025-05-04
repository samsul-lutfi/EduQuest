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
$name = '';
$email = '';

// Process registration form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate input
    $name = sanitizeInput($_POST['name'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = sanitizeInput($_POST['role'] ?? 'student'); // Get role from form
    
    // Validate input
    if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'All fields are required.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        // Check if email already exists
        $checkSql = "SELECT id FROM users WHERE email = '" . sanitizeInput($email) . "'";
        $checkResult = executeQuery($checkSql);
        
        if ($checkResult && numRows($checkResult) > 0) {
            $error = 'Email address is already registered.';
        } else {
            // Register the user
            $registerResult = registerUser($name, $email, $password, $role);
            
            if ($registerResult['success']) {
                $_SESSION['success'] = 'Registration successful! You can now log in.';
                redirect('/auth/login.php');
            } else {
                $error = $registerResult['message'];
            }
        }
    }
}

// Page title
$pageTitle = 'Register - EduQuest';
?>

<?php include_once '../includes/header.php'; ?>

<div class="row justify-content-center">
    <div class="col-lg-6 col-md-8">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white text-center py-3">
                <h4 class="mb-0"><i class="fas fa-user-plus me-2"></i><?php echo __('create_account'); ?></h4>
            </div>
            <div class="card-body p-4">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>
                
                <form method="POST" action="" class="needs-validation" novalidate>
                    <div class="mb-3">
                        <label for="name" class="form-label"><?php echo __('full_name'); ?></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                            <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" required autofocus>
                        </div>
                        <div class="invalid-feedback">Please enter your full name.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label"><?php echo __('email'); ?></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                        </div>
                        <div class="invalid-feedback">Please enter a valid email address.</div>
                        <small class="form-text text-muted">We'll never share your email with anyone else.</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="role" class="form-label"><?php echo __('role'); ?></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-user-tag"></i></span>
                            <select class="form-select" id="role" name="role" required>
                                <option value="student"><?php echo __('student'); ?></option>
                                <option value="teacher"><?php echo __('teacher'); ?></option>
                            </select>
                        </div>
                        <div class="invalid-feedback">Pilih peran Anda.</div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label"><?php echo __('password'); ?></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <div class="invalid-feedback">Please enter a password.</div>
                            <small class="form-text text-muted">At least 6 characters long.</small>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="confirm_password" class="form-label"><?php echo __('confirm_password'); ?></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                            <div class="invalid-feedback">Please confirm your password.</div>
                        </div>
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="terms" name="terms" required>
                        <label class="form-check-label" for="terms"><?php echo __('agree_terms'); ?> <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal"><?php echo __('terms_conditions'); ?></a></label>
                        <div class="invalid-feedback">You must agree before submitting.</div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-user-plus me-2"></i><?php echo __('register'); ?>
                        </button>
                    </div>
                </form>
            </div>
            <div class="card-footer text-center py-3">
                <div class="small">
                    <?php echo __('already_have_account'); ?> <a href="login.php"><?php echo __('login'); ?></a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Terms and Conditions Modal -->
<div class="modal fade" id="termsModal" tabindex="-1" aria-labelledby="termsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="termsModalLabel">Terms and Conditions</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h6>1. Acceptance of Terms</h6>
                <p>By registering for EduQuest, you agree to these Terms and Conditions.</p>
                
                <h6>2. User Accounts</h6>
                <p>You are responsible for maintaining the confidentiality of your account information and password.</p>
                
                <h6>3. Privacy Policy</h6>
                <p>Your use of EduQuest is also governed by our Privacy Policy.</p>
                
                <h6>4. Code of Conduct</h6>
                <p>Users must respect the academic integrity guidelines and not engage in any form of cheating or plagiarism.</p>
                
                <h6>5. Termination</h6>
                <p>We reserve the right to terminate accounts that violate these terms.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">I Understand</button>
            </div>
        </div>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>
