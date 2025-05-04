<?php
/**
 * Functions related to course content management
 */

/**
 * Get all course contents for a specific course
 * 
 * @param int $courseId The course ID
 * @return array An array of course contents
 */
function getCourseContents($courseId) {
    $courseId = (int)$courseId;
    
    $sql = "SELECT * FROM course_contents 
            WHERE course_id = $courseId 
            ORDER BY order_index ASC, start_date ASC";
    
    $result = executeQuery($sql);
    
    if (!$result) {
        return [];
    }
    
    return fetchAllAssoc($result);
}

/**
 * Get a specific course content by ID
 * 
 * @param int $contentId The content ID
 * @return array|false The content details or false if not found
 */
function getContentById($contentId) {
    $contentId = (int)$contentId;
    
    $sql = "SELECT cc.*, c.name AS course_name, c.teacher_id
            FROM course_contents cc
            JOIN courses c ON cc.course_id = c.id
            WHERE cc.id = $contentId";
    
    $result = executeQuery($sql);
    
    if (!$result || pg_num_rows($result) == 0) {
        return false;
    }
    
    return pg_fetch_assoc($result);
}

/**
 * Add a new course content
 * 
 * @param array $contentData The content data
 * @return array Result with success status and message
 */
function addCourseContent($contentData) {
    // Validate required fields
    if (empty($contentData['course_id']) || empty($contentData['title']) || empty($contentData['content_type'])) {
        return [
            'success' => false,
            'message' => 'Course ID, title, and content type are required.'
        ];
    }
    
    // Sanitize and prepare data
    $courseId = (int)$contentData['course_id'];
    $title = pg_escape_string($contentData['title']);
    $contentType = pg_escape_string($contentData['content_type']);
    $description = isset($contentData['description']) ? pg_escape_string($contentData['description']) : '';
    $filePath = isset($contentData['file_path']) ? pg_escape_string($contentData['file_path']) : null;
    $startDate = isset($contentData['start_date']) && !empty($contentData['start_date']) ? 
                  "'" . pg_escape_string($contentData['start_date']) . "'" : 'NULL';
    $endDate = isset($contentData['end_date']) && !empty($contentData['end_date']) ? 
                "'" . pg_escape_string($contentData['end_date']) . "'" : 'NULL';
    $status = isset($contentData['status']) ? pg_escape_string($contentData['status']) : 'draft';
    $orderIndex = isset($contentData['order_index']) ? (int)$contentData['order_index'] : 0;
    
    // Get the next order index if not provided
    if ($orderIndex == 0) {
        $sqlMaxOrder = "SELECT MAX(order_index) AS max_order FROM course_contents WHERE course_id = $courseId";
        $resultMaxOrder = executeQuery($sqlMaxOrder);
        
        if ($resultMaxOrder && ($rowMaxOrder = pg_fetch_assoc($resultMaxOrder))) {
            $orderIndex = $rowMaxOrder['max_order'] ? $rowMaxOrder['max_order'] + 1 : 1;
        } else {
            $orderIndex = 1;
        }
    }
    
    // Create insert SQL
    $sql = "INSERT INTO course_contents (
                course_id, title, content_type, description, file_path,
                start_date, end_date, status, order_index, created_at
            ) VALUES (
                $courseId, '$title', '$contentType', '$description', " . 
                ($filePath ? "'$filePath'" : "NULL") . ", 
                $startDate, $endDate, '$status', $orderIndex, CURRENT_TIMESTAMP
            ) RETURNING id";
    
    $result = executeQuery($sql);
    
    if (!$result) {
        return [
            'success' => false,
            'message' => 'Failed to add course content. Please try again.'
        ];
    }
    
    $row = pg_fetch_assoc($result);
    
    return [
        'success' => true,
        'message' => 'Course content added successfully.',
        'content_id' => $row['id']
    ];
}

/**
 * Update a course content
 * 
 * @param int $contentId The content ID
 * @param array $contentData The content data
 * @return array Result with success status and message
 */
function updateCourseContent($contentId, $contentData) {
    $contentId = (int)$contentId;
    
    // Check if content exists
    $content = getContentById($contentId);
    
    if (!$content) {
        return [
            'success' => false,
            'message' => 'Course content not found.'
        ];
    }
    
    // Sanitize and prepare data
    $title = isset($contentData['title']) ? "title = '" . pg_escape_string($contentData['title']) . "'" : '';
    $contentType = isset($contentData['content_type']) ? "content_type = '" . pg_escape_string($contentData['content_type']) . "'" : '';
    $description = isset($contentData['description']) ? "description = '" . pg_escape_string($contentData['description']) . "'" : '';
    
    $filePath = '';
    if (isset($contentData['file_path'])) {
        $filePath = $contentData['file_path'] ? "file_path = '" . pg_escape_string($contentData['file_path']) . "'" : "file_path = NULL";
    }
    
    $startDate = '';
    if (isset($contentData['start_date'])) {
        $startDate = !empty($contentData['start_date']) ? 
            "start_date = '" . pg_escape_string($contentData['start_date']) . "'" : 
            "start_date = NULL";
    }
    
    $endDate = '';
    if (isset($contentData['end_date'])) {
        $endDate = !empty($contentData['end_date']) ? 
            "end_date = '" . pg_escape_string($contentData['end_date']) . "'" : 
            "end_date = NULL";
    }
    
    $status = isset($contentData['status']) ? "status = '" . pg_escape_string($contentData['status']) . "'" : '';
    $orderIndex = isset($contentData['order_index']) ? "order_index = " . (int)$contentData['order_index'] : '';
    
    // Build the update fields
    $updateFields = [];
    
    if ($title) $updateFields[] = $title;
    if ($contentType) $updateFields[] = $contentType;
    if ($description) $updateFields[] = $description;
    if ($filePath) $updateFields[] = $filePath;
    if ($startDate) $updateFields[] = $startDate;
    if ($endDate) $updateFields[] = $endDate;
    if ($status) $updateFields[] = $status;
    if ($orderIndex) $updateFields[] = $orderIndex;
    
    // Add updated_at timestamp
    $updateFields[] = "updated_at = CURRENT_TIMESTAMP";
    
    // If no fields to update, return success
    if (empty($updateFields)) {
        return [
            'success' => true,
            'message' => 'No changes made to course content.'
        ];
    }
    
    // Create update SQL
    $sql = "UPDATE course_contents SET " . implode(", ", $updateFields) . " WHERE id = $contentId";
    
    $result = executeQuery($sql);
    
    if (!$result) {
        return [
            'success' => false,
            'message' => 'Failed to update course content. Please try again.'
        ];
    }
    
    return [
        'success' => true,
        'message' => 'Course content updated successfully.'
    ];
}

/**
 * Delete a course content
 * 
 * @param int $contentId The content ID
 * @return array Result with success status and message
 */
function deleteCourseContent($contentId) {
    $contentId = (int)$contentId;
    
    // Check if content exists
    $content = getContentById($contentId);
    
    if (!$content) {
        return [
            'success' => false,
            'message' => 'Course content not found.'
        ];
    }
    
    // Create delete SQL
    $sql = "DELETE FROM course_contents WHERE id = $contentId";
    
    $result = executeQuery($sql);
    
    if (!$result) {
        return [
            'success' => false,
            'message' => 'Failed to delete course content. Please try again.'
        ];
    }
    
    return [
        'success' => true,
        'message' => 'Course content deleted successfully.'
    ];
}

/**
 * Update content order indexes
 * 
 * @param int $courseId The course ID
 * @param array $contentOrders Array of content IDs with their new order indexes
 * @return array Result with success status and message
 */
function updateContentOrders($courseId, $contentOrders) {
    $courseId = (int)$courseId;
    
    if (empty($contentOrders) || !is_array($contentOrders)) {
        return [
            'success' => false,
            'message' => 'Invalid content order data.'
        ];
    }
    
    // Begin transaction
    $result = executeQuery('BEGIN');
    
    if (!$result) {
        return [
            'success' => false,
            'message' => 'Failed to start transaction. Please try again.'
        ];
    }
    
    // Update order indexes
    foreach ($contentOrders as $contentId => $orderIndex) {
        $contentId = (int)$contentId;
        $orderIndex = (int)$orderIndex;
        
        $sql = "UPDATE course_contents 
                SET order_index = $orderIndex, updated_at = CURRENT_TIMESTAMP
                WHERE id = $contentId AND course_id = $courseId";
        
        $result = executeQuery($sql);
        
        if (!$result) {
            // Rollback transaction if any update fails
            executeQuery('ROLLBACK');
            
            return [
                'success' => false,
                'message' => 'Failed to update content order. Please try again.'
            ];
        }
    }
    
    // Commit transaction
    $result = executeQuery('COMMIT');
    
    if (!$result) {
        return [
            'success' => false,
            'message' => 'Failed to commit transaction. Please try again.'
        ];
    }
    
    return [
        'success' => true,
        'message' => 'Content order updated successfully.'
    ];
}

/**
 * Count course contents
 * 
 * @param int $courseId The course ID
 * @return int The number of contents
 */
function countCourseContents($courseId) {
    $courseId = (int)$courseId;
    
    $sql = "SELECT COUNT(*) AS count FROM course_contents WHERE course_id = $courseId";
    
    $result = executeQuery($sql);
    
    if (!$result) {
        return 0;
    }
    
    $row = pg_fetch_assoc($result);
    
    return (int)$row['count'];
}

/**
 * Get all content types with their descriptions
 * 
 * @return array An array of content types
 */
function getContentTypes() {
    return [
        'lecture' => 'Lecture materials (text, slides)',
        'video' => 'Video content',
        'document' => 'Document or PDF',
        'assignment' => 'Assignment to be submitted',
        'link' => 'External link or resource',
        'discussion' => 'Discussion topic',
        'other' => 'Other content type'
    ];
}

/**
 * Mark content as completed for a student
 * 
 * @param int $studentId The student ID
 * @param int $contentId The content ID
 * @param string $status The completion status
 * @param int $grade Optional grade
 * @param string $feedback Optional feedback
 * @return array Result with success status and message
 */
function markContentCompletion($studentId, $contentId, $status = 'completed', $grade = null, $feedback = null) {
    $studentId = (int)$studentId;
    $contentId = (int)$contentId;
    $status = pg_escape_string($status);
    $grade = $grade !== null ? (int)$grade : 'NULL';
    $feedback = $feedback !== null ? "'" . pg_escape_string($feedback) . "'" : 'NULL';
    
    // Check if a record already exists
    $sql = "SELECT id FROM content_completions 
            WHERE student_id = $studentId AND content_id = $contentId";
    
    $result = executeQuery($sql);
    
    if ($result && pg_num_rows($result) > 0) {
        // Update existing record
        $row = pg_fetch_assoc($result);
        $id = (int)$row['id'];
        
        $sql = "UPDATE content_completions 
                SET completion_status = '$status', 
                    grade = $grade, 
                    feedback = $feedback, 
                    completed_at = CURRENT_TIMESTAMP,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = $id";
    } else {
        // Insert new record
        $sql = "INSERT INTO content_completions (
                    student_id, content_id, completion_status, 
                    grade, feedback, completed_at, 
                    created_at, updated_at
                ) VALUES (
                    $studentId, $contentId, '$status', 
                    $grade, $feedback, CURRENT_TIMESTAMP, 
                    CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
                )";
    }
    
    $result = executeQuery($sql);
    
    if (!$result) {
        return [
            'success' => false,
            'message' => 'Failed to mark content completion. Please try again.'
        ];
    }
    
    return [
        'success' => true,
        'message' => 'Content completion marked successfully.'
    ];
}

/**
 * Get completion status for a content and student
 * 
 * @param int $studentId The student ID
 * @param int $contentId The content ID
 * @return array|false The completion details or false if not found
 */
function getContentCompletion($studentId, $contentId) {
    $studentId = (int)$studentId;
    $contentId = (int)$contentId;
    
    $sql = "SELECT * FROM content_completions 
            WHERE student_id = $studentId AND content_id = $contentId";
    
    $result = executeQuery($sql);
    
    if (!$result || pg_num_rows($result) == 0) {
        return false;
    }
    
    return pg_fetch_assoc($result);
}

/**
 * Get all completions for a student in a course
 * 
 * @param int $studentId The student ID
 * @param int $courseId The course ID
 * @return array An array of completions
 */
function getStudentCourseCompletions($studentId, $courseId) {
    $studentId = (int)$studentId;
    $courseId = (int)$courseId;
    
    $sql = "SELECT cc.*, c.title AS content_title, c.content_type
            FROM content_completions cc
            JOIN course_contents c ON cc.content_id = c.id
            WHERE cc.student_id = $studentId AND c.course_id = $courseId
            ORDER BY c.order_index ASC";
    
    $result = executeQuery($sql);
    
    if (!$result) {
        return [];
    }
    
    return fetchAllAssoc($result);
}

/**
 * Calculate student progress in a course
 * 
 * @param int $studentId The student ID
 * @param int $courseId The course ID
 * @return array Progress data with completion percentage and counts
 */
function calculateCourseProgress($studentId, $courseId) {
    $studentId = (int)$studentId;
    $courseId = (int)$courseId;
    
    // Get total number of contents
    $sqlTotal = "SELECT COUNT(*) AS total FROM course_contents WHERE course_id = $courseId";
    $resultTotal = executeQuery($sqlTotal);
    $total = 0;
    
    if ($resultTotal && ($rowTotal = pg_fetch_assoc($resultTotal))) {
        $total = (int)$rowTotal['total'];
    }
    
    // Get number of completed contents
    $sqlCompleted = "SELECT COUNT(*) AS completed 
                    FROM content_completions cc
                    JOIN course_contents c ON cc.content_id = c.id
                    WHERE cc.student_id = $studentId 
                    AND c.course_id = $courseId
                    AND cc.completion_status = 'completed'";
    
    $resultCompleted = executeQuery($sqlCompleted);
    $completed = 0;
    
    if ($resultCompleted && ($rowCompleted = pg_fetch_assoc($resultCompleted))) {
        $completed = (int)$rowCompleted['completed'];
    }
    
    // Calculate percentage
    $percentage = $total > 0 ? round(($completed / $total) * 100) : 0;
    
    return [
        'total' => $total,
        'completed' => $completed,
        'percentage' => $percentage
    ];
}

/**
 * Add a comment to a course content
 * 
 * @param array $commentData The comment data
 * @return array Result with success status and message
 */
function addContentComment($commentData) {
    // Validate required fields
    if (empty($commentData['content_id']) || empty($commentData['user_id']) || empty($commentData['comment'])) {
        return [
            'success' => false,
            'message' => 'Content ID, user ID, and comment text are required.'
        ];
    }
    
    // Sanitize and prepare data
    $contentId = (int)$commentData['content_id'];
    $userId = (int)$commentData['user_id'];
    $comment = pg_escape_string($commentData['comment']);
    $parentId = isset($commentData['parent_id']) && $commentData['parent_id'] > 0 ? 
                (int)$commentData['parent_id'] : 'NULL';
    
    // Create insert SQL
    $sql = "INSERT INTO content_comments (
                content_id, user_id, parent_id, comment, created_at
            ) VALUES (
                $contentId, $userId, $parentId, '$comment', CURRENT_TIMESTAMP
            ) RETURNING id";
    
    $result = executeQuery($sql);
    
    if (!$result) {
        return [
            'success' => false,
            'message' => 'Failed to add comment. Please try again.'
        ];
    }
    
    $row = pg_fetch_assoc($result);
    
    return [
        'success' => true,
        'message' => 'Comment added successfully.',
        'comment_id' => $row['id']
    ];
}

/**
 * Get comments for a course content
 * 
 * @param int $contentId The content ID
 * @return array An array of comments
 */
function getContentComments($contentId) {
    $contentId = (int)$contentId;
    
    $sql = "SELECT cc.*, u.full_name, u.role as user_role
            FROM content_comments cc
            JOIN users u ON cc.user_id = u.id
            WHERE cc.content_id = $contentId
            ORDER BY cc.parent_id NULLS FIRST, cc.created_at ASC";
    
    $result = executeQuery($sql);
    
    if (!$result) {
        return [];
    }
    
    // Fetch all comments
    $comments = fetchAllAssoc($result);
    
    // Organize into threaded structure with parent and child comments
    $threaded = [];
    $childComments = [];
    
    foreach ($comments as $comment) {
        if ($comment['parent_id'] === null) {
            // This is a parent comment
            $comment['replies'] = [];
            $threaded[$comment['id']] = $comment;
        } else {
            // This is a child comment (reply)
            $childComments[] = $comment;
        }
    }
    
    // Add child comments to their parents
    foreach ($childComments as $child) {
        if (isset($threaded[$child['parent_id']])) {
            $threaded[$child['parent_id']]['replies'][] = $child;
        }
    }
    
    // Convert the associative array to indexed array
    return array_values($threaded);
}



/**
 * Get enrollment status and date for a student in a course
 * 
 * @param int $studentId The student ID
 * @param int $courseId The course ID
 * @return array|false The enrollment details or false if not enrolled
 */
function getEnrollmentDetails($studentId, $courseId) {
    $studentId = (int)$studentId;
    $courseId = (int)$courseId;
    
    $sql = "SELECT * FROM student_courses 
            WHERE student_id = $studentId AND course_id = $courseId";
    
    $result = executeQuery($sql);
    
    if (!$result || pg_num_rows($result) == 0) {
        return false;
    }
    
    return pg_fetch_assoc($result);
}

/**
 * Update enrollment status
 * 
 * @param int $studentId The student ID
 * @param int $courseId The course ID
 * @param string $status The new status
 * @return array Result with success status and message
 */
function updateEnrollmentStatus($studentId, $courseId, $status) {
    $studentId = (int)$studentId;
    $courseId = (int)$courseId;
    $status = pg_escape_string($status);
    
    $enrollment = getEnrollmentDetails($studentId, $courseId);
    
    if (!$enrollment) {
        return [
            'success' => false,
            'message' => 'Student is not enrolled in this course.'
        ];
    }
    
    $sql = "UPDATE student_courses 
            SET status = '$status', updated_at = CURRENT_TIMESTAMP
            WHERE student_id = $studentId AND course_id = $courseId";
    
    $result = executeQuery($sql);
    
    if (!$result) {
        return [
            'success' => false,
            'message' => 'Failed to update enrollment status. Please try again.'
        ];
    }
    
    return [
        'success' => true,
        'message' => 'Enrollment status updated successfully.'
    ];
}

/**
 * Mark course as completed for a student
 * 
 * @param int $studentId The student ID
 * @param int $courseId The course ID
 * @return array Result with success status and message
 */
function markCourseCompleted($studentId, $courseId) {
    $studentId = (int)$studentId;
    $courseId = (int)$courseId;
    
    $enrollment = getEnrollmentDetails($studentId, $courseId);
    
    if (!$enrollment) {
        return [
            'success' => false,
            'message' => 'Student is not enrolled in this course.'
        ];
    }
    
    $sql = "UPDATE student_courses 
            SET status = 'completed', 
                completion_date = CURRENT_TIMESTAMP,
                updated_at = CURRENT_TIMESTAMP
            WHERE student_id = $studentId AND course_id = $courseId";
    
    $result = executeQuery($sql);
    
    if (!$result) {
        return [
            'success' => false,
            'message' => 'Failed to mark course as completed. Please try again.'
        ];
    }
    
    return [
        'success' => true,
        'message' => 'Course marked as completed successfully.'
    ];
}