<?php
 
try {
$dbh = new PDO(
    'mysql:host=localhost;dbname=quizzeo;charset=utf8',
    'root',
    ''
);
} catch (Exception $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}
session_start();
 
 
if (isset($_POST['register'])) {
    if ($_POST['username'] != '' && $_POST['password'] != '') {
        $hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
            try {
                $sth = $dbh->prepare("INSERT INTO sql_utilisateur (username, password) VALUES (:username, :password)");
                $sth->execute([
                    'username' => $_POST['username'],
                    'password' => $hash,
                ]);
                $_SESSION['username'] = $_POST['username'];
                $stmt = $dbh->prepare("SELECT id FROM sql_utilisateur WHERE username = :username");
                $stmt->execute(['username' => $_POST['username']]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($user && isset($user['id'])) {
                    $_SESSION['id'] = (int)$user['id'];
                }
            } catch (PDOException $e) {
                echo "<b>Erreur lors de l'inscription : " . htmlspecialchars($e->getMessage()) . "</b>";
            }
    } else {
        echo "<b>Veuillez remplir tous les champs pour vous inscrire.</b>";
    }
}
if (isset($_POST['connect'])) {
    $stmt = $dbh->prepare("SELECT * FROM sql_utilisateur WHERE username = :username");
    $stmt->execute(['username' => $_POST['username']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($_POST['password'], $user['password'])) {
        $_SESSION['username'] = $_POST['username'];
        if (isset($user['id'])) {
            $_SESSION['id'] = (int)$user['id'];
        }
    } else {
        echo "<b>Erreur : nom d'utilisateur ou mot de passe incorrect.</b>";
    }
}
 
 
 
function displaygroup($groupinfo){
    if (!$groupinfo || !is_array($groupinfo)) return;
    foreach ($groupinfo as $group) {
        echo '<li>';
        echo htmlspecialchars($group['nom'] ?? $group['name'] ?? 'Groupe #' . ($group['id'] ?? '?'));
        if (!empty($group['description'])) {
            echo ' : ' . htmlspecialchars($group['description']);
        }
        echo '</li>';
    }
}
 
 
 
 
function showgroups($dbh){
    if (empty($_SESSION['id'])) {
        echo "<p>Aucun utilisateur connecté.</p>";
        return;
    }

    $user_id = (int)$_SESSION['id'];

    try {
        $sth = $dbh->prepare("SELECT g.* FROM utilisateur_groups ug JOIN sql_groups g ON ug.group_id = g.id WHERE ug.user_id = :user_id");
        $sth->execute(['user_id' => $user_id]);
        $groups = $sth->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        echo "<p>Erreur lors de la récupération des groupes : " . htmlspecialchars($e->getMessage()) . "</p>";
        return;
    }
    if (!$groups || count($groups) === 0) {
        echo "<p>Vous n'appartenez à aucun groupe.</p>";
        return;
    }

    echo '<h3>Groupes auxquels vous appartenez :</h3>';
    echo '<div style="display:flex; flex-wrap:wrap; gap:16px; margin-bottom:20px;">';
    foreach ($groups as $group) {
        echo '<div style="background:#e3e9f7; border-radius:10px; padding:18px 28px; min-width:160px; box-shadow:0 2px 8px #0001; font-size:1.1em;">';
        echo htmlspecialchars($group['nomgroupe'] ?? $group['name'] ?? 'Groupe #' . ($group['id'] ?? '?'));
        if (!empty($group['descriptiongroupe'])) {
            echo '<br><span style="font-size:0.95em;color:#555;">' . htmlspecialchars($group['descriptiongroupe']) . '</span>';
        }
        echo '</div>';
    }
    echo '</div>';
}

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['group_name']) && !empty($_POST['group_name'])
    && !empty($_SESSION['id'])
) {
    $groupName = trim($_POST['group_name']);
    $desc = isset($_POST['group_desc']) ? trim($_POST['group_desc']) : '';
    try {
        $sth = $dbh->prepare("INSERT INTO sql_groups (nomgroupe, descriptiongroupe) VALUES (:nomgroupe, :descriptiongroupe)");
        $sth->execute(['nomgroupe' => $groupName, 'descriptiongroupe' => $desc]);
        $groupId = $dbh->lastInsertId();
        $userId = (int)$_SESSION['id'];
        $sth2 = $dbh->prepare("INSERT INTO utilisateur_groups (user_id, group_id) VALUES (:user_id, :group_id)");
        $sth2->execute(['user_id' => $userId, 'group_id' => $groupId]);
        echo '<div style="color:green;">Groupe créé et vous y êtes ajouté !</div>';
    } catch (PDOException $e) {
        echo '<div style="color:red;">Erreur lors de la création : '.htmlspecialchars($e->getMessage()).'</div>';
    }
}

?>
<button id="showCreateGroupBtn" style="margin:20px 0;">Créer un groupe</button>
<div id="createGroupDiv" style="display:none; margin-bottom:20px;">
    <form method="post" id="createGroupForm">
        <label for="group_name">Nom du groupe :</label>
        <input type="text" name="group_name" id="group_name" required>
        <br>
        <label for="group_desc">Description :</label>
        <textarea name="group_desc" id="group_desc" rows="2" style="width:220px;resize:vertical;"></textarea>
        <br>
        <button type="submit">Créer</button>
        <button type="button" id="cancelCreateGroup">Annuler</button>
    </form>
</div>
<script>
document.getElementById('showCreateGroupBtn').onclick = function() {
    document.getElementById('createGroupDiv').style.display = 'block';
    this.style.display = 'none';
};
document.getElementById('cancelCreateGroup').onclick = function() {
    document.getElementById('createGroupDiv').style.display = 'none';
    document.getElementById('showCreateGroupBtn').style.display = 'inline-block';
};
</script>

<?php
showgroups($dbh);
?>
 
 
 