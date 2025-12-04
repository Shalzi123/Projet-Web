<?php
session_set_cookie_params(14400);
session_start();

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

if (!isset($_SESSION['username'])) {
    header('Location: index.php');
    exit;
}

try {
    $database = new PDO(
        'mysql:host=localhost;dbname=quizzeo;charset=utf8mb4',
        'root',
        ''
    );
} catch (Exception $exception) {
    echo '<div style="color:red;font-weight:bold;margin:10px 0;">Erreur de connexion BDD</div>';
    exit;
}

$groupes = [];
if (isset($_SESSION['id'])) {
    $stmt = $database->prepare("SELECT g.id, g.nomgroupe, g.descriptiongroupe FROM sql_groups g JOIN utilisateur_groups ug ON g.id = ug.group_id WHERE ug.user_id = :user_id");
    $stmt->execute(['user_id' => $_SESSION['id']]);
    $groupes = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
$groupeMessage = '';
$canCreateGroup = isset($_SESSION['role']) && ($_SESSION['role'] === 'entreprise' || $_SESSION['role'] === 'ecole');

if (isset($_SESSION['groupe_message'])) {
    $groupeMessage = $_SESSION['groupe_message'];
    unset($_SESSION['groupe_message']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['creer_groupe']) && !empty($_POST['nomgroupe'])) {
    if (!$canCreateGroup) {
        $groupeMessage = '<div style="color:red;font-weight:bold;margin:10px 0;">Vous n\'avez pas les permissions pour créer un groupe. Seules les entreprises et les écoles peuvent créer des groupes.</div>';
    } else {
        $stmt = $database->prepare("SELECT COUNT(*) FROM sql_groups WHERE nomgroupe = :nomgroupe");
        $stmt->execute(['nomgroupe' => $_POST['nomgroupe']]);
        $description = isset($_POST['descriptiongroupe']) ? $_POST['descriptiongroupe'] : '';
        $count = $stmt->fetchColumn();
        if ($count > 0) {
            $_SESSION['groupe_message'] = '<div style="color:red;font-weight:bold;margin:10px 0;">Ce nom de groupe existe déjà.</div>';
            header('Location: groupes.php');
            exit;
        } else {
            $stmt = $database->prepare("INSERT INTO sql_groups (nomgroupe, descriptiongroupe) VALUES (:nomgroupe, :descriptiongroupe)");
            $stmt->execute([
                'nomgroupe' => $_POST['nomgroupe'],
                'descriptiongroupe' => $description
            ]);
            $groupId = $database->lastInsertId();
            $stmt = $database->prepare("INSERT INTO utilisateur_groups (user_id, group_id) VALUES (:user_id, :group_id)");
            $stmt->execute([
                'user_id' => $_SESSION['id'],
                'group_id' => $groupId
            ]);
            $_SESSION['groupe_message'] = '<div style="color:green;font-weight:bold;margin:10px 0;">Groupe créé avec succès !</div>';
            header('Location: groupes.php');
            exit;
        }
    }
}




?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes groupes</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="header">
        <a href="<?php echo isset($_SESSION['username']) ? 'groupes.php' : 'index.php'; ?>" style="display: inline-block; text-decoration: none;">
            <img src="images/quizzeo_logo.png" alt="Logo Quizzeo" class="logo" style="max-width: 350px; cursor: pointer;">
        </a>
        <a href="logout.php" class="logout-btn" style="position: absolute; right: 20px; top: 50%; transform: translateY(-50%); background-color: #dc3545; color: #ffffff; border: none; padding: 10px 25px; border-radius: 8px; font-size: 14px; font-weight: 600; text-decoration: none; box-shadow: 0 2px 5px rgba(0,0,0,0.2);">Déconnexion</a>
    </div>
    <?php if ($canCreateGroup) { ?>
    <div class="formulaire">
        <h1>Créer un groupe</h1>
        <form class="form" method="POST">
            <div>
                <label for="nomgroupe">Nom du groupe</label>
                <input type="text" name="nomgroupe" id="nomgroupe" required>
            </div>
            <div>
                <label for="descriptiongroupe">Description</label>
                <textarea name="descriptiongroupe" id="descriptiongroupe" rows="3" style="width:100%;"></textarea>
            </div>
            <input class="submit_btn" type="submit" value="Créer" name="creer_groupe">
        </form>
        <div id="groupe-message">
            <?php if (!empty($groupeMessage)) { echo $groupeMessage; $groupeMessage = ''; } ?>
        </div>
    </div>
    <?php } ?>
    <div class="formulaire">
        <h1>Mes groupes</h1>
        <?php if (empty($groupes)) { ?>
            <div>Aucun groupe trouvé.</div>
        <?php } else { ?>
            <div class="groupes-list">
                <?php foreach ($groupes as $groupe) {?>
                    <div class="groupe-case" style="position: relative;">
                        <a href="groupe_page.php?group_id=<?php echo $groupe['id']; ?>" style="text-decoration: none; color: inherit;">
                            <div><div class="groupe-nom"><?php echo htmlspecialchars($groupe['nomgroupe']); ?></div></div>
                            <div class="groupe-description" style="display:flex; justify-content:center;">
                                <?php echo nl2br(htmlspecialchars($groupe['descriptiongroupe'])); ?>
                            </div>
                        </a>
                        <?php if ($canCreateGroup) { ?>
                        <button onclick="deleteGroup(<?php echo $groupe['id']; ?>, '<?php echo htmlspecialchars($groupe['nomgroupe'], ENT_QUOTES); ?>')" class="delete-group-btn" style="position: absolute; top: 10px; right: 10px; background: #dc3545; color: white; border: none; padding: 5px 10px; border-radius: 5px; cursor: pointer; font-size: 12px;" title="Supprimer ce groupe">×</button>
                        <?php } ?>
                    </div>
                <?php } ?>
            </div>
        <?php } ?>
    </div>
    
    <script>
    function deleteGroup(groupId, groupName) {
        if (!confirm('Voulez-vous vraiment supprimer le groupe "' + groupName + '" ?\n\nAttention : Cela supprimera également tous les quiz, questions et réponses associés.')) {
            return;
        }
        
        fetch('delete_group.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ group_id: groupId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Groupe supprimé avec succès');
                location.reload();
            } else {
                alert('Erreur : ' + data.error);
            }
        })
        .catch(error => {
            alert('Erreur lors de la suppression');
            console.error('Erreur:', error);
        });
    }
    </script>
</body>
</html>
