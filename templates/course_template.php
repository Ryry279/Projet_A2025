<?php
// /templates/course_template.php
/**
 * Template to display a single course card.
 *
 * Expects a $course associative array with course data:
 * $course = [
 * 'id' => (int) course_id,
 * 'title' => (string) course_title,
 * 'description' => (string) course_description,
 * 'thumbnail_url' => (string) path_to_thumbnail,
 * 'category_name' => (string) category_name (optional),
 * 'duration_minutes' => (int) duration (optional),
 * 'is_premium' => (bool) is_premium_course,
 * 'is_favorited_by_current_user' => (bool) (optional, determined by calling page)
 * ];
 * Expects $baseUrl and isLoggedIn() function to be available from functions.php
 * Expects $conn (database connection) to be available if live favorite status check is needed here.
 */

if (!isset($course) || !is_array($course)) {
    echo '<p>Error: Course data is missing for template.</p>';
    return; // Exit if no course data
}

// Ensure functions are available
if (!function_exists('getBaseUrl')) {
    // Attempt to include if not already (though ideally, functions.php is included by the calling page)
    if (file_exists(__DIR__ . '/../includes/functions.php')) {
        require_once __DIR__ . '/../includes/functions.php';
    } else {
        // Fallback or error if functions are critical and missing
        echo "<p>Error: Core functions are unavailable.</p>";
        return;
    }
}
$baseUrl = getBaseUrl(); // Get base URL

$default_thumbnail = $baseUrl . '/assets/images/default_thumbnail.jpg';
$thumbnail_url = (!empty($course['thumbnail_url']) && filter_var($course['thumbnail_url'], FILTER_VALIDATE_URL))
                 ? htmlspecialchars($course['thumbnail_url'])
                 : $baseUrl . '/' . htmlspecialchars($course['thumbnail_url'] ?? 'assets/images/default_thumbnail.jpg');

// Sanitize course data for display
$course_id = filter_var($course['id'], FILTER_VALIDATE_INT);
$course_title = htmlspecialchars($course['title'] ?? 'Titre Indisponible');
$course_description_excerpt = htmlspecialchars(createExcerpt($course['description'] ?? '', 80)); // Assuming createExcerpt is in functions.php
$category_name = htmlspecialchars($course['category_name'] ?? 'N/A');
$duration_minutes = filter_var($course['duration_minutes'] ?? 0, FILTER_VALIDATE_INT);
$is_premium = filter_var($course['is_premium'] ?? false, FILTER_VALIDATE_BOOLEAN);

// Favorite status
$isFavorited = $course['is_favorited_by_current_user'] ?? false; // Passed from the calling page
$favClass = $isFavorited ? 'favorited' : '';
$favText = $isFavorited ? '★' : '☆';
$favTitle = $isFavorited ? 'Retirer des favoris' : 'Ajouter aux favoris';

?>
<div class="course-card reveal-on-scroll">
    <a href="<?php echo $baseUrl; ?>/course_detail.php?id=<?php echo $course_id; ?>" class="course-card-link-wrapper" style="text-decoration: none; color: inherit; display:flex; flex-direction:column; height:100%;">
        <img src="<?php echo $thumbnail_url; ?>" alt="Miniature de <?php echo $course_title; ?>" class="thumbnail" 
             onerror="this.onerror=null; this.src='<?php echo $default_thumbnail; ?>';">
        
        <div class="course-card-content">
            <h3><?php echo $course_title; ?></h3>
            <p class="description"><?php echo $course_description_excerpt; ?></p>
            
            <div class="meta">
                <?php if ($duration_minutes > 0): ?>
                    <span class="duration">🕒 <?php echo $duration_minutes; ?> min</span>
                <?php endif; ?>
                <?php if ($category_name !== 'N/A'): ?>
                    <span class="category-tag"><?php echo $category_name; ?></span>
                <?php endif; ?>
                <?php if ($is_premium): ?>
                    <span class="premium-tag">Premium ✨</span>
                <?php endif; ?>
            </div>
        </div>
    </a>
    <div class="actions" style="padding: 0 22px 20px 22px; margin-top:auto;">
        <a href="<?php echo $baseUrl; ?>/course_detail.php?id=<?php echo $course_id; ?>" class="button button-secondary" style="width: calc(100% - 50px);">Voir la formation</a>
        <?php if (isLoggedIn()): ?>
            <button class="favorite-btn <?php echo $favClass; ?>" data-course-id="<?php echo $course_id; ?>" title="<?php echo $favTitle; ?>">
                <?php echo $favText; ?>
            </button>
        <?php else: ?>
            <a href="<?php echo $baseUrl; ?>/login.php" class="favorite-btn-placeholder" title="Connectez-vous pour ajouter aux favoris">☆</a>
        <?php endif; ?>
    </div>
</div>