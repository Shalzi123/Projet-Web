<?php

try {
    $dbh = new PDO(
        'mysql:host=localhost;dbname=quizzeo_sql;charset=utf8',
        'root',
        ''
    );
} catch (Exception $e) {
    echo "Erreur de connexion BDD";
    exit;
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
            echo "<b>Ce nom d'utilisateur existe déjà choisissez-en un autre.</b>";
        } else{
            $hash = password_hash($_POST['password'], PASSWORD_DEFAULT);

        $sth = $dbh->prepare("INSERT INTO sql_utilisateur (username, password, role) VALUES (:username, :password, :role)");
        $sth->execute([
            'username' => $_POST['username'],
            'password' => $hash,
            'role' => $_POST['role']

        ]);

        echo "<b>Inscription Valider</b>";
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

        if($role = 'admin'){
            header("Location: adminpage.php");
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
            
            header("Location: test.php");
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



?>
