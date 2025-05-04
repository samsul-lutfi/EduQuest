<?php
/**
 * Attempt to login a user
 * 
 * @param string $email The user's email
 * @param string $password The user's password
 * @return array An array containing success status and message
 */
function attemptLogin($email, $password) {
    global $conn;

    $email = sanitizeInput($email);

    // Get user by email with improved role handling
    $sql = "SELECT id, name, email, password, role FROM users WHERE email = $1";
    $result = pg_query_params($conn, $sql, array($email));

    if ($result && pg_num_rows($result) > 0) {
        $user = pg_fetch_assoc($result);
        
        error_log("Attempting password verification for user: " . $user['email']);
        error_log("Stored hash: " . $user['password']);
        
        // Verify password with debug
        $verified = password_verify($password, $user['password']);
        error_log("Password verification result: " . ($verified ? 'true' : 'false'));
        
        if ($verified) {
            // Password is correct, set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];

            // Debug log
            error_log("User logged in - ID: {$user['id']}, Role: {$user['role']}");

            // Update last login time
            $updateSql = "UPDATE users SET last_login = NOW() WHERE id = " . $user['id'];
            executeQuery($updateSql);

            return [
                'success' => true,
                'message' => 'Login successful'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Invalid password. Please try again.'
            ];
        }
    } else {
        return [
            'success' => false,
            'message' => 'Email not found. Please check your email or register for a new account.'
        ];
    }
}

/**
 * Register a new user
 * 
 * @param string $name The user's full name
 * @param string $email The user's email
 * @param string $password The user's password
 * @param string $role The user's role (default: student)
 * @return array An array containing success status and message
 */
function registerUser($name, $email, $password, $role = 'student') {
    global $conn;

    $name = sanitizeInput($name);
    $email = sanitizeInput($email);
    $role = sanitizeInput($role);

    // Hash the password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Insert the new user
    $sql = "INSERT INTO users (name, email, password, role, created_at) 
            VALUES ('$name', '$email', '$hashedPassword', '$role', NOW())";

    $result = executeQuery($sql);

    if ($result) {
        return [
            'success' => true,
            'message' => 'Registration successful',
            'user_id' => lastInsertId()
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Registration failed. Please try again later.'
        ];
    }
}

/**
 * Change a user's password
 * 
 * @param int $userId The user ID
 * @param string $currentPassword The current password
 * @param string $newPassword The new password
 * @return array An array containing success status and message
 */
function changePassword($userId, $currentPassword, $newPassword) {
    global $conn;

    // Get user's current password
    $sql = "SELECT password FROM users WHERE id = " . (int)$userId;
    $result = executeQuery($sql);

    if ($result && numRows($result) > 0) {
        $user = fetchAssoc($result);

        // Verify current password
        if (password_verify($currentPassword, $user['password'])) {
            // Hash the new password
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

            // Update password
            $updateSql = "UPDATE users SET password = '$hashedPassword' WHERE id = " . (int)$userId;
            $updateResult = executeQuery($updateSql);

            if ($updateResult) {
                return [
                    'success' => true,
                    'message' => 'Password changed successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to update password. Please try again.'
                ];
            }
        } else {
            return [
                'success' => false,
                'message' => 'Current password is incorrect.'
            ];
        }
    } else {
        return [
            'success' => false,
            'message' => 'User not found.'
        ];
    }
}

/**
 * Reset a user's password
 * 
 * @param string $email The user's email
 * @return array An array containing success status and message
 */
function resetPassword($email) {
    global $conn;

    $email = sanitizeInput($email);

    // Check if email exists
    $sql = "SELECT id, name FROM users WHERE email = '$email'";
    $result = executeQuery($sql);

    if ($result && numRows($result) > 0) {
        $user = fetchAssoc($result);

        // Generate a reset token
        $token = generateToken();
        $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));

        // Store the token in the database
        $tokenSql = "INSERT INTO password_resets (user_id, token, expires_at) 
                     VALUES (" . $user['id'] . ", '$token', '$expiry')";
        $tokenResult = executeQuery($tokenSql);

        if ($tokenResult) {
            // In a real application, you would send an email with the reset link
            // Here we just return the token for demonstration purposes
            return [
                'success' => true,
                'message' => 'Password reset link has been sent to your email.',
                'token' => $token // This would be removed in a production environment
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to create reset token. Please try again.'
            ];
        }
    } else {
        return [
            'success' => false,
            'message' => 'Email not found.'
        ];
    }
}

/**
 * Verify if a reset token is valid
 * 
 * @param string $token The reset token
 * @return array An array containing success status, message, and user ID if valid
 */
function verifyResetToken($token) {
    global $conn;

    $token = sanitizeInput($token);

    // Find the token
    $sql = "SELECT user_id, expires_at FROM password_resets 
            WHERE token = '$token' AND expires_at > NOW() AND used = 0";
    $result = executeQuery($sql);

    if ($result && numRows($result) > 0) {
        $resetData = fetchAssoc($result);

        return [
            'success' => true,
            'message' => 'Token is valid',
            'user_id' => $resetData['user_id']
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Token is invalid or has expired.'
        ];
    }
}

/**
 * Complete password reset with a valid token
 * 
 * @param string $token The reset token
 * @param string $newPassword The new password
 * @return array An array containing success status and message
 */
function completePasswordReset($token, $newPassword) {
    global $conn;

    $token = sanitizeInput($token);

    // Verify the token
    $verifyResult = verifyResetToken($token);

    if ($verifyResult['success']) {
        $userId = $verifyResult['user_id'];

        // Hash the new password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        // Update the user's password
        $updateSql = "UPDATE users SET password = '$hashedPassword' WHERE id = " . (int)$userId;
        $updateResult = executeQuery($updateSql);

        if ($updateResult) {
            // Mark the token as used
            $tokenSql = "UPDATE password_resets SET used = 1 WHERE token = '$token'";
            executeQuery($tokenSql);

            return [
                'success' => true,
                'message' => 'Password has been reset successfully.'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to update password. Please try again.'
            ];
        }
    } else {
        return $verifyResult;
    }
}
?>