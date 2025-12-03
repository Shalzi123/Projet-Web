
let quizzes = [];

// Charger les quizz depuis la base de données
async function loadQuizzesFromDatabase() {
    try {
        // Récupérer l'ID du groupe actuel depuis la page PHP
        const groupId = typeof CURRENT_GROUP_ID !== 'undefined' ? CURRENT_GROUP_ID : 0;
        const response = await fetch(`quiz_api.php?action=getAll&group_id=${groupId}`);
        const data = await response.json();
        
        if (data.success) {
            // Transformer les données de la BD au format attendu
            quizzes = data.quizzes.map(quiz => ({
                id: quiz.id,
                title: quiz.nom,
                description: quiz.description,
                icon: quiz.theme,
                questions: quiz.questions.map(q => ({
                    id: q.id,
                    type: q.type,
                    text: q.question,
                    options: q.options,
                    answer: q.reponse
                }))
            }));
            renderQuizzes();
        } else {
            console.error('Erreur lors du chargement des quizzes:', data.error);
        }
    } catch (error) {
        console.error('Erreur réseau:', error);
    }
}

// Génération des cartes de quizz
function renderQuizzes() {
    const container = document.getElementById('quizz-container');
    container.innerHTML = '';

    quizzes.forEach(quiz => {
        const card = document.createElement('div');
        card.className = 'quizz-card';
        
        card.innerHTML = `
            <div class="quizz-icon">${quiz.icon}</div>
            <h3>${quiz.title}</h3>
            <p>${quiz.description}</p>
            <div class="quizz-info">
                <span>${quiz.questions.length} questions</span>
            </div>
            <div class="card-actions">
                <button class="btn primary" onclick="startQuiz(${quiz.id})">Commencer</button>
                <button class="btn btn-danger" onclick="deleteQuiz(${quiz.id})">Supprimer</button>
            </div>
        `;
        container.appendChild(card);
    });
}

// Démarrer un quizz
function startQuiz(quizId) {
    const quiz = quizzes.find(q => q.id === quizId);
    if (!quiz) return;

    // Cacher le menu principal et afficher le quiz
    document.getElementById('quiz-list-section').style.display = 'none';
    document.getElementById('quiz-player-section').style.display = 'block';
    
    // Initialiser le lecteur de quiz
    displayQuiz(quiz);
}

// Afficher le quiz
let currentQuiz = null;
let currentQuestionIndex = 0;
let userAnswers = [];

function displayQuiz(quiz) {
    currentQuiz = quiz;
    currentQuestionIndex = 0;
    userAnswers = new Array(quiz.questions.length).fill(null);
    
    const playerSection = document.getElementById('quiz-player-section');
    playerSection.innerHTML = `
        <div class="quiz-player">
            <header class="quiz-header">
                <h2>${quiz.icon} ${quiz.title}</h2>
                <p>${quiz.description}</p>
                <div class="quiz-progress">
                    <span>Question <span id="current-q">1</span> / ${quiz.questions.length}</span>
                    <div class="progress-bar">
                        <div id="progress-fill" class="progress-fill" style="width: ${(1/quiz.questions.length)*100}%"></div>
                    </div>
                </div>
            </header>
            
            <div id="question-content" class="question-content"></div>
            
            <div class="quiz-navigation">
                <button id="prev-btn" class="btn" onclick="previousQuestion()" disabled>← Précédent</button>
                <button id="next-btn" class="btn primary" onclick="nextQuestion()">Suivant →</button>
                <button id="submit-btn" class="btn primary" onclick="submitQuiz()" style="display: none;">Terminer le quiz</button>
            </div>
            
            <button class="btn btn-secondary" onclick="exitQuiz()" style="margin-top: 20px;">Quitter le quiz</button>
        </div>
    `;
    
    displayQuestion(0);
}

function displayQuestion(index) {
    const question = currentQuiz.questions[index];
    const questionContent = document.getElementById('question-content');
    
    let optionsHTML = '';
    question.options.forEach((option, i) => {
        const inputType = question.type === 'single' ? 'radio' : 'checkbox';
        const checked = userAnswers[index] && userAnswers[index].includes(i) ? 'checked' : '';
        optionsHTML += `
            <label class="quiz-option">
                <input type="${inputType}" name="answer" value="${i}" ${checked} onchange="saveAnswer(${i})">
                <span>${option}</span>
            </label>
        `;
    });
    
    questionContent.innerHTML = `
        <div class="question-card">
            <h3>Question ${index + 1}</h3>
            <p class="question-text">${question.text}</p>
            <div class="options-list">
                ${optionsHTML}
            </div>
        </div>
    `;
    
    // Mettre à jour les boutons de navigation
    document.getElementById('prev-btn').disabled = index === 0;
    document.getElementById('current-q').textContent = index + 1;
    document.getElementById('progress-fill').style.width = `${((index + 1) / currentQuiz.questions.length) * 100}%`;
    
    // Afficher le bouton terminer sur la dernière question
    if (index === currentQuiz.questions.length - 1) {
        document.getElementById('next-btn').style.display = 'none';
        document.getElementById('submit-btn').style.display = 'inline-block';
    } else {
        document.getElementById('next-btn').style.display = 'inline-block';
        document.getElementById('submit-btn').style.display = 'none';
    }
}

function saveAnswer(optionIndex) {
    const question = currentQuiz.questions[currentQuestionIndex];
    
    if (question.type === 'single') {
        userAnswers[currentQuestionIndex] = [optionIndex];
    } else {
        if (!userAnswers[currentQuestionIndex]) {
            userAnswers[currentQuestionIndex] = [];
        }
        const answers = userAnswers[currentQuestionIndex];
        const idx = answers.indexOf(optionIndex);
        if (idx > -1) {
            answers.splice(idx, 1);
        } else {
            answers.push(optionIndex);
        }
    }
}

function nextQuestion() {
    if (currentQuestionIndex < currentQuiz.questions.length - 1) {
        currentQuestionIndex++;
        displayQuestion(currentQuestionIndex);
    }
}

function previousQuestion() {
    if (currentQuestionIndex > 0) {
        currentQuestionIndex--;
        displayQuestion(currentQuestionIndex);
    }
}

function submitQuiz() {
    // Calculer le score
    let correctAnswers = 0;
    currentQuiz.questions.forEach((question, index) => {
        const userAnswer = userAnswers[index];
        const correctAnswer = question.answer;
        
        if (userAnswer && JSON.stringify(userAnswer.sort()) === JSON.stringify(correctAnswer.sort())) {
            correctAnswers++;
        }
    });
    
    const score = (correctAnswers / currentQuiz.questions.length) * 100;
    
    // Afficher les résultats
    const playerSection = document.getElementById('quiz-player-section');
    playerSection.innerHTML = `
        <div class="quiz-results">
            <h2>🎉 Quiz terminé !</h2>
            <div class="score-display">
                <div class="score-circle">
                    <span class="score-number">${Math.round(score)}%</span>
                </div>
                <p class="score-text">${correctAnswers} / ${currentQuiz.questions.length} réponses correctes</p>
            </div>
            
            <div class="results-details">
                <h3>Détails des réponses</h3>
                ${currentQuiz.questions.map((q, i) => {
                    const userAnswer = userAnswers[i] || [];
                    const isCorrect = JSON.stringify(userAnswer.sort()) === JSON.stringify(q.answer.sort());
                    return `
                        <div class="result-item ${isCorrect ? 'correct' : 'incorrect'}">
                            <h4>${isCorrect ? '✓' : '✗'} Question ${i + 1}</h4>
                            <p>${q.text}</p>
                            <p><strong>Votre réponse:</strong> ${userAnswer.map(idx => q.options[idx]).join(', ') || 'Aucune réponse'}</p>
                            <p><strong>Réponse correcte:</strong> ${q.answer.map(idx => q.options[idx]).join(', ')}</p>
                        </div>
                    `;
                }).join('')}
            </div>
            
            <div class="results-actions">
                <button class="btn primary" onclick="startQuiz(${currentQuiz.id})">Recommencer</button>
                <button class="btn" onclick="exitQuiz()">Retour aux quiz</button>
            </div>
        </div>
    `;
}

function exitQuiz() {
    document.getElementById('quiz-player-section').style.display = 'none';
    document.getElementById('quiz-list-section').style.display = 'block';
    currentQuiz = null;
    currentQuestionIndex = 0;
    userAnswers = [];
}

// Supprimer un quizz
async function deleteQuiz(quizId) {
    if (confirm('Êtes-vous sûr de vouloir supprimer ce quizz ?')) {
        try {
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('quiz_id', quizId);
            
            const response = await fetch('quiz_api.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                quizzes = quizzes.filter(q => q.id !== quizId);
                renderQuizzes();
                alert('Quizz supprimé avec succès !');
            } else {
                alert('Erreur lors de la suppression: ' + data.error);
            }
        } catch (error) {
            console.error('Erreur réseau:', error);
            alert('Erreur de connexion');
        }
    }
}

// Afficher/masquer le créateur de quizz
function toggleCreator() {
    const creator = document.getElementById('quiz-creator');
    creator.hidden = !creator.hidden;
    if (!creator.hidden) {
        document.getElementById('questions-container').innerHTML = '';
        addQuestion();
    }
}

// Ajouter une question au formulaire
function addQuestion(questionData = null) {
    const container = document.getElementById('questions-container');
    const questionIndex = container.children.length;
    const questionId = questionIndex + 1;

    const questionBlock = document.createElement('div');
    questionBlock.className = 'question-block';
    questionBlock.id = `question-${questionId}`;

    const questionText = questionData?.text || '';
    const questionType = questionData?.type || 'single';
    const questionOptions = questionData?.options || ['', '', ''];
    const questionAnswer = questionData?.answer || [];

    let optionsHTML = questionOptions.map((opt, idx) => `
        <div class="option-input">
            <input type="text" placeholder="Option ${idx + 1}" value="${opt}" class="option-text" data-index="${idx}">
            <label class="checkbox-label">
                <input type="checkbox" class="answer-checkbox" data-index="${idx}" ${questionAnswer.includes(idx) ? 'checked' : ''}>
                Correcte
            </label>
        </div>
    `).join('');

    questionBlock.innerHTML = `
        <div class="question-header">
            <h4>Question ${questionId}</h4>
            <button type="button" class="btn btn-small btn-danger" onclick="removeQuestion('question-${questionId}')">Supprimer</button>
        </div>

        <div class="form-group">
            <label>Texte de la question</label>
            <input type="text" class="question-text" placeholder="Entrez le texte de la question" value="${questionText}" required>
        </div>

        <div class="form-group">
            <label>Type de question</label>
            <select class="question-type" value="${questionType}">
                <option value="single">Choix unique</option>
                <option value="multiple">Choix multiple</option>
            </select>
        </div>

        <div class="form-group">
            <label>Options</label>
            <div id="options-${questionId}" class="options-group">
                ${optionsHTML}
            </div>
            <button type="button" class="btn btn-small" onclick="addOption('options-${questionId}')">+ Ajouter option</button>
        </div>
    `;

    container.appendChild(questionBlock);

    // Update question type when changed
    questionBlock.querySelector('.question-type').addEventListener('change', (e) => {
        updateQuestionType(questionId, e.target.value);
    });
}

// Ajouter une option à une question
function addOption(containerId) {
    const container = document.getElementById(containerId);
    const optionCount = container.querySelectorAll('.option-input').length;
    const optionDiv = document.createElement('div');
    optionDiv.className = 'option-input';
    optionDiv.innerHTML = `
        <input type="text" placeholder="Option ${optionCount + 1}" class="option-text" data-index="${optionCount}">
        <label class="checkbox-label">
            <input type="checkbox" class="answer-checkbox" data-index="${optionCount}">
            Correcte
        </label>
    `;
    container.appendChild(optionDiv);
}

// Supprimer une question
function removeQuestion(questionId) {
    const element = document.getElementById(questionId);
    if (element) {
        element.remove();
        if (document.getElementById('questions-container').children.length === 0) {
            addQuestion();
        }
    }
}

// Mettre à jour le type de question
function updateQuestionType(questionId, type) {
    const questionBlock = document.getElementById(`question-${questionId}`);
    const checkboxes = questionBlock.querySelectorAll('.answer-checkbox');
    
    if (type === 'single') {
        checkboxes.forEach(cb => {
            cb.type = 'radio';
            cb.name = `answer-${questionId}`;
        });
    } else {
        checkboxes.forEach(cb => {
            cb.type = 'checkbox';
            cb.removeAttribute('name');
        });
    }
}

// Créer le quizz
async function createQuiz(event) {
    event.preventDefault();

    const title = document.getElementById('quiz-title').value;
    const description = document.getElementById('quiz-description').value;
    const icon = document.getElementById('quiz-icon').value;

    const questionElements = document.querySelectorAll('.question-block');
    const questions = [];

    questionElements.forEach((qEl, idx) => {
        const text = qEl.querySelector('.question-text').value;
        const type = qEl.querySelector('.question-type').value;
        const optionsInputs = qEl.querySelectorAll('.option-text');
        const answers = qEl.querySelectorAll('.answer-checkbox:checked');

        const options = Array.from(optionsInputs).map(input => input.value);
        const answer = Array.from(answers).map(checkbox => parseInt(checkbox.getAttribute('data-index')));

        if (!text || options.some(opt => !opt) || answer.length === 0) {
            alert('Veuillez remplir toutes les options et cocher au moins une réponse correcte pour chaque question.');
            return;
        }

        questions.push({
            id: idx + 1,
            type,
            text,
            options,
            answer
        });
    });

    if (questions.length === 0) return;

    // Récupérer l'ID du groupe actuel
    const groupId = typeof CURRENT_GROUP_ID !== 'undefined' ? CURRENT_GROUP_ID : 0;

    const newQuiz = {
        nom: title,
        description: description,
        theme: icon,
        icon: icon,
        questions: questions,
        group_id: groupId
    };

    // Envoyer à la base de données
    try {
        const response = await fetch('quiz_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'create',
                ...newQuiz
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Réinitialiser le formulaire
            document.getElementById('quiz-form').reset();
            document.getElementById('questions-container').innerHTML = '';
            document.getElementById('quiz-creator').hidden = true;
            
            // Recharger les quizzes depuis la BD
            await loadQuizzesFromDatabase();
            
            alert(`Quizz "${title}" créé avec succès !`);
        } else {
            alert('Erreur lors de la création: ' + data.error);
        }
    } catch (error) {
        console.error('Erreur réseau:', error);
        alert('Erreur de connexion');
    }
}

// Initialisation menu
function initMenu() {
    loadQuizzesFromDatabase();

    const toggleCreatorBtn = document.getElementById('toggle-creator');
    if (toggleCreatorBtn) {
        toggleCreatorBtn.addEventListener('click', toggleCreator);
    }
    
    const cancelCreatorBtn = document.getElementById('cancel-creator');
    if (cancelCreatorBtn) {
        cancelCreatorBtn.addEventListener('click', toggleCreator);
    }
    
    const quizForm = document.getElementById('quiz-form');
    if (quizForm) {
        quizForm.addEventListener('submit', createQuiz);
    }
    
    const addQuestionBtn = document.getElementById('add-question');
    if (addQuestionBtn) {
        addQuestionBtn.addEventListener('click', () => addQuestion());
    }
}

// Appeler l'initialisation quand le DOM est prêt
document.addEventListener('DOMContentLoaded', initMenu);

// Capturer les promesses non gérées et erreurs globales pour debug
window.addEventListener('unhandledrejection', function(event) {
    console.error('Unhandled promise rejection:', event.reason, event);
    try {
        // afficher une alerte légère pour l'utilisateur
        // (en production on pourrait envoyer ces infos au serveur)
        // évitez d'appeler alert trop souvent
        console.warn('Une erreur asynchrone est survenue. Voir console pour détails.');
    } catch (e) {}
});

window.addEventListener('error', function(event) {
    console.error('Unhandled error:', event.message, 'at', event.filename + ':' + event.lineno + ':' + event.colno, event.error);
});
