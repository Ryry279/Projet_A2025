<?php
// search.php
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

$search_query_raw = $_GET['query'] ?? '';
$search_query_sanitized = sanitizeInput($search_query_raw);

$page_title = empty($search_query_sanitized) ? "Recherche" : "Résultats pour \"" . htmlspecialchars($search_query_sanitized) . "\"";
$page_description = "Résultats de recherche pour \"" . htmlspecialchars($search_query_sanitized) . "\" sur Find Your Course.";

require_once 'includes/header.php';

$search_results = [];
$search_performed = !empty($search_query_sanitized);

if ($search_performed) {
    $user_id_for_fav_check = isLoggedIn() ? $_SESSION['user_id'] : 0;
    $search_term_like = "%" . $search_query_sanitized . "%";

    // Search in course titles, descriptions, and category names
    $stmt = $conn->prepare("
        SELECT DISTINCT c.*, cat.name as category_name,
               (SELECT COUNT(*) FROM favorites f WHERE f.course_id = c.id AND f.user_id = ?) AS is_favorited_by_current_user
        FROM courses c
        LEFT JOIN categories cat ON c.category_id = cat.id
        WHERE (c.title LIKE ? OR c.description LIKE ? OR cat.name LIKE ?)
        ORDER BY c.title ASC
    ");
    // Note: The first '?' is for user_id for favorite check, the next three are for search terms.
    $stmt->bind_param("isss", $user_id_for_fav_check, $search_term_like, $search_term_like, $search_term_like);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $row['is_favorited_by_current_user'] = (bool)$row['is_favorited_by_current_user'];
        $search_results[] = $row;
    }
    $stmt->close();
}
?>

<div class="container">
    <section class="page-header" style="text-align:center; margin-bottom:30px;">
        <h1><?php echo $page_title; ?></h1>
        <?php if (!$search_performed): ?>
            <p class="lead">Veuillez entrer un terme de recherche ci-dessus pour trouver des formations.</p>
        <?php elseif (empty($search_results)): ?>
            <p class="lead">Aucune formation ne correspond à votre recherche pour "<strong><?php echo htmlspecialchars($search_query_sanitized); ?></strong>".</p>
            <p>Essayez des mots-clés différents ou plus généraux, ou <a href="<?php echo getBaseUrl(); ?>/courses.php">parcourez toutes nos formations</a>.</p>
        <?php else: ?>
            <p class="lead">Nous avons trouvé <?php echo count($search_results); ?> formation(s) correspondant à "<strong><?php echo htmlspecialchars($search_query_sanitized); ?></strong>".</p>
        <?php endif; ?>
    </section>

    <?php if ($search_performed && !empty($search_results)): ?>
        <div class="course-grid" style="margin-top: 30px;">
            <?php foreach ($search_results as $course_data): ?>
                <?php 
                    $course = $course_data; // Set $course for the template
                    include __DIR__ . '/templates/course_template.php'; 
                ?>
            <?php endforeach; ?>
        </div>
    <?php elseif ($search_performed && empty($search_results)): ?>
        <?php endif; ?>
</div>

<?php
if (isset($conn)) {
    $conn->close();
}
require_once 'includes/footer.php';
?>