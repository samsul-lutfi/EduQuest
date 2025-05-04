
<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once 'course_functions.php';

// Ensure user is logged in
requireLogin();

$courseId = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
$action = isset($_GET['action']) ? $_GET['action'] : 'view';

// Validate course access
$course = getCourseById($courseId);
if (!$course) {
    $_SESSION['error'] = 'Invalid course ID.';
    redirect('/courses/index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['import_attendance'])) {
        // Handle CSV import
        if (isset($_FILES['attendance_file'])) {
            $file = $_FILES['attendance_file'];
            if ($file['type'] === 'text/csv') {
                $handle = fopen($file['tmp_name'], 'r');
                while (($data = fgetcsv($handle)) !== FALSE) {
                    $studentId = (int)$data[0];
                    $status = pg_escape_string($data[1]);
                    $date = pg_escape_string($data[2]);
                    
                    $sql = "INSERT INTO attendance (student_id, course_id, status, date) 
                            VALUES ($studentId, $courseId, '$status', '$date')";
                    executeQuery($sql);
                }
                fclose($handle);
                $_SESSION['success'] = 'Attendance data imported successfully.';
            }
        }
    }
}

include_once '../includes/header.php';
?>

<div class="row">
    <div class="col-lg-3">
        <?php include_once '../includes/sidebar.php'; ?>
    </div>
    
    <div class="col-lg-9">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="mb-0">Attendance Management</h4>
                <div>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#importModal">
                        <i class="fas fa-file-import me-1"></i>Import Attendance
                    </button>
                    <a href="attendance_export.php?course_id=<?php echo $courseId; ?>" class="btn btn-success">
                        <i class="fas fa-file-export me-1"></i>Export Attendance
                    </a>
                </div>
            </div>
            <div class="card-body">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <?php
                            $sessions = getAttendanceSessions($courseId);
                            foreach ($sessions as $session): ?>
                                <th><?php echo formatDate($session['date']); ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $students = getEnrolledStudents($courseId);
                        foreach ($students as $student): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($student['name']); ?></td>
                                <?php foreach ($sessions as $session): 
                                    $status = getAttendanceStatus($student['id'], $courseId, $session['date']);
                                ?>
                                    <td>
                                        <select class="form-select form-select-sm attendance-select" 
                                                data-student="<?php echo $student['id']; ?>"
                                                data-date="<?php echo $session['date']; ?>">
                                            <option value="present" <?php echo $status === 'present' ? 'selected' : ''; ?>>Present</option>
                                            <option value="absent" <?php echo $status === 'absent' ? 'selected' : ''; ?>>Absent</option>
                                            <option value="late" <?php echo $status === 'late' ? 'selected' : ''; ?>>Late</option>
                                        </select>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Import Modal -->
<div class="modal fade" id="importModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Import Attendance</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="attendance_file" class="form-label">CSV File</label>
                        <input type="file" class="form-control" id="attendance_file" name="attendance_file" accept=".csv" required>
                    </div>
                    <button type="submit" name="import_attendance" class="btn btn-primary">Import</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>
