
let quizzes = [];
fetch('get_quizzes.php')
    .then(response => response.json())
    .then(data => {
        quizzes = data;
        // Appelle ici ta fonction d'affichage ou d'initialisation des quiz
        // ex: displayQuizzes(quizzes);
    });

// Charger les quizz depuis localStorage
function loadQuizzesFromStorage() {
    const stored = localStorage.getItem('customQuizzes');
    if (stored) {
        const customQuizzes = JSON.parse(stored);
        quizzes = [...quizzes, ...customQuizzes];
    }
}

// Sauvegarder les quizz custom dans localStorage
function saveQuizzesToStorage() {
    const customQuizzes = quizzes.filter(q => q.id > 6);
    localStorage.setItem('customQuizzes', JSON.stringify(customQuizzes));
}

// Génération des cartes de quizz
function renderQuizzes() {
    const container = document.getElementById('quizz-container');
    container.innerHTML = '';

    quizzes.forEach(quiz => {
        const card = document.createElement('div');
        card.className = 'quizz-card';
        const isCustom = quiz.id > 6;
        
        card.innerHTML = `
            <div class="quizz-icon">${quiz.icon}</div>
            <h3>${quiz.title}</h3>
            <p>${quiz.description}</p>
            <div class="quizz-info">
                <span>${quiz.questions.length} questions</span>
                ${isCustom ? '<span class="custom-badge">Personnalisé</span>' : ''}
            </div>
            <div class="card-actions">
                <button class="btn primary" onclick="startQuiz(${quiz.id})">Commencer</button>
                ${isCustom ? `<button class="btn btn-danger" onclick="deleteQuiz(${quiz.id})">Supprimer</button>` : ''}
            </div>
        `;
        container.appendChild(card);
    });
}

// Démarrer un quizz
function startQuiz(quizId) {
    const quiz = quizzes.find(q => q.id === quizId);
    if (!quiz) return;

    // Stocker les données du quizz dans sessionStorage
    sessionStorage.setItem('currentQuiz', JSON.stringify({
        title: quiz.title,
        questions: quiz.questions
    }));

    // Rediriger vers la page du quizz
    window.location.href = `../basequizz/index.html?quiz=${quizId}`;
}

// Supprimer un quizz
function deleteQuiz(quizId) {
    if (confirm('Êtes-vous sûr de vouloir supprimer ce quizz ?')) {
        quizzes = quizzes.filter(q => q.id !== quizId);
        saveQuizzesToStorage();
        renderQuizzes();
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
function createQuiz(event) {
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

    const newQuiz = {
        id: Math.max(...quizzes.map(q => q.id)) + 1,
        title,
        description,
        icon,
        questions
    };

    quizzes.push(newQuiz);
    saveQuizzesToStorage();

    // Réinitialiser le formulaire
    document.getElementById('quiz-form').reset();
    document.getElementById('questions-container').innerHTML = '';
    document.getElementById('quiz-creator').hidden = true;

    renderQuizzes();
    alert(`Quizz "${title}" créé avec succès !`);
}

// Initialisation menu
function initMenu() {
    loadQuizzesFromStorage();
    renderQuizzes();

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
