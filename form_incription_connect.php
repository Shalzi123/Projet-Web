<?php

try {
$dbh = new PDO(
    'mysql:host=localhost;dbname=quizzeo_sql;charset=utf8',
    'root',
    ''
);
} catch (Exception $e) {
    echo"erreur";
}
session_start();
$_SESSION["username"] = '';

if (isset($_POST['register'])) {
    if ($_POST['username'] != '' && $_POST['password'] != '') {
        $hash = password_hash($_POST['password'], algo: PASSWORD_DEFAULT);
        $sth = $dbh->prepare("INSERT INTO sql_utilisateur (username, password) VALUES (:username, :password)");
        $sth->execute([
            'username' => $_POST['username'],
            'password' => $hash,
        ]);
        echo "<b>Votre inscription est validée</b>";
    }
}
if (isset($_POST['connect'])) {
    $stmt = $dbh->prepare("SELECT * FROM sql_utilisateur WHERE username = :username");
    $stmt->execute(['username' => $_POST['username']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        if (password_verify($_POST['password'], $user['password'])) {
            $_SESSION['username'] = $_POST['username'];
            
        }
    }
}

?>