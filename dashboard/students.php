<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../achievements/achievement_functions.php';

// Ensure user is a teacher or admin
requireLogin();
if (!isTeacher() && !isAdmin()) {
    $_SESSION['error'] = "You don't have permission to view student lists.";
    redirect('/dashboard/index.php');
}

// Search and filter parameters
$searchQuery = sanitizeInput($_GET['search'] ?? '');
$sortBy = sanitizeInput($_GET['sort'] ?? 'name');
$sortOrder = strtoupper(sanitizeInput($_GET['order'] ?? 'ASC'));

// Validate sort order
if ($sortOrder !== 'ASC' && $sortOrder !== 'DESC') {
    $sortOrder = 'ASC';
}

// Validate sort column
$allowedSortColumns = ['name', 'email', 'created_at', 'last_login', 'achievement_count'];
if (!in_array($sortBy, $allowedSortColumns)) {
    $sortBy = 'name';
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 15;
$offset = ($page - 1) * $perPage;

// Build the query
$searchCondition = !empty($searchQuery) ? "AND (u.name LIKE '%$searchQuery%' OR u.email LIKE '%$searchQuery%')" : "";

$sql = "SELECT u.id, u.name, u.email, u.created_at, u.last_login, 
              COUNT(a.id) as achievement_count
       FROM users u
       LEFT JOIN achievements a ON u.id = a.user_id
       WHERE u.role = 'student' $searchCondition
       GROUP BY u.id";

// Add sorting
if ($sortBy === 'achievement_count') {
    $sql .= " ORDER BY achievement_count $sortOrder, u.name ASC";
} else {
    $sql .= " ORDER BY u.$sortBy $sortOrder";
}

// Add pagination
$sql .= " LIMIT $perPage OFFSET $offset";

$result = executeQuery($sql);
$students = $result ? fetchAllAssoc($result) : [];

// Get total count for pagination
$countSql = "SELECT COUNT(*) as count FROM users WHERE role = 'student' $searchCondition";
$countResult = executeQuery($countSql);
$totalStudents = $countResult ? (int)fetchAssoc($countResult)['count'] : 0;
$totalPages = ceil($totalStudents / $perPage);

// Page title
$pageTitle = 'Students List - EduQuest';
?>

<?php include_once '../includes/header.php'; ?>

<div class="row">
    <!-- Sidebar -->
    <div class="col-lg-3">
        <?php include_once '../includes/sidebar.php'; ?>
    </div>
    
    <!-- Main content -->
    <div class="col-lg-9">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-users me-2"></i>Students List</h1>
            <?php if (isAdmin()): ?>
                <a href="/admin/users.php" class="btn btn-primary">
                    <i class="fas fa-user-plus me-1"></i>Add New Student
                </a>
            <?php endif; ?>
        </div>
        
        <!-- Search and filters -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" action="" class="row g-3">
                    <div class="col-md-6">
                        <div class="input-group">
                            <input type="text" class="form-control" placeholder="Search students..." name="search" value="<?php echo htmlspecialchars($searchQuery); ?>">
                            <button class="btn btn-outline-secondary" type="submit">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="input-group">
                            <label class="input-group-text" for="sortBy">Sort By</label>
                            <select class="form-select" id="sortBy" name="sort" onchange="this.form.submit()">
                                <option value="name" <?php echo $sortBy === 'name' ? 'selected' : ''; ?>>Name</option>
                                <option value="created_at" <?php echo $sortBy === 'created_at' ? 'selected' : ''; ?>>Registration Date</option>
                                <option value="last_login" <?php echo $sortBy === 'last_login' ? 'selected' : ''; ?>>Last Login</option>
                                <option value="achievement_count" <?php echo $sortBy === 'achievement_count' ? 'selected' : ''; ?>>Achievement Count</option>
                            </select>
                            <select class="form-select" name="order" onchange="this.form.submit()">
                                <option value="ASC" <?php echo $sortOrder === 'ASC' ? 'selected' : ''; ?>>Ascending</option>
                                <option value="DESC" <?php echo $sortOrder === 'DESC' ? 'selected' : ''; ?>>Descending</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="col-md-2">
                        <a href="/dashboard/students.php" class="btn btn-outline-secondary w-100">
                            <i class="fas fa-redo me-1"></i>Reset
                        </a>
                    </div>
                </form>
            </div>
        </div>
        
        <?php if (empty($students)): ?>
            <div class="alert alert-info">
                <h4 class="alert-heading"><i class="fas fa-info-circle me-2"></i>No students found</h4>
                <?php if (!empty($searchQuery)): ?>
                    <p>No students match your search criteria. Try adjusting your search query.</p>
                <?php else: ?>
                    <p>There are no students registered in the system yet.</p>
                    <?php if (isAdmin()): ?>
                        <hr>
                        <a href="/admin/users.php" class="btn btn-primary">
                            <i class="fas fa-user-plus me-1"></i>Add First Student
                        </a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <!-- Students list -->
            <div class="card shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Registered</th>
                                    <th>Last Login</th>
                                    <th>Achievements</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($students as $student): ?>
                                    <tr>
                                        <td>
                                            <a href="/dashboard/student_profile.php?id=<?php echo $student['id']; ?>">
                                                <?php echo htmlspecialchars($student['name']); ?>
                                            </a>
                                        </td>
                                        <td><?php echo htmlspecialchars($student['email']); ?></td>
                                        <td><?php echo formatDate($student['created_at'], 'M j, Y'); ?></td>
                                        <td>
                                            <?php echo $student['last_login'] ? formatDate($student['last_login'], 'M j, Y g:i a') : 'Never'; ?>
                                        </td>
                                        <td>
                                            <?php if ((int)$student['achievement_count'] > 0): ?>
                                                <a href="/achievements/view.php?student_id=<?php echo $student['id']; ?>">
                                                    <?php echo (int)$student['achievement_count']; ?>
                                                </a>
                                            <?php else: ?>
                                                0
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="/dashboard/student_profile.php?id=<?php echo $student['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-user"></i>
                                                </a>
                                                <a href="/achievements/add.php?student_id=<?php echo $student['id']; ?>" class="btn btn-sm btn-outline-success">
                                                    <i class="fas fa-plus-circle"></i>
                                                </a>
                                                <a href="/achievements/view.php?student_id=<?php echo $student['id']; ?>" class="btn btn-sm btn-outline-info">
                                                    <i class="fas fa-trophy"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <nav class="mt-4">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($searchQuery); ?>&sort=<?php echo $sortBy; ?>&order=<?php echo $sortOrder; ?>" aria-label="Previous">
                                <span aria-hidden="true">&laquo;</span>
                            </a>
                        </li>
                        
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($searchQuery); ?>&sort=<?php echo $sortBy; ?>&order=<?php echo $sortOrder; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($searchQuery); ?>&sort=<?php echo $sortBy; ?>&order=<?php echo $sortOrder; ?>" aria-label="Next">
                                <span aria-hidden="true">&raquo;</span>
                            </a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
            
            <div class="text-muted text-center mt-2">
                Showing <?php echo count($students); ?> of <?php echo $totalStudents; ?> students
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>
