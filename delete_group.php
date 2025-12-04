<?php
session_set_cookie_params(14400);
session_start();

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

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
    echo json_encode(['success' => false, 'error' => 'Erreur de connexion à la base de données']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$groupId = isset($data['group_id']) ? intval($data['group_id']) : 0;

if ($groupId <= 0) {
    echo json_encode(['success' => false, 'error' => 'ID de groupe invalide']);
    exit;
}

if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'entreprise' && $_SESSION['role'] !== 'ecole')) {
    echo json_encode(['success' => false, 'error' => 'Vous n\'avez pas la permission de supprimer des groupes']);
    exit;
}

$stmt = $database->prepare("SELECT COUNT(*) FROM utilisateur_groups WHERE user_id = ? AND group_id = ?");
$stmt->execute([$_SESSION['id'], $groupId]);
if ($stmt->fetchColumn() == 0) {
    echo json_encode(['success' => false, 'error' => 'Vous n\'êtes pas membre de ce groupe']);
    exit;
}

try {
    $database->beginTransaction();
    
    $stmt = $database->prepare("
        DELETE ru FROM sql_reponse_utilisateur ru
        INNER JOIN sql_questions q ON ru.id_question = q.id
        INNER JOIN sql_quizz qz ON q.quizz_id = qz.id
        WHERE qz.group_id = ?
    ");
    $stmt->execute([$groupId]);
    
    $stmt = $database->prepare("
        DELETE q FROM sql_questions q
        INNER JOIN sql_quizz qz ON q.quizz_id = qz.id
        WHERE qz.group_id = ?
    ");
    $stmt->execute([$groupId]);
    
    $stmt = $database->prepare("DELETE FROM sql_quizz WHERE group_id = ?");
    $stmt->execute([$groupId]);
    
    $stmt = $database->prepare("DELETE FROM group_invitations WHERE group_id = ?");
    $stmt->execute([$groupId]);
    
    $stmt = $database->prepare("DELETE FROM utilisateur_groups WHERE group_id = ?");
    $stmt->execute([$groupId]);
    
    $stmt = $database->prepare("DELETE FROM sql_groups WHERE id = ?");
    $stmt->execute([$groupId]);
    
    $database->commit();
    
    echo json_encode(['success' => true, 'message' => 'Groupe supprimé avec succès']);
} catch (PDOException $exception) {
    $database->rollBack();
    echo json_encode(['success' => false, 'error' => 'Erreur lors de la suppression : ' . $exception->getMessage()]);
}
?>
