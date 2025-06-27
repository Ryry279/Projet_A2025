<?php
// course_detail.php
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

// Initialisation de la variable $course pour éviter les erreurs
$course = null;
$page_title = "Formation non trouvée";
$page_description = "La formation que vous recherchez n'existe pas ou a été déplacée.";

// Récupérer et valider l'ID du cours depuis l'URL
$course_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($course_id) {
    // Si l'ID est valide, on essaie de récupérer les informations du cours
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
        $course['is_favorited_by_current_user'] = (bool)$course['is_favorited_by_current_user'];
        $page_title = htmlspecialchars($course['title']);
        $page_description = htmlspecialchars(createExcerpt($course['description'] ?? '', 150));
    } else {
        http_response_code(404);
    }
    $stmt->close();
}

require_once 'includes/header.php'; // Contient le $csrf_token
?>

<div class="container course-detail-page">
    <?php if ($course): ?>
        <article class="course-content-wrapper">
            <header class="course-header" style="margin-bottom:30px;">
                <h1 style="margin-bottom: 0.2em;"><?php echo htmlspecialchars($course['title']); ?></h1>
            </header>

            <div class="course-main-content" style="display:flex; flex-wrap:wrap; gap:30px;">
                <div class="course-description-and-media" style="flex:3; min-width: 300px;">
                    <?php
                        $has_access = !$course['is_premium'] || isPremiumStudent();
                        if ($has_access) {
                            // Affichage du contenu du cours (description, vidéo, etc.)
                            echo '<h2 style="font-size:1.6em;">Description de la Formation</h2>';
                            echo '<div class="description-text">' . nl2br(htmlspecialchars($course['description'])) . '</div>';

                            // Logique de chargement du Quiz
                            $stmt_quiz = $conn->prepare("SELECT id, title, description FROM quizzes WHERE course_id = ? LIMIT 1");
                            $stmt_quiz->bind_param("i", $course['id']);
                            $stmt_quiz->execute();
                            $quiz_result = $stmt_quiz->get_result();

                            if ($quiz_result->num_rows > 0) {
                                $quiz_data_raw = $quiz_result->fetch_assoc();
                                $quiz_id_found = $quiz_data_raw['id'];
                                $stmt_quiz->close();
                                
                                $stmt_questions = $conn->prepare("SELECT id, question_text, type FROM questions WHERE quiz_id = ? ORDER BY id ASC");
                                $stmt_questions->bind_param("i", $quiz_id_found);
                                $stmt_questions->execute();
                                $questions_result = $stmt_questions->get_result();
                                
                                $quiz_data_raw['questions'] = [];
                                if ($questions_result->num_rows > 0) {
                                    while ($question_row = $questions_result->fetch_assoc()) {
                                        $stmt_answers = $conn->prepare("SELECT id, answer_text FROM answers WHERE question_id = ? ORDER BY RAND()");
                                        $stmt_answers->bind_param("i", $question_row['id']);
                                        $stmt_answers->execute();
                                        $question_row['answers'] = $stmt_answers->get_result()->fetch_all(MYSQLI_ASSOC);
                                        $stmt_answers->close();
                                        $quiz_data_raw['questions'][] = $question_row;
                                    }
                                }
                                $stmt_questions->close();
                                
                                $quiz_data = $quiz_data_raw; 
                                echo "<hr style='margin: 40px 0;'>";
                                include __DIR__ . '/templates/quiz_template.php';
                            }
                        } else {
                             // Message pour l'accès premium requis
                        }
                    ?>
                </div>
                <aside class="course-sidebar" style="flex:1; min-width: 250px;">
                    </aside>
            </div>
        </article>

    <?php else: ?>
        <section style="text-align:center;">
            <h1>Formation non trouvée</h1>
            <p>Désolé, la formation que vous essayez de consulter n'existe pas.</p>
            <p><a href="<?php echo getBaseUrl(); ?>/courses.php" class="button">Voir toutes nos formations</a></p>
        </section>
    <?php endif; ?>
</div>

<div id="score-modal" class="modal-backdrop" style="display: none;">
    <div class="modal-content">
        <button id="modal-close-btn" class="modal-close-btn">&times;</button>
        <div id="modal-body"></div>
    </div>
</div>

<style>
    .modal-backdrop { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.6); z-index: 2000; display: flex; justify-content: center; align-items: center; opacity: 0; transition: opacity 0.3s ease; pointer-events: none; }
    .modal-backdrop.visible { opacity: 1; pointer-events: auto; }
    .modal-content { background-color: white; padding: 30px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.3); max-width: 500px; width: 90%; position: relative; transform: translateY(-50px); transition: transform 0.3s ease; }
    .modal-backdrop.visible .modal-content { transform: translateY(0); }
    .modal-close-btn { position: absolute; top: 10px; right: 15px; background: none; border: none; font-size: 2.5rem; color: #aaa; cursor: pointer; line-height: 1; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const quizForm = document.getElementById('quiz-form');
    const scoreModal = document.getElementById('score-modal');
    const modalBody = document.getElementById('modal-body');
    const modalCloseBtn = document.getElementById('modal-close-btn');

    if (!quizForm || !scoreModal) return;

    function openModal() {
        scoreModal.style.display = 'flex';
        setTimeout(() => scoreModal.classList.add('visible'), 10);
    }

    function closeModal() {
        scoreModal.classList.remove('visible');
        setTimeout(() => scoreModal.style.display = 'none', 300);
    }

    quizForm.addEventListener('submit', function (event) {
        event.preventDefault();
        const submitBtn = document.getElementById('submit-quiz-btn');
        submitBtn.disabled = true;
        submitBtn.textContent = 'Correction...';

        const formData = new FormData(quizForm);

        fetch('submit_quiz.php', { method: 'POST', body: formData })
        .then(response => response.json())
        .then(data => {
            let resultHTML = '';
            if (data.success) {
                const score = parseFloat(data.score);
                // Construction de l'affichage du score (version simplifiée)
                resultHTML = `
                    <h2 style="text-align:center; margin-top:0; color: ${score >= 50 ? 'green' : '#c62828'};">Quiz Terminé !</h2>
                    <div style="font-size: 3.5em; font-weight: bold; text-align:center; margin: 20px 0;">${score}%</div>
                    <p style="text-align:center; color: #555;">Vous avez répondu correctement à ${data.details.correctlyAnswered} question(s) sur ${data.details.totalQuestions}.</p>
                    <div style="text-align:center; margin-top:25px;">
                        <button class="button modal-close-btn-bottom">Fermer</button>
                    </div>
                `;
                quizForm.style.display = 'none';
            } else {
                resultHTML = `<h2 style="color:red;">Erreur</h2><p>${data.message}</p><button class="button modal-close-btn-bottom">Réessayer</button>`;
                submitBtn.disabled = false;
                submitBtn.textContent = 'Soumettre le Quiz';
            }
            
            modalBody.innerHTML = resultHTML;
            openModal();
        })
        .catch(error => {
            console.error('Erreur:', error);
            modalBody.innerHTML = `<h2 style="color:red;">Erreur de Connexion</h2><p>Impossible de contacter le serveur.</p><button class="button modal-close-btn-bottom">Fermer</button>`;
            openModal();
            submitBtn.disabled = false;
            submitBtn.textContent = 'Soumettre le Quiz';
        });
    });

    // Gérer la fermeture du pop-up
    modalCloseBtn.addEventListener('click', closeModal);
    scoreModal.addEventListener('click', e => { if (e.target === scoreModal) closeModal(); });
    modalBody.addEventListener('click', e => { if (e.target.classList.contains('modal-close-btn-bottom')) closeModal(); });
});
</script>

<?php
if (isset($conn)) {
    $conn->close();
}
require_once 'includes/footer.php';
?>