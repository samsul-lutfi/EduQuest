<?php
/**
 * Language settings and translations
 */

// Set default language if not set
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'id'; // Default to Indonesian
}

// Handle language change but only if headers haven't been sent
if (isset($_GET['lang']) && in_array($_GET['lang'], ['en', 'id']) && !headers_sent()) {
    $_SESSION['lang'] = $_GET['lang'];
    
    // Redirect to the same page without the query string
    $redirect_url = strtok($_SERVER['REQUEST_URI'], '?');
    header("Location: $redirect_url");
    exit;
} elseif (isset($_GET['lang']) && in_array($_GET['lang'], ['en', 'id'])) {
    // If headers are already sent, just set the session variable
    $_SESSION['lang'] = $_GET['lang'];
}

// Language translations
$translations = [
    'en' => [
        // Common
        'site_name' => 'EduQuest',
        'welcome' => 'Welcome to EduQuest',
        'login' => 'Login',
        'register' => 'Register',
        'logout' => 'Logout',
        'dashboard' => 'Dashboard',
        'profile' => 'Profile',
        'achievements' => 'Achievements',
        'students' => 'Students',
        'teachers' => 'Teachers',
        'courses' => 'Courses',
        'settings' => 'Settings',
        
        // Auth
        'email' => 'Email Address',
        'password' => 'Password',
        'confirm_password' => 'Confirm Password',
        'remember_me' => 'Remember Me',
        'forgot_password' => 'Forgot Password?',
        'already_have_account' => 'Already have an account?',
        'dont_have_account' => 'Don\'t have an account?',
        'full_name' => 'Full Name',
        'create_account' => 'Create an Account',
        'terms_conditions' => 'Terms and Conditions',
        'agree_terms' => 'I agree to the Terms and Conditions',
        'student' => 'Student',
        'teacher' => 'Teacher',
        'role' => 'Role',
        
        // Dashboard
        'your_achievements' => 'Your Achievements',
        'recent_achievements' => 'Recent Achievements',
        'achievement_points' => 'Achievement Points',
        'achievement_progress' => 'Achievement Progress',
        'view_all' => 'View All',
        'category_distribution' => 'Category Distribution',
        'achievement_history' => 'Achievement History',
        
        // Achievements
        'add_achievement' => 'Add Achievement',
        'edit_achievement' => 'Edit Achievement',
        'delete_achievement' => 'Delete Achievement',
        'achievement_title' => 'Title',
        'achievement_description' => 'Description',
        'achievement_category' => 'Category',
        'achievement_date' => 'Date',
        'achievement_points' => 'Points',
        'no_achievements' => 'No achievements found.',
        
        // Categories
        'manage_categories' => 'Manage Categories',
        'categories' => 'Categories',
        'add_category' => 'Add Category',
        'edit_category' => 'Edit Category',
        'delete_category' => 'Delete Category',
        'category_name' => 'Category Name',
        'category_description' => 'Category Description',
        'no_categories' => 'No categories found.',
        'confirm_delete_category' => 'Are you sure you want to delete this category?',
        'delete_category_warning' => 'This will only work if the category is not being used by any achievements.',
        'update' => 'Update',
        
        // Courses & Quizzes
        'courses' => 'Courses',
        'add_course' => 'Add Course',
        'edit_course' => 'Edit Course',
        'delete_course' => 'Delete Course',
        'course_name' => 'Course Name',
        'course_description' => 'Course Description',
        'quizzes' => 'Quizzes',
        'add_quiz' => 'Add Quiz',
        'edit_quiz' => 'Edit Quiz',
        'delete_quiz' => 'Delete Quiz',
        'quiz_name' => 'Quiz Name',
        'quiz_description' => 'Quiz Description',
        'quiz_type' => 'Quiz Type',
        'weekly_quiz' => 'Weekly Quiz',
        'daily_quiz' => 'Daily Quiz',
        'midterm_exam' => 'Midterm Exam',
        'final_exam' => 'Final Exam',
        'start_date' => 'Start Date',
        'end_date' => 'End Date',
        'no_courses' => 'No courses found.',
        'no_quizzes' => 'No quizzes found for this course.',
        'students' => 'Students',
        'created_at' => 'Created At',
        'actions' => 'Actions',
        'confirm_delete_course' => 'Are you sure you want to delete this course?',
        'delete_course_warning' => 'All quizzes and student enrollments will be permanently deleted.',
        'confirm_delete_quiz' => 'Are you sure you want to delete this quiz?',
        'delete_quiz_warning' => 'All questions and student submissions will be permanently deleted.',
        'enrolled_students' => 'Enrolled Students',
        'no_enrolled_students' => 'No students enrolled in this course yet.',
        'enroll_student' => 'Enroll Student',
        'unenroll_student' => 'Unenroll Student',
        'confirm_unenroll_student' => 'Are you sure you want to unenroll this student?',
        'unenroll_student_warning' => 'The student will no longer have access to this course and all their progress will be lost.',
        'enrolled' => 'Enrolled',
        'enrollment_date' => 'Enrollment Date',
        'student_name' => 'Student Name',
        'select_student' => 'Select Student',
        'unenroll' => 'Unenroll',
        
        // Course Contents
        'content' => 'Content',
        'contents' => 'Contents',
        'add_content' => 'Add Content',
        'edit_content' => 'Edit Content',
        'delete_content' => 'Delete Content',
        'confirm_delete_content' => 'Are you sure you want to delete this content?',
        'delete_content_warning' => 'This will permanently delete the content and all associated student progress.',
        'this_action_cannot_be_undone' => 'This action cannot be undone.',
        'content_title' => 'Content Title',
        'content_description' => 'Content Description',
        'content_type' => 'Content Type',
        'select_content_type' => 'Select Content Type',
        'file_upload' => 'File Upload',
        'file_upload_help' => 'Upload PDF, documents, images, or other files (max size: 10MB)',
        'content_status' => 'Content Status',
        'draft' => 'Draft',
        'published' => 'Published',
        'view_contents' => 'View Contents',
        'back_to_contents' => 'Back to Contents',
        'back_to_course' => 'Back to Course',
        'description' => 'Description',
        'attached_file' => 'Attached File',
        'file_name' => 'File Name',
        'download_file' => 'Download File',
        'content_completed' => 'You have completed this content',
        'mark_as_completed' => 'Mark as Completed',
        'discussion' => 'Discussion',
        'add_comment' => 'Add Comment',
        'no_comments' => 'No comments yet. Be the first to comment!',
        'reply' => 'Reply',
        'submit_reply' => 'Submit Reply',
        'no_contents' => 'No contents found for this course.',
        
        // Quiz Questions
        'questions' => 'Questions',
        'add_question' => 'Add Question',
        'edit_question' => 'Edit Question',
        'delete_question' => 'Delete Question',
        'question_text' => 'Question Text',
        'question_type' => 'Question Type',
        'question_type_cannot_be_changed' => 'Question type cannot be changed after creation.',
        'multiple_choice' => 'Multiple Choice',
        'essay' => 'Essay',
        'points' => 'Points',
        'no_questions' => 'No questions found for this quiz.',
        'options' => 'Options',
        'edit_options' => 'Edit Options',
        'multiple_choice_instructions' => 'Add options below. Mark at least one option as correct.',
        'current_options' => 'Current Options',
        'add_new_option' => 'Add New Option',
        'option_text' => 'Option Text',
        'is_correct_answer' => 'Is Correct Answer',
        'add_option' => 'Add Option',
        'edit_option' => 'Edit Option',
        'delete_option' => 'Delete Option',
        'confirm_delete_option' => 'Are you sure you want to delete this option?',
        'back_to_questions' => 'Back to Questions',
        'delete_question_warning' => 'This will permanently delete the question and all associated options.',
        
        // Buttons
        'submit' => 'Submit',
        'save' => 'Save',
        'cancel' => 'Cancel',
        'delete' => 'Delete',
        'edit' => 'Edit',
        'add' => 'Add',
        
        // Messages
        'success' => 'Success',
        'error' => 'Error',
        'confirm_delete' => 'Are you sure you want to delete this?',
    ],
    'id' => [
        // Common
        'site_name' => 'EduQuest',
        'welcome' => 'Selamat Datang di EduQuest',
        'login' => 'Masuk',
        'register' => 'Daftar',
        'logout' => 'Keluar',
        'dashboard' => 'Dasbor',
        'profile' => 'Profil',
        'achievements' => 'Pencapaian',
        'students' => 'Siswa',
        'teachers' => 'Guru/Dosen',
        'courses' => 'Mata Pelajaran',
        'settings' => 'Pengaturan',
        
        // Auth
        'email' => 'Alamat Email',
        'password' => 'Kata Sandi',
        'confirm_password' => 'Konfirmasi Kata Sandi',
        'remember_me' => 'Ingat Saya',
        'forgot_password' => 'Lupa Kata Sandi?',
        'already_have_account' => 'Sudah punya akun?',
        'dont_have_account' => 'Belum punya akun?',
        'full_name' => 'Nama Lengkap',
        'create_account' => 'Buat Akun',
        'terms_conditions' => 'Syarat dan Ketentuan',
        'agree_terms' => 'Saya setuju dengan Syarat dan Ketentuan',
        'student' => 'Siswa',
        'teacher' => 'Guru/Dosen',
        'role' => 'Peran',
        
        // Dashboard
        'your_achievements' => 'Pencapaian Anda',
        'recent_achievements' => 'Pencapaian Terbaru',
        'achievement_points' => 'Poin Pencapaian',
        'achievement_progress' => 'Progres Pencapaian',
        'view_all' => 'Lihat Semua',
        'category_distribution' => 'Distribusi Kategori',
        'achievement_history' => 'Riwayat Pencapaian',
        
        // Achievements
        'add_achievement' => 'Tambah Pencapaian',
        'edit_achievement' => 'Edit Pencapaian',
        'delete_achievement' => 'Hapus Pencapaian',
        'achievement_title' => 'Judul',
        'achievement_description' => 'Deskripsi',
        'achievement_category' => 'Kategori',
        'achievement_date' => 'Tanggal',
        'achievement_points' => 'Poin',
        'no_achievements' => 'Tidak ada pencapaian ditemukan.',
        
        // Categories
        'manage_categories' => 'Kelola Kategori',
        'categories' => 'Kategori',
        'add_category' => 'Tambah Kategori',
        'edit_category' => 'Edit Kategori',
        'delete_category' => 'Hapus Kategori',
        'category_name' => 'Nama Kategori',
        'category_description' => 'Deskripsi Kategori',
        'no_categories' => 'Tidak ada kategori ditemukan.',
        'confirm_delete_category' => 'Apakah Anda yakin ingin menghapus kategori ini?',
        'delete_category_warning' => 'Ini hanya akan berhasil jika kategori tidak digunakan oleh pencapaian apapun.',
        'update' => 'Perbarui',
        
        // Courses & Quizzes
        'courses' => 'Mata Pelajaran',
        'add_course' => 'Tambah Mata Pelajaran',
        'edit_course' => 'Edit Mata Pelajaran',
        'delete_course' => 'Hapus Mata Pelajaran',
        'course_name' => 'Nama Mata Pelajaran',
        'course_description' => 'Deskripsi Mata Pelajaran',
        'quizzes' => 'Kuis',
        'add_quiz' => 'Tambah Kuis',
        'edit_quiz' => 'Edit Kuis',
        'delete_quiz' => 'Hapus Kuis',
        'quiz_name' => 'Nama Kuis',
        'quiz_description' => 'Deskripsi Kuis',
        'quiz_type' => 'Jenis Kuis',
        'weekly_quiz' => 'Kuis Mingguan',
        'daily_quiz' => 'Kuis Harian',
        'midterm_exam' => 'Ujian Tengah Semester',
        'final_exam' => 'Ujian Akhir Semester',
        'start_date' => 'Tanggal Mulai',
        'end_date' => 'Tanggal Selesai',
        'no_courses' => 'Tidak ada mata pelajaran ditemukan.',
        'no_quizzes' => 'Tidak ada kuis ditemukan untuk mata pelajaran ini.',
        'students' => 'Siswa',
        'created_at' => 'Dibuat Pada',
        'actions' => 'Aksi',
        'confirm_delete_course' => 'Apakah Anda yakin ingin menghapus mata pelajaran ini?',
        'delete_course_warning' => 'Semua kuis dan pendaftaran siswa akan dihapus secara permanen.',
        'confirm_delete_quiz' => 'Apakah Anda yakin ingin menghapus kuis ini?',
        'delete_quiz_warning' => 'Semua pertanyaan dan jawaban siswa akan dihapus secara permanen.',
        'enrolled_students' => 'Siswa Terdaftar',
        'no_enrolled_students' => 'Belum ada siswa yang terdaftar dalam mata pelajaran ini.',
        'enroll_student' => 'Daftarkan Siswa',
        'unenroll_student' => 'Keluarkan Siswa',
        'confirm_unenroll_student' => 'Apakah Anda yakin ingin mengeluarkan siswa ini?',
        'unenroll_student_warning' => 'Siswa tidak akan lagi memiliki akses ke mata pelajaran ini dan semua kemajuan mereka akan hilang.',
        'enrolled' => 'Terdaftar',
        'enrollment_date' => 'Tanggal Pendaftaran',
        'student_name' => 'Nama Siswa',
        'select_student' => 'Pilih Siswa',
        'unenroll' => 'Keluarkan',
        
        // Course Contents
        'content' => 'Konten',
        'contents' => 'Konten',
        'add_content' => 'Tambah Konten',
        'edit_content' => 'Edit Konten',
        'delete_content' => 'Hapus Konten',
        'confirm_delete_content' => 'Apakah Anda yakin ingin menghapus konten ini?',
        'delete_content_warning' => 'Ini akan menghapus konten dan semua kemajuan siswa yang terkait secara permanen.',
        'this_action_cannot_be_undone' => 'Tindakan ini tidak dapat dibatalkan.',
        'content_title' => 'Judul Konten',
        'content_description' => 'Deskripsi Konten',
        'content_type' => 'Jenis Konten',
        'select_content_type' => 'Pilih Jenis Konten',
        'file_upload' => 'Unggah Berkas',
        'file_upload_help' => 'Unggah PDF, dokumen, gambar, atau berkas lainnya (ukuran maks: 10MB)',
        'content_status' => 'Status Konten',
        'draft' => 'Draf',
        'published' => 'Dipublikasikan',
        'view_contents' => 'Lihat Konten',
        'back_to_contents' => 'Kembali ke Konten',
        'back_to_course' => 'Kembali ke Mata Pelajaran',
        'description' => 'Deskripsi',
        'attached_file' => 'Berkas Terlampir',
        'file_name' => 'Nama Berkas',
        'download_file' => 'Unduh Berkas',
        'content_completed' => 'Anda telah menyelesaikan konten ini',
        'mark_as_completed' => 'Tandai Selesai',
        'discussion' => 'Diskusi',
        'add_comment' => 'Tambahkan Komentar',
        'no_comments' => 'Belum ada komentar. Jadilah yang pertama berkomentar!',
        'reply' => 'Balas',
        'submit_reply' => 'Kirim Balasan',
        'no_contents' => 'Tidak ada konten yang ditemukan untuk mata pelajaran ini.',
        
        // Quiz Questions
        'questions' => 'Pertanyaan',
        'add_question' => 'Tambah Pertanyaan',
        'edit_question' => 'Edit Pertanyaan',
        'delete_question' => 'Hapus Pertanyaan',
        'question_text' => 'Teks Pertanyaan',
        'question_type' => 'Jenis Pertanyaan',
        'question_type_cannot_be_changed' => 'Jenis pertanyaan tidak dapat diubah setelah dibuat.',
        'multiple_choice' => 'Pilihan Ganda',
        'essay' => 'Esai',
        'points' => 'Poin',
        'no_questions' => 'Tidak ada pertanyaan ditemukan untuk kuis ini.',
        'options' => 'Pilihan',
        'edit_options' => 'Edit Pilihan',
        'multiple_choice_instructions' => 'Tambahkan pilihan di bawah. Tandai setidaknya satu pilihan sebagai jawaban yang benar.',
        'current_options' => 'Pilihan Saat Ini',
        'add_new_option' => 'Tambah Pilihan Baru',
        'option_text' => 'Teks Pilihan',
        'is_correct_answer' => 'Apakah Jawaban Benar',
        'add_option' => 'Tambah Pilihan',
        'edit_option' => 'Edit Pilihan',
        'delete_option' => 'Hapus Pilihan',
        'confirm_delete_option' => 'Apakah Anda yakin ingin menghapus pilihan ini?',
        'back_to_questions' => 'Kembali ke Pertanyaan',
        'delete_question_warning' => 'Hal ini akan menghapus pertanyaan dan semua pilihan terkait secara permanen.',
        
        // Buttons
        'submit' => 'Kirim',
        'save' => 'Simpan',
        'cancel' => 'Batal',
        'delete' => 'Hapus',
        'edit' => 'Edit',
        'add' => 'Tambah',
        
        // Messages
        'success' => 'Berhasil',
        'error' => 'Kesalahan',
        'confirm_delete' => 'Apakah Anda yakin ingin menghapus ini?',
    ]
];

/**
 * Get translation for a key in the current language
 * 
 * @param string $key The translation key
 * @return string The translated text
 */
function __($key) {
    global $translations;
    $lang = $_SESSION['lang'] ?? 'id';
    
    if (isset($translations[$lang][$key])) {
        return $translations[$lang][$key];
    }
    
    // Fallback to English
    if (isset($translations['en'][$key])) {
        return $translations['en'][$key];
    }
    
    // Return the key if no translation found
    return $key;
}