<?php
session_set_cookie_params(14400);
session_start();


if (!isset($_SESSION['username'])) {
    header('Location: index.php');
    exit;
}

try {
    $dbh = new PDO(
        'mysql:host=localhost;dbname=quizzeo;charset=utf8',
        'root',
        ''
    );
} catch (Exception $e) {
    echo '<div style="color:red;font-weight:bold;margin:10px 0;">Erreur de connexion BDD</div>';
    exit;
}

$groupes = [];
if (isset($_SESSION['id'])) {
    $stmt = $dbh->prepare("SELECT g.id, g.nomgroupe, g.descriptiongroupe FROM sql_groups g JOIN utilisateur_groups ug ON g.id = ug.group_id WHERE ug.user_id = :user_id");
    $stmt->execute(['user_id' => $_SESSION['id']]);
    $groupes = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
$groupe_message = '';
if (isset($_POST['creer_groupe']) && !empty($_POST['nomgroupe'])) {
    $stmt = $dbh->prepare("SELECT COUNT(*) FROM sql_groups WHERE nomgroupe = :nomgroupe");
    $stmt->execute(['nomgroupe' => $_POST['nomgroupe']]);
    $description = isset($_POST['descriptiongroupe']) ? $_POST['descriptiongroupe'] : '';
    $count = $stmt->fetchColumn();
    if ($count > 0) {
        $groupe_message = '<div style="color:red;font-weight:bold;margin:10px 0;">Ce nom de groupe existe déjà.</div>';
    } else {
        $stmt = $dbh->prepare("INSERT INTO sql_groups (nomgroupe, descriptiongroupe) VALUES (:nomgroupe, :descriptiongroupe)");
        $stmt->execute([
            'nomgroupe' => $_POST['nomgroupe'],
            'descriptiongroupe' => $description
        ]);
        $group_id = $dbh->lastInsertId();
        $stmt = $dbh->prepare("INSERT INTO utilisateur_groups (user_id, group_id) VALUES (:user_id, :group_id)");
        $stmt->execute([
            'user_id' => $_SESSION['id'],
            'group_id' => $group_id
        ]);
        $groupe_message = '<div style="color:green;font-weight:bold;margin:10px 0;">Groupe créé avec succès !</div>';
    }
}




?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes groupes</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="header">
        <img src="images/quizzeo_logo.png" alt="Logo Quizzeo" class="logo" style="max-width: 350px;">
    </div>
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
            <?php if (!empty($groupe_message)) echo $groupe_message; ?>
        </div>
    </div>
    <div class="formulaire">
        <h1>Mes groupes</h1>
        <?php if (empty($groupes)) { ?>
            <div>Aucun groupe trouvé.</div>
        <?php } else { ?>
            <div class="groupes-list">
                <?php foreach ($groupes as $groupe) {?>
                    <div class="groupe-case">

                        <div> <a href="groupe_page.php?group_id=<?php echo $groupe['id']; ?>"><div class="groupe-nom"><?php echo htmlspecialchars($groupe['nomgroupe']); ?></div></a></div>
                        <div class="groupe-description">
                            <?php echo nl2br(htmlspecialchars($groupe['descriptiongroupe'])); ?>
                        </div>
                    </div>
                <?php } ?>
            </div>
        <?php } ?>
    </div>
</body>
</html>
