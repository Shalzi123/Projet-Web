
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accueil</title>
</head>
<body>
    <?php
session_start();

if (isset($_SESSION['username'])) {
    echo "<h1>Bienvenue ".htmlspecialchars($_SESSION['username'])."</h1>";
    echo '<a href="form_incription_connect.php?logout=1">Déconnexion</a>';
    exit;
}
?>

    
    <h1>Incription</h1>
    <form action="form_incription_connect.php" method="POST">

        <label for="">Username</label>
            <input type="text" name="username">
        </div>
       
        <div>
            <label for="">Password</label>
            <input type="password" name="password">
        </div>
 
        <input type="submit" value="Valider" name="register">
    </form>

    <h2>Connection</h2>
    <form action="form_incription_connect.php" method="POST">

        <label for="">Username</label>
            <input type="text" name="username">
        </div>
       
        <div>
            <label for="">Password</label>
            <input type="password" name="password">
        </div>
        <label>
            <input type="checkbox" name="remember"> Se souvenir de moi
        </label>
        
        
        <input type="submit" value="Valider" name="connect">
    </form>
    
    
</body>
</html>