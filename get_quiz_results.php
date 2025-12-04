<?php
session_set_cookie_params(14400);
session_start();

header('Content-Type: application/json; charset=utf-8');
header("Cache-Control: no-cache, no-store, must-revalidate");

if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'error' => 'Non authentifié']);
    exit;
}

try {
    $database = new PDO(
        'mysql:host=localhost;dbname=quizzeo;charset=utf8mb4',
        'root',
        '',
        array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
    );
} catch (PDOException $exception) {
    echo json_encode(['success' => false, 'error' => 'Erreur de connexion BDD']);
    exit;
}

$quizId = isset($_GET['quiz_id']) ? intval($_GET['quiz_id']) : 0;

if ($quizId <= 0) {
    echo json_encode(['success' => false, 'error' => 'ID de quiz invalide']);
    exit;
}

if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'entreprise' && $_SESSION['role'] !== 'ecole')) {
    echo json_encode(['success' => false, 'error' => 'Permission refusée']);
    exit;
}

$stmt = $database->prepare("SELECT id, question, options, reponse FROM sql_questions WHERE quizz_id = ? ORDER BY id");
$stmt->execute([$quizId]);
$questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($questions)) {
    echo json_encode(['success' => true, 'results' => []]);
    exit;
}

$questionIds = array_column($questions, 'id');
$placeholders = str_repeat('?,', count($questionIds) - 1) . '?';

$stmt = $database->prepare("
    SELECT DISTINCT 
        u.id as user_id,
        u.username,
        ru.id_question,
        ru.reponse_utilisateur
    FROM sql_reponse_utilisateur ru
    INNER JOIN sql_utilisateur u ON ru.id_utilisateur = u.id
    WHERE ru.id_question IN ($placeholders)
    ORDER BY u.username, ru.id_question
");
$stmt->execute($questionIds);
$responses = $stmt->fetchAll(PDO::FETCH_ASSOC);

$results = [];
foreach ($responses as $response) {
    $userId = $response['user_id'];
    $username = $response['username'];
    
    if (!isset($results[$userId])) {
        $results[$userId] = [
            'username' => $username,
            'responses' => [],
            'score' => 0,
            'total_questions' => count($questions)
        ];
    }
    
    $questionIndex = null;
    foreach ($questions as $index => $question) {
        if ($question['id'] == $response['id_question']) {
            $questionIndex = $index;
            break;
        }
    }
    
    if ($questionIndex !== null) {
        $userAnswers = json_decode($response['reponse_utilisateur'], true);
        $correctAnswers = json_decode($questions[$questionIndex]['reponse'], true);
        
        $isCorrect = (sort($userAnswers) == sort($correctAnswers)) && 
                      (json_encode($userAnswers) === json_encode($correctAnswers));
        
        if ($isCorrect) {
            $results[$userId]['score']++;
        }
        $results[$userId]['responses'][] = [
            'question_id' => $response['id_question'],
            'question' => $questions[$questionIndex]['question'],
            'user_answer' => $userAnswers,
            'correct_answer' => $correctAnswers,
            'is_correct' => $isCorrect
        ];
    }
}

foreach ($results as &$result) {
    $result['percentage'] = $result['total_questions'] > 0 
        ? round(($result['score'] / $result['total_questions']) * 100, 1)
        : 0;
}

echo json_encode([
    'success' => true,
    'results' => array_values($results)
]);
?>
