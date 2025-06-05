<?php
// category.php
require_once 'includes/db_connect.php';
require_once 'includes/functions.php'; // For isLoggedIn, getBaseUrl, createExcerpt etc.

$category_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$category_slug = filter_input(INPUT_GET, 'slug', FILTER_SANITIZE_STRING); // Alternative way to identify category

$category_data = null;
$courses_in_category = [];

if ($category_id) {
    $stmt_cat = $conn->prepare("SELECT id, name, description FROM categories WHERE id = ?");
    $stmt_cat->bind_param("i", $category_id);
} elseif ($category_slug) {
    // Assuming 'name' can be used as a simple slug for now.
    // In a real app, you might have a dedicated 'slug' column.
    $stmt_cat = $conn->prepare("SELECT id, name, description FROM categories WHERE name = ?"); // Or a proper slug column
    $stmt_cat->bind_param("s", $category_slug);
} else {
    // No ID or slug provided, redirect or show error
    redirect(getBaseUrl() . '/courses.php?error=nocategory');
    exit;
}

$stmt_cat->execute();
$result_cat = $stmt_cat->get_result();
if ($result_cat->num_rows > 0) {
    $category_data = $result_cat->fetch_assoc();
    $page_title = "Formations en " . htmlspecialchars($category_data['name']);
    $page_description = "Parcourez toutes nos formations dans la catégorie : " . htmlspecialchars($category_data['name']) . ". " . htmlspecialchars(createExcerpt($category_data['description'] ?? '', 120));
    $actual_category_id = $category_data['id']; // Use this ID to fetch courses

    // Fetch courses in this category
    // Also check favorite status for the current user if logged in
    $user_id_for_fav_check = isLoggedIn() ? $_SESSION['user_id'] : 0; // Use 0 or null if not logged in

    // Updated query to check favorite status
    $stmt_courses = $conn->prepare("
        SELECT c.*, cat.name as category_name, 
               (SELECT COUNT(*) FROM favorites f WHERE f.course_id = c.id AND f.user_id = ?) AS is_favorited_by_current_user
        FROM courses c
        LEFT JOIN categories cat ON c.category_id = cat.id
        WHERE c.category_id = ?
        ORDER BY c.title ASC
    ");
    $stmt_courses->bind_param("ii", $user_id_for_fav_check, $actual_category_id);
    $stmt_courses->execute();
    $result_courses = $stmt_courses->get_result();
    while ($row = $result_courses->fetch_assoc()) {
        $row['is_favorited_by_current_user'] = (bool)$row['is_favorited_by_current_user']; // Cast to boolean
        $courses_in_category[] = $row;
    }
    $stmt_courses->close();

} else {
    $page_title = "Catégorie non trouvée";
    $page_description = "La catégorie que vous recherchez n'existe pas ou n'est plus disponible.";
    // Optionally, redirect to a 404 page or courses index
    // http_response_code(404); // Set 404 status
}
$stmt_cat->close();

require_once 'includes/header.php';
?>

<div class="container">
    <?php if ($category_data): ?>
        <section class="category-header" style="margin-bottom: 30px; text-align:center;">
            <h1>Formations en <?php echo htmlspecialchars($category_data['name']); ?></h1>
            <?php if (!empty($category_data['description'])): ?>
                <p class="lead"><?php echo nl2br(htmlspecialchars($category_data['description'])); ?></p>
            <?php endif; ?>
        </section>

        <?php if (!empty($courses_in_category)): ?>
            <div class="course-grid">
                <?php foreach ($courses_in_category as $course_data): ?>
                    <?php 
                        // Set $course variable for the template
                        $course = $course_data; 
                        include __DIR__ . '/templates/course_template.php'; // Use course card template
                    ?>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p style="text-align:center;">Il n'y a actuellement aucune formation disponible dans cette catégorie.</p>
        <?php endif; ?>

    <?php else: ?>
        <section style="text-align:center;">
            <h1>Catégorie non trouvée</h1>
            <p>Désolé, la catégorie que vous essayez de consulter n'existe pas.</p>
            <p><a href="<?php echo getBaseUrl(); ?>/courses.php" class="button">Voir toutes les formations</a></p>
        </section>
    <?php endif; ?>
</div>

<?php
if (isset($conn)) {
    $conn->close();
}
require_once 'includes/footer.php';
?>