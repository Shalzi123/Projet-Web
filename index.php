<?php
$message = '';
try {
    $dbh = new PDO(
        'mysql:host=localhost;dbname=quizzeo;charset=utf8',
        'root',
        ''
    );
} catch (Exception $e) {
    $message = '<div style="color:red;font-weight:bold;margin:10px 0;">Erreur de connexion BDD</div>';
}

session_set_cookie_params(14400);
session_start();

if (!isset($_SESSION['username']) && isset($_COOKIE['remember_token'])) {
    $token = $_COOKIE['remember_token'];
    $stmt = $dbh->prepare("SELECT * FROM sql_utilisateur WHERE remember_token IS NOT NULL");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($users as $user) {
        if (password_verify($token, $user['remember_token'])) {
            $_SESSION['username'] = $user['username'];
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
        if($count>0){
            $message = "<div style='color:red;font-weight:bold;margin:10px 0;'>Ce nom d'utilisateur existe déjà choisissez-en un autre.</div>";
        } else{
            $hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $sth = $dbh->prepare("INSERT INTO sql_utilisateur (username, password, role) VALUES (:username, :password, :role)");
            $sth->execute([
                'username' => $_POST['username'],
                'password' => $hash,
                'role' => $_POST['role'] ?? 'user'
            ]);
            $stmt = $dbh->prepare("SELECT id FROM sql_utilisateur WHERE username = :username");
            $stmt->execute(['username' => $_POST['username']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($user && isset($user['id'])) {
                $_SESSION['id'] = (int)$user['id'];
            }
            $_SESSION['username'] = $_POST['username'];
            $_SESSION['role'] = $_POST['role'] ?? 'user';
            header("Location: groupes.php");
            exit;
        }
    }
}

if (isset($_POST['connect'])) {
    $stmt = $dbh->prepare("SELECT * FROM sql_utilisateur WHERE username = :username");
    $stmt->execute(['username' => $_POST['username']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($_POST['password'], $user['password'])) {
        if (isset($user['banned']) && $user['banned'] == 1) {
            $message = '<div style="color:red;font-weight:bold;margin:10px 0;">Votre compte a été banni. Connexion impossible.</div>';
        } else {
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            if (isset($user['id'])) {
                $_SESSION['id'] = (int)$user['id'];
            }
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
            if ($user['role'] === 'admin') {
                header("Location: adminpage.php");
                exit;
            } else {
                header("Location: groupes.php");
                exit;
            }
        }
    } else {
        $message = '<div style="color:red;font-weight:bold;margin:10px 0;">Identifiants incorrects</div>';
    }
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quizzeo</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="header">
        <img src="images/quizzeo_logo.png" alt="Logo Quizzeo" class="logo" style="max-width: 350px;">
    </div>
    <?php if(!empty($message)) echo $message; ?>
    <div class="formulaire">
    <h1>Connexion</h1>
    <form class="form" method="POST">
        <div>
        <label for="">Nom d'utilisateur</label>
            <input type="text" name="username" required>
        </div>
        <div>
            <label for="">Mot de passe</label>
            <input type="password" name="password" required>
        </div>
        <input class="submit_btn" type="submit" value="Valider" name="connect">
    </form>
        <div id="login-error-message">
            <?php
            if (isset($_POST['connect']) && !empty($message) && strpos($message, 'color:red') !== false) {
                echo $message;
            }
            ?>
        </div>
    </div>
    <div class="formulaire">
    <h1>Inscription</h1>
    <form class="form" method="POST">
        <div>
        <label for="">Nom d'utilisateur</label>
            <input type="text" name="username" required>
        </div>
        <div>
            <label for="">Mot de passe</label>
            <input type="password" name="password" required>
        </div>
        <div>
            <label for="role">Type d’utilisateur</label>
            <select name="role" id="role">
                <option value="utilisateur">Utilisateur</option>
                <option value="entreprise">Entreprise</option>
                <option value="ecole">École</option>
            </select>
        </div>
        <input class="submit_btn" type="submit" value="Valider" name="register">
    </form>
        <div id="register-error-message">
            <?php
            if (isset($_POST['register']) && !empty($message) && strpos($message, "existe déjà") !== false) {
                echo $message;
            }
            ?>
        </div>
    </div>
</body>
</html>
