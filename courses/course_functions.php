<?php
/**
 * Functions for managing courses
 */

/**
 * Get all courses
 * 
 * @param int $limit The maximum number of courses to return (0 for all)
 * @return array An array of courses
 */
function getAllCourses($limit = 0) {
    $limitClause = $limit > 0 ? "LIMIT $limit" : "";
    
    $sql = "SELECT c.*, u.name AS teacher_name 
            FROM courses c
            JOIN users u ON c.teacher_id = u.id
            ORDER BY c.name ASC
            $limitClause";
    
    $result = executeQuery($sql);
    
    if (!$result) {
        return [];
    }
    
    return fetchAllAssoc($result);
}

/**
 * Get courses taught by a specific teacher
 * 
 * @param int $teacherId The teacher ID
 * @return array An array of courses
 */
function getTeacherCourses($teacherId) {
    $teacherId = (int)$teacherId;
    
    $sql = "SELECT c.*
            FROM courses c
            WHERE c.teacher_id = $teacherId
            ORDER BY c.name ASC";
    
    $result = executeQuery($sql);
    
    if (!$result) {
        return [];
    }
    
    return fetchAllAssoc($result);
}

/**
 * Get courses a student is enrolled in
 * 
 * @param int $studentId The student ID
 * @return array An array of courses
 */
function getStudentCourses($studentId) {
    $studentId = (int)$studentId;
    
    $sql = "SELECT c.*, u.name AS teacher_name, sc.enrollment_date
            FROM courses c
            JOIN users u ON c.teacher_id = u.id
            JOIN student_courses sc ON c.id = sc.course_id
            WHERE sc.student_id = $studentId
            ORDER BY c.name ASC";
    
    $result = executeQuery($sql);
    
    if (!$result) {
        return [];
    }
    
    return fetchAllAssoc($result);
}

/**
 * Get course details by ID
 * 
 * @param int $courseId The course ID
 * @return array|false The course details or false if not found
 */
function getCourseById($courseId) {
    $courseId = (int)$courseId;
    
    $sql = "SELECT c.*, u.name AS teacher_name, u.email AS teacher_email
            FROM courses c
            JOIN users u ON c.teacher_id = u.id
            WHERE c.id = $courseId";
    
    $result = executeQuery($sql);
    
    if (!$result || numRows($result) === 0) {
        return false;
    }
    
    return fetchAssoc($result);
}

/**
 * Add a new course
 * 
 * @param string $name The course name
 * @param string $description The course description
 * @param int $teacherId The teacher ID
 * @return array An array containing success status and message
 */
function addCourse($name, $description, $teacherId) {
    $name = sanitizeInput($name);
    $description = sanitizeInput($description);
    $teacherId = (int)$teacherId;
    
    $sql = "INSERT INTO courses (name, description, teacher_id, created_at) 
            VALUES ('$name', '$description', $teacherId, NOW())";
    
    $result = executeQuery($sql);
    
    if ($result) {
        $courseId = lastInsertId(null, 'courses');
        return [
            'success' => true,
            'message' => 'Course added successfully.',
            'course_id' => $courseId
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Failed to add course.'
        ];
    }
}

/**
 * Update an existing course
 * 
 * @param int $courseId The course ID
 * @param string $name The course name
 * @param string $description The course description
 * @return array An array containing success status and message
 */
function updateCourse($courseId, $name, $description) {
    $courseId = (int)$courseId;
    $name = sanitizeInput($name);
    $description = sanitizeInput($description);
    
    $sql = "UPDATE courses 
            SET name = '$name', 
                description = '$description', 
                updated_at = NOW() 
            WHERE id = $courseId";
    
    $result = executeQuery($sql);
    
    if ($result) {
        return [
            'success' => true,
            'message' => 'Course updated successfully.'
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Failed to update course.'
        ];
    }
}

/**
 * Delete a course
 * 
 * @param int $courseId The course ID
 * @return array An array containing success status and message
 */
function deleteCourse($courseId) {
    $courseId = (int)$courseId;
    
    $sql = "DELETE FROM courses WHERE id = $courseId";
    
    $result = executeQuery($sql);
    
    if ($result) {
        return [
            'success' => true,
            'message' => 'Course deleted successfully.'
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Failed to delete course.'
        ];
    }
}

/**
 * Enroll a student in a course
 * 
 * @param int $studentId The student ID
 * @param int $courseId The course ID
 * @return array An array containing success status and message
 */
function enrollStudent($studentId, $courseId) {
    $studentId = (int)$studentId;
    $courseId = (int)$courseId;
    
    // Check if already enrolled
    $checkSql = "SELECT id FROM student_courses 
                 WHERE student_id = $studentId AND course_id = $courseId";
    $checkResult = executeQuery($checkSql);
    
    if ($checkResult && numRows($checkResult) > 0) {
        return [
            'success' => false,
            'message' => 'Student is already enrolled in this course.'
        ];
    }
    
    $sql = "INSERT INTO student_courses (student_id, course_id, enrollment_date) 
            VALUES ($studentId, $courseId, NOW())";
    
    $result = executeQuery($sql);
    
    if ($result) {
        return [
            'success' => true,
            'message' => 'Student enrolled successfully.'
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Failed to enroll student.'
        ];
    }
}

/**
 * Unenroll a student from a course
 * 
 * @param int $studentId The student ID
 * @param int $courseId The course ID
 * @return array An array containing success status and message
 */
function unenrollStudent($studentId, $courseId) {
    $studentId = (int)$studentId;
    $courseId = (int)$courseId;
    
    $sql = "DELETE FROM student_courses 
            WHERE student_id = $studentId AND course_id = $courseId";
    
    $result = executeQuery($sql);
    
    if ($result) {
        return [
            'success' => true,
            'message' => 'Student unenrolled successfully.'
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Failed to unenroll student.'
        ];
    }
}

/**
 * Get students enrolled in a course
 * 
 * @param int $courseId The course ID
 * @return array An array of enrolled students
 */
function getEnrolledStudents($courseId) {
    $courseId = (int)$courseId;
    
    $sql = "SELECT u.id, u.name, u.email, sc.enrollment_date
            FROM users u
            JOIN student_courses sc ON u.id = sc.student_id
            WHERE sc.course_id = $courseId
            ORDER BY u.name ASC";
    
    $result = executeQuery($sql);
    
    if (!$result) {
        return [];
    }
    
    return fetchAllAssoc($result);
}

/**
 * Check if a student is enrolled in a course
 * 
 * @param int $studentId The student ID
 * @param int $courseId The course ID
 * @return bool True if enrolled, false otherwise
 */
function isStudentEnrolled($studentId, $courseId) {
    $studentId = (int)$studentId;
    $courseId = (int)$courseId;
    
    $sql = "SELECT id FROM student_courses 
            WHERE student_id = $studentId AND course_id = $courseId";
    
    $result = executeQuery($sql);
    
    return ($result && numRows($result) > 0);
}

/**
 * Search courses by keyword
 * 
 * @param string $keyword The search keyword
 * @return array An array of matching courses
 */
function searchCourses($keyword) {
    $keyword = sanitizeInput($keyword);
    
    $sql = "SELECT c.*, u.name AS teacher_name
            FROM courses c
            JOIN users u ON c.teacher_id = u.id
            WHERE c.name LIKE '%$keyword%' OR c.description LIKE '%$keyword%' OR u.name LIKE '%$keyword%'
            ORDER BY c.name ASC";
    
    $result = executeQuery($sql);
    
    if (!$result) {
        return [];
    }
    
    return fetchAllAssoc($result);
}

/**
 * Count total courses
 * 
 * @return int The number of courses
 */
function countCourses() {
    $sql = "SELECT COUNT(*) as count FROM courses";
    
    $result = executeQuery($sql);
    
    if (!$result) {
        return 0;
    }
    
    $row = fetchAssoc($result);
    return (int)$row['count'];
}

/**
 * Count courses taught by a teacher
 * 
 * @param int $teacherId The teacher ID
 * @return int The number of courses
 */
function countTeacherCourses($teacherId) {
    $teacherId = (int)$teacherId;
    
    $sql = "SELECT COUNT(*) as count FROM courses WHERE teacher_id = $teacherId";
    
    $result = executeQuery($sql);
    
    if (!$result) {
        return 0;
    }
    
    $row = fetchAssoc($result);
    return (int)$row['count'];
}

/**
 * Count courses a student is enrolled in
 * 
 * @param int $studentId The student ID
 * @return int The number of courses
 */
function countStudentCourses($studentId) {
    $studentId = (int)$studentId;
    
    $sql = "SELECT COUNT(*) as count 
            FROM student_courses 
            WHERE student_id = $studentId";
    
    $result = executeQuery($sql);
    
    if (!$result) {
        return 0;
    }
    
    $row = fetchAssoc($result);
    return (int)$row['count'];
}

/**
 * Count quizzes in a course
 * 
 * @param int $courseId The course ID
 * @return int The number of quizzes
 */
function countCourseQuizzes($courseId) {
    $courseId = (int)$courseId;
    
    $sql = "SELECT COUNT(*) as count 
            FROM quizzes 
            WHERE course_id = $courseId";
    
    $result = executeQuery($sql);
    
    if (!$result) {
        return 0;
    }
    
    $row = fetchAssoc($result);
    return (int)$row['count'];
}
/**
 * Get courses with enrollment counts
 * 
 * @param int|null $teacherId Optional teacher ID to filter courses
 * @return array Array of courses with enrollment counts
 */
function getCoursesWithEnrollmentCounts($teacherId = null) {
    $teacherClause = '';
    if ($teacherId) {
        $teacherClause = "WHERE c.teacher_id = " . (int)$teacherId;
    }
    
    $sql = "SELECT c.*, 
            (SELECT COUNT(*) FROM student_courses sc WHERE sc.course_id = c.id) as enrollment_count,
            u.name as teacher_name
            FROM courses c 
            LEFT JOIN users u ON c.teacher_id = u.id 
            $teacherClause 
            ORDER BY c.created_at DESC";
            
    $result = executeQuery($sql);
    
    if (!$result) {
        return [];
    }
    
    return fetchAllAssoc($result);
}
