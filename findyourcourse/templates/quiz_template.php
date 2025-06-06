<?php
// /templates/quiz_template.php
/**
 * Template to display a quiz form.
 *
 * Expects a $quiz_data associative array:
 * $quiz_data = [
 * 'id' => (int) quiz_id,
 * 'title' => (string) quiz_title,
 * 'description' => (string) quiz_description (optional),
 * 'questions' => [
 * [
 * 'id' => (int) question_id,
 * 'question_text' => (string) question_text,
 * 'type' => (string) 'single_choice' or 'multiple_choice',
 * 'answers' => [
 * ['id' => (int) answer_id, 'answer_text' => (string) answer_text],
 * // ... more answers
 * ]
 * ],
 * // ... more questions
 * ]
 * ];
 * Expects $csrf_token to be available (generated by calling page or functions.php)
 */

if (!isset($quiz_data) || !is_array($quiz_data) || empty($quiz_data['questions'])) {
    echo '<p>Le contenu du quiz n\'est pas disponible ou le quiz ne contient aucune question.</p>';
    return;
}

// Ensure CSRF token is available
if (empty($csrf_token) && function_exists('generateCsrfToken')) {
    $csrf_token = generateCsrfToken();
} elseif (empty($csrf_token)) {
    // Fallback if token generation is missing, though this indicates an issue.
    // For security, forms should not submit without CSRF.
    echo "<p class='error'>Erreur de sécurité : Jeton CSRF manquant. Impossible d'afficher le quiz.</p>";
    return;
}

?>
<section class="quiz-container reveal-on-scroll" aria-labelledby="quiz-title-<?php echo htmlspecialchars($quiz_data['id']); ?>">
    <h3 id="quiz-title-<?php echo htmlspecialchars($quiz_data['id']); ?>"><?php echo htmlspecialchars($quiz_data['title']); ?></h3>
    <?php if (!empty($quiz_data['description'])): ?>
        <p class="quiz-description"><?php echo htmlspecialchars($quiz_data['description']); ?></p>
    <?php endif; ?>

    <form id="quizForm-<?php echo htmlspecialchars($quiz_data['id']); ?>" class="quiz-form" data-quiz-id="<?php echo htmlspecialchars($quiz_data['id']); ?>">
        <input type="hidden" name="quiz_id" value="<?php echo htmlspecialchars($quiz_data['id']); ?>">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

        <?php foreach ($quiz_data['questions'] as $q_idx => $question): ?>
            <fieldset class="question-block form-group" aria-labelledby="question-text-<?php echo htmlspecialchars($question['id']); ?>">
                <legend id="question-text-<?php echo htmlspecialchars($question['id']); ?>" class="question-text">
                    <strong>Question <?php echo ($q_idx + 1); ?>:</strong> <?php echo htmlspecialchars($question['question_text']); ?>
                </legend>
                <div class="answers-group">
                    <?php if (empty($question['answers'])): ?>
                        <p><em>Aucune option de réponse disponible pour cette question.</em></p>
                    <?php else: ?>
                        <?php foreach ($question['answers'] as $a_idx => $answer): ?>
                            <div class="answer-option">
                                <input 
                                    type="<?php echo ($question['type'] === 'multiple_choice') ? 'checkbox' : 'radio'; ?>"
                                    name="answers[<?php echo htmlspecialchars($question['id']); ?>]<?php echo ($question['type'] === 'multiple_choice') ? '[]' : ''; ?>"
                                    value="<?php echo htmlspecialchars($answer['id']); ?>"
                                    id="answer_<?php echo htmlspecialchars($answer['id']); ?>"
                                    aria-labelledby="answer-label-<?php echo htmlspecialchars($answer['id']); ?>"
                                >
                                <label id="answer-label-<?php echo htmlspecialchars($answer['id']); ?>" for="answer_<?php echo htmlspecialchars($answer['id']); ?>">
                                    <?php echo htmlspecialchars($answer['answer_text']); ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </fieldset>
        <?php endforeach; ?>

        <div class="form-actions" style="margin-top: 25px;">
            <button type="submit" class="button submit-quiz-btn">Soumettre le Quiz</button>
        </div>
    </form>
    <div id="quizResult-<?php echo htmlspecialchars($quiz_data['id']); ?>" class="quiz-result-container" aria-live="polite" style="margin-top: 20px;">
        </div>
</section>

<script>
// JavaScript specific to this quiz instance can be added here or handled by main.js
// by targeting forms with class 'quiz-form' and using data-quiz-id.
// For example, the AJAX submission logic in main.js might look for:
// document.querySelectorAll('.quiz-form').forEach(form => {
//    form.addEventListener('submit', function(e) {
//        e.preventDefault();
//        const quizId = this.dataset.quizId;
//        const resultContainer = document.getElementById(`quizResult-${quizId}`);
//        // ... rest of AJAX logic ...
//    });
// });
</script>