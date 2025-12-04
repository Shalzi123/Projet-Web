<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'error' => 'Non connecté']);
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

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'generate':
        try {
            if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'entreprise' && $_SESSION['role'] !== 'ecole')) {
                echo json_encode(['success' => false, 'error' => 'Seules les entreprises et les écoles peuvent générer des liens d\'invitation']);
                exit;
            }
            
            $groupId = $_POST['group_id'] ?? 0;
            
            if (!$groupId) {
                echo json_encode(['success' => false, 'error' => 'ID groupe manquant']);
                exit;
            }
            
            $stmt = $database->prepare("SELECT COUNT(*) FROM utilisateur_groups WHERE user_id = ? AND group_id = ?");
            $stmt->execute([$_SESSION['id'], $groupId]);
            
            if ($stmt->fetchColumn() == 0) {
                echo json_encode(['success' => false, 'error' => 'Vous ne faites pas partie de ce groupe']);
                exit;
            }
            
            $token = bin2hex(random_bytes(16));
            $expiration = date('Y-m-d H:i:s', strtotime('+7 days'));
            
            $stmt = $database->prepare("DELETE FROM group_invitations WHERE group_id = ?");
            $stmt->execute([$groupId]);
            
            $stmt = $database->prepare("
                INSERT INTO group_invitations (group_id, token, created_by, expires_at) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$groupId, $token, $_SESSION['id'], $expiration]);
            
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
            $host = $_SERVER['HTTP_HOST'];
            $basePath = dirname($_SERVER['SCRIPT_NAME']);
            $inviteUrl = "{$protocol}://{$host}{$basePath}/join_group.php?token={$token}";
            
            echo json_encode([
                'success' => true, 
                'invite_url' => $inviteUrl,
                'expires_at' => $expiration
            ]);
            
        } catch (PDOException $exception) {
            echo json_encode(['success' => false, 'error' => $exception->getMessage()]);
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'error' => 'Action non reconnue']);
        break;
}
?>
