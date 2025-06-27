<?php
// admin/add_question.php
$admin_page_title = "Ajouter une Question";
require_once '../includes/admin_header.php'; // Gère la session, la connexion BD et le check admin

if (!isAdmin()) {
    redirect(getBaseUrl() . '/admin/login.php?error=unauthorized');
    exit;
}

$quiz_id = filter_input(INPUT_GET, 'quiz_id', FILTER_VALIDATE_INT);
$quiz_title = '';
$errors = [];

if (!$quiz_id) {
    $_SESSION['message'] = '<p class="admin-error-message">ID de quiz non spécifié.</p>';
    redirect(getBaseUrl() . '/admin/manage_quizzes.php');
    exit;
}

// Récupérer le titre du quiz pour l'affichage
$stmt_quiz = $conn->prepare("SELECT title FROM quizzes WHERE id = ?");
$stmt_quiz->bind_param("i", $quiz_id);
$stmt_quiz->execute();
$result_quiz = $stmt_quiz->get_result();
if ($result_quiz->num_rows === 1) {
    $quiz_title = $result_quiz->fetch_assoc()['title'];
} else {
    redirect(getBaseUrl() . '/admin/manage_quizzes.php');
    exit;
}
$stmt_quiz->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        $errors[] = "Erreur de sécurité.";
    } else {
        $question_text = sanitizeInput($_POST['question_text'] ?? '');
        $question_type = sanitizeInput($_POST['question_type'] ?? 'single_choice');
        $points = filter_var($_POST['points'] ?? 1, FILTER_VALIDATE_INT);
        $answers = $_POST['answers'] ?? [];
        $correct_answer_indices = $_POST['is_correct'] ?? []; // Array for multiple choice, string for single

        if (empty($question_text)) $errors[] = "Le texte de la question est requis.";
        if (count($answers) < 2) $errors[] = "Vous devez fournir au moins deux options de réponse.";
        if (empty($correct_answer_indices)) $errors[] = "Vous devez marquer au moins une réponse comme correcte.";
        
        // Vérifier que les réponses ne sont pas vides
        foreach($answers as $index => $answer_text) {
            if(empty(trim($answer_text))) {
                $errors[] = "Le texte de la réponse N°" . ($index + 1) . " ne peut pas être vide.";
            }
        }

        if (empty($errors)) {
            // Utiliser une transaction pour assurer l'intégrité des données
            $conn->begin_transaction();
            try {
                // Insérer la question
                $stmt_q = $conn->prepare("INSERT INTO questions (quiz_id, question_text, type, points) VALUES (?, ?, ?, ?)");
                $stmt_q->bind_param("issi", $quiz_id, $question_text, $question_type, $points);
                $stmt_q->execute();
                $new_question_id = $stmt_q->insert_id;
                $stmt_q->close();

                // Insérer les réponses
                $stmt_a = $conn->prepare("INSERT INTO answers (question_id, answer_text, is_correct) VALUES (?, ?, ?)");
                foreach ($answers as $index => $answer_text) {
                    $is_correct = 0;
                    if ($question_type === 'single_choice') {
                        if (isset($correct_answer_indices) && $index == $correct_answer_indices) {
                            $is_correct = 1;
                        }
                    } elseif ($question_type === 'multiple_choice') {
                        if (isset($correct_answer_indices) && is_array($correct_answer_indices) && in_array($index, $correct_answer_indices)) {
                            $is_correct = 1;
                        }
                    }
                    $sanitized_answer_text = sanitizeInput($answer_text);
                    $stmt_a->bind_param("isi", $new_question_id, $sanitized_answer_text, $is_correct);
                    $stmt_a->execute();
                }
                $stmt_a->close();

                // Si tout s'est bien passé, valider la transaction
                $conn->commit();
                $_SESSION['message'] = '<p class="admin-success-message">Question ajoutée avec succès !</p>';
                redirect(getBaseUrl() . '/admin/manage_questions.php?quiz_id=' . $quiz_id);
                exit;

            } catch (Exception $e) {
                // En cas d'erreur, annuler la transaction
                $conn->rollback();
                $errors[] = "Erreur de base de données : " . $e->getMessage();
            }
        }
    }
}
$csrf_token_admin = generateCsrfToken();
?>

<h3><?php echo htmlspecialchars($admin_page_title); ?> pour le Quiz "<?php echo htmlspecialchars($quiz_title); ?>"</h3>
<p><a href="manage_questions.php?quiz_id=<?php echo $quiz_id; ?>" class="admin-button-secondary">&laquo; Retour à la gestion du quiz</a></p>

<?php if (!empty($errors)): ?>
    <div class="admin-error-message"><strong>Erreurs :</strong><ul><?php foreach ($errors as $e) echo '<li>' . htmlspecialchars($e) . '</li>'; ?></ul></div>
<?php endif; ?>

<form action="add_question.php?quiz_id=<?php echo $quiz_id; ?>" method="POST" class="admin-form">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token_admin); ?>">

    <div class="form-group">
        <label for="question_text">Texte de la question <span style="color:red;">*</span></label>
        <textarea id="question_text" name="question_text" rows="3" required><?php echo isset($_POST['question_text']) ? htmlspecialchars($_POST['question_text']) : ''; ?></textarea>
    </div>

    <div style="display:flex; gap: 20px;">
        <div class="form-group" style="flex:1;">
            <label for="question_type">Type de question <span style="color:red;">*</span></label>
            <select id="question_type" name="question_type">
                <option value="single_choice" selected>Choix unique</option>
                <option value="multiple_choice">Choix multiples</option>
            </select>
        </div>
        <div class="form-group" style="flex:1;">
            <label for="points">Points</label>
            <input type="number" id="points" name="points" value="1" min="1" required>
        </div>
    </div>

    <hr>
    <h4>Réponses</h4>
    <div id="answers-container">
        </div>
    <button type="button" id="add-answer-btn" class="admin-button-secondary">Ajouter une réponse</button>

    <div class="form-actions" style="margin-top:20px;">
        <button type="submit" class="admin-button-primary">Enregistrer la Question</button>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('answers-container');
    const addBtn = document.getElementById('add-answer-btn');
    const questionTypeSelect = document.getElementById('question_type');
    let answerIndex = 0;

    function addAnswerField() {
        const div = document.createElement('div');
        div.className = 'answer-field form-group';
        div.style.display = 'flex';
        div.style.alignItems = 'center';
        div.style.gap = '10px';
        
        const inputType = questionTypeSelect.value === 'single_choice' ? 'radio' : 'checkbox';
        const inputName = questionTypeSelect.value === 'single_choice' ? 'is_correct' : `is_correct[${answerIndex}]`;
        
        div.innerHTML = `
            <input type="${inputType}" name="${inputName}" value="${answerIndex}" title="Marquer comme correcte">
            <input type="text" name="answers[${answerIndex}]" class="answer-text" placeholder="Texte de la réponse ${answerIndex + 1}" style="flex-grow:1;" required>
            <button type="button" class="remove-answer-btn admin-button-danger" style="padding: 5px 10px;">X</button>
        `;
        
        container.appendChild(div);
        answerIndex++;
    }

    // Gérer le changement de type de question
    questionTypeSelect.addEventListener('change', () => {
        container.innerHTML = ''; // Vide les réponses existantes
        answerIndex = 0;
        addAnswerField(); // Ajoute le premier champ
        addAnswerField(); // Ajoute le deuxième champ
    });

    // Ajouter un champ de réponse
    addBtn.addEventListener('click', addAnswerField);
    
    // Supprimer un champ de réponse
    container.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-answer-btn')) {
            e.target.closest('.answer-field').remove();
        }
    });

    // Ajouter 2 champs par défaut au chargement
    addAnswerField();
    addAnswerField();
});
</script>

<?php
if (isset($conn)) $conn->close();
require_once '../includes/admin_footer.php';
?>