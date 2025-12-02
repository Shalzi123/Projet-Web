<?php
 
try {
$dbh = new PDO(
    'mysql:host=localhost;dbname=quizzeo_sql;charset=utf8',
    'root',
    '13062007'
);
} catch (Exception $e) {
    echo"erreur";
}
session_start();
 
 
if (isset($_POST['register'])) {
    if ($_POST['username'] != '' && $_POST['password'] != '') {
    $hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
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
            if (isset($user['id'])) {
                $_SESSION['id'] = (int)$user['id'];
            }
        }
    }
}
 
 
 
function displaygroup($groupinfo){
    var_dump($groupinfo);
}
 
 
 
 
function showgroups($dbh){
    if (empty($_SESSION['id'])) {
        echo "No user id in session, cannot show groups.";
        return;
    }
 
    $user_id = (int)$_SESSION['id'];
 
    try {
        $sth = $dbh->prepare("SELECT sql_groups FROM sql_utilisateur WHERE id = :id");
        $sth->execute(['id' => $user_id]);
        $row = $sth->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        echo "Database error while fetching groups: " . htmlspecialchars($e->getMessage());
        return;
    }
    if (!$row || empty($row['sql_groups'])) {
        return;
    }
 
    $chain = $row['sql_groups'];
    $groupIds = array_filter(array_map('trim', explode(',', $chain)), function($v){ return $v !== ''; });
 
    $groupStmt = $dbh->prepare("SELECT * FROM sql_groups WHERE id = :id");
    foreach ($groupIds as $gid) {
        $gid = (int)$gid;
        if ($gid <= 0) continue;
        $groupStmt->execute(['id' => $gid]);
        $datagroups = $groupStmt->fetchAll(PDO::FETCH_ASSOC);
        if ($datagroups) {
            displaygroup($datagroups);
        }
    }
 
}
showgroups($dbh);
?>
 
 
 