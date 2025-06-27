<?php
// /submit_quiz.php

// Toujours retourner une réponse JSON
header('Content-Type: application/json');

require_once 'includes/db_connect.php';
require_once 'includes/functions.php'; // Pour la session, CSRF, etc.

$response = ['success' => false, 'message' => 'Une erreur inconnue est survenue.'];

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    $response['message'] = 'Vous devez être connecté pour soumettre un quiz.';
    echo json_encode($response);
    exit;
}

// Vérifier si la requête est bien de type POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Méthode de requête invalide.';
    echo json_encode($response);
    exit;
}

// Valider le jeton CSRF
if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
    $response['message'] = 'Erreur de sécurité. Veuillez rafraîchir la page et réessayer.';
    echo json_encode($response);
    exit;
}

// Valider les entrées
$quiz_id = filter_input(INPUT_POST, 'quiz_id', FILTER_VALIDATE_INT);
$user_answers = $_POST['answers'] ?? []; // ex: [question_id => answer_id] ou [question_id => [answer_id1, answer_id2]]

if (!$quiz_id || empty($user_answers)) {
    $response['message'] = 'Données du quiz manquantes ou invalides.';
    echo json_encode($response);
    exit;
}

// --- Logique principale de correction du Quiz ---
try {
    // 1. Récupérer toutes les questions et leurs réponses correctes pour ce quiz
    $correct_answers_map = [];
    $question_points_map = [];
    $total_possible_points = 0;

    $stmt_questions = $conn->prepare(
        "SELECT q.id as question_id, q.type, q.points, a.id as answer_id 
         FROM questions q 
         JOIN answers a ON q.id = a.question_id 
         WHERE q.quiz_id = ? AND a.is_correct = 1"
    );
    $stmt_questions->bind_param("i", $quiz_id);
    $stmt_questions->execute();
    $result_questions = $stmt_questions->get_result();

    while ($row = $result_questions->fetch_assoc()) {
        $correct_answers_map[$row['question_id']][] = (string)$row['answer_id']; // Stocker les IDs des bonnes réponses
        $question_points_map[$row['question_id']] = $row['points'];
    }
    $stmt_questions->close();
    
    // Calculer le total des points possibles pour ce quiz
    foreach ($question_points_map as $points) {
        $total_possible_points += $points;
    }


    // 2. Comparer les réponses de l'utilisateur avec les réponses correctes
    $user_score = 0;
    $questions_attempted = count($user_answers);
    $correctly_answered_questions = 0;

    foreach ($user_answers as $question_id => $submitted_answer) {
        if (isset($correct_answers_map[$question_id])) {
            $correct_answers_for_q = $correct_answers_map[$question_id];
            
            // Normaliser la réponse de l'utilisateur en un tableau pour une comparaison facile
            $submitted_answer_array = is_array($submitted_answer) ? $submitted_answer : [$submitted_answer];
            
            // Trier les deux tableaux pour une comparaison fiable
            sort($correct_answers_for_q);
            sort($submitted_answer_array);

            // Si les réponses de l'utilisateur correspondent exactement aux bonnes réponses
            if ($submitted_answer_array === $correct_answers_for_q) {
                $user_score += $question_points_map[$question_id];
                $correctly_answered_questions++;
            }
        }
    }

    // 3. Calculer le score final en pourcentage
    $final_score_percentage = ($total_possible_points > 0) ? round(($user_score / $total_possible_points) * 100, 2) : 0;

    // 4. Enregistrer la tentative dans la base de données
    $user_id = $_SESSION['user_id'];
    $stmt_save = $conn->prepare("INSERT INTO quiz_attempts (user_id, quiz_id, score, total_questions_attempted, correct_answers) VALUES (?, ?, ?, ?, ?)");
    $stmt_save->bind_param("iidis", $user_id, $quiz_id, $final_score_percentage, $questions_attempted, $correctly_answered_questions);
    
    if ($stmt_save->execute()) {
        $response['success'] = true;
        $response['message'] = "Quiz soumis avec succès !";
        $response['score'] = $final_score_percentage;
        $response['details'] = [ // Ajouter plus de détails pour le front-end
            'userScore' => $user_score,
            'totalPoints' => $total_possible_points,
            'correctlyAnswered' => $correctly_answered_questions,
            'totalQuestions' => count($question_points_map)
        ];
    } else {
        throw new Exception("Erreur lors de l'enregistrement de votre tentative.");
    }
    $stmt_save->close();

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    error_log("Quiz Submission Error: " . $e->getMessage()); // Logger l'erreur pour le debug
}

$conn->close();
echo json_encode($response);
exit;
?>