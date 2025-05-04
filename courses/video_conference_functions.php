<?php
/**
 * Functions related to video conferencing
 */

/**
 * Get all video conference providers
 * 
 * @param bool $enabledOnly Only get enabled providers
 * @return array An array of video conference providers
 */
function getVideoConferenceProviders($enabledOnly = true) {
    $enabledClause = $enabledOnly ? "WHERE enabled = TRUE" : "";
    
    $sql = "SELECT * FROM video_conference_providers $enabledClause ORDER BY name";
    
    $result = executeQuery($sql);
    
    if (!$result) {
        return [];
    }
    
    return fetchAllAssoc($result);
}

/**
 * Get a video conference provider by ID
 * 
 * @param int $providerId The provider ID
 * @return array|false The provider details or false if not found
 */
function getVideoConferenceProviderById($providerId) {
    $providerId = (int)$providerId;
    
    $sql = "SELECT * FROM video_conference_providers WHERE id = $providerId";
    
    $result = executeQuery($sql);
    
    if (!$result || pg_num_rows($result) == 0) {
        return false;
    }
    
    return pg_fetch_assoc($result);
}

/**
 * Get a video conference provider by name
 * 
 * @param string $providerName The provider name
 * @return array|false The provider details or false if not found
 */
function getVideoConferenceProviderByName($providerName) {
    $providerName = pg_escape_string($providerName);
    
    $sql = "SELECT * FROM video_conference_providers WHERE name = '$providerName'";
    
    $result = executeQuery($sql);
    
    if (!$result || pg_num_rows($result) == 0) {
        return false;
    }
    
    return pg_fetch_assoc($result);
}

/**
 * Update a video conference provider
 * 
 * @param int $providerId The provider ID
 * @param array $providerData The provider data
 * @return array Result with success status and message
 */
function updateVideoConferenceProvider($providerId, $providerData) {
    $providerId = (int)$providerId;
    
    // Check if provider exists
    $provider = getVideoConferenceProviderById($providerId);
    
    if (!$provider) {
        return [
            'success' => false,
            'message' => 'Provider not found.'
        ];
    }
    
    // Sanitize and prepare data
    $updateFields = [];
    
    if (isset($providerData['api_key'])) {
        $apiKey = pg_escape_string($providerData['api_key']);
        $updateFields[] = "api_key = '$apiKey'";
    }
    
    if (isset($providerData['api_secret'])) {
        $apiSecret = pg_escape_string($providerData['api_secret']);
        $updateFields[] = "api_secret = '$apiSecret'";
    }
    
    if (isset($providerData['enabled'])) {
        $enabled = $providerData['enabled'] ? 'TRUE' : 'FALSE';
        $updateFields[] = "enabled = $enabled";
    }
    
    if (isset($providerData['config'])) {
        $config = pg_escape_string($providerData['config']);
        $updateFields[] = "config = '$config'";
    }
    
    // Add updated_at timestamp
    $updateFields[] = "updated_at = CURRENT_TIMESTAMP";
    
    // If no fields to update, return success
    if (empty($updateFields)) {
        return [
            'success' => true,
            'message' => 'No changes made to provider.'
        ];
    }
    
    // Create update SQL
    $sql = "UPDATE video_conference_providers SET " . implode(", ", $updateFields) . " WHERE id = $providerId";
    
    $result = executeQuery($sql);
    
    if (!$result) {
        return [
            'success' => false,
            'message' => 'Failed to update provider. Please try again.'
        ];
    }
    
    return [
        'success' => true,
        'message' => 'Provider updated successfully.'
    ];
}

/**
 * Create a new video conference
 * 
 * @param array $conferenceData The conference data
 * @return array Result with success status, message, and conference ID (if successful)
 */
function createVideoConference($conferenceData) {
    // Validate required fields
    if (empty($conferenceData['course_id']) || 
        empty($conferenceData['title']) || 
        empty($conferenceData['provider_id']) || 
        empty($conferenceData['start_time']) ||
        empty($conferenceData['duration']) ||
        empty($conferenceData['host_id'])) {
        return [
            'success' => false,
            'message' => 'Missing required fields for creating a video conference.'
        ];
    }
    
    // Sanitize and prepare data
    $courseId = (int)$conferenceData['course_id'];
    $title = pg_escape_string($conferenceData['title']);
    $description = isset($conferenceData['description']) ? "'" . pg_escape_string($conferenceData['description']) . "'" : 'NULL';
    $providerId = (int)$conferenceData['provider_id'];
    $meetingId = isset($conferenceData['meeting_id']) ? "'" . pg_escape_string($conferenceData['meeting_id']) . "'" : 'NULL';
    $password = isset($conferenceData['password']) ? "'" . pg_escape_string($conferenceData['password']) . "'" : 'NULL';
    $joinUrl = isset($conferenceData['join_url']) ? "'" . pg_escape_string($conferenceData['join_url']) . "'" : 'NULL';
    $startTime = "'" . pg_escape_string($conferenceData['start_time']) . "'";
    $duration = (int)$conferenceData['duration'];
    $hostId = (int)$conferenceData['host_id'];
    $status = isset($conferenceData['status']) ? "'" . pg_escape_string($conferenceData['status']) . "'" : "'scheduled'";
    
    // Create insert SQL
    $sql = "INSERT INTO video_conferences (
                course_id, title, description, provider_id, meeting_id,
                password, join_url, start_time, duration, host_id,
                status, created_at
            ) VALUES (
                $courseId, '$title', $description, $providerId, $meetingId,
                $password, $joinUrl, $startTime, $duration, $hostId,
                $status, CURRENT_TIMESTAMP
            ) RETURNING id";
    
    $result = executeQuery($sql);
    
    if (!$result) {
        return [
            'success' => false,
            'message' => 'Failed to create video conference. Please try again.'
        ];
    }
    
    $row = pg_fetch_assoc($result);
    $conferenceId = $row['id'];
    
    // Automatically add course enrolled students as participants
    $sqlEnrolled = "SELECT student_id FROM student_courses WHERE course_id = $courseId";
    $resultEnrolled = executeQuery($sqlEnrolled);
    
    if ($resultEnrolled) {
        $enrolledStudents = fetchAllAssoc($resultEnrolled);
        
        foreach ($enrolledStudents as $student) {
            $studentId = $student['student_id'];
            
            $sqlParticipant = "INSERT INTO video_conference_participants (
                conference_id, user_id, attendance_status, created_at
            ) VALUES (
                $conferenceId, $studentId, 'invited', CURRENT_TIMESTAMP
            ) ON CONFLICT (conference_id, user_id) DO NOTHING";
            
            executeQuery($sqlParticipant);
        }
    }
    
    return [
        'success' => true,
        'message' => 'Video conference created successfully.',
        'conference_id' => $conferenceId
    ];
}

/**
 * Get video conferences for a course
 * 
 * @param int $courseId The course ID
 * @param string $status Optional status filter
 * @return array An array of video conferences
 */
function getCourseVideoConferences($courseId, $status = null) {
    $courseId = (int)$courseId;
    $statusClause = '';
    
    if ($status) {
        $status = pg_escape_string($status);
        $statusClause = "AND status = '$status'";
    }
    
    $sql = "SELECT vc.*, vcp.name AS provider_name, u.name AS host_name, 
                (SELECT COUNT(*) FROM video_conference_participants WHERE conference_id = vc.id) AS participant_count
            FROM video_conferences vc
            JOIN video_conference_providers vcp ON vc.provider_id = vcp.id
            JOIN users u ON vc.host_id = u.id
            WHERE vc.course_id = $courseId $statusClause
            ORDER BY vc.start_time ASC";
    
    $result = executeQuery($sql);
    
    if (!$result) {
        return [];
    }
    
    return fetchAllAssoc($result);
}

/**
 * Get a video conference by ID
 * 
 * @param int $conferenceId The conference ID
 * @return array|false The conference details or false if not found
 */
function getVideoConferenceById($conferenceId) {
    $conferenceId = (int)$conferenceId;
    
    $sql = "SELECT vc.*, vcp.name AS provider_name, u.name AS host_name, c.name AS course_name 
            FROM video_conferences vc
            JOIN video_conference_providers vcp ON vc.provider_id = vcp.id
            JOIN users u ON vc.host_id = u.id
            JOIN courses c ON vc.course_id = c.id
            WHERE vc.id = $conferenceId";
    
    $result = executeQuery($sql);
    
    if (!$result || pg_num_rows($result) == 0) {
        return false;
    }
    
    return pg_fetch_assoc($result);
}

/**
 * Update a video conference
 * 
 * @param int $conferenceId The conference ID
 * @param array $conferenceData The conference data
 * @return array Result with success status and message
 */
function updateVideoConference($conferenceId, $conferenceData) {
    $conferenceId = (int)$conferenceId;
    
    // Check if conference exists
    $conference = getVideoConferenceById($conferenceId);
    
    if (!$conference) {
        return [
            'success' => false,
            'message' => 'Video conference not found.'
        ];
    }
    
    // Sanitize and prepare data
    $updateFields = [];
    
    if (isset($conferenceData['title'])) {
        $title = pg_escape_string($conferenceData['title']);
        $updateFields[] = "title = '$title'";
    }
    
    if (isset($conferenceData['description'])) {
        $description = $conferenceData['description'] ? "'" . pg_escape_string($conferenceData['description']) . "'" : 'NULL';
        $updateFields[] = "description = $description";
    }
    
    if (isset($conferenceData['provider_id'])) {
        $providerId = (int)$conferenceData['provider_id'];
        $updateFields[] = "provider_id = $providerId";
    }
    
    if (isset($conferenceData['meeting_id'])) {
        $meetingId = $conferenceData['meeting_id'] ? "'" . pg_escape_string($conferenceData['meeting_id']) . "'" : 'NULL';
        $updateFields[] = "meeting_id = $meetingId";
    }
    
    if (isset($conferenceData['password'])) {
        $password = $conferenceData['password'] ? "'" . pg_escape_string($conferenceData['password']) . "'" : 'NULL';
        $updateFields[] = "password = $password";
    }
    
    if (isset($conferenceData['join_url'])) {
        $joinUrl = $conferenceData['join_url'] ? "'" . pg_escape_string($conferenceData['join_url']) . "'" : 'NULL';
        $updateFields[] = "join_url = $joinUrl";
    }
    
    if (isset($conferenceData['start_time'])) {
        $startTime = "'" . pg_escape_string($conferenceData['start_time']) . "'";
        $updateFields[] = "start_time = $startTime";
    }
    
    if (isset($conferenceData['duration'])) {
        $duration = (int)$conferenceData['duration'];
        $updateFields[] = "duration = $duration";
    }
    
    if (isset($conferenceData['status'])) {
        $status = pg_escape_string($conferenceData['status']);
        $updateFields[] = "status = '$status'";
    }
    
    if (isset($conferenceData['recording_url'])) {
        $recordingUrl = $conferenceData['recording_url'] ? "'" . pg_escape_string($conferenceData['recording_url']) . "'" : 'NULL';
        $updateFields[] = "recording_url = $recordingUrl";
    }
    
    // Add updated_at timestamp
    $updateFields[] = "updated_at = CURRENT_TIMESTAMP";
    
    // If no fields to update, return success
    if (empty($updateFields)) {
        return [
            'success' => true,
            'message' => 'No changes made to video conference.'
        ];
    }
    
    // Create update SQL
    $sql = "UPDATE video_conferences SET " . implode(", ", $updateFields) . " WHERE id = $conferenceId";
    
    $result = executeQuery($sql);
    
    if (!$result) {
        return [
            'success' => false,
            'message' => 'Failed to update video conference. Please try again.'
        ];
    }
    
    return [
        'success' => true,
        'message' => 'Video conference updated successfully.'
    ];
}

/**
 * Add a participant to a video conference
 * 
 * @param int $conferenceId The conference ID
 * @param int $userId The user ID
 * @param string $status The attendance status
 * @return array Result with success status and message
 */
function addVideoConferenceParticipant($conferenceId, $userId, $status = 'invited') {
    $conferenceId = (int)$conferenceId;
    $userId = (int)$userId;
    $status = pg_escape_string($status);
    
    // Check if conference exists
    $conference = getVideoConferenceById($conferenceId);
    
    if (!$conference) {
        return [
            'success' => false,
            'message' => 'Video conference not found.'
        ];
    }
    
    // Check if user exists
    $sql = "SELECT id FROM users WHERE id = $userId";
    $result = executeQuery($sql);
    
    if (!$result || pg_num_rows($result) == 0) {
        return [
            'success' => false,
            'message' => 'User not found.'
        ];
    }
    
    // Add participant
    $sql = "INSERT INTO video_conference_participants (
                conference_id, user_id, attendance_status, created_at
            ) VALUES (
                $conferenceId, $userId, '$status', CURRENT_TIMESTAMP
            ) ON CONFLICT (conference_id, user_id) 
            DO UPDATE SET 
                attendance_status = '$status',
                updated_at = CURRENT_TIMESTAMP";
    
    $result = executeQuery($sql);
    
    if (!$result) {
        return [
            'success' => false,
            'message' => 'Failed to add participant. Please try again.'
        ];
    }
    
    return [
        'success' => true,
        'message' => 'Participant added successfully.'
    ];
}

/**
 * Update a participant's attendance status
 * 
 * @param int $conferenceId The conference ID
 * @param int $userId The user ID
 * @param string $status The attendance status
 * @param string $joinTime Optional join time
 * @param string $leaveTime Optional leave time
 * @param int $duration Optional duration in minutes
 * @return array Result with success status and message
 */
function updateParticipantAttendance($conferenceId, $userId, $status, $joinTime = null, $leaveTime = null, $duration = null) {
    $conferenceId = (int)$conferenceId;
    $userId = (int)$userId;
    $status = pg_escape_string($status);
    
    // Prepare update fields
    $updateFields = ["attendance_status = '$status'"];
    
    if ($joinTime) {
        $joinTime = "'" . pg_escape_string($joinTime) . "'";
        $updateFields[] = "join_time = $joinTime";
    }
    
    if ($leaveTime) {
        $leaveTime = "'" . pg_escape_string($leaveTime) . "'";
        $updateFields[] = "leave_time = $leaveTime";
    }
    
    if ($duration !== null) {
        $duration = (int)$duration;
        $updateFields[] = "duration = $duration";
    }
    
    $updateFields[] = "updated_at = CURRENT_TIMESTAMP";
    
    // Create update SQL
    $sql = "UPDATE video_conference_participants SET 
            " . implode(", ", $updateFields) . " 
            WHERE conference_id = $conferenceId AND user_id = $userId";
    
    $result = executeQuery($sql);
    
    if (!$result) {
        return [
            'success' => false,
            'message' => 'Failed to update participant attendance. Please try again.'
        ];
    }
    
    return [
        'success' => true,
        'message' => 'Participant attendance updated successfully.'
    ];
}

/**
 * Get participants for a conference
 * 
 * @param int $conferenceId The conference ID
 * @return array An array of participants
 */
function getVideoConferenceParticipants($conferenceId) {
    $conferenceId = (int)$conferenceId;
    
    $sql = "SELECT vcp.*, u.name, u.email, u.role
            FROM video_conference_participants vcp
            JOIN users u ON vcp.user_id = u.id
            WHERE vcp.conference_id = $conferenceId
            ORDER BY u.name";
    
    $result = executeQuery($sql);
    
    if (!$result) {
        return [];
    }
    
    return fetchAllAssoc($result);
}

/**
 * Get upcoming video conferences for a user
 * 
 * @param int $userId The user ID
 * @param int $limit Optional limit
 * @return array An array of upcoming conferences
 */
/**
 * Get user permissions for video conferences based on role
 */
function getUserVideoConferencePermissions($userId) {
    $sql = "SELECT role FROM users WHERE id = " . (int)$userId;
    $result = executeQuery($sql);
    $user = pg_fetch_assoc($result);
    
    $permissions = [
        'can_create' => false,
        'can_edit' => false,
        'can_delete' => false,
        'can_manage_participants' => false,
        'can_record' => false,
        'can_view_recordings' => false
    ];
    
    switch ($user['role']) {
        case 'admin':
            $permissions = array_fill_keys(array_keys($permissions), true);
            break;
        case 'teacher':
            $permissions['can_create'] = true;
            $permissions['can_edit'] = true;
            $permissions['can_manage_participants'] = true;
            $permissions['can_record'] = true;
            $permissions['can_view_recordings'] = true;
            break;
        case 'class_admin':
            $permissions['can_create'] = true;
            $permissions['can_manage_participants'] = true;
            $permissions['can_view_recordings'] = true;
            break;
        case 'observer':
            $permissions['can_view_recordings'] = true;
            break;
    }
    
    return $permissions;
}

function getUserUpcomingConferences($userId, $limit = 5) {
    $userId = (int)$userId;
    $limit = (int)$limit;
    $now = date('Y-m-d H:i:s');
    
    $sql = "SELECT vc.*, c.name AS course_name, vcp.name AS provider_name, u.name AS host_name
            FROM video_conferences vc
            JOIN courses c ON vc.course_id = c.id
            JOIN video_conference_providers vcp ON vc.provider_id = vcp.id
            JOIN users u ON vc.host_id = u.id
            JOIN video_conference_participants vcp2 ON vc.id = vcp2.conference_id
            WHERE vcp2.user_id = $userId 
            AND vc.start_time > '$now'
            AND vc.status = 'scheduled'
            ORDER BY vc.start_time ASC
            LIMIT $limit";
    
    $result = executeQuery($sql);
    
    if (!$result) {
        return [];
    }
    
    return fetchAllAssoc($result);
}

/**
 * Get video conferences in a date range for a user
 * 
 * @param int $userId The user ID
 * @param string $startDate Start date (Y-m-d)
 * @param string $endDate End date (Y-m-d)
 * @return array An array of conferences
 */


function getUserConferencesInDateRange($userId, $startDate, $endDate) {
    global $conn;
    $userId = (int)$userId;
    $startDate = pg_escape_string($conn, $startDate);
    $endDate = pg_escape_string($conn, $endDate);
    
    $sql = "SELECT vc.*, c.name AS course_name, vcp.name AS provider_name, u.name AS host_name
            FROM video_conferences vc
            JOIN courses c ON vc.course_id = c.id
            JOIN video_conference_providers vcp ON vc.provider_id = vcp.id
            JOIN users u ON vc.host_id = u.id
            JOIN video_conference_participants vcp2 ON vc.id = vcp2.conference_id
            WHERE vcp2.user_id = $userId 
            AND vc.start_time BETWEEN '$startDate 00:00:00' AND '$endDate 23:59:59'
            ORDER BY vc.start_time ASC";
    
    $result = executeQuery($sql);
    
    if (!$result) {
        return [];
    }
    
    return fetchAllAssoc($result);
}

/**
 * Format a conference date in user-friendly format
 * 
 * @param string $dateTime The date time string
 * @param string $format Optional format
 * @return string Formatted date
 */
function formatConferenceDate($dateTime, $format = 'd M Y, H:i') {
    return date($format, strtotime($dateTime));
}

/**
 * Calculate conference duration in human-readable format
 * 
 * @param int $minutes Duration in minutes
 * @return string Human-readable duration
 */
function formatConferenceDuration($minutes) {
    if ($minutes < 60) {
        return $minutes . ' menit';
    }
    
    $hours = floor($minutes / 60);
    $remainingMinutes = $minutes % 60;
    
    if ($remainingMinutes > 0) {
        return $hours . ' jam ' . $remainingMinutes . ' menit';
    }
    
    return $hours . ' jam';
}