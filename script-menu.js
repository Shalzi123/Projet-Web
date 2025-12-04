let quizzes = [];

async function loadQuizzesFromDatabase() {
    try {
        const groupId = typeof CURRENT_GROUP_ID !== 'undefined' ? CURRENT_GROUP_ID : 0;
        const response = await fetch(`quiz_api.php?action=getAll&group_id=${groupId}`);
        const data = await response.json();
        
        if (data.success) {
            quizzes = data.quizzes.map(quiz => ({
                id: quiz.id,
                title: quiz.nom,
                description: quiz.description,
                icon: quiz.theme,
                questions: quiz.questions.map(question => ({
                    id: question.id,
                    type: question.type,
                    text: question.question,
                    options: question.options,
                    answer: question.reponse
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

function renderQuizzes() {
    const container = document.getElementById('quizz-container');
    container.textContent = '';

    quizzes.forEach(quiz => {
        const card = document.createElement('div');
        card.className = 'quizz-card';
        
        const iconDiv = document.createElement('div');
        iconDiv.className = 'quizz-icon';
        iconDiv.textContent = quiz.icon;
        
        const title = document.createElement('h3');
        title.textContent = quiz.title;
        
        const description = document.createElement('p');
        description.textContent = quiz.description;
        
        const info = document.createElement('div');
        info.className = 'quizz-info';
        const questionsCount = document.createElement('span');
        questionsCount.textContent = `${quiz.questions.length} questions`;
        info.appendChild(questionsCount);
        
        const actions = document.createElement('div');
        actions.className = 'card-actions';
        
        const startBtn = document.createElement('button');
        startBtn.className = 'btn primary';
        startBtn.textContent = 'Commencer';
        startBtn.onclick = () => startQuiz(quiz.id);
        
        const resultsBtn = document.createElement('button');
        resultsBtn.className = 'btn btn-secondary';
        resultsBtn.textContent = '📊 Résultats';
        resultsBtn.id = `view-results-${quiz.id}`;
        resultsBtn.style.display = 'none';
        resultsBtn.onclick = () => viewResults(quiz.id);
        
        const deleteBtn = document.createElement('button');
        deleteBtn.className = 'btn btn-danger';
        deleteBtn.textContent = 'Supprimer';
        deleteBtn.onclick = () => deleteQuiz(quiz.id);
        
        actions.appendChild(startBtn);
        actions.appendChild(resultsBtn);
        
        const isAdmin = typeof USER_ROLE !== 'undefined' && USER_ROLE && ['entreprise', 'ecole', 'admin'].includes(USER_ROLE);
        if (isAdmin) {
            actions.appendChild(deleteBtn);
        }
        
        const resultsSection = document.createElement('div');
        resultsSection.id = `results-section-${quiz.id}`;
        resultsSection.className = 'quiz-results-section';
        resultsSection.style.display = 'none';
        resultsSection.style.marginTop = '15px';
        resultsSection.style.padding = '15px';
        resultsSection.style.background = '#f8f9fa';
        resultsSection.style.borderRadius = '8px';
        
        card.appendChild(iconDiv);
        card.appendChild(title);
        card.appendChild(description);
        card.appendChild(info);
        card.appendChild(actions);
        card.appendChild(resultsSection);
        
        container.appendChild(card);
        
        checkCanViewResults(quiz.id);
    });
}

async function checkCanViewResults(quizId) {
    const userRole = document.body.getAttribute('data-user-role');
    if (userRole === 'entreprise' || userRole === 'ecole') {
        const btn = document.getElementById(`view-results-${quizId}`);
        if (btn) {
            btn.style.display = 'inline-block';
        }
    }
}

async function viewResults(quizId) {
    const resultsSection = document.getElementById(`results-section-${quizId}`);
    
    if (resultsSection.style.display === 'block') {
        resultsSection.style.display = 'none';
        return;
    }
    
    resultsSection.textContent = 'Chargement des résultats...';
    resultsSection.style.display = 'block';
    
    try {
        const response = await fetch(`get_quiz_results.php?quiz_id=${quizId}`);
        const data = await response.json();
        
        if (data.success) {
            resultsSection.textContent = '';
            
            if (data.results.length === 0) {
                const noResults = document.createElement('p');
                noResults.style.color = '#666';
                noResults.style.textAlign = 'center';
                noResults.textContent = 'Aucun utilisateur n\'a encore répondu à ce quiz.';
                resultsSection.appendChild(noResults);
                return;
            }
            
            const heading = document.createElement('h4');
            heading.style.marginBottom = '10px';
            heading.textContent = '📊 Résultats des participants';
            resultsSection.appendChild(heading);
            
            const resultsContainer = document.createElement('div');
            resultsContainer.style.display = 'flex';
            resultsContainer.style.flexDirection = 'column';
            resultsContainer.style.gap = '10px';
            
            data.results.forEach(result => {
                const scoreColor = result.percentage >= 70 ? '#28a745' : result.percentage >= 50 ? '#ffc107' : '#dc3545';
                
                const resultCard = document.createElement('div');
                resultCard.style.background = 'white';
                resultCard.style.padding = '12px';
                resultCard.style.borderRadius = '6px';
                resultCard.style.borderLeft = `4px solid ${scoreColor}`;
                
                const header = document.createElement('div');
                header.style.display = 'flex';
                header.style.justifyContent = 'space-between';
                header.style.alignItems = 'center';
                
                const username = document.createElement('strong');
                username.style.fontSize = '1.1em';
                username.textContent = `👤 ${result.username}`;
                
                const percentage = document.createElement('span');
                percentage.style.fontSize = '1.3em';
                percentage.style.fontWeight = 'bold';
                percentage.style.color = scoreColor;
                percentage.textContent = `${result.percentage}%`;
                
                header.appendChild(username);
                header.appendChild(percentage);
                
                const scoreInfo = document.createElement('div');
                scoreInfo.style.marginTop = '5px';
                scoreInfo.style.fontSize = '0.9em';
                scoreInfo.style.color = '#666';
                scoreInfo.textContent = `${result.score} / ${result.total_questions} réponses correctes`;
                
                const detailsBtn = document.createElement('button');
                detailsBtn.className = 'btn btn-small';
                detailsBtn.style.marginTop = '8px';
                detailsBtn.style.fontSize = '0.85em';
                detailsBtn.textContent = 'Voir détails';
                detailsBtn.onclick = () => toggleUserDetails(`${quizId}-${result.username}`);
                
                const detailsDiv = document.createElement('div');
                detailsDiv.id = `details-${quizId}-${result.username}`;
                detailsDiv.style.display = 'none';
                detailsDiv.style.marginTop = '10px';
                detailsDiv.style.paddingTop = '10px';
                detailsDiv.style.borderTop = '1px solid #dee2e6';
                
                result.responses.forEach((resp, idx) => {
                    const responseCard = document.createElement('div');
                    responseCard.style.marginBottom = '8px';
                    responseCard.style.padding = '8px';
                    responseCard.style.background = resp.is_correct ? '#d4edda' : '#f8d7da';
                    responseCard.style.borderRadius = '4px';
                    
                    const questionLabel = document.createElement('strong');
                    questionLabel.textContent = `${resp.is_correct ? '✓' : '✗'} Question ${idx + 1}: `;
                    
                    const questionText = document.createTextNode(resp.question);
                    
                    const answerDiv = document.createElement('div');
                    answerDiv.style.fontSize = '0.9em';
                    answerDiv.style.color = '#666';
                    answerDiv.textContent = `Réponse: ${resp.user_answer.join(', ')}`;
                    
                    responseCard.appendChild(questionLabel);
                    responseCard.appendChild(questionText);
                    responseCard.appendChild(document.createElement('br'));
                    responseCard.appendChild(answerDiv);
                    
                    detailsDiv.appendChild(responseCard);
                });
                
                resultCard.appendChild(header);
                resultCard.appendChild(scoreInfo);
                resultCard.appendChild(detailsBtn);
                resultCard.appendChild(detailsDiv);
                
                resultsContainer.appendChild(resultCard);
            });
            
            resultsSection.appendChild(resultsContainer);
        } else {
            resultsSection.textContent = '';
            const errorMsg = document.createElement('p');
            errorMsg.style.color = 'red';
            errorMsg.textContent = `Erreur: ${data.error}`;
            resultsSection.appendChild(errorMsg);
        }
    } catch (error) {
        console.error('Erreur:', error);
        resultsSection.textContent = '';
        const errorMsg = document.createElement('p');
        errorMsg.style.color = 'red';
        errorMsg.textContent = 'Erreur lors du chargement des résultats.';
        resultsSection.appendChild(errorMsg);
    }
}

function toggleUserDetails(detailsId) {
    const details = document.getElementById(`details-${detailsId}`);
    if (details) {
        details.style.display = details.style.display === 'none' ? 'block' : 'none';
    }
}

function startQuiz(quizId) {
    const quiz = quizzes.find(currentQuiz => currentQuiz.id === quizId);
    if (!quiz) return;

    document.getElementById('quiz-list-section').style.display = 'none';
    document.getElementById('quiz-player-section').style.display = 'block';
    
    displayQuiz(quiz);
}

let currentQuiz = null;
let currentQuestionIndex = 0;
let userAnswers = [];

function displayQuiz(quiz) {
    currentQuiz = quiz;
    currentQuestionIndex = 0;
    userAnswers = new Array(quiz.questions.length).fill(null);
    
    const playerSection = document.getElementById('quiz-player-section');
    playerSection.textContent = '';
    
    const player = document.createElement('div');
    player.className = 'quiz-player';
    
    const header = document.createElement('header');
    header.className = 'quiz-header';
    
    const heading = document.createElement('h2');
    heading.textContent = `${quiz.icon} ${quiz.title}`;
    
    const desc = document.createElement('p');
    desc.textContent = quiz.description;
    
    const progress = document.createElement('div');
    progress.className = 'quiz-progress';
    
    const progressText = document.createElement('span');
    progressText.textContent = 'Question ';
    const currentNumber = document.createElement('span');
    currentNumber.id = 'current-q';
    currentNumber.textContent = '1';
    const totalText = document.createTextNode(` / ${quiz.questions.length}`);
    progressText.appendChild(currentNumber);
    progressText.appendChild(totalText);
    
    const progressBar = document.createElement('div');
    progressBar.className = 'progress-bar';
    const progressFill = document.createElement('div');
    progressFill.id = 'progress-fill';
    progressFill.className = 'progress-fill';
    progressFill.style.width = `${(1/quiz.questions.length)*100}%`;
    progressBar.appendChild(progressFill);
    
    progress.appendChild(progressText);
    progress.appendChild(progressBar);
    
    header.appendChild(heading);
    header.appendChild(desc);
    header.appendChild(progress);
    
    const questionContent = document.createElement('div');
    questionContent.id = 'question-content';
    questionContent.className = 'question-content';
    
    const navigation = document.createElement('div');
    navigation.className = 'quiz-navigation';
    
    const prevBtn = document.createElement('button');
    prevBtn.id = 'prev-btn';
    prevBtn.className = 'btn';
    prevBtn.textContent = '← Précédent';
    prevBtn.disabled = true;
    prevBtn.onclick = previousQuestion;
    
    const nextBtn = document.createElement('button');
    nextBtn.id = 'next-btn';
    nextBtn.className = 'btn primary';
    nextBtn.textContent = 'Suivant →';
    nextBtn.onclick = nextQuestion;
    
    const submitBtn = document.createElement('button');
    submitBtn.id = 'submit-btn';
    submitBtn.className = 'btn primary';
    submitBtn.textContent = 'Terminer le quiz';
    submitBtn.style.display = 'none';
    submitBtn.onclick = submitQuiz;
    
    navigation.appendChild(prevBtn);
    navigation.appendChild(nextBtn);
    navigation.appendChild(submitBtn);
    
    const exitBtn = document.createElement('button');
    exitBtn.className = 'btn btn-secondary';
    exitBtn.style.marginTop = '20px';
    exitBtn.textContent = 'Quitter le quiz';
    exitBtn.onclick = exitQuiz;
    
    player.appendChild(header);
    player.appendChild(questionContent);
    player.appendChild(navigation);
    player.appendChild(exitBtn);
    
    playerSection.appendChild(player);
    
    displayQuestion(0);
}

function displayQuestion(index) {
    const question = currentQuiz.questions[index];
    const questionContent = document.getElementById('question-content');
    questionContent.textContent = '';
    
    const questionCard = document.createElement('div');
    questionCard.className = 'question-card';
    
    const heading = document.createElement('h3');
    heading.textContent = `Question ${index + 1}`;
    
    const questionText = document.createElement('p');
    questionText.className = 'question-text';
    questionText.textContent = question.text;
    
    const optionsList = document.createElement('div');
    optionsList.className = 'options-list';
    
    question.options.forEach((option, optionIndex) => {
        const label = document.createElement('label');
        label.className = 'quiz-option';
        
        const input = document.createElement('input');
        input.type = question.type === 'single' ? 'radio' : 'checkbox';
        input.name = 'answer';
        input.value = optionIndex;
        if (userAnswers[index] && userAnswers[index].includes(optionIndex)) {
            input.checked = true;
        }
        input.onchange = () => saveAnswer(optionIndex);
        
        const span = document.createElement('span');
        span.textContent = option;
        
        label.appendChild(input);
        label.appendChild(span);
        optionsList.appendChild(label);
    });
    
    questionCard.appendChild(heading);
    questionCard.appendChild(questionText);
    questionCard.appendChild(optionsList);
    questionContent.appendChild(questionCard);
    
    document.getElementById('prev-btn').disabled = index === 0;
    document.getElementById('current-q').textContent = index + 1;
    document.getElementById('progress-fill').style.width = `${((index + 1) / currentQuiz.questions.length) * 100}%`;
    
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
        const existingIndex = answers.indexOf(optionIndex);
        if (existingIndex > -1) {
            answers.splice(existingIndex, 1);
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
    let correctAnswers = 0;
    currentQuiz.questions.forEach((question, index) => {
        const userAnswer = userAnswers[index];
        const correctAnswer = question.answer;
        
        if (userAnswer && JSON.stringify(userAnswer.sort()) === JSON.stringify(correctAnswer.sort())) {
            correctAnswers++;
        }
    });
    
    const score = (correctAnswers / currentQuiz.questions.length) * 100;
    
    saveUserResponses();
    
    const playerSection = document.getElementById('quiz-player-section');
    playerSection.textContent = '';
    
    const results = document.createElement('div');
    results.className = 'quiz-results';
    
    const heading = document.createElement('h2');
    heading.textContent = '🎉 Quiz terminé !';
    
    const scoreDisplay = document.createElement('div');
    scoreDisplay.className = 'score-display';
    
    const scoreCircle = document.createElement('div');
    scoreCircle.className = 'score-circle';
    const scoreNumber = document.createElement('span');
    scoreNumber.className = 'score-number';
    scoreNumber.textContent = `${Math.round(score)}%`;
    scoreCircle.appendChild(scoreNumber);
    
    const scoreText = document.createElement('p');
    scoreText.className = 'score-text';
    scoreText.textContent = `${correctAnswers} / ${currentQuiz.questions.length} réponses correctes`;
    
    scoreDisplay.appendChild(scoreCircle);
    scoreDisplay.appendChild(scoreText);
    
    const details = document.createElement('div');
    details.className = 'results-details';
    
    const detailsHeading = document.createElement('h3');
    detailsHeading.textContent = 'Détails des réponses';
    details.appendChild(detailsHeading);
    
    currentQuiz.questions.forEach((question, index) => {
        const userAnswer = userAnswers[index] || [];
        const isCorrect = JSON.stringify(userAnswer.sort()) === JSON.stringify(question.answer.sort());
        
        const resultItem = document.createElement('div');
        resultItem.className = `result-item ${isCorrect ? 'correct' : 'incorrect'}`;
        
        const itemHeading = document.createElement('h4');
        itemHeading.textContent = `${isCorrect ? '✓' : '✗'} Question ${index + 1}`;
        
        const questionPara = document.createElement('p');
        questionPara.textContent = question.text;
        
        const userAnswerPara = document.createElement('p');
        const userAnswerLabel = document.createElement('strong');
        userAnswerLabel.textContent = 'Votre réponse: ';
        userAnswerPara.appendChild(userAnswerLabel);
        const userAnswerText = document.createTextNode(userAnswer.map(idx => question.options[idx]).join(', ') || 'Aucune réponse');
        userAnswerPara.appendChild(userAnswerText);
        
        const correctAnswerPara = document.createElement('p');
        const correctAnswerLabel = document.createElement('strong');
        correctAnswerLabel.textContent = 'Réponse correcte: ';
        correctAnswerPara.appendChild(correctAnswerLabel);
        const correctAnswerText = document.createTextNode(question.answer.map(idx => question.options[idx]).join(', '));
        correctAnswerPara.appendChild(correctAnswerText);
        
        resultItem.appendChild(itemHeading);
        resultItem.appendChild(questionPara);
        resultItem.appendChild(userAnswerPara);
        resultItem.appendChild(correctAnswerPara);
        
        details.appendChild(resultItem);
    });
    
    const actions = document.createElement('div');
    actions.className = 'results-actions';
    
    const retryBtn = document.createElement('button');
    retryBtn.className = 'btn primary';
    retryBtn.textContent = 'Recommencer';
    retryBtn.onclick = () => startQuiz(currentQuiz.id);
    
    const backBtn = document.createElement('button');
    backBtn.className = 'btn';
    backBtn.textContent = 'Retour aux quiz';
    backBtn.onclick = exitQuiz;
    
    actions.appendChild(retryBtn);
    actions.appendChild(backBtn);
    
    results.appendChild(heading);
    results.appendChild(scoreDisplay);
    results.appendChild(details);
    results.appendChild(actions);
    
    playerSection.appendChild(results);
}

async function saveUserResponses() {
    try {
        const responses = [];
        currentQuiz.questions.forEach((question, index) => {
            const userAnswer = userAnswers[index] || [];
            responses.push({
                question_id: question.id,
                user_answers: userAnswer
            });
        });
        
        const response = await fetch('save_responses.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                quiz_id: currentQuiz.id,
                responses: responses
            })
        });
        
        const data = await response.json();
        
        if (!data.success) {
            console.error('Erreur lors de l\'enregistrement des réponses:', data.error);
        }
    } catch (error) {
        console.error('Erreur réseau:', error);
    }
}

function exitQuiz() {
    document.getElementById('quiz-player-section').style.display = 'none';
    document.getElementById('quiz-list-section').style.display = 'block';
    currentQuiz = null;
    currentQuestionIndex = 0;
    userAnswers = [];
}

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
                quizzes = quizzes.filter(quiz => quiz.id !== quizId);
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

function toggleCreator() {
    const creator = document.getElementById('quiz-creator');
    creator.hidden = !creator.hidden;
    if (!creator.hidden) {
        document.getElementById('questions-container').textContent = '';
        addQuestion();
    }
}

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

    const header = document.createElement('div');
    header.className = 'question-header';
    
    const heading = document.createElement('h4');
    heading.textContent = `Question ${questionId}`;
    
    const removeBtn = document.createElement('button');
    removeBtn.type = 'button';
    removeBtn.className = 'btn btn-small btn-danger';
    removeBtn.textContent = 'Supprimer';
    removeBtn.onclick = () => removeQuestion(`question-${questionId}`);
    
    header.appendChild(heading);
    header.appendChild(removeBtn);

    const textGroup = document.createElement('div');
    textGroup.className = 'form-group';
    const textLabel = document.createElement('label');
    textLabel.textContent = 'Texte de la question';
    const textInput = document.createElement('input');
    textInput.type = 'text';
    textInput.className = 'question-text';
    textInput.placeholder = 'Entrez le texte de la question';
    textInput.value = questionText;
    textInput.required = true;
    textGroup.appendChild(textLabel);
    textGroup.appendChild(textInput);

    const typeGroup = document.createElement('div');
    typeGroup.className = 'form-group';
    const typeLabel = document.createElement('label');
    typeLabel.textContent = 'Type de question';
    const typeSelect = document.createElement('select');
    typeSelect.className = 'question-type';
    typeSelect.value = questionType;
    
    const singleOption = document.createElement('option');
    singleOption.value = 'single';
    singleOption.textContent = 'Choix unique';
    const multipleOption = document.createElement('option');
    multipleOption.value = 'multiple';
    multipleOption.textContent = 'Choix multiple';
    
    typeSelect.appendChild(singleOption);
    typeSelect.appendChild(multipleOption);
    typeSelect.value = questionType;
    typeSelect.addEventListener('change', (event) => {
        updateQuestionType(questionId, event.target.value);
    });
    
    typeGroup.appendChild(typeLabel);
    typeGroup.appendChild(typeSelect);

    const optionsGroup = document.createElement('div');
    optionsGroup.className = 'form-group';
    const optionsLabel = document.createElement('label');
    optionsLabel.textContent = 'Options';
    
    const optionsContainer = document.createElement('div');
    optionsContainer.id = `options-${questionId}`;
    optionsContainer.className = 'options-group';
    
    questionOptions.forEach((optionValue, optionIndex) => {
        const optionDiv = document.createElement('div');
        optionDiv.className = 'option-input';
        
        const optionInput = document.createElement('input');
        optionInput.type = 'text';
        optionInput.placeholder = `Option ${optionIndex + 1}`;
        optionInput.value = optionValue;
        optionInput.className = 'option-text';
        optionInput.setAttribute('data-index', optionIndex);
        
        const checkboxLabel = document.createElement('label');
        checkboxLabel.className = 'checkbox-label';
        
        const checkbox = document.createElement('input');
        checkbox.type = 'checkbox';
        checkbox.className = 'answer-checkbox';
        checkbox.setAttribute('data-index', optionIndex);
        if (questionAnswer.includes(optionIndex)) {
            checkbox.checked = true;
        }
        
        const checkboxText = document.createTextNode('Correcte');
        
        checkboxLabel.appendChild(checkbox);
        checkboxLabel.appendChild(checkboxText);
        
        optionDiv.appendChild(optionInput);
        optionDiv.appendChild(checkboxLabel);
        optionsContainer.appendChild(optionDiv);
    });
    
    const addOptionBtn = document.createElement('button');
    addOptionBtn.type = 'button';
    addOptionBtn.className = 'btn btn-small';
    addOptionBtn.textContent = '+ Ajouter option';
    addOptionBtn.onclick = () => addOption(`options-${questionId}`);
    
    optionsGroup.appendChild(optionsLabel);
    optionsGroup.appendChild(optionsContainer);
    optionsGroup.appendChild(addOptionBtn);

    questionBlock.appendChild(header);
    questionBlock.appendChild(textGroup);
    questionBlock.appendChild(typeGroup);
    questionBlock.appendChild(optionsGroup);

    container.appendChild(questionBlock);
}

function addOption(containerId) {
    const container = document.getElementById(containerId);
    const optionCount = container.querySelectorAll('.option-input').length;
    
    const optionDiv = document.createElement('div');
    optionDiv.className = 'option-input';
    
    const input = document.createElement('input');
    input.type = 'text';
    input.placeholder = `Option ${optionCount + 1}`;
    input.className = 'option-text';
    input.setAttribute('data-index', optionCount);
    
    const label = document.createElement('label');
    label.className = 'checkbox-label';
    
    const checkbox = document.createElement('input');
    checkbox.type = 'checkbox';
    checkbox.className = 'answer-checkbox';
    checkbox.setAttribute('data-index', optionCount);
    
    const labelText = document.createTextNode('Correcte');
    
    label.appendChild(checkbox);
    label.appendChild(labelText);
    
    optionDiv.appendChild(input);
    optionDiv.appendChild(label);
    
    container.appendChild(optionDiv);
}

function removeQuestion(questionId) {
    const element = document.getElementById(questionId);
    if (element) {
        element.remove();
        if (document.getElementById('questions-container').children.length === 0) {
            addQuestion();
        }
    }
}

function updateQuestionType(questionId, type) {
    const questionBlock = document.getElementById(`question-${questionId}`);
    const checkboxes = questionBlock.querySelectorAll('.answer-checkbox');
    
    if (type === 'single') {
        checkboxes.forEach(checkbox => {
            checkbox.type = 'radio';
            checkbox.name = `answer-${questionId}`;
        });
    } else {
        checkboxes.forEach(checkbox => {
            checkbox.type = 'checkbox';
            checkbox.removeAttribute('name');
        });
    }
}

async function createQuiz(event) {
    event.preventDefault();

    const title = document.getElementById('quiz-title').value;
    const description = document.getElementById('quiz-description').value;
    const icon = document.getElementById('quiz-icon').value;

    const questionElements = document.querySelectorAll('.question-block');
    const questions = [];

    questionElements.forEach((questionElement, idx) => {
        const text = questionElement.querySelector('.question-text').value;
        const type = questionElement.querySelector('.question-type').value;
        const optionsInputs = questionElement.querySelectorAll('.option-text');
        const answers = questionElement.querySelectorAll('.answer-checkbox:checked');

        const options = Array.from(optionsInputs).map(input => input.value);
        const answer = Array.from(answers).map(checkbox => parseInt(checkbox.getAttribute('data-index')));

        if (!text || options.some(option => !option) || answer.length === 0) {
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

    const groupId = typeof CURRENT_GROUP_ID !== 'undefined' ? CURRENT_GROUP_ID : 0;

    const newQuiz = {
        nom: title,
        description: description,
        theme: icon,
        icon: icon,
        questions: questions,
        group_id: groupId
    };

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
            document.getElementById('quiz-form').reset();
            document.getElementById('questions-container').textContent = '';
            document.getElementById('quiz-creator').hidden = true;
            
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

document.addEventListener('DOMContentLoaded', initMenu);

window.addEventListener('unhandledrejection', function(event) {
    console.error('Unhandled promise rejection:', event.reason, event);
});

window.addEventListener('error', function(event) {
    console.error('Unhandled error:', event.message, 'at', event.filename + ':' + event.lineno + ':' + event.colno, event.error);
});
