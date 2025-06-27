<?php
// admin/edit_question.php
$admin_page_title = "Modifier une Question";
require_once '../includes/admin_header.php';

if (!isAdmin()) {
    redirect(getBaseUrl() . '/admin/login.php?error=unauthorized');
    exit;
}

$question_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$quiz_id = filter_input(INPUT_GET, 'quiz_id', FILTER_VALIDATE_INT);
$question_data = null;
$answers_data = [];
$errors = [];

if (!$question_id || !$quiz_id) {
    $_SESSION['message'] = '<p class="admin-error-message">ID de question ou de quiz invalide.</p>';
    redirect(getBaseUrl() . '/admin/manage_quizzes.php');
    exit;
}

// Fetch existing question data
$stmt_q = $conn->prepare("SELECT q.*, qz.title as quiz_title FROM questions q JOIN quizzes qz ON q.quiz_id = qz.id WHERE q.id = ? AND q.quiz_id = ?");
$stmt_q->bind_param("ii", $question_id, $quiz_id);
$stmt_q->execute();
$result_q = $stmt_q->get_result();
if ($result_q->num_rows === 1) {
    $question_data = $result_q->fetch_assoc();
    // Fetch answers
    $stmt_a = $conn->prepare("SELECT id, answer_text, is_correct FROM answers WHERE question_id = ? ORDER BY id ASC");
    $stmt_a->bind_param("i", $question_id);
    $stmt_a->execute();
    $result_a = $stmt_a->get_result();
    while ($row = $result_a->fetch_assoc()) {
        $answers_data[] = $row;
    }
    $stmt_a->close();
} else {
    $_SESSION['message'] = '<p class="admin-error-message">Question non trouvée.</p>';
    redirect(getBaseUrl() . '/admin/manage_questions.php?quiz_id=' . $quiz_id);
    exit;
}
$stmt_q->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        $errors[] = "Erreur de sécurité.";
    } else {
        $question_text = sanitizeInput($_POST['question_text'] ?? '');
        $question_type = sanitizeInput($_POST['question_type'] ?? 'single_choice');
        $points = filter_var($_POST['points'] ?? 1, FILTER_VALIDATE_INT);
        $answers = $_POST['answers'] ?? [];
        $correct_answer_indices = $_POST['is_correct'] ?? [];

        // Validation... (identique à add_question.php)
        if (empty($question_text)) $errors[] = "Texte de la question requis.";
        if (count($answers) < 2) $errors[] = "Au moins deux réponses sont requises.";
        if (empty($correct_answer_indices)) $errors[] = "Au moins une réponse correcte doit être cochée.";

        if (empty($errors)) {
            $conn->begin_transaction();
            try {
                // Mettre à jour la question
                $stmt_q_update = $conn->prepare("UPDATE questions SET question_text = ?, type = ?, points = ? WHERE id = ?");
                $stmt_q_update->bind_param("ssii", $question_text, $question_type, $points, $question_id);
                $stmt_q_update->execute();
                $stmt_q_update->close();

                // Approche simple : supprimer toutes les anciennes réponses et réinsérer les nouvelles
                $stmt_del_a = $conn->prepare("DELETE FROM answers WHERE question_id = ?");
                $stmt_del_a->bind_param("i", $question_id);
                $stmt_del_a->execute();
                $stmt_del_a->close();

                // Réinsérer les réponses
                $stmt_a_insert = $conn->prepare("INSERT INTO answers (question_id, answer_text, is_correct) VALUES (?, ?, ?)");
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
                    $stmt_a_insert->bind_param("isi", $question_id, $sanitized_answer_text, $is_correct);
                    $stmt_a_insert->execute();
                }
                $stmt_a_insert->close();
                
                $conn->commit();
                $_SESSION['message'] = '<p class="admin-success-message">Question mise à jour avec succès !</p>';
                redirect(getBaseUrl() . '/admin/manage_questions.php?quiz_id=' . $quiz_id);
                exit;

            } catch (Exception $e) {
                $conn->rollback();
                $errors[] = "Erreur de base de données : " . $e->getMessage();
            }
        }
    }
}
$csrf_token_admin = generateCsrfToken();
?>

<h3><?php echo htmlspecialchars($admin_page_title); ?> pour le Quiz "<?php echo htmlspecialchars($question_data['quiz_title']); ?>"</h3>
<p><a href="manage_questions.php?quiz_id=<?php echo $quiz_id; ?>" class="admin-button-secondary">&laquo; Retour à la gestion du quiz</a></p>

<?php if (!empty($errors)): ?>
    <div class="admin-error-message"><strong>Erreurs :</strong><ul><?php foreach ($errors as $e) echo '<li>' . htmlspecialchars($e) . '</li>'; ?></ul></div>
<?php endif; ?>

<form action="edit_question.php?id=<?php echo $question_id; ?>&quiz_id=<?php echo $quiz_id; ?>" method="POST" class="admin-form">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token_admin); ?>">

    <div class="form-group">
        <label for="question_text">Texte de la question <span style="color:red;">*</span></label>
        <textarea id="question_text" name="question_text" rows="3" required><?php echo htmlspecialchars($question_data['question_text']); ?></textarea>
    </div>
    <div style="display:flex; gap: 20px;">
        <div class="form-group" style="flex:1;">
            <label for="question_type">Type de question <span style="color:red;">*</span></label>
            <select id="question_type" name="question_type">
                <option value="single_choice" <?php echo ($question_data['type'] === 'single_choice') ? 'selected' : ''; ?>>Choix unique</option>
                <option value="multiple_choice" <?php echo ($question_data['type'] === 'multiple_choice') ? 'selected' : ''; ?>>Choix multiples</option>
            </select>
        </div>
        <div class="form-group" style="flex:1;">
            <label for="points">Points</label>
            <input type="number" id="points" name="points" value="<?php echo htmlspecialchars($question_data['points']); ?>" min="1" required>
        </div>
    </div>
    <hr>
    <h4>Réponses</h4>
    <div id="answers-container">
        <?php foreach ($answers_data as $index => $answer): ?>
            <div class="answer-field form-group" style="display: flex; align-items: center; gap: 10px;">
                <input type="<?php echo ($question_data['type'] === 'single_choice') ? 'radio' : 'checkbox'; ?>" 
                       name="<?php echo ($question_data['type'] === 'single_choice') ? 'is_correct' : 'is_correct[]'; ?>" 
                       value="<?php echo $index; ?>" 
                       title="Marquer comme correcte"
                       <?php if ($answer['is_correct']) echo 'checked'; ?>>
                <input type="text" name="answers[]" class="answer-text" value="<?php echo htmlspecialchars($answer['answer_text']); ?>" style="flex-grow:1;" required>
                <button type="button" class="remove-answer-btn admin-button-danger" style="padding: 5px 10px;">X</button>
            </div>
        <?php endforeach; ?>
    </div>
    <button type="button" id="add-answer-btn" class="admin-button-secondary">Ajouter une réponse</button>

    <div class="form-actions" style="margin-top:20px;">
        <button type="submit" class="admin-button-primary">Mettre à Jour la Question</button>
    </div>
</form>

<script>
// Le script est quasiment identique à celui de add_question.php
// Il faut juste adapter le nommage des inputs "is_correct" au changement de type
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('answers-container');
    const addBtn = document.getElementById('add-answer-btn');
    const questionTypeSelect = document.getElementById('question_type');
    let answerIndex = <?php echo count($answers_data); ?>; // Démarrer l'index après les réponses existantes

    function updateInputTypes() {
        const inputType = questionTypeSelect.value === 'single_choice' ? 'radio' : 'checkbox';
        const correctInputs = container.querySelectorAll('input[name^="is_correct"]');
        correctInputs.forEach(input => {
            input.type = inputType;
            if (inputType === 'checkbox') {
                 // Assure que le name est un tableau pour les checkboxes
                 if (!input.name.includes('[')) {
                    input.name = 'is_correct[]';
                 }
            } else {
                // Assure que le name est unique pour les radios
                input.name = 'is_correct';
            }
        });
    }

    function addAnswerField() {
        const div = document.createElement('div');
        div.className = 'answer-field form-group';
        div.style.display = 'flex';
        div.style.alignItems = 'center';
        div.style.gap = '10px';
        
        const inputType = questionTypeSelect.value === 'single_choice' ? 'radio' : 'checkbox';
        const inputName = questionTypeSelect.value === 'single_choice' ? 'is_correct' : 'is_correct[]';
        
        div.innerHTML = `
            <input type="${inputType}" name="${inputName}" value="${answerIndex}" title="Marquer comme correcte">
            <input type="text" name="answers[]" class="answer-text" placeholder="Nouveau texte de réponse" style="flex-grow:1;" required>
            <button type="button" class="remove-answer-btn admin-button-danger" style="padding: 5px 10px;">X</button>
        `;
        
        container.appendChild(div);
        answerIndex++;
    }

    questionTypeSelect.addEventListener('change', updateInputTypes);
    addBtn.addEventListener('click', addAnswerField);
    container.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-answer-btn')) {
            e.target.closest('.answer-field').remove();
        }
    });
});
</script>

<?php
if (isset($conn)) $conn->close();
require_once '../includes/admin_footer.php';
?>