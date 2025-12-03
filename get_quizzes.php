<?php
header('Content-Type: application/json');
$dbh = new PDO('mysql:host=localhost;dbname=quizzeo;charset=utf8', 'root', '');

$quizzes = [];
foreach ($dbh->query('SELECT * FROM sql_quizz') as $quiz) {
    $quiz_id = $quiz['id'];
    $stmt = $dbh->prepare('SELECT * FROM sql_questions WHERE quizz_id = ?');
    $stmt->execute([$quiz_id]);
    $questions = [];
    while ($q = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $questions[] = [
            'id' => $q['id'],
            'type' => $q['type'],
            'text' => $q['question'],
            'options' => explode('|', $q['options']),
            'answer' => array_map('intval', explode(',', $q['reponse']))
        ];
    }
    $quizzes[] = [
        'id' => $quiz['id'],
        'title' => $quiz['nom'],
        'description' => $quiz['description'],
        'theme' => $quiz['theme'],
        'questions' => $questions
    ];
}
echo json_encode($quizzes);
