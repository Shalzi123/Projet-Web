<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

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

$action = $_GET['action'] ?? $_POST['action'] ?? '';

if (empty($action)) {
    $jsonInput = file_get_contents('php://input');
    $data = json_decode($jsonInput, true);
    if ($data && isset($data['action'])) {
        $action = $data['action'];
    }
}

switch ($action) {
    case 'getAll':
        try {
            $groupId = $_GET['group_id'] ?? 0;
            
            if ($groupId > 0) {
                $stmt = $database->prepare("SELECT * FROM sql_quizz WHERE group_id = ? ORDER BY id DESC");
                $stmt->execute([$groupId]);
            } else {
                $stmt = $database->prepare("SELECT * FROM sql_quizz ORDER BY id DESC");
                $stmt->execute();
            }
            $quizzes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($quizzes as &$quiz) {
                $stmt = $database->prepare("SELECT * FROM sql_questions WHERE quizz_id = ? ORDER BY id");
                $stmt->execute([$quiz['id']]);
                $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                foreach ($questions as &$question) {
                    $question['options'] = json_decode($question['options'], true);
                    $question['reponse'] = json_decode($question['reponse'], true);
                }
                
                $quiz['questions'] = $questions;
            }
            
            echo json_encode(['success' => true, 'quizzes' => $quizzes]);
        } catch (PDOException $exception) {
            echo json_encode(['success' => false, 'error' => $exception->getMessage()]);
        }
        break;
        
    case 'create':
        try {
            if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'entreprise' && $_SESSION['role'] !== 'ecole')) {
                echo json_encode(['success' => false, 'error' => 'Vous n\'avez pas la permission de créer des quiz']);
                exit;
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!$data || !isset($data['nom']) || !isset($data['questions'])) {
                echo json_encode(['success' => false, 'error' => 'Données invalides']);
                exit;
            }
            
            $stmt = $database->prepare("
                INSERT INTO sql_quizz (nom, theme, description, etatquizz, nbr_question, question_id, group_id) 
                VALUES (?, ?, ?, ?, ?, 0, ?)
            ");
            
            $theme = $data['theme'] ?? $data['icon'] ?? '📝';
            $description = $data['description'] ?? '';
            $etatQuizz = 'actif';
            $nombreQuestions = count($data['questions']);
            $groupId = $data['group_id'] ?? 0;
            
            $stmt->execute([
                $data['nom'],
                $theme,
                $description,
                $etatQuizz,
                $nombreQuestions,
                $groupId
            ]);
            
            $quizId = $database->lastInsertId();
            
            $stmt = $database->prepare("
                INSERT INTO sql_questions (quizz_id, type, question, options, reponse) 
                VALUES (?, ?, ?, ?, ?)
            ");
            
            foreach ($data['questions'] as $question) {
                $stmt->execute([
                    $quizId,
                    $question['type'] ?? 'single',
                    $question['text'],
                    json_encode($question['options']),
                    json_encode($question['answer'])
                ]);
            }
            
            echo json_encode(['success' => true, 'quiz_id' => $quizId]);
        } catch (PDOException $exception) {
            echo json_encode(['success' => false, 'error' => $exception->getMessage()]);
        }
        break;
        
    case 'delete':
        try {
            if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['entreprise', 'ecole', 'admin'])) {
                echo json_encode(['success' => false, 'error' => 'Permissions insuffisantes']);
                exit;
            }
            
            $quizId = $_POST['quiz_id'] ?? $_GET['quiz_id'] ?? null;
            
            if (!$quizId) {
                echo json_encode(['success' => false, 'error' => 'ID manquant']);
                exit;
            }
            
            $stmt = $database->prepare("DELETE FROM sql_questions WHERE quizz_id = ?");
            $stmt->execute([$quizId]);
            
            $stmt = $database->prepare("DELETE FROM sql_quizz WHERE id = ?");
            $stmt->execute([$quizId]);
            
            echo json_encode(['success' => true]);
        } catch (PDOException $exception) {
            echo json_encode(['success' => false, 'error' => $exception->getMessage()]);
        }
        break;
        
    case 'getById':
        try {
            $quizId = $_GET['quiz_id'] ?? null;
            
            if (!$quizId) {
                echo json_encode(['success' => false, 'error' => 'ID manquant']);
                exit;
            }
            
            $stmt = $database->prepare("SELECT * FROM sql_quizz WHERE id = ?");
            $stmt->execute([$quizId]);
            $quiz = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$quiz) {
                echo json_encode(['success' => false, 'error' => 'Quiz non trouvé']);
                exit;
            }
            
            $stmt = $database->prepare("SELECT * FROM sql_questions WHERE quizz_id = ? ORDER BY id");
            $stmt->execute([$quizId]);
            $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($questions as &$question) {
                $question['options'] = json_decode($question['options'], true);
                $question['reponse'] = json_decode($question['reponse'], true);
            }
            
            $quiz['questions'] = $questions;
            
            echo json_encode(['success' => true, 'quiz' => $quiz]);
        } catch (PDOException $exception) {
            echo json_encode(['success' => false, 'error' => $exception->getMessage()]);
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'error' => 'Action non reconnue']);
        break;
}
?>
