<?php
session_start();
header('Content-Type: application/json');

try {
    $dbh = new PDO(
        'mysql:host=localhost;dbname=quizzeo;charset=utf8',
        'root',
        '',
        array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
    );
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Erreur de connexion à la base de données']);
    exit;
}

// Récupérer l'action depuis GET, POST ou JSON body
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Si l'action n'est pas trouvée, vérifier dans le corps JSON
if (empty($action)) {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    if ($data && isset($data['action'])) {
        $action = $data['action'];
    }
}

switch ($action) {
    case 'getAll':
        // Récupérer tous les quizzes d'un groupe
        try {
            $group_id = $_GET['group_id'] ?? 0;
            
            if ($group_id > 0) {
                $stmt = $dbh->prepare("SELECT * FROM sql_quizz WHERE group_id = ? ORDER BY id DESC");
                $stmt->execute([$group_id]);
            } else {
                $stmt = $dbh->prepare("SELECT * FROM sql_quizz ORDER BY id DESC");
                $stmt->execute();
            }
            $quizzes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Pour chaque quiz, récupérer ses questions
            foreach ($quizzes as &$quiz) {
                $stmt = $dbh->prepare("SELECT * FROM sql_questions WHERE quizz_id = ? ORDER BY id");
                $stmt->execute([$quiz['id']]);
                $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Décoder les options JSON
                foreach ($questions as &$question) {
                    $question['options'] = json_decode($question['options'], true);
                    $question['reponse'] = json_decode($question['reponse'], true);
                }
                
                $quiz['questions'] = $questions;
            }
            
            echo json_encode(['success' => true, 'quizzes' => $quizzes]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;
        
    case 'create':
        // Créer un nouveau quiz
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!$data || !isset($data['nom']) || !isset($data['questions'])) {
                echo json_encode(['success' => false, 'error' => 'Données invalides']);
                exit;
            }
            
            // Insérer le quiz
            $stmt = $dbh->prepare("
                INSERT INTO sql_quizz (nom, theme, description, etatquizz, nbr_question, question_id, group_id) 
                VALUES (?, ?, ?, ?, ?, 0, ?)
            ");
            
            $theme = $data['theme'] ?? $data['icon'] ?? '📝';
            $description = $data['description'] ?? '';
            $etatquizz = 'actif';
            $nbr_question = count($data['questions']);
            $group_id = $data['group_id'] ?? 0;
            
            $stmt->execute([
                $data['nom'],
                $theme,
                $description,
                $etatquizz,
                $nbr_question,
                $group_id
            ]);
            
            $quizId = $dbh->lastInsertId();
            
            // Insérer les questions
            $stmt = $dbh->prepare("
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
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;
        
    case 'delete':
        // Supprimer un quiz
        try {
            $quizId = $_POST['quiz_id'] ?? $_GET['quiz_id'] ?? null;
            
            if (!$quizId) {
                echo json_encode(['success' => false, 'error' => 'ID manquant']);
                exit;
            }
            
            // Supprimer les questions associées
            $stmt = $dbh->prepare("DELETE FROM sql_questions WHERE quizz_id = ?");
            $stmt->execute([$quizId]);
            
            // Supprimer le quiz
            $stmt = $dbh->prepare("DELETE FROM sql_quizz WHERE id = ?");
            $stmt->execute([$quizId]);
            
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;
        
    case 'getById':
        // Récupérer un quiz spécifique
        try {
            $quizId = $_GET['quiz_id'] ?? null;
            
            if (!$quizId) {
                echo json_encode(['success' => false, 'error' => 'ID manquant']);
                exit;
            }
            
            $stmt = $dbh->prepare("SELECT * FROM sql_quizz WHERE id = ?");
            $stmt->execute([$quizId]);
            $quiz = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$quiz) {
                echo json_encode(['success' => false, 'error' => 'Quiz non trouvé']);
                exit;
            }
            
            // Récupérer les questions
            $stmt = $dbh->prepare("SELECT * FROM sql_questions WHERE quizz_id = ? ORDER BY id");
            $stmt->execute([$quizId]);
            $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($questions as &$question) {
                $question['options'] = json_decode($question['options'], true);
                $question['reponse'] = json_decode($question['reponse'], true);
            }
            
            $quiz['questions'] = $questions;
            
            echo json_encode(['success' => true, 'quiz' => $quiz]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'error' => 'Action non reconnue']);
        break;
}
?>
