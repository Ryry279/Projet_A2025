<?php
// admin/index.php
$admin_page_title = "Tableau de Bord";
require_once '../includes/admin_header.php'; // Handles session check for isAdmin(), DB connection

// Check if the user is an admin, if not, redirect to login
if (!isAdmin()) {
    redirect(getBaseUrl() . '/admin/login.php?error=unauthorized');
    exit;
}
?>

<h2><?php echo htmlspecialchars($admin_page_title); ?></h2>
<p>Bienvenue sur le tableau de bord d'administration, <strong><?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></strong> !</p>
<p>Utilisez la navigation ci-dessus pour gérer les différentes sections du site.</p>

<div class="admin-dashboard-stats" style="margin-top: 30px;">
    <?php
    // Example: Count users
    $user_count_result = $conn->query("SELECT COUNT(*) as total_users FROM users");
    $total_users = ($user_count_result && $user_count_result->num_rows > 0) ? $user_count_result->fetch_assoc()['total_users'] : 0;

    // Example: Count courses
    $course_count_result = $conn->query("SELECT COUNT(*) as total_courses FROM courses");
    $total_courses = ($course_count_result && $course_count_result->num_rows > 0) ? $course_count_result->fetch_assoc()['total_courses'] : 0;
    
    // Example: Count categories
    $category_count_result = $conn->query("SELECT COUNT(*) as total_categories FROM categories");
    $total_categories = ($category_count_result && $category_count_result->num_rows > 0) ? $category_count_result->fetch_assoc()['total_categories'] : 0;
    
    // Example: Count newsletter subscribers
    $newsletter_count_result = $conn->query("SELECT COUNT(*) as total_subscribers FROM newsletter_subscriptions");
    $total_subscribers = ($newsletter_count_result && $newsletter_count_result->num_rows > 0) ? $newsletter_count_result->fetch_assoc()['total_subscribers'] : 0;
    ?>
    <div class="admin-stat-card users">
        <h4>Utilisateurs Inscrits</h4>
        <div class="stat-value"><?php echo $total_users; ?></div>
        <a href="manage_users.php">Gérer les utilisateurs</a>
    </div>
    <div class="admin-stat-card courses">
        <h4>Formations Publiées</h4>
        <div class="stat-value"><?php echo $total_courses; ?></div>
        <a href="manage_courses.php">Gérer les formations</a>
    </div>
    <div class="admin-stat-card categories">
        <h4>Catégories Créées</h4>
        <div class="stat-value"><?php echo $total_categories; ?></div>
        <a href="manage_categories.php">Gérer les catégories</a>
    </div>
     <div class="admin-stat-card"> <h4>Abonnés Newsletter</h4>
        <div class="stat-value"><?php echo $total_subscribers; ?></div>
        </div>
</div>

<div style="margin-top: 40px;">
    <h3>Actions Rapides</h3>
    <p>
        <a href="add_course.php" class="admin-button-primary">Ajouter une nouvelle Formation</a>
        <a href="add_category.php" class="admin-button-secondary" style="margin-left:10px;">Ajouter une Catégorie</a>
        </p>
</div>


<?php
// No need to close $conn here if admin_footer.php does it, or if it's closed at the end of script execution.
// However, if admin_footer.php is just presentation, close it here.
if (isset($conn)) {
    $conn->close();
}
require_once '../includes/admin_footer.php';
?>