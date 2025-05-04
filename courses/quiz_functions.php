<?php
/**
 * Functions for managing quizzes
 */

/**
 * Get all quizzes for a course
 * 
 * @param int $courseId The course ID
 * @return array An array of quizzes
 */
function getCourseQuizzes($courseId) {
    $courseId = (int)$courseId;
    
    $sql = "SELECT q.*, c.name AS course_name 
            FROM quizzes q
            JOIN courses c ON q.course_id = c.id
            WHERE q.course_id = $courseId
            ORDER BY q.start_date DESC";
    
    $result = executeQuery($sql);
    
    if (!$result) {
        return [];
    }
    
    return fetchAllAssoc($result);
}

/**
 * Get all quizzes created by a teacher
 * 
 * @param int $teacherId The teacher ID
 * @return array An array of quizzes
 */
function getTeacherQuizzes($teacherId) {
    $teacherId = (int)$teacherId;
    
    $sql = "SELECT q.*, c.name AS course_name 
            FROM quizzes q
            JOIN courses c ON q.course_id = c.id
            WHERE c.teacher_id = $teacherId
            ORDER BY q.start_date DESC";
    
    $result = executeQuery($sql);
    
    if (!$result) {
        return [];
    }
    
    return fetchAllAssoc($result);
}

/**
 * Get quiz details by ID
 * 
 * @param int $quizId The quiz ID
 * @return array|false The quiz details or false if not found
 */
function getQuizById($quizId) {
    $quizId = (int)$quizId;
    
    $sql = "SELECT q.*, c.name AS course_name, c.teacher_id
            FROM quizzes q
            JOIN courses c ON q.course_id = c.id
            WHERE q.id = $quizId";
    
    $result = executeQuery($sql);
    
    if (!$result || numRows($result) === 0) {
        return false;
    }
    
    return fetchAssoc($result);
}

/**
 * Add a new quiz
 * 
 * @param int $courseId The course ID
 * @param string $name The quiz name
 * @param string $description The quiz description
 * @param string $type The quiz type (daily, weekly, etc.)
 * @param string $startDate The start date (YYYY-MM-DD HH:MM:SS)
 * @param string $endDate The end date (YYYY-MM-DD HH:MM:SS)
 * @return array An array containing success status and message
 */
function addQuiz($courseId, $name, $description, $type, $startDate, $endDate) {
    $courseId = (int)$courseId;
    $name = sanitizeInput($name);
    $description = sanitizeInput($description);
    $type = sanitizeInput($type);
    $startDate = sanitizeInput($startDate);
    $endDate = sanitizeInput($endDate);
    
    $sql = "INSERT INTO quizzes (course_id, name, description, type, start_date, end_date, created_at) 
            VALUES ($courseId, '$name', '$description', '$type', '$startDate', '$endDate', NOW())";
    
    $result = executeQuery($sql);
    
    if ($result) {
        $quizId = lastInsertId(null, 'quizzes');
        return [
            'success' => true,
            'message' => 'Quiz added successfully.',
            'quiz_id' => $quizId
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Failed to add quiz.'
        ];
    }
}

/**
 * Update an existing quiz
 * 
 * @param int $quizId The quiz ID
 * @param string $name The quiz name
 * @param string $description The quiz description
 * @param string $type The quiz type
 * @param string $startDate The start date
 * @param string $endDate The end date
 * @return array An array containing success status and message
 */
function updateQuiz($quizId, $name, $description, $type, $startDate, $endDate) {
    $quizId = (int)$quizId;
    $name = sanitizeInput($name);
    $description = sanitizeInput($description);
    $type = sanitizeInput($type);
    $startDate = sanitizeInput($startDate);
    $endDate = sanitizeInput($endDate);
    
    $sql = "UPDATE quizzes 
            SET name = '$name', 
                description = '$description', 
                type = '$type',
                start_date = '$startDate',
                end_date = '$endDate',
                updated_at = NOW() 
            WHERE id = $quizId";
    
    $result = executeQuery($sql);
    
    if ($result) {
        return [
            'success' => true,
            'message' => 'Quiz updated successfully.'
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Failed to update quiz.'
        ];
    }
}

/**
 * Delete a quiz
 * 
 * @param int $quizId The quiz ID
 * @return array An array containing success status and message
 */
function deleteQuiz($quizId) {
    $quizId = (int)$quizId;
    
    $sql = "DELETE FROM quizzes WHERE id = $quizId";
    
    $result = executeQuery($sql);
    
    if ($result) {
        return [
            'success' => true,
            'message' => 'Quiz deleted successfully.'
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Failed to delete quiz.'
        ];
    }
}

/**
 * Add a question to a quiz
 * 
 * @param int $quizId The quiz ID
 * @param string $questionText The question text
 * @param string $questionType The question type (multiple_choice, essay, etc.)
 * @param int $points The points value
 * @return array An array containing success status and message
 */
function addQuizQuestion($quizId, $questionText, $questionType, $points = 1) {
    $quizId = (int)$quizId;
    $questionText = sanitizeInput($questionText);
    $questionType = sanitizeInput($questionType);
    $points = (int)$points;
    
    $sql = "INSERT INTO quiz_questions (quiz_id, question_text, question_type, points, created_at) 
            VALUES ($quizId, '$questionText', '$questionType', $points, NOW())";
    
    $result = executeQuery($sql);
    
    if ($result) {
        $questionId = lastInsertId(null, 'quiz_questions');
        return [
            'success' => true,
            'message' => 'Question added successfully.',
            'question_id' => $questionId
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Failed to add question.'
        ];
    }
}

/**
 * Update a quiz question
 * 
 * @param int $questionId The question ID
 * @param string $questionText The question text
 * @param string $questionType The question type
 * @param int $points The points value
 * @return array An array containing success status and message
 */
function updateQuizQuestion($questionId, $questionText, $questionType, $points) {
    $questionId = (int)$questionId;
    $questionText = sanitizeInput($questionText);
    $questionType = sanitizeInput($questionType);
    $points = (int)$points;
    
    $sql = "UPDATE quiz_questions 
            SET question_text = '$questionText', 
                question_type = '$questionType', 
                points = $points,
                updated_at = NOW() 
            WHERE id = $questionId";
    
    $result = executeQuery($sql);
    
    if ($result) {
        return [
            'success' => true,
            'message' => 'Question updated successfully.'
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Failed to update question.'
        ];
    }
}

/**
 * Delete a quiz question
 * 
 * @param int $questionId The question ID
 * @return array An array containing success status and message
 */
function deleteQuizQuestion($questionId) {
    $questionId = (int)$questionId;
    
    $sql = "DELETE FROM quiz_questions WHERE id = $questionId";
    
    $result = executeQuery($sql);
    
    if ($result) {
        return [
            'success' => true,
            'message' => 'Question deleted successfully.'
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Failed to delete question.'
        ];
    }
}

/**
 * Add an option to a multiple choice question
 * 
 * @param int $questionId The question ID
 * @param string $optionText The option text
 * @param bool $isCorrect Whether the option is correct
 * @return array An array containing success status and message
 */
function addQuizOption($questionId, $optionText, $isCorrect = false) {
    $questionId = (int)$questionId;
    $optionText = sanitizeInput($optionText);
    $isCorrect = $isCorrect ? 'TRUE' : 'FALSE';
    
    $sql = "INSERT INTO quiz_options (question_id, option_text, is_correct, created_at) 
            VALUES ($questionId, '$optionText', $isCorrect, NOW())";
    
    $result = executeQuery($sql);
    
    if ($result) {
        $optionId = lastInsertId(null, 'quiz_options');
        return [
            'success' => true,
            'message' => 'Option added successfully.',
            'option_id' => $optionId
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Failed to add option.'
        ];
    }
}

/**
 * Update a quiz option
 * 
 * @param int $optionId The option ID
 * @param string $optionText The option text
 * @param bool $isCorrect Whether the option is correct
 * @return array An array containing success status and message
 */
function updateQuizOption($optionId, $optionText, $isCorrect) {
    $optionId = (int)$optionId;
    $optionText = sanitizeInput($optionText);
    $isCorrect = $isCorrect ? 'TRUE' : 'FALSE';
    
    $sql = "UPDATE quiz_options 
            SET option_text = '$optionText', 
                is_correct = $isCorrect,
                updated_at = NOW() 
            WHERE id = $optionId";
    
    $result = executeQuery($sql);
    
    if ($result) {
        return [
            'success' => true,
            'message' => 'Option updated successfully.'
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Failed to update option.'
        ];
    }
}

/**
 * Delete a quiz option
 * 
 * @param int $optionId The option ID
 * @return array An array containing success status and message
 */
function deleteQuizOption($optionId) {
    $optionId = (int)$optionId;
    
    $sql = "DELETE FROM quiz_options WHERE id = $optionId";
    
    $result = executeQuery($sql);
    
    if ($result) {
        return [
            'success' => true,
            'message' => 'Option deleted successfully.'
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Failed to delete option.'
        ];
    }
}

/**
 * Get all questions for a quiz
 * 
 * @param int $quizId The quiz ID
 * @return array An array of questions
 */
function getQuizQuestions($quizId) {
    $quizId = (int)$quizId;
    
    $sql = "SELECT * FROM quiz_questions 
            WHERE quiz_id = $quizId
            ORDER BY id ASC";
    
    $result = executeQuery($sql);
    
    if (!$result) {
        return [];
    }
    
    $questions = fetchAllAssoc($result);
    
    // Add options for multiple choice questions
    foreach ($questions as &$question) {
        if ($question['question_type'] === 'multiple_choice') {
            $question['options'] = getQuestionOptions($question['id']);
        }
    }
    
    return $questions;
}

/**
 * Get options for a question
 * 
 * @param int $questionId The question ID
 * @return array An array of options
 */
function getQuestionOptions($questionId) {
    $questionId = (int)$questionId;
    
    $sql = "SELECT * FROM quiz_options 
            WHERE question_id = $questionId
            ORDER BY id ASC";
    
    $result = executeQuery($sql);
    
    if (!$result) {
        return [];
    }
    
    return fetchAllAssoc($result);
}

/**
 * Submit a quiz answer
 * 
 * @param int $submissionId The submission ID
 * @param int $questionId The question ID
 * @param string $answerText The answer text (for essay questions)
 * @param int $selectedOptionId The selected option ID (for multiple choice)
 * @return array An array containing success status and message
 */
function submitQuizAnswer($submissionId, $questionId, $answerText = null, $selectedOptionId = null) {
    $submissionId = (int)$submissionId;
    $questionId = (int)$questionId;
    $answerText = $answerText !== null ? sanitizeInput($answerText) : null;
    $selectedOptionId = $selectedOptionId !== null ? (int)$selectedOptionId : null;
    
    // Auto-scoring for multiple choice
    $score = 0;
    if ($selectedOptionId !== null) {
        $optionSql = "SELECT is_correct FROM quiz_options WHERE id = $selectedOptionId";
        $optionResult = executeQuery($optionSql);
        
        if ($optionResult && $optionRow = fetchAssoc($optionResult)) {
            $isCorrect = $optionRow['is_correct'];
            
            if ($isCorrect) {
                $questionSql = "SELECT points FROM quiz_questions WHERE id = $questionId";
                $questionResult = executeQuery($questionSql);
                
                if ($questionResult && $questionRow = fetchAssoc($questionResult)) {
                    $score = (int)$questionRow['points'];
                }
            }
        }
    }
    
    // Build the SQL based on the answer type
    $answerTextSql = $answerText !== null ? "'$answerText'" : "NULL";
    $selectedOptionIdSql = $selectedOptionId !== null ? $selectedOptionId : "NULL";
    
    $sql = "INSERT INTO quiz_answers (submission_id, question_id, answer_text, selected_option_id, score, created_at) 
            VALUES ($submissionId, $questionId, $answerTextSql, $selectedOptionIdSql, $score, NOW())";
    
    $result = executeQuery($sql);
    
    if ($result) {
        // Update the total score in the submission
        updateSubmissionScore($submissionId);
        
        return [
            'success' => true,
            'message' => 'Answer submitted successfully.',
            'score' => $score
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Failed to submit answer.'
        ];
    }
}

/**
 * Start a new quiz submission
 * 
 * @param int $quizId The quiz ID
 * @param int $studentId The student ID
 * @return array An array containing success status and message
 */
function startQuizSubmission($quizId, $studentId) {
    $quizId = (int)$quizId;
    $studentId = (int)$studentId;
    
    // Check if there's already an incomplete submission
    $checkSql = "SELECT id FROM quiz_submissions 
                 WHERE quiz_id = $quizId AND student_id = $studentId AND completed = FALSE";
    $checkResult = executeQuery($checkSql);
    
    if ($checkResult && numRows($checkResult) > 0) {
        $row = fetchAssoc($checkResult);
        return [
            'success' => true,
            'message' => 'Continuing existing submission.',
            'submission_id' => $row['id']
        ];
    }
    
    // Create a new submission
    $sql = "INSERT INTO quiz_submissions (quiz_id, student_id, submission_date, total_score, completed) 
            VALUES ($quizId, $studentId, NOW(), 0, FALSE)";
    
    $result = executeQuery($sql);
    
    if ($result) {
        $submissionId = lastInsertId(null, 'quiz_submissions');
        return [
            'success' => true,
            'message' => 'Quiz started successfully.',
            'submission_id' => $submissionId
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Failed to start quiz.'
        ];
    }
}

/**
 * Complete a quiz submission
 * 
 * @param int $submissionId The submission ID
 * @return array An array containing success status and message
 */
function completeQuizSubmission($submissionId) {
    $submissionId = (int)$submissionId;
    
    // Update the submission as completed
    $sql = "UPDATE quiz_submissions 
            SET completed = TRUE
            WHERE id = $submissionId";
    
    $result = executeQuery($sql);
    
    if ($result) {
        return [
            'success' => true,
            'message' => 'Quiz completed successfully.'
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Failed to complete quiz.'
        ];
    }
}

/**
 * Update the total score for a quiz submission
 * 
 * @param int $submissionId The submission ID
 * @return bool True if successful, false otherwise
 */
function updateSubmissionScore($submissionId) {
    $submissionId = (int)$submissionId;
    
    // Calculate the total score
    $scoreSql = "SELECT SUM(score) as total_score FROM quiz_answers WHERE submission_id = $submissionId";
    $scoreResult = executeQuery($scoreSql);
    
    if ($scoreResult && $scoreRow = fetchAssoc($scoreResult)) {
        $totalScore = (int)$scoreRow['total_score'];
        
        // Update the total score in the submission
        $updateSql = "UPDATE quiz_submissions SET total_score = $totalScore WHERE id = $submissionId";
        return executeQuery($updateSql) ? true : false;
    }
    
    return false;
}

/**
 * Get quiz submissions for a student
 * 
 * @param int $studentId The student ID
 * @param int $quizId Optional quiz ID to filter by
 * @return array An array of submissions
 */
function getStudentSubmissions($studentId, $quizId = null) {
    $studentId = (int)$studentId;
    $quizFilter = $quizId !== null ? "AND s.quiz_id = " . (int)$quizId : "";
    
    $sql = "SELECT s.*, q.name AS quiz_name, c.name AS course_name
            FROM quiz_submissions s
            JOIN quizzes q ON s.quiz_id = q.id
            JOIN courses c ON q.course_id = c.id
            WHERE s.student_id = $studentId $quizFilter
            ORDER BY s.submission_date DESC";
    
    $result = executeQuery($sql);
    
    if (!$result) {
        return [];
    }
    
    return fetchAllAssoc($result);
}

/**
 * Get submissions for a quiz
 * 
 * @param int $quizId The quiz ID
 * @return array An array of submissions
 */
function getQuizSubmissions($quizId) {
    $quizId = (int)$quizId;
    
    $sql = "SELECT s.*, u.name AS student_name
            FROM quiz_submissions s
            JOIN users u ON s.student_id = u.id
            WHERE s.quiz_id = $quizId
            ORDER BY s.submission_date DESC";
    
    $result = executeQuery($sql);
    
    if (!$result) {
        return [];
    }
    
    return fetchAllAssoc($result);
}

/**
 * Get submission details by ID
 * 
 * @param int $submissionId The submission ID
 * @return array|false The submission details or false if not found
 */
function getSubmissionById($submissionId) {
    $submissionId = (int)$submissionId;
    
    $sql = "SELECT s.*, q.name AS quiz_name, q.description AS quiz_description, 
                   c.name AS course_name, u.name AS student_name
            FROM quiz_submissions s
            JOIN quizzes q ON s.quiz_id = q.id
            JOIN courses c ON q.course_id = c.id
            JOIN users u ON s.student_id = u.id
            WHERE s.id = $submissionId";
    
    $result = executeQuery($sql);
    
    if (!$result || numRows($result) === 0) {
        return false;
    }
    
    $submission = fetchAssoc($result);
    
    // Get the answers
    $answersSql = "SELECT a.*, qq.question_text, qq.question_type, qq.points,
                          o.option_text, o.is_correct
                   FROM quiz_answers a
                   JOIN quiz_questions qq ON a.question_id = qq.id
                   LEFT JOIN quiz_options o ON a.selected_option_id = o.id
                   WHERE a.submission_id = $submissionId";
    
    $answersResult = executeQuery($answersSql);
    
    if ($answersResult) {
        $submission['answers'] = fetchAllAssoc($answersResult);
    } else {
        $submission['answers'] = [];
    }
    
    return $submission;
}

/**
 * Grade an essay question
 * 
 * @param int $answerId The answer ID
 * @param int $score The score to assign
 * @return array An array containing success status and message
 */
function gradeEssayQuestion($answerId, $score) {
    $answerId = (int)$answerId;
    $score = (int)$score;
    
    // Get the submission ID for updating the total score later
    $submissionSql = "SELECT submission_id FROM quiz_answers WHERE id = $answerId";
    $submissionResult = executeQuery($submissionSql);
    $submissionId = null;
    
    if ($submissionResult && $submissionRow = fetchAssoc($submissionResult)) {
        $submissionId = (int)$submissionRow['submission_id'];
    } else {
        return [
            'success' => false,
            'message' => 'Answer not found.'
        ];
    }
    
    // Update the score
    $sql = "UPDATE quiz_answers SET score = $score WHERE id = $answerId";
    
    $result = executeQuery($sql);
    
    if ($result) {
        // Update the total score in the submission
        if ($submissionId) {
            updateSubmissionScore($submissionId);
        }
        
        return [
            'success' => true,
            'message' => 'Answer graded successfully.'
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Failed to grade answer.'
        ];
    }
}

// Note: countCourseQuizzes() function is already defined in course_functions.php
// Note: getCourseQuizzes() function is already defined at the top of this file