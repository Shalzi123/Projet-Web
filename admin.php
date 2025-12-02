<?php
 
try {
    $dbh = new PDO(
        'mysql:host=localhost;dbname=quizzeo;charset=utf8',
        'root',
        ''
    );
} catch (PDOException $e){
    die($e->getMessage());
}
 
 
if($_GET['action'] ?? false) {
    header('Content-Type: application/json');
    $action = $_GET['action'];

    if($action == 'users'){
        $sth = $dbh->prepare("SELECT * FROM sql_utilisateur");
        $sth->execute();
        $data = $sth->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($data);
        exit;
    }
    elseif($action == 'groups'){
        $sth = $dbh->prepare("SELECT * FROM sql_groups");
        $sth->execute();
        $data = $sth->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($data);
        exit;
    }
    elseif($action == 'quizz'){
        $sth = $dbh->prepare("SELECT * FROM sql_quizz");
        $sth->execute();
        $data = $sth->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($data);
        exit;
    }
    elseif($action == 'ban' && isset($_POST['id'])) {
        $sth = $dbh->prepare("UPDATE sql_utilisateur SET banned = 1 WHERE id = :id");
        $sth->execute(['id' => $_POST['id']]);
        echo json_encode(['success' => true]);
        exit;
    }
    elseif($action == 'unban' && isset($_POST['id'])) {
        $sth = $dbh->prepare("UPDATE sql_utilisateur SET banned = 0 WHERE id = :id");
        $sth->execute(['id' => $_POST['id']]);
        echo json_encode(['success' => true]);
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
                    // Ajout d'une colonne Bannir si users
                    if(type === 'users') {
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
                        // Ajout du bouton Bannir
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
 