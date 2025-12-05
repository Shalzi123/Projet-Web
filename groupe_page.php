<?php
session_set_cookie_params(14400);
session_start();

if (!isset($_SESSION['username'])) {
    header('Location: index.php');
    exit;
}

if (!isset($_GET['group_id'])) {
    header('Location: groupes.php');
    exit;
}

$groupId = intval($_GET['group_id']);

try {
    $database = new PDO(
        'mysql:host=localhost;dbname=quizzeo;charset=utf8mb4',
        'root',
        ''
    );
} catch (Exception $exception) {
    echo '<div style="color:red;font-weight:bold;margin:10px 0;">Erreur de connexion BDD</div>';
    exit;
}

$stmt = $database->prepare("SELECT COUNT(*) FROM utilisateur_groups WHERE user_id = :user_id AND group_id = :group_id");
$stmt->execute(['user_id' => $_SESSION['id'], 'group_id' => $groupId]);
$isMember = $stmt->fetchColumn() > 0;

if (!$isMember) {
    header('Location: groupes.php');
    exit;
}

$stmt = $database->prepare("SELECT nomgroupe, descriptiongroupe FROM sql_groups WHERE id = :group_id");
$stmt->execute(['group_id' => $groupId]);
$groupe = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$groupe) {
    header('Location: groupes.php');
    exit;
}

$canInvite = isset($_SESSION['role']) && ($_SESSION['role'] === 'entreprise' || $_SESSION['role'] === 'ecole');

$canCreateQuiz = isset($_SESSION['role']) && ($_SESSION['role'] === 'entreprise' || $_SESSION['role'] === 'ecole');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($groupe['nomgroupe']); ?> - Centre QCM & Satisfaction</title>
    <link rel="icon" type="image/vnd.icon" href="images/quiz_logo.ico">
    <link rel="stylesheet" href="style.css">
    <style>
        html, body {
            overflow-y: auto !important;
            height: auto !important;
            scroll-behavior: smooth;
        }
        
        .quiz-container {
            max-width: 1200px;
            margin: 90px auto 20px;
            padding: 20px;
            position: relative;
        }
        
        .quiz-header-section {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .quiz-header-section h1 {
            color: #333;
            margin-bottom: 10px;
        }
        
        .quiz-header-section .subtitle {
            color: #666;
            font-size: 1.1em;
        }
        
        .btn-create {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-size: 1em;
            cursor: pointer;
            margin-top: 15px;
            transition: transform 0.2s;
        }
        
        .btn-create:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        
        .quiz-creator {
            background: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            margin-top: 20px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .quizz-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .quizz-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .quizz-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        }
        
        .quizz-icon {
            font-size: 3em;
            text-align: center;
            margin-bottom: 15px;
        }
        
        .quizz-card h3 {
            color: #333;
            margin-bottom: 10px;
            font-size: 1.5em;
        }
        
        .quizz-card p {
            color: #666;
            margin-bottom: 15px;
        }
        
        .quizz-info {
            color: #888;
            font-size: 0.9em;
            margin-bottom: 15px;
        }
        
        .card-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn.primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            flex: 1;
        }
        
        .btn-danger {
            background: #f44336;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
        }
        
        .btn-link {
            display: inline-block;
            margin-top: 20px;
            color: #667eea;
            text-decoration: none;
            font-size: 1.1em;
        }
        
        .footer {
            text-align: center;
            margin-top: 40px;
        }
        
        .quiz-player {
            background: white;
            border-radius: 12px;
            padding: 30px;
            max-width: 800px;
            margin: 0 auto 40px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
            position: relative;
        }
        
        .quiz-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .quiz-header h2 {
            color: #333;
            font-size: 2em;
            margin-bottom: 10px;
        }
        
        .quiz-progress {
            margin-top: 20px;
        }
        
        .quiz-progress span {
            display: block;
            margin-bottom: 10px;
            color: #666;
            font-weight: 500;
        }
        
        .progress-bar {
            width: 100%;
            height: 8px;
            background: #e0e0e0;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            transition: width 0.3s ease;
        }
        
        .question-card {
            background: #f9f9f9;
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 20px;
        }
        
        .question-card h3 {
            color: #667eea;
            margin-bottom: 15px;
            font-size: 1.1em;
        }
        
        .question-text {
            color: #333;
            font-size: 1.3em;
            font-weight: 500;
            margin-bottom: 20px;
        }
        
        .options-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        .quiz-option {
            display: flex;
            align-items: center;
            padding: 15px;
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .quiz-option:hover {
            border-color: #667eea;
            background: #f0f4ff;
        }
        
        .quiz-option input[type="radio"],
        .quiz-option input[type="checkbox"] {
            margin-right: 12px;
            width: 20px;
            height: 20px;
            cursor: pointer;
        }
        
        .quiz-option span {
            flex: 1;
            color: #333;
            font-size: 1.05em;
        }
        
        .quiz-navigation {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            margin-top: 20px;
        }
        
        .quiz-navigation button {
            flex: 1;
            padding: 12px 24px;
        }
        
        .quiz-results {
            background: white;
            border-radius: 12px;
            padding: 30px;
            max-width: 900px;
            margin: 0 auto;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
        }
        
        .quiz-results h2 {
            text-align: center;
            color: #333;
            font-size: 2em;
            margin-bottom: 30px;
        }
        
        .score-display {
            text-align: center;
            margin: 40px 0;
        }
        
        .score-circle {
            width: 180px;
            height: 180px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            box-shadow: 0 8px 24px rgba(102, 126, 234, 0.4);
        }
        
        .score-number {
            color: white;
            font-size: 3em;
            font-weight: bold;
        }
        
        .score-text {
            color: #666;
            font-size: 1.2em;
        }
        
        .results-details {
            margin-top: 40px;
        }
        
        .results-details h3 {
            color: #333;
            margin-bottom: 20px;
            font-size: 1.5em;
        }
        
        .result-item {
            background: #f9f9f9;
            border-left: 4px solid #ddd;
            padding: 20px;
            margin-bottom: 15px;
            border-radius: 8px;
        }
        
        .result-item.correct {
            border-left-color: #4caf50;
            background: #f1f8f4;
        }
        
        .result-item.incorrect {
            border-left-color: #f44336;
            background: #fef1f0;
        }
        
        .result-item h4 {
            margin-bottom: 10px;
            color: #333;
        }
        
        .result-item.correct h4 {
            color: #4caf50;
        }
        
        .result-item.incorrect h4 {
            color: #f44336;
        }
        
        .result-item p {
            margin: 8px 0;
            color: #666;
        }
        
        .results-actions {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 30px;
        }
        
        .results-actions button {
            padding: 12px 30px;
            background-color: #6c757d;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.95rem;
            transition: background 0.2s, box-shadow 0.2s;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }
        
        .results-actions button:hover {
            background-color: #5a6268;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
        }
        
        .results-actions button.primary {
            background-color: #0056ab;
            color: white;
        }
        
        .results-actions button.primary:hover {
            background-color: #003e7a;
        }
        
        .question-block {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .question-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }
        
        .form-group input[type="text"],
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1em;
        }
        
        .option-input {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }
        
        .option-input input[type="text"] {
            flex: 1;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 6px;
        }
        
        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 5px;
            white-space: nowrap;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9em;
        }
        
        .btn-small {
            padding: 6px 12px;
            font-size: 0.85em;
        }
        
        .form-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 20px;
        }
    </style>
</head>
<body data-user-role="<?php echo isset($_SESSION['role']) ? $_SESSION['role'] : 'utilisateur'; ?>">
    <div class="header">
        <a href="<?php echo isset($_SESSION['username']) ? 'groupes.php' : 'index.php'; ?>" style="display: inline-block; text-decoration: none;">
            <img src="images/quizzeo_logo.png" alt="Logo Quizzeo" class="logo" style="max-width: 350px; cursor: pointer;">
        </a>
        <a href="logout.php" class="logout-btn" style="position: absolute; right: 20px; top: 50%; transform: translateY(-50%); background-color: #dc3545; color: #ffffff; border: none; padding: 10px 25px; border-radius: 8px; font-size: 14px; font-weight: 600; text-decoration: none; box-shadow: 0 2px 5px rgba(0,0,0,0.2);">Déconnexion</a>
    </div>

    <div class="quiz-container">
        <div class="quiz-header-section">
            <h1>🎯 <?php echo htmlspecialchars($groupe['nomgroupe']); ?></h1>
            <p class="subtitle"><?php echo htmlspecialchars($groupe['descriptiongroupe']); ?></p>
            <div style="display: flex; gap: 10px; justify-content: center; margin-top: 15px; flex-wrap: wrap;">
                <?php if ($canCreateQuiz): ?>
                <button id="toggle-creator" class="btn-create">+ Créer un nouveau quizz</button>
                <?php endif; ?>
                <?php if ($canInvite): ?>
                <button id="generate-invite" class="btn-create" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%);">🔗 Inviter des membres</button>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Modal pour le lien d'invitation -->
        <div id="invite-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
            <div style="background: white; padding: 30px; border-radius: 12px; max-width: 500px; width: 90%; box-shadow: 0 8px 24px rgba(0,0,0,0.2);">
                <h2 style="margin-bottom: 20px; color: #333;">Lien d'invitation</h2>
                <p style="color: #666; margin-bottom: 15px;">Partagez ce lien pour inviter des personnes à rejoindre ce groupe:</p>
                <div style="background: #f5f5f5; padding: 15px; border-radius: 8px; margin-bottom: 15px; word-break: break-all;">
                    <input type="text" id="invite-url-display" readonly style="width: 100%; border: none; background: transparent; font-family: monospace; font-size: 0.9em; color: #333;">
                </div>
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button onclick="copyInviteLink()" style="background: #28a745; color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer;">📋 Copier le lien</button>
                    <button onclick="closeInviteModal()" style="background: #6c757d; color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer;">Fermer</button>
                </div>
            </div>
        </div>

        <!-- Section Créateur de Quizz -->
        <?php if ($canCreateQuiz): ?>
        <section id="quiz-creator" class="quiz-creator" hidden>
                    <h2>Créer un nouveau quizz</h2>
                    <form id="quiz-form">
                        <div class="form-group">
                            <label for="quiz-title">Titre du quizz</label>
                            <input type="text" id="quiz-title" placeholder="ex: Géographie Avancée" required>
                        </div>

                        <div class="form-group">
                            <label for="quiz-description">Description</label>
                            <input type="text" id="quiz-description" placeholder="ex: Testez vos connaissances..." required>
                        </div>

                        <div class="form-group">
                            <label for="quiz-icon">Emoji (icône)</label>
                            <input type="text" id="quiz-icon" placeholder="ex: 🌍" maxlength="2" value="📝" required>
                        </div>

                        <div id="questions-container"></div>

                        <button type="button" id="add-question" class="btn btn-secondary">+ Ajouter une question</button>
                        <div class="form-actions">
                            <button type="submit" class="btn primary">Créer le quizz</button>
                            <button type="button" id="cancel-creator" class="btn">Annuler</button>
                        </div>
                    </form>
                </section>
        <?php endif; ?>

        <div id="quiz-list-section">
            <div class="quizz-grid" id="quizz-container">
            </div>
        </div>

        <div id="quiz-player-section" style="display: none;">
        </div>

        <div class="footer">
            <a href="./groupes.php" class="btn-link">← Retour aux groupes</a>
        </div>
    </div>

    <div id="satisfaction" style="display: none;">
        <div class="quiz-container">
            <div class="quiz-header-section">
                <h1>📊 Indice de Satisfaction</h1>
                <p class="subtitle">Gérez et analysez la satisfaction de vos clients</p>
            </div>

                <nav class="nav-tabs">
                    <button class="tab-btn active" data-tab="dashboard">Tableau de bord</button>
                    <button class="tab-btn" data-tab="create-survey">Créer un questionnaire</button>
                    <button class="tab-btn" data-tab="surveys">Mes questionnaires</button>
                    <button class="tab-btn" data-tab="results">Résultats</button>
                </nav>

                <section id="dashboard" class="tab-content active">
                    <div class="dashboard-grid">
                        <div class="stat-card">
                            <h3>Score Global</h3>
                            <div class="stat-value" id="globalScore">0%</div>
                            <p class="stat-label">Moyenne générale</p>
                        </div>
                        <div class="stat-card">
                            <h3>Questionnaires</h3>
                            <div class="stat-value" id="surveyCount">0</div>
                            <p class="stat-label">Actifs</p>
                        </div>
                        <div class="stat-card">
                            <h3>Réponses</h3>
                            <div class="stat-value" id="responseCount">0</div>
                            <p class="stat-label">Total</p>
                        </div>
                        <div class="stat-card">
                            <h3>Taux de satisfaction</h3>
                            <div class="stat-value" id="satisfactionRate">0%</div>
                            <p class="stat-label">Clients satisfaits (≥4/5)</p>
                        </div>
                    </div>
                </section>

                <section id="create-survey" class="tab-content">
                    <div class="form-section">
                        <h2>Créer un nouveau questionnaire</h2>
                        <form id="surveyForm" class="survey-form">
                            <div class="form-group">
                                <label for="surveyTitle">Titre du questionnaire</label>
                                <input type="text" id="surveyTitle" placeholder="Ex: Satisfaction juin 2024" required>
                            </div>

                            <div class="form-group">
                                <label for="surveyDescription">Description</label>
                                <textarea id="surveyDescription" placeholder="Décrivez l'objectif du questionnaire" rows="3"></textarea>
                            </div>

                            <div class="questions-section">
                                <h3>Questions</h3>
                                <div id="questionsList"></div>
                                <button type="button" class="btn-secondary" id="addQuestionBtn">+ Ajouter une question</button>
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="btn-primary">Créer le questionnaire</button>
                                <button type="reset" class="btn-secondary">Réinitialiser</button>
                            </div>
                        </form>
                    </div>
                </section>

                <section id="surveys" class="tab-content">
                    <h2>Mes questionnaires</h2>
                    <div id="surveysList" class="surveys-list">
                        <p class="empty-state">Aucun questionnaire créé. <a href="#" onclick="switchTab('create-survey')">Créez-en un</a></p>
                    </div>
                </section>

                <section id="results" class="tab-content">
                    <h2>Résultats et Analyses</h2>
                    <div id="resultsList" class="results-list">
                        <p class="empty-state">Aucun résultat disponible pour le moment.</p>
                    </div>
                </section>
            </div>

            <div id="responseModal" class="modal">
                <div class="modal-content">
                    <span class="close">&times;</span>
                    <h2 id="modalTitle"></h2>
                    <p id="modalDescription"></p>
                    <form id="responseForm" class="response-form">
                        <div id="questionsResponse"></div>
                        <div class="form-actions">
                            <button type="submit" class="btn-primary">Soumettre les réponses</button>
                            <button type="button" class="btn-secondary" onclick="closeResponseModal()">Annuler</button>
                        </div>
                    </form>
                </div>
            </div>

            <div id="detailsModal" class="modal">
                <div class="modal-content modal-lg">
                    <span class="close">&times;</span>
                    <h2 id="detailsTitle"></h2>
                    <div class="details-tabs">
                        <button class="details-tab-btn active" data-details-tab="info">Informations</button>
                        <button class="details-tab-btn" data-details-tab="responses">Réponses</button>
                    </div>
                    <div id="info" class="details-tab-content active">
                        <div id="surveyDetails"></div>
                    </div>
                    <div id="responses" class="details-tab-content">
                        <div id="responsesList"></div>
                    </div>
                    <div class="form-actions" style="margin-top: 20px;">
                        <button type="button" class="btn-primary" id="respondBtn">Répondre au questionnaire</button>
                        <button type="button" class="btn-danger" id="deleteBtn">Supprimer</button>
                        <button type="button" class="btn-secondary" onclick="closeDetailsModal()">Fermer</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const CURRENT_GROUP_ID = <?php echo $groupId; ?>;
        
        document.addEventListener('DOMContentLoaded', function() {
            const generateBtn = document.getElementById('generate-invite');
            if (generateBtn) {
                generateBtn.addEventListener('click', async function() {
                    try {
                        const formData = new FormData();
                        formData.append('action', 'generate');
                        formData.append('group_id', CURRENT_GROUP_ID);
                        
                        const response = await fetch('invite_link.php', {
                            method: 'POST',
                            body: formData
                        });
                        
                        const data = await response.json();
                        
                        if (data.success) {
                            document.getElementById('invite-url-display').value = data.invite_url;
                            document.getElementById('invite-modal').style.display = 'flex';
                        } else {
                            alert('Erreur: ' + data.error);
                        }
                    } catch (error) {
                        console.error('Erreur:', error);
                        alert('Erreur lors de la génération du lien');
                    }
                });
            }
        });
        
        function closeInviteModal() {
            document.getElementById('invite-modal').style.display = 'none';
        }
        
        function copyInviteLink() {
            const input = document.getElementById('invite-url-display');
            input.select();
            input.setSelectionRange(0, 99999);
            
            try {
                document.execCommand('copy');
                alert('Lien copié dans le presse-papier !');
            } catch (err) {
                navigator.clipboard.writeText(input.value).then(function() {
                    alert('Lien copié dans le presse-papier !');
                }).catch(function(err) {
                    alert('Impossible de copier le lien. Veuillez le copier manuellement.');
                });
            }
        }
        
        document.addEventListener('click', function(event) {
            const modal = document.getElementById('invite-modal');
            if (event.target === modal) {
                closeInviteModal();
            }
        });
    </script>
    <script>
        const USER_ROLE = '<?php echo isset($_SESSION['role']) ? htmlspecialchars($_SESSION['role']) : 'utilisateur'; ?>';
        console.log('User Role:', USER_ROLE);
    </script>
    <script src="script-menu.js" defer></script>
    <script src="script-satisfaction.js" defer></script>
    <script src="script-navigation.js" defer></script>
</body>
</html>
