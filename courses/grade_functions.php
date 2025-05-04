
<?php
function calculateFinalGrade($studentId, $courseId) {
    // Get all components
    $attendance = getAttendanceScore($studentId, $courseId) * 0.15; // 15%
    $quizzes = getQuizAverage($studentId, $courseId) * 0.15; // 15%
    $assignments = getAssignmentScore($studentId, $courseId) * 0.20; // 20%
    $midterm = getMidtermScore($studentId, $courseId) * 0.25; // 25%
    $final = getFinalScore($studentId, $courseId) * 0.25; // 25%
    
    return $attendance + $quizzes + $assignments + $midterm + $final;
}

function getAttendanceScore($studentId, $courseId) {
    $sql = "SELECT COUNT(*) as total_present 
            FROM attendance 
            WHERE student_id = $studentId 
            AND course_id = $courseId 
            AND status = 'present'";
    
    $result = executeQuery($sql);
    $present = pg_fetch_assoc($result)['total_present'];
    
    $sql = "SELECT COUNT(*) as total_sessions 
            FROM attendance_sessions 
            WHERE course_id = $courseId";
    
    $result = executeQuery($sql);
    $total = pg_fetch_assoc($result)['total_sessions'];
    
    return ($total > 0) ? ($present / $total) * 100 : 0;
}
