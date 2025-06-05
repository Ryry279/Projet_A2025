<?php
// courses.php
$page_title = "Toutes nos Formations";
$page_description = "Explorez notre catalogue complet de formations en ligne sur les ERP, Salesforce, et plus encore. Trouvez le cours qui vous convient.";
require_once 'includes/db_connect.php'; // Must be first for $conn
require_once 'includes/functions.php';   // For isLoggedIn, getBaseUrl, etc.
require_once 'includes/header.php';      // Includes functions.php again, but it's fine due to session_status() check

// --- Search and Filter Logic (Basic Example) ---
$search_query = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$filter_category = isset($_GET['category']) ? filter_var($_GET['category'], FILTER_VALIDATE_INT) : null;
$filter_access = isset($_GET['access']) ? sanitizeInput($_GET['access']) : ''; // 'free', 'premium'

$sql = "SELECT c.*, cat.name as category_name, 
               (SELECT COUNT(*) FROM favorites f WHERE f.course_id = c.id AND f.user_id = ?) AS is_favorited_by_current_user
        FROM courses c
        LEFT JOIN categories cat ON c.category_id = cat.id";
$where_clauses = [];
$params = [];
$types = '';

// User ID for favorite check
$user_id_for_fav_check = isLoggedIn() ? $_SESSION['user_id'] : 0;
$params[] = $user_id_for_fav_check;
$types .= 'i';

if (!empty($search_query)) {
    $where_clauses[] = "(c.title LIKE ? OR c.description LIKE ?)";
    $search_like = "%" . $search_query . "%";
    $params[] = $search_like;
    $params[] = $search_like;
    $types .= 'ss';
}
if ($filter_category) {
    $where_clauses[] = "c.category_id = ?";
    $params[] = $filter_category;
    $types .= 'i';
}
if ($filter_access === 'free') {
    $where_clauses[] = "c.is_premium = 0";
} elseif ($filter_access === 'premium') {
    $where_clauses[] = "c.is_premium = 1";
}

if (!empty($where_clauses)) {
    $sql .= " WHERE " . implode(" AND ", $where_clauses);
}
$sql .= " ORDER BY c.created_at DESC"; // Or by title, etc.

$stmt_courses = $conn->prepare($sql);
if ($stmt_courses && !empty($types)) {
    $stmt_courses->bind_param($types, ...$params);
} elseif (!$stmt_courses) {
    // Handle prepare error
    error_log("Failed to prepare statement for courses list: " . $conn->error);
    echo "<p class='container error'>Une erreur est survenue lors de la récupération des formations.</p>";
    require_once 'includes/footer.php';
    exit;
}

$stmt_courses->execute();
$result_courses = $stmt_courses->get_result();
$courses_list = [];
while ($row = $result_courses->fetch_assoc()) {
    $row['is_favorited_by_current_user'] = (bool)$row['is_favorited_by_current_user']; // Cast
    $courses_list[] = $row;
}
$stmt_courses->close();

// Fetch categories for the filter dropdown
$categories_for_filter = [];
$cat_result = $conn->query("SELECT id, name FROM categories ORDER BY name ASC");
if ($cat_result) {
    while($cat_row = $cat_result->fetch_assoc()) {
        $categories_for_filter[] = $cat_row;
    }
    $cat_result->close();
}

?>

<div class="container">
    <section class="page-header" style="text-align:center; margin-bottom:40px;">
        <h1>Nos Formations</h1>
        <p class="lead">Découvrez une gamme complète de cours pour débuter ou approfondir vos connaissances en ERP et Salesforce.</p>
    </section>

    <section class="filters-search-bar" style="margin-bottom: 30px; padding: 20px; background-color: #f9f9f9; border-radius: 10px;">
        <form action="courses.php" method="GET" style="display: flex; flex-wrap: wrap; gap: 15px; align-items: center;">
            <div style="flex: 2; min-width: 200px;">
                <label for="search-course" class="sr-only">Rechercher un cours</label>
                <input type="text" id="search-course" name="search" value="<?php echo htmlspecialchars($search_query); ?>" placeholder="Rechercher par mot-clé..." style="width:100%; padding:10px; border-radius:6px; border:1px solid #ddd;">
            </div>
            <div style="flex: 1; min-width: 150px;">
                <label for="filter-category" class="sr-only">Filtrer par catégorie</label>
                <select id="filter-category" name="category" style="width:100%; padding:10px; border-radius:6px; border:1px solid #ddd;">
                    <option value="">Toutes les catégories</option>
                    <?php foreach ($categories_for_filter as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo ($filter_category == $cat['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="flex: 1; min-width: 150px;">
                <label for="filter-access" class="sr-only">Filtrer par accès</label>
                <select id="filter-access" name="access" style="width:100%; padding:10px; border-radius:6px; border:1px solid #ddd;">
                    <option value="">Tous les accès</option>
                    <option value="free" <?php echo ($filter_access === 'free') ? 'selected' : ''; ?>>Gratuit</option>
                    <option value="premium" <?php echo ($filter_access === 'premium') ? 'selected' : ''; ?>>Premium</option>
                </select>
            </div>
            <button type="submit" class="button" style="padding: 10px 20px;">Filtrer</button>
            <?php if (!empty($search_query) || $filter_category || !empty($filter_access)): ?>
                 <a href="courses.php" style="padding: 10px 15px; text-decoration:none; color:#555; font-size:0.9em; align-self:center; background-color:#e0e0e0; border-radius:6px;">Réinitialiser</a>
            <?php endif; ?>
        </form>
    </section>

    <?php if (!empty($courses_list)): ?>
        <div class="course-grid">
            <?php foreach ($courses_list as $course_data): ?>
                <?php 
                    // Set $course variable for the template
                    $course = $course_data; 
                    include __DIR__ . '/templates/course_template.php'; // Use course card template
                ?>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p style="text-align:center; font-size:1.1em; padding: 30px 0;">
            Aucune formation ne correspond à vos critères de recherche pour le moment.
            <?php if (empty($search_query) && !$filter_category && empty($filter_access)): ?>
                Revenez bientôt pour découvrir nos nouveaux cours !
            <?php else: ?>
                Essayez d'élargir votre recherche ou de <a href="courses.php">réinitialiser les filtres</a>.
            <?php endif; ?>
        </p>
    <?php endif; ?>

    </div>

<?php
if (isset($conn)) {
    $conn->close();
}
require_once 'includes/footer.php';
?>