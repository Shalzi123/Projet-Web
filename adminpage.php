
<?php
 
try {
    $database = new PDO(
        'mysql:host=localhost;dbname=quizzeo;charset=utf8mb4',
        'root',
        ''
    );
} catch (PDOException $exception){
    die($exception->getMessage());
}
 
 
if($_GET['action'] ?? false) {
    header('Content-Type: application/json');
    $action = $_GET['action'];

    if($action == 'users'){
        $stmt = $database->prepare("SELECT * FROM sql_utilisateur");
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($data);
        exit;
    }
    elseif($action == 'groups'){
        $stmt = $database->prepare("SELECT * FROM sql_groups");
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($data);
        exit;
    }
    elseif($action == 'quizz'){
        $stmt = $database->prepare("SELECT * FROM sql_quizz");
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($data);
        exit;
    }
    elseif($action == 'ban' && isset($_POST['id'])) {
        $stmt = $database->prepare("UPDATE sql_utilisateur SET banned = 1 WHERE id = :id");
        $stmt->execute(['id' => $_POST['id']]);
        echo json_encode(['success' => true]);
        exit;
    }
    elseif($action == 'unban' && isset($_POST['id'])) {
        $stmt = $database->prepare("UPDATE sql_utilisateur SET banned = 0 WHERE id = :id");
        $stmt->execute(['id' => $_POST['id']]);
        echo json_encode(['success' => true]);
        exit;
    }
    elseif($action == 'deleteGroup' && isset($_POST['id'])) {
        try {
            $groupId = $_POST['id'];
            
            $database->beginTransaction();
            
            $stmt = $database->prepare("
                DELETE ru FROM sql_reponse_utilisateur ru
                INNER JOIN sql_questions q ON ru.id_question = q.id
                INNER JOIN sql_quizz qz ON q.quizz_id = qz.id
                WHERE qz.group_id = :group_id
            ");
            $stmt->execute(['group_id' => $groupId]);
            
            $stmt = $database->prepare("
                DELETE q FROM sql_questions q
                INNER JOIN sql_quizz qz ON q.quizz_id = qz.id
                WHERE qz.group_id = :group_id
            ");
            $stmt->execute(['group_id' => $groupId]);
            
            $stmt = $database->prepare("DELETE FROM sql_quizz WHERE group_id = :group_id");
            $stmt->execute(['group_id' => $groupId]);
            
            $stmt = $database->prepare("DELETE FROM group_invitations WHERE group_id = :group_id");
            $stmt->execute(['group_id' => $groupId]);
            
            $stmt = $database->prepare("DELETE FROM utilisateur_groups WHERE group_id = :group_id");
            $stmt->execute(['group_id' => $groupId]);
            
            $stmt = $database->prepare("DELETE FROM sql_groups WHERE id = :group_id");
            $stmt->execute(['group_id' => $groupId]);
            
            $database->commit();
            
            echo json_encode(['success' => true]);
        } catch (PDOException $exception) {
            $database->rollBack();
            echo json_encode(['success' => false, 'error' => $exception->getMessage()]);
        }
        exit;
    }
    else {
        echo json_encode(['error' => 'Action non reconnue']);
        exit;
    }
}
 
?>
 
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion - Utilisateurs, Groupes et Quizz</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
       
        .button-container {
            margin-bottom: 20px;
        }
       
        button {
            padding: 10px 20px;
            margin-right: 10px;
            font-size: 16px;
            cursor: pointer;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            transition: background-color 0.3s;
        }
       
        button:hover {
            background-color: #45a049;
        }
       
        button.active {
            background-color: #008CBA;
        }
       
        table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-top: 20px;
        }
       
        th {
            background-color: #333;
            color: white;
            padding: 12px;
            text-align: left;
        }
       
        td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
        }
       
        tr:hover {
            background-color: #f5f5f5;
        }
       
        #tableContainer {
            display: none;
        }
       
        .error {
            color: red;
            padding: 10px;
            background-color: #ffe6e6;
            border-radius: 4px;
        }
       
        .loading {
            color: #666;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="header">
        <a href="adminpage.php" style="display: inline-block; text-decoration: none;">
            <img src="images/quizzeo_logo.png" alt="Logo Quizzeo" class="logo" style="max-width: 350px; cursor: pointer;">
        </a>
        <a href="logout.php" class="logout-btn" style="position: absolute; right: 20px; top: 50%; transform: translateY(-50%); background-color: #dc3545; color: #ffffff; border: none; padding: 10px 25px; border-radius: 8px; font-size: 14px; font-weight: 600; text-decoration: none; box-shadow: 0 2px 5px rgba(0,0,0,0.2);">Déconnexion</a>
    </div>
    
    <div style="margin-top: 100px;">
        <h1>Gestion des Données</h1>
   
        <div class="button-container">
            <button onclick="loadData('users')">Afficher les Utilisateurs</button>
            <button onclick="loadData('groups')">Afficher les Groupes</button>
            <button onclick="loadData('quizz')">Afficher les Quizz</button>
        </div>
   
        <div id="tableContainer">
            <table id="dataTable">
                <thead>
                    <tr id="headerRow"></tr>
                </thead>
                <tbody id="tableBody">
                </tbody>
            </table>
        </div>
    </div>
   
    <script>
        function loadData(type) {
            const tableContainer = document.getElementById('tableContainer');
            const tableBody = document.getElementById('tableBody');
            const headerRow = document.getElementById('headerRow');
           
            tableBody.innerHTML = '<tr><td class="loading">Chargement...</td></tr>';
            tableContainer.style.display = 'block';
           
            fetch(`?action=${type}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Erreur réseau');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.error) {
                        tableBody.innerHTML = `<tr><td class="error">${data.error}</td></tr>`;
                        return;
                    }
                   
                    if (data.length === 0) {
                        tableBody.innerHTML = '<tr><td>Aucune donnée trouvée</td></tr>';
                        return;
                    }
                   
 
                    const firstRow = data[0];
                    const headers = Object.keys(firstRow);
                    headerRow.innerHTML = '';
                    headers.forEach(header => {
                        const th = document.createElement('th');
                        th.textContent = header;
                        headerRow.appendChild(th);
                    });
                    // Ajout d'une colonne Action si users ou groups
                    if(type === 'users' || type === 'groups') {
                        const th = document.createElement('th');
                        th.textContent = 'Action';
                        headerRow.appendChild(th);
                    }

                    tableBody.innerHTML = '';
                    data.forEach(row => {
                        const tr = document.createElement('tr');
                        headers.forEach(header => {
                            const td = document.createElement('td');
                            td.textContent = row[header] || '-';
                            tr.appendChild(td);
                        });
                        
                        // Ajout du bouton Bannir pour les utilisateurs
                        if(type === 'users') {
                            const td = document.createElement('td');
                            const btn = document.createElement('button');
                            function updateButton(isBanned) {
                                if(isBanned) {
                                    btn.textContent = 'Débannir';
                                    btn.style.backgroundColor = '#5cb85c';
                                } else {
                                    btn.textContent = 'Bannir';
                                    btn.style.backgroundColor = '#d9534f';
                                }
                            }
                            updateButton(row['banned'] == 1);
                            btn.style.color = 'white';
                            btn.style.border = 'none';
                            btn.style.borderRadius = '4px';
                            btn.style.padding = '6px 14px';
                            btn.style.cursor = 'pointer';
                            btn.onclick = function() {
                                const action = (row['banned'] == 1) ? 'unban' : 'ban';
                                fetch(`?action=${action}`, {
                                    method: 'POST',
                                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                                    body: 'id=' + encodeURIComponent(row['id'])
                                })
                                .then(resp => resp.json())
                                .then(resp => {
                                    if(resp.success) {
                                        // Inverse l'état local et met à jour le bouton
                                        row['banned'] = row['banned'] == 1 ? 0 : 1;
                                        updateButton(row['banned'] == 1);
                                    }
                                });
                            };
                            td.appendChild(btn);
                            tr.appendChild(td);
                        }
                        
                        // Ajout du bouton Supprimer pour les groupes
                        if(type === 'groups') {
                            const td = document.createElement('td');
                            const btn = document.createElement('button');
                            btn.textContent = 'Supprimer';
                            btn.style.backgroundColor = '#d9534f';
                            btn.style.color = 'white';
                            btn.style.border = 'none';
                            btn.style.borderRadius = '4px';
                            btn.style.padding = '6px 14px';
                            btn.style.cursor = 'pointer';
                            btn.onclick = function() {
                                if(confirm('Êtes-vous sûr de vouloir supprimer ce groupe ? Cela supprimera également tous les quiz, questions et réponses associés.')) {
                                    fetch('?action=deleteGroup', {
                                        method: 'POST',
                                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                                        body: 'id=' + encodeURIComponent(row['id'])
                                    })
                                    .then(resp => resp.json())
                                    .then(resp => {
                                        if(resp.success) {
                                            alert('Groupe supprimé avec succès');
                                            loadData('groups'); // Recharger la liste
                                        } else {
                                            alert('Erreur lors de la suppression: ' + (resp.error || 'Erreur inconnue'));
                                        }
                                    })
                                    .catch(error => {
                                        alert('Erreur réseau: ' + error.message);
                                    });
                                }
                            };
                            td.appendChild(btn);
                            tr.appendChild(td);
                        }
                        
                        tableBody.appendChild(tr);
                    });
                })
                .catch(error => {
                    tableBody.innerHTML = `<tr><td class="error">Erreur: ${error.message}</td></tr>`;
                    console.error('Erreur:', error);
                });
        }
    </script>
</body>
</html>
