<?php
// course_detail.php
require_once 'includes/db_connect.php';
require_once 'includes/functions.php'; // For isLoggedIn, getBaseUrl, isPremiumStudent, etc.

$course_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$course_id) {
    redirect(getBaseUrl() . '/courses.php?error=invalidcourse');
    exit;
}

// Fetch course details along with category name and favorite status
$user_id_for_fav_check = isLoggedIn() ? $_SESSION['user_id'] : 0;

$stmt = $conn->prepare("
    SELECT c.*, cat.name AS category_name,
           (SELECT COUNT(*) FROM favorites f WHERE f.course_id = c.id AND f.user_id = ?) AS is_favorited_by_current_user
    FROM courses c
    LEFT JOIN categories cat ON c.category_id = cat.id
    WHERE c.id = ?
");
$stmt->bind_param("ii", $user_id_for_fav_check, $course_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $course = $result->fetch_assoc();
    $course['is_favorited_by_current_user'] = (bool)$course['is_favorited_by_current_user']; // Cast to boolean
    $page_title = htmlspecialchars($course['title']);
    $page_description = htmlspecialchars(createExcerpt($course['description'] ?? '', 150));
} else {
    $page_title = "Formation non trouvée";
    $page_description = "La formation que vous recherchez n'existe pas ou a été déplacée.";
    http_response_code(404); // Set 404 status for not found
    $course = null; // Ensure $course is null if not found
}
$stmt->close();

require_once 'includes/header.php'; // Contains CSRF token generation
?>

<div class="container course-detail-page">
    <?php if ($course): ?>
        <article class="course-content-wrapper">
            <header class="course-header" style="margin-bottom:30px;">
                <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:15px;">
                    <div>
                        <h1 style="margin-bottom: 0.2em;"><?php echo htmlspecialchars($course['title']); ?></h1>
                        <?php if (!empty($course['category_name'])): ?>
                            <p class="course-category" style="font-size: 1.1em; color: #555;">
                                Catégorie : <a href="category.php?id=<?php echo htmlspecialchars($course['category_id']); ?>"><?php echo htmlspecialchars($course['category_name']); ?></a>
                            </p>
                        <?php endif; ?>
                    </div>
                    <div class="course-actions" style="padding-top:10px;">
                        <?php if (isLoggedIn()): ?>
                            <button class="favorite-btn <?php echo $course['is_favorited_by_current_user'] ? 'favorited' : ''; ?>" 
                                    data-course-id="<?php echo $course['id']; ?>" 
                                    title="<?php echo $course['is_favorited_by_current_user'] ? 'Retirer des favoris' : 'Ajouter aux favoris'; ?>">
                                <?php echo $course['is_favorited_by_current_user'] ? '★' : '☆'; ?>
                            </button>
                        <?php else: ?>
                             <a href="<?php echo getBaseUrl(); ?>/login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" class="favorite-btn-placeholder" title="Connectez-vous pour ajouter aux favoris">☆</a>
                        <?php endif; ?>
                        </div>
                </div>
                
                <?php if ($course['is_premium']): ?>
                    <p class="premium-notice" style="background-color: #fffbe6; border: 1px solid #ffe58f; color: #fa8c16; padding: 10px 15px; border-radius: 6px; margin-top:15px;">
                        ✨ Ceci est une formation <strong>Premium</strong>. Un accès complet est requis pour le contenu avancé.
                    </p>
                <?php endif; ?>
            </header>

            <div class="course-main-content" style="display:flex; flex-wrap:wrap; gap:30px;">
                <div class="course-description-and-media" style="flex:3; min-width: 300px;">
                    <?php
                    // Check if user has access to premium content
                    $has_access = !$course['is_premium'] || isPremiumStudent();

                    if (!empty($course['thumbnail_url'])): 
                        $thumbnail_path = (filter_var($course['thumbnail_url'], FILTER_VALIDATE_URL) ? $course['thumbnail_url'] : getBaseUrl() . '/' . $course['thumbnail_url']);
                    ?>
                        <img src="<?php echo htmlspecialchars($thumbnail_path); ?>" alt="Image de la formation <?php echo htmlspecialchars($course['title']); ?>" 
                             style="width:100%; max-height:400px; object-fit:cover; border-radius:10px; margin-bottom:20px;">
                    <?php endif; ?>

                    <h2 style="font-size:1.6em;">Description de la Formation</h2>
                    <div class="description-text">
                        <?php echo nl2br(htmlspecialchars($course['description'])); ?>
                         </div>
                    
                    <?php if ($has_access): ?>
                        <?php if ($course['content_type'] === 'video' && !empty($course['content_url'])): ?>
                            <div class="course-video-embed reveal-on-scroll" style="margin-top:30px;">
                                <h3 style="font-size:1.4em;">Vidéo de la Formation</h3>
                                <?php if (filter_var($course['content_url'], FILTER_VALIDATE_URL)): ?>
                                    <?php if (strpos($course['content_url'], 'youtube.com') !== false || strpos($course['content_url'], 'youtu.be') !== false): 
                                        // Basic YouTube embed
                                        $video_id = '';
                                        if (strpos($course['content_url'], 'watch?v=') !== false) {
                                            parse_str(parse_url($course['content_url'], PHP_URL_QUERY), $query_params);
                                            $video_id = $query_params['v'] ?? '';
                                        } elseif (strpos($course['content_url'], 'youtu.be/') !== false) {
                                            $video_id = substr(parse_url($course['content_url'], PHP_URL_PATH), 1);
                                        }
                                    ?>
                                        <?php if ($video_id): ?>
                                        <div style="position: relative; padding-bottom: 56.25%; /* 16:9 */ height: 0; overflow: hidden; max-width: 100%; background: #000;">
                                            <iframe style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; border:0;"
                                                    src="https://www.youtube.com/embed/<?php echo htmlspecialchars($video_id); ?>"
                                                    title="YouTube video player"
                                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                                    allowfullscreen>
                                            </iframe>
                                        </div>
                                        <?php else: ?>
                                            <p>Lien vidéo YouTube invalide. <a href="<?php echo htmlspecialchars($course['content_url']); ?>" target="_blank">Voir la vidéo</a></p>
                                        <?php endif; ?>
                                    <?php elseif (strpos($course['content_url'], 'vimeo.com') !== false): 
                                        // Basic Vimeo embed
                                        $video_id = substr(parse_url($course['content_url'], PHP_URL_PATH), 1);
                                    ?>
                                         <?php if (is_numeric($video_id)): ?>
                                        <div style="padding:56.25% 0 0 0;position:relative;">
                                            <iframe src="https://player.vimeo.com/video/<?php echo htmlspecialchars($video_id); ?>?h=<?php /* Optional hash if needed */ ?>&amp;badge=0&amp;autopause=0&amp;player_id=0&amp;app_id=<?php /* Your Vimeo App ID if any */ ?>"
                                                    frameborder="0" allow="autoplay; fullscreen; picture-in-picture" style="position:absolute;top:0;left:0;width:100%;height:100%;"
                                                    title="<?php echo htmlspecialchars($course['title']); ?>">
                                            </iframe>
                                        </div>
                                        <script src="https://player.vimeo.com/api/player.js"></script>
                                        <?php else: ?>
                                             <p>Lien vidéo Vimeo invalide. <a href="<?php echo htmlspecialchars($course['content_url']); ?>" target="_blank">Voir la vidéo</a></p>
                                        <?php endif; ?>
                                    <?php else: // Generic video link ?>
                                        <p><a href="<?php echo htmlspecialchars($course['content_url']); ?>" target="_blank" class="button">Regarder la Vidéo Principale</a></p>
                                    <?php endif; ?>
                                <?php else: // content_url is not a valid URL but content_type is video ?>
                                    <p>Le lien vidéo pour cette formation n'est pas correctement configuré.</p>
                                <?php endif; ?>
                            </div>
                        <?php elseif ($course['content_type'] === 'text' && !empty($course['content_body'])): ?>
                            <div class="course-text-content reveal-on-scroll" style="margin-top:30px;">
                                <h3 style="font-size:1.4em;">Contenu Principal de la Formation</h3>
                                <div class="formatted-text-content">
                                    <?php
                                    // For security and better display, consider using a Markdown parser
                                    // if content_body is in Markdown format.
                                    // Example with Parsedown (you'd need to include the library):
                                    // $Parsedown = new Parsedown();
                                    // echo $Parsedown->text($course['content_body']);
                                    // For now, simple nl2br and htmlspecialchars:
                                    echo nl2br(htmlspecialchars($course['content_body']));
                                    ?>
                                </div>
                            </div>
                        <?php elseif ($course['content_type'] === 'mixed' || $course['content_type'] === 'interactive'): ?>
                             <div class="course-mixed-content reveal-on-scroll" style="margin-top:30px;">
                                <h3 style="font-size:1.4em;">Contenu de la Formation</h3>
                                <?php if (!empty($course['content_body'])): ?>
                                     <div class="formatted-text-content" style="margin-bottom:20px;"><?php echo nl2br(htmlspecialchars($course['content_body'])); ?></div>
                                <?php endif; ?>
                                <?php if (!empty($course['content_url']) && filter_var($course['content_url'], FILTER_VALIDATE_URL)): ?>
                                    <p><a href="<?php echo htmlspecialchars($course['content_url']); ?>" target="_blank" class="button">Accéder au contenu interactif / ressource principale</a></p>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php
                        // Attempt to load Quiz data
                        $stmt_quiz = $conn->prepare("SELECT id, title, description FROM quizzes WHERE course_id = ? LIMIT 1");
                        $stmt_quiz->bind_param("i", $course['id']);
                        $stmt_quiz->execute();
                        $quiz_result = $stmt_quiz->get_result();

                        if ($quiz_result->num_rows > 0) {
                            $quiz_data_raw = $quiz_result->fetch_assoc();
                            $stmt_quiz->close();

                            // Fetch questions for this quiz
                            $stmt_questions = $conn->prepare("SELECT id, question_text, type FROM questions WHERE quiz_id = ? ORDER BY id ASC");
                            $stmt_questions->bind_param("i", $quiz_data_raw['id']);
                            $stmt_questions->execute();
                            $questions_result = $stmt_questions->get_result();
                            $quiz_data_raw['questions'] = [];

                            while ($question_row = $questions_result->fetch_assoc()) {
                                $stmt_answers = $conn->prepare("SELECT id, answer_text FROM answers WHERE question_id = ? ORDER BY RAND()"); // Randomize answer order
                                $stmt_answers->bind_param("i", $question_row['id']);
                                $stmt_answers->execute();
                                $answers_result = $stmt_answers->get_result();
                                $question_row['answers'] = $answers_result->fetch_all(MYSQLI_ASSOC);
                                $stmt_answers->close();
                                $quiz_data_raw['questions'][] = $question_row;
                            }
                            $stmt_questions->close();
                            
                            // Set $quiz_data for the template
                            $quiz_data = $quiz_data_raw; 
                            echo "<hr style='margin: 40px 0;'>";
                            include __DIR__ . '/templates/quiz_template.php'; // Include the quiz template
                        } else {
                            // $stmt_quiz->close(); // Already closed if num_rows > 0
                            if(isset($stmt_quiz) && $stmt_quiz instanceof mysqli_stmt) $stmt_quiz->close();
                            // echo "<p style='margin-top:30px;'>Aucun quiz n'est actuellement associé à cette formation.</p>";
                        }
                        ?>

                    <?php else: // User does not have access to premium content ?>
                        <div class="premium-access-required" style="margin-top:30px; padding: 20px; background-color: #fff3cd; border: 1px solid #ffeeba; border-radius: 6px; text-align:center;">
                            <h3 style="color: #856404;">Accès Réservé au Contenu Premium</h3>
                            <p>Cette partie de la formation est réservée aux membres Premium.</p>
                            <p><a href="<?php echo getBaseUrl(); ?>/subscribe_premium.php" class="button">Devenir Membre Premium</a> 
                               <?php if (!isLoggedIn()): ?>
                               ou <a href="<?php echo getBaseUrl(); ?>/login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>">Connectez-vous</a> si vous avez déjà un compte premium.
                               <?php endif; ?>
                            </p>
                        </div>
                    <?php endif; ?>
                </div>

                <aside class="course-sidebar" style="flex:1; min-width: 250px;">
                    <div style="background-color:#f9f9f9; padding:20px; border-radius:10px;">
                        <h4>Détails Clés</h4>
                        <ul style="list-style:none; padding:0;">
                            <?php if ($course['duration_minutes']): ?>
                                <li><strong>🕒 Durée :</strong> <?php echo htmlspecialchars($course['duration_minutes']); ?> minutes</li>
                            <?php endif; ?>
                            <li><strong>📈 Niveau :</strong> Débutant / Intermédiaire (à adapter)</li>
                            <li><strong>🏷️ Type :</strong> <?php echo htmlspecialchars(ucfirst($course['content_type'])); ?></li>
                            <?php if ($course['is_premium']): ?>
                                <li><strong>⭐ Accès :</strong> Premium</li>
                            <?php else: ?>
                                <li><strong>✅ Accès :</strong> Gratuit</li>
                            <?php endif; ?>
                            <li><strong>🗓️ Publié le :</strong> <?php echo formatDisplayDate($course['created_at'], 'd F Y'); ?></li>
                        </ul>
                        
                        <?php if (!$has_access && $course['is_premium']): ?>
                             <a href="<?php echo getBaseUrl(); ?>/subscribe_premium.php" class="button" style="width:100%; margin-top:15px;">Accéder au Contenu Premium</a>
                        <?php elseif ($has_access && $course['is_premium']): ?>
                            <p style="color:green; font-weight:bold; margin-top:15px;">✔️ Vous avez accès au contenu Premium.</p>
                        <?php endif; ?>
                        
                        </div>
                </aside>
            </div>
        </article>

    <?php else: ?>
        <section style="text-align:center;">
            <h1>Formation non trouvée</h1>
            <p>Désolé, la formation que vous essayez de consulter n'existe pas ou a été retirée.</p>
            <p><a href="<?php echo getBaseUrl(); ?>/courses.php" class="button">Voir toutes nos formations</a></p>
        </section>
    <?php endif; ?>
</div>

<?php
if (isset($conn)) {
    $conn->close();
}
require_once 'includes/footer.php';
?>