<?php
session_set_cookie_params(14400);
session_start();

if (!isset($_SESSION['username'])) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header('Location: index.php');
    exit;
}

$token = $_GET['token'] ?? '';
$message = '';
$groupInfo = null;

if (empty($token)) {
    $message = '<div style="color:red;font-weight:bold;margin:10px 0;">Lien d\'invitation invalide.</div>';
} else {
    try {
        $database = new PDO(
            'mysql:host=localhost;dbname=quizzeo;charset=utf8mb4',
            'root',
            '',
            array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
        );
        
        $stmt = $database->prepare("
            SELECT gi.*, g.nomgroupe, g.descriptiongroupe 
            FROM group_invitations gi
            JOIN sql_groups g ON gi.group_id = g.id
            WHERE gi.token = ? AND gi.expires_at > NOW()
        ");
        $stmt->execute([$token]);
        $invitation = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$invitation) {
            $message = '<div style="color:red;font-weight:bold;margin:10px 0;">Ce lien d\'invitation est invalide ou a expiré.</div>';
        } else {
            $groupInfo = $invitation;
            
            $stmt = $database->prepare("SELECT COUNT(*) FROM utilisateur_groups WHERE user_id = ? AND group_id = ?");
            $stmt->execute([$_SESSION['id'], $invitation['group_id']]);
            $alreadyMember = $stmt->fetchColumn() > 0;
            
            if ($alreadyMember) {
                $message = '<div style="color:orange;font-weight:bold;margin:10px 0;">Vous faites déjà partie de ce groupe.</div>';
            } elseif (isset($_POST['join_group'])) {
                $stmt = $database->prepare("INSERT INTO utilisateur_groups (user_id, group_id, role) VALUES (?, ?, 'member')");
                $stmt->execute([$_SESSION['id'], $invitation['group_id']]);
                
                $message = '<div style="color:green;font-weight:bold;margin:10px 0;">Vous avez rejoint le groupe avec succès !</div>';
                header("refresh:2;url=groupe_page.php?group_id=" . $invitation['group_id']);
            }
        }
        
    } catch (Exception $exception) {
        $message = '<div style="color:red;font-weight:bold;margin:10px 0;">Erreur: ' . $exception->getMessage() . '</div>';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rejoindre un groupe - Quizzeo</title>
    <link rel="icon" type="image/vnd.icon" href="images/quiz_logo.ico">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="header">
        <a href="<?php echo isset($_SESSION['username']) ? 'groupes.php' : 'index.php'; ?>" style="display: inline-block; text-decoration: none;">
            <img src="images/quizzeo_logo.png" alt="Logo Quizzeo" class="logo" style="max-width: 350px; cursor: pointer;">
        </a>
        <a href="logout.php" class="logout-btn" style="position: absolute; right: 20px; top: 50%; transform: translateY(-50%); background-color: #dc3545; color: #ffffff; border: none; padding: 10px 25px; border-radius: 8px; font-size: 14px; font-weight: 600; text-decoration: none; box-shadow: 0 2px 5px rgba(0,0,0,0.2);">Déconnexion</a>
    </div>
    
    <div class="formulaire">
        <h1>Rejoindre un groupe</h1>
        
        <?php echo $message; ?>
        
        <?php if ($groupInfo && !isset($_POST['join_group'])): ?>
            <?php
            $stmt = $database->prepare("SELECT COUNT(*) FROM utilisateur_groups WHERE user_id = ? AND group_id = ?");
            $stmt->execute([$_SESSION['id'], $groupInfo['group_id']]);
            $alreadyMember = $stmt->fetchColumn() > 0;
            
            if (!$alreadyMember):
            ?>
            <div style="margin: 20px 0;">
                <h2 style="color: #0056ab; margin-bottom: 10px;"><?php echo htmlspecialchars($groupInfo['nomgroupe']); ?></h2>
                <p style="color: #666; margin-bottom: 20px;"><?php echo htmlspecialchars($groupInfo['descriptiongroupe']); ?></p>
                
                <form method="POST">
                    <input type="hidden" name="join_group" value="1">
                    <input class="submit_btn" type="submit" value="Rejoindre ce groupe">
                </form>
            </div>
            <?php else: ?>
            <div style="margin: 20px 0;">
                <a href="groupe_page.php?group_id=<?php echo $groupInfo['group_id']; ?>" class="submit_btn" style="text-decoration:none;display:inline-block;">
                    Aller au groupe
                </a>
            </div>
            <?php endif; ?>
        <?php endif; ?>
        
        <div style="margin-top: 20px;">
            <a href="groupes.php" style="color: #0056ab; text-decoration: none;">← Retour à mes groupes</a>
        </div>
    </div>
</body>
</html>
