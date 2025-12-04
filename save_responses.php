<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'error' => 'Utilisateur non connecté']);
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
    echo json_encode(['success' => false, 'error' => 'Erreur de connexion à la base de données']);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data || !isset($data['responses']) || !is_array($data['responses'])) {
        echo json_encode(['success' => false, 'error' => 'Données invalides']);
        exit;
    }
    
    $userId = $_SESSION['id'];
    $stmt = $database->prepare("
        INSERT INTO sql_reponse_utilisateur (id_utilisateur, id_question, reponse_utilisateur) 
        VALUES (?, ?, ?)
    ");
    
    foreach ($data['responses'] as $response) {
        if (!isset($response['question_id'])) {
            continue;
        }
        
        $questionId = $response['question_id'];
        $userAnswers = $response['user_answers'] ?? [];
        
        $reponseJson = json_encode($userAnswers);
        
        $stmt->execute([
            $userId,
            $questionId,
            $reponseJson
        ]);
    }
    
    echo json_encode(['success' => true]);
} catch (PDOException $exception) {
    echo json_encode(['success' => false, 'error' => $exception->getMessage()]);
} catch (Exception $exception) {
    echo json_encode(['success' => false, 'error' => $exception->getMessage()]);
}
