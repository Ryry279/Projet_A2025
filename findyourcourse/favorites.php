<?php
// favorites.php
$page_title = "Mes Formations Favorites";
$page_description = "Retrouvez ici toutes les formations que vous avez ajoutées à vos favoris pour un accès rapide.";
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';
require_once 'includes/header.php';

if (!isLoggedIn()) {
    // Store the intended destination to redirect after login
    $_SESSION['redirect_url'] = getBaseUrl() . '/favorites.php';
    $_SESSION['info_message'] = "Vous devez être connecté pour voir vos favoris.";
    redirect(getBaseUrl() . '/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$favorited_courses = [];

// Fetch favorited courses for the current user, along with their category name
// And re-check favorite status (which should always be true here, but good for template consistency)
$stmt = $conn->prepare("
    SELECT c.*, cat.name as category_name, 1 AS is_favorited_by_current_user
    FROM courses c
    JOIN favorites f ON c.id = f.course_id
    LEFT JOIN categories cat ON c.category_id = cat.id
    WHERE f.user_id = ?
    ORDER BY f.favorited_at DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $row['is_favorited_by_current_user'] = (bool)$row['is_favorited_by_current_user']; // Cast
    $favorited_courses[] = $row;
}
$stmt->close();
?>

<div class="container">
    <section class="page-header" style="text-align:center; margin-bottom:40px;">
        <h1>Mes Favoris</h1>
        <p class="lead">Voici les formations que vous avez sauvegardées. Cliquez sur une formation pour y accéder.</p>
    </section>

    <?php if (isset($_SESSION['favorite_action_message'])): ?>
        <p style="padding: 10px; border-radius: 5px; background-color: #e6fffa; color: #00695c; border: 1px solid #b2dfdb; text-align:center; margin-bottom:20px;">
            <?php echo htmlspecialchars($_SESSION['favorite_action_message']); unset($_SESSION['favorite_action_message']); ?>
        </p>
    <?php endif; ?>
    
    <?php if (!empty($favorited_courses)): ?>
        <div class="course-grid">
            <?php foreach ($favorited_courses as $course_data): ?>
                <?php 
                    $course = $course_data; // Set $course for the template
                    // The 'is_favorited_by_current_user' is already set to true from the SQL query
                    include __DIR__ . '/templates/course_template.php'; 
                ?>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p style="text-align:center; font-size:1.1em; padding: 30px 0;">
            Vous n'avez pas encore de formations favorites. 
            <a href="<?php echo getBaseUrl(); ?>/courses.php">Explorez nos formations</a> et cliquez sur l'icône '☆' pour en ajouter !
        </p>
    <?php endif; ?>
</div>

<?php
if (isset($conn)) {
    $conn->close();
}
require_once 'includes/footer.php';
?>