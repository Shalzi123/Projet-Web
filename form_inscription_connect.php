<?php
try {
    $database = new PDO(
        'mysql:host=localhost;dbname=quizzeo;charset=utf8mb4',
        'root',
        ''
    );
} catch (Exception $exception) {
    echo "Erreur de connexion BDD";
    exit;
}
 
session_set_cookie_params(14400, "/", "", false, true);
session_start();
 
if (!isset($_SESSION['username']) && isset($_COOKIE['remember_token'])) {
    $token = $_COOKIE['remember_token'];
    $stmt = $dbh->prepare("SELECT * FROM sql_utilisateur WHERE remember_token IS NOT NULL");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($users as $user) {
        if (password_verify($token, $user['remember_token'])) {
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            break;
        }
    }
    if (!isset($_SESSION['username'])) {
        setcookie('remember_token', '', time() - 3600, "/");
    }
}
 
if (isset($_POST['register'])) {
    if (!empty($_POST['username']) && !empty($_POST['password'])) {
        $sth = $dbh->prepare("SELECT COUNT(*) FROM sql_utilisateur WHERE username = :username");
        $sth->execute(['username' => $_POST['username']]);
        $count = $sth->fetchColumn();
        if ($count > 0) {
            echo "<b>Ce nom d'utilisateur existe déjà choisissez-en un autre.</b>";
        } else {
            $hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $sth = $dbh->prepare("INSERT INTO sql_utilisateur (username, password, role) VALUES (:username, :password, :role)");
            $sth->execute([
                'username' => $_POST['username'],
                'password' => $hash,
                'role' => 'user'
            ]);
            echo "<b>Inscription validée</b>";
        }
    }
}
 
if (isset($_POST['connect'])) {
    $stmt = $dbh->prepare("SELECT * FROM sql_utilisateur WHERE username = :username");
    $stmt->execute(['username' => $_POST['username']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
 
    if ($user && password_verify($_POST['password'], $user['password'])) {
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
 
        if (!empty($_POST['remember'])) {
            $token = bin2hex(random_bytes(32));
            $stmt = $dbh->prepare("UPDATE sql_utilisateur SET remember_token = :token WHERE username = :username");
            $stmt->execute([
                'token' => password_hash($token, PASSWORD_DEFAULT),
                'username' => $user['username']
            ]);
            setcookie(
                "remember_token",
                $token,
                time() + (60 * 60 * 24 * 30),
                "/",
                "",
                false,
                true
            );
        }
 
        if ($_SESSION['role'] === 'admin') {
            header("Location: adminpage.php");
            exit;
        } else {
            header("Location: test.php");
            exit;
        }
    } else {
        echo "Identifiants incorrects";
    }
}
 
if (isset($_GET['logout'])) {
    if (isset($_SESSION['username'])) {
        $stmt = $dbh->prepare("UPDATE sql_utilisateur SET remember_token = NULL WHERE username = :username");
        $stmt->execute(['username' => $_SESSION['username']]);
        setcookie('remember_token', '', time() - 3600, "/");
        session_destroy();
    }
    header("Location: index.php?success=logout");
    exit;
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

    $userId = (int)$_SESSION['id'];

    try {
        $stmt = $database->prepare("SELECT g.* FROM utilisateur_groups ug JOIN sql_groups g ON ug.group_id = g.id WHERE ug.user_id = :user_id");
        $stmt->execute(['user_id' => $userId]);
        $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $exception) {
        echo "<p>Erreur lors de la récupération des groupes : " . htmlspecialchars($exception->getMessage()) . "</p>";
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
        $stmt = $database->prepare("INSERT INTO sql_groups (nomgroupe, descriptiongroupe) VALUES (:nomgroupe, :descriptiongroupe)");
        $stmt->execute(['nomgroupe' => $groupName, 'descriptiongroupe' => $desc]);
        $groupId = $database->lastInsertId();
        $userId = (int)$_SESSION['id'];
        $stmt2 = $database->prepare("INSERT INTO utilisateur_groups (user_id, group_id) VALUES (:user_id, :group_id)");
        $stmt2->execute(['user_id' => $userId, 'group_id' => $groupId]);
        echo '<div style="color:green;">Groupe créé et vous y êtes ajouté !</div>';
    } catch (PDOException $exception) {
        echo '<div style="color:red;">Erreur lors de la création : '.htmlspecialchars($exception->getMessage()).'</div>';
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
 
 
 