<?php
/**
 * /templates/quiz_template.php
 * Template pour afficher un formulaire de quiz.
 * * Attend que les variables $quiz_data et $csrf_token soient définies par la page qui l'inclut.
 */

if (!isset($quiz_data) || !isset($csrf_token)) {
    echo '<p>Erreur : Les données nécessaires pour afficher le quiz sont manquantes.</p>';
    return;
}
?>
<section class="quiz-section" id="quiz-container">
    <h2 style="font-size: 1.8em; border-bottom: 2px solid #eee; padding-bottom: 10px; margin-bottom: 20px;">
        Quiz : <?php echo htmlspecialchars($quiz_data['title']); ?>
    </h2>
    
    <?php if (!empty($quiz_data['description'])): ?>
        <p class="lead"><?php echo htmlspecialchars($quiz_data['description']); ?></p>
    <?php endif; ?>

    <?php if (isLoggedIn()): ?>
        <form id="quiz-form" method="POST">
            <input type="hidden" name="quiz_id" value="<?php echo $quiz_data['id']; ?>">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

            <?php if (!empty($quiz_data['questions'])): ?>

                <?php foreach ($quiz_data['questions'] as $q_index => $question): ?>
                    <div class="question-block" style="margin-bottom: 25px; padding: 15px; border: 1px solid #f0f0f0; border-radius: 8px;">
                        <p class="question-text" style="font-weight: bold; font-size: 1.1em;">
                            Question <?php echo $q_index + 1; ?>: <?php echo htmlspecialchars($question['question_text']); ?>
                        </p>
                        <div class="answers-group" role="group" aria-labelledby="question-text-<?php echo $question['id']; ?>">
                            <?php foreach ($question['answers'] as $answer): ?>
                                <div class="answer-option" style="margin: 5px 0;">
                                    <input 
                                        type="<?php echo ($question['type'] === 'single_choice') ? 'radio' : 'checkbox'; ?>" 
                                        name="answers[<?php echo $question['id']; ?>]<?php echo ($question['type'] === 'multiple_choice') ? '[]' : ''; ?>" 
                                        id="answer-<?php echo $answer['id']; ?>"
                                        value="<?php echo $answer['id']; ?>">
                                    <label for="answer-<?php echo $answer['id']; ?>" style="margin-left: 8px; cursor:pointer;"><?php echo htmlspecialchars($answer['answer_text']); ?></label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <div class="form-actions" style="margin-top: 30px;">
                    <button type="submit" id="submit-quiz-btn" class="button">Soumettre le Quiz</button>
                </div>

            <?php else: // Si le tableau des questions est vide ?>
                <div class="no-questions-message" style="padding: 20px; background-color: #fffbe6; border-radius: 8px; text-align: center;">
                    <p>Ce quiz ne contient pas encore de questions. Revenez bientôt !</p>
                </div>
            <?php endif; ?>
            </form>

    <?php else: // Si l'utilisateur n'est pas connecté ?>
        <div class="login-prompt" style="text-align:center; padding: 20px; background-color: #f9f9f9; border-radius: 8px;">
            <p>Vous devez être <a href="<?php echo getBaseUrl(); ?>/login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>">connecté</a> pour participer à ce quiz.</p>
        </div>
    <?php endif; ?>
</section>