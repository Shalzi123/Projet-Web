let surveys = JSON.parse(localStorage.getItem('surveys')) || [];
let currentSurveyId = null;

document.addEventListener('DOMContentLoaded', function() {
    initializeSatisfactionEventListeners();
    updateDashboard();
    updateSurveysList();
    updateResults();
});

function initializeSatisfactionEventListeners() {
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            switchTab(this.dataset.tab);
        });
    });

    const surveyForm = document.getElementById('surveyForm');
    if (surveyForm) {
        surveyForm.addEventListener('submit', createSurvey);
    }

    const addQuestionBtn = document.getElementById('addQuestionBtn');
    if (addQuestionBtn) {
        addQuestionBtn.addEventListener('click', addQuestionField);
    }

    document.querySelectorAll('.close').forEach(close => {
        close.addEventListener('click', function() {
            this.closest('.modal').style.display = 'none';
        });
    });

    window.addEventListener('click', function(event) {
        const responseModal = document.getElementById('responseModal');
        const detailsModal = document.getElementById('detailsModal');
        if (event.target === responseModal) {
            responseModal.style.display = 'none';
        }
        if (event.target === detailsModal) {
            detailsModal.style.display = 'none';
        }
    });

    document.querySelectorAll('.details-tab-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            switchDetailsTab(this.dataset.detailsTab);
        });
    });
}

function switchTab(tabName) {
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.remove('active');
    });

    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });

    const tabContent = document.getElementById(tabName);
    if (tabContent) {
        tabContent.classList.add('active');
    }
    
    const tabBtn = document.querySelector(`[data-tab="${tabName}"]`);
    if (tabBtn) {
        tabBtn.classList.add('active');
    }

    if (tabName === 'surveys') {
        updateSurveysList();
    } else if (tabName === 'results') {
        updateResults();
    }
}

function addQuestionField() {
    const questionsList = document.getElementById('questionsList');
    const questionIndex = questionsList.children.length;

    const questionItem = document.createElement('div');
    questionItem.className = 'question-item';
    questionItem.innerHTML = `
        <input type="text" class="question-text" placeholder="Entrez votre question" required>
        <select class="question-type">
            <option value="rating">Échelle de notation (1-5)</option>
            <option value="text">Texte court</option>
            <option value="textarea">Texte long</option>
        </select>
        <button type="button" class="remove-question" onclick="removeQuestion(this)">Supprimer</button>
    `;

    questionsList.appendChild(questionItem);
}

function removeQuestion(btn) {
    btn.closest('.question-item').remove();
}

function createSurvey(e) {
    e.preventDefault();

    const title = document.getElementById('surveyTitle').value;
    const description = document.getElementById('surveyDescription').value;
    const questionItems = document.querySelectorAll('.question-item');

    if (questionItems.length === 0) {
        alert('Veuillez ajouter au moins une question');
        return;
    }

    const questions = Array.from(questionItems).map(item => ({
        id: Date.now() + Math.random(),
        text: item.querySelector('.question-text').value,
        type: item.querySelector('.question-type').value
    }));

    const survey = {
        id: Date.now(),
        title: title,
        description: description,
        questions: questions,
        responses: [],
        createdAt: new Date().toLocaleDateString('fr-FR'),
        status: 'active'
    };

    surveys.push(survey);
    saveSurveys();
    
    document.getElementById('surveyForm').reset();
    document.getElementById('questionsList').innerHTML = '';
    
    alert('Questionnaire créé avec succès!');
    switchTab('surveys');
    updateSurveysList();
    updateDashboard();
}

function updateSurveysList() {
    const surveysList = document.getElementById('surveysList');

    if (surveys.length === 0) {
        surveysList.innerHTML = '<p class="empty-state">Aucun questionnaire créé. <a href="#" onclick="switchTab(\'create-survey\')">Créez-en un</a></p>';
        return;
    }

    surveysList.innerHTML = surveys.map(survey => {
        const avgScore = calculateSurveyAverage(survey);
        return `
            <div class="survey-card">
                <h3>${survey.title}</h3>
                <p>${survey.description || 'Aucune description'}</p>
                <div class="survey-meta">
                    <span>📅 ${survey.createdAt}</span>
                    <span>📋 ${survey.responses.length} réponse${survey.responses.length > 1 ? 's' : ''}</span>
                </div>
                <div style="margin-bottom: 15px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                        <span style="font-weight: 600;">Score moyen</span>
                        <span style="color: #4f46e5; font-weight: 600;">${avgScore}%</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: ${avgScore}%"></div>
                    </div>
                </div>
                <div class="survey-actions">
                    <button class="btn-respond" onclick="openResponseModal(${survey.id})">Répondre</button>
                    <button class="btn-view" onclick="openDetailsModal(${survey.id})">Détails</button>
                </div>
            </div>
        `;
    }).join('');
}

function openResponseModal(surveyId) {
    currentSurveyId = surveyId;
    const survey = surveys.find(s => s.id === surveyId);

    if (!survey) return;

    document.getElementById('modalTitle').textContent = survey.title;
    document.getElementById('modalDescription').textContent = survey.description || '';

    const questionsResponse = document.getElementById('questionsResponse');
    questionsResponse.innerHTML = survey.questions.map((q, index) => {
        if (q.type === 'rating') {
            return `
                <div class="question-response">
                    <h4>${index + 1}. ${q.text}</h4>
                    <div class="rating-scale">
                        ${Array.from({length: 5}, (_, i) => i + 1).map(rating => `
                            <button type="button" class="rating-btn" data-question-id="${q.id}" data-rating="${rating}">${rating}</button>
                        `).join('')}
                    </div>
                </div>
            `;
        } else if (q.type === 'text') {
            return `
                <div class="question-response">
                    <h4>${index + 1}. ${q.text}</h4>
                    <input type="text" class="response-text" data-question-id="${q.id}" placeholder="Votre réponse" style="width: 100%; padding: 10px; border: 1px solid #e2e8f0; border-radius: 6px;">
                </div>
            `;
        } else if (q.type === 'textarea') {
            return `
                <div class="question-response">
                    <h4>${index + 1}. ${q.text}</h4>
                    <textarea class="response-textarea" data-question-id="${q.id}" placeholder="Votre réponse" style="width: 100%; padding: 10px; border: 1px solid #e2e8f0; border-radius: 6px; min-height: 80px;"></textarea>
                </div>
            `;
        }
    }).join('');

    document.querySelectorAll('.rating-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const questionId = this.dataset.questionId;
            document.querySelectorAll(`[data-question-id="${questionId}"]`).forEach(b => b.classList.remove('selected'));
            this.classList.add('selected');
        });
    });

    const responseForm = document.getElementById('responseForm');
    responseForm.removeEventListener('submit', submitResponse);
    responseForm.addEventListener('submit', submitResponse);
    
    document.getElementById('responseModal').style.display = 'block';
}

function submitResponse(e) {
    e.preventDefault();

    const survey = surveys.find(s => s.id === currentSurveyId);
    const responses = {};

    document.querySelectorAll('.rating-btn.selected').forEach(btn => {
        responses[btn.dataset.questionId] = {
            type: 'rating',
            value: parseInt(btn.dataset.rating)
        };
    });

    document.querySelectorAll('.response-text').forEach(input => {
        responses[input.dataset.questionId] = {
            type: 'text',
            value: input.value
        };
    });

    document.querySelectorAll('.response-textarea').forEach(textarea => {
        responses[textarea.dataset.questionId] = {
            type: 'textarea',
            value: textarea.value
        };
    });

    if (Object.keys(responses).length !== survey.questions.length) {
        alert('Veuillez répondre à toutes les questions');
        return;
    }

    survey.responses.push({
        id: Date.now(),
        timestamp: new Date().toLocaleString('fr-FR'),
        answers: responses
    });

    saveSurveys();
    closeResponseModal();
    updateSurveysList();
    updateResults();
    updateDashboard();
    alert('Réponses soumises avec succès!');
}

function closeResponseModal() {
    document.getElementById('responseModal').style.display = 'none';
}

function openDetailsModal(surveyId) {
    currentSurveyId = surveyId;
    const survey = surveys.find(s => s.id === surveyId);

    if (!survey) return;

    document.getElementById('detailsTitle').textContent = survey.title;

    const surveyDetails = document.getElementById('surveyDetails');
    surveyDetails.innerHTML = `
        <div style="margin-bottom: 20px;">
            <p><strong>Description:</strong> ${survey.description || 'Aucune description'}</p>
            <p><strong>Date de création:</strong> ${survey.createdAt}</p>
            <p><strong>Nombre de réponses:</strong> ${survey.responses.length}</p>
            <p><strong>Score moyen:</strong> ${calculateSurveyAverage(survey)}%</p>
        </div>
        <h3 style="margin-bottom: 15px;">Questions</h3>
        <div>
            ${survey.questions.map((q, i) => `
                <div style="background: #f8fafc; padding: 15px; border-radius: 8px; margin-bottom: 10px;">
                    <p><strong>Q${i + 1}: ${q.text}</strong></p>
                    <p style="color: #64748b; margin-top: 5px;">Type: ${q.type === 'rating' ? 'Échelle de notation' : q.type === 'text' ? 'Texte court' : 'Texte long'}</p>
                </div>
            `).join('')}
        </div>
    `;

    const responsesList = document.getElementById('responsesList');
    if (survey.responses.length === 0) {
        responsesList.innerHTML = '<p class="empty-state">Aucune réponse pour le moment</p>';
    } else {
        responsesList.innerHTML = survey.responses.map(response => `
            <div style="background: #f8fafc; padding: 15px; border-radius: 8px; margin-bottom: 15px;">
                <p style="font-weight: 600; margin-bottom: 10px;">Réponse du ${response.timestamp}</p>
                ${survey.questions.map(q => `
                    <div style="margin-bottom: 10px;">
                        <p style="font-weight: 500;">${q.text}</p>
                        <p style="color: #64748b;">Réponse: ${response.answers[q.id]?.value || 'N/A'}</p>
                    </div>
                `).join('')}
            </div>
        `).join('');
    }

    document.getElementById('respondBtn').onclick = () => {
        closeDetailsModal();
        openResponseModal(surveyId);
    };

    document.getElementById('deleteBtn').onclick = () => {
        if (confirm('Êtes-vous sûr de vouloir supprimer ce questionnaire?')) {
            surveys = surveys.filter(s => s.id !== surveyId);
            saveSurveys();
            closeDetailsModal();
            updateSurveysList();
            updateResults();
            updateDashboard();
            alert('Questionnaire supprimé');
        }
    };

    document.querySelectorAll('.details-tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    document.querySelectorAll('.details-tab-content').forEach(content => {
        content.classList.remove('active');
    });
    document.querySelector('[data-details-tab="info"]').classList.add('active');
    document.getElementById('info').classList.add('active');

    document.getElementById('detailsModal').style.display = 'block';
}

function switchDetailsTab(tabName) {
    document.querySelectorAll('.details-tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    document.querySelectorAll('.details-tab-content').forEach(content => {
        content.classList.remove('active');
    });

    document.querySelector(`[data-details-tab="${tabName}"]`).classList.add('active');
    document.getElementById(tabName).classList.add('active');
}

function closeDetailsModal() {
    document.getElementById('detailsModal').style.display = 'none';
}

function updateResults() {
    const resultsList = document.getElementById('resultsList');

    if (surveys.length === 0 || surveys.every(s => s.responses.length === 0)) {
        resultsList.innerHTML = '<p class="empty-state">Aucun résultat disponible pour le moment.</p>';
        return;
    }

    resultsList.innerHTML = surveys
        .filter(s => s.responses.length > 0)
        .map(survey => {
            const ratingQuestions = survey.questions.filter(q => q.type === 'rating');
            
            if (ratingQuestions.length === 0) {
                return '';
            }

            const questionResults = ratingQuestions.map(q => {
                const ratings = survey.responses
                    .map(r => r.answers[q.id]?.value)
                    .filter(v => v !== undefined);

                if (ratings.length === 0) return null;

                const average = (ratings.reduce((a, b) => a + b, 0) / ratings.length * 20).toFixed(0);

                return {
                    question: q.text,
                    average: average,
                    ratings: ratings
                };
            }).filter(Boolean);

            if (questionResults.length === 0) return '';

            const surveyAverage = calculateSurveyAverage(survey);

            return `
                <div class="result-card">
                    <h3>${survey.title}</h3>
                    <div style="margin-bottom: 20px;">
                        <div class="result-item-label">
                            <span><strong>Score global</strong></span>
                            <span style="color: #4f46e5; font-weight: 600;">${surveyAverage}%</span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: ${surveyAverage}%"></div>
                        </div>
                    </div>
                    ${questionResults.map(qr => `
                        <div class="result-item">
                            <div class="result-item-label">
                                <span>${qr.question}</span>
                                <span>${qr.average}%</span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: ${qr.average}%"></div>
                            </div>
                        </div>
                    `).join('')}
                    <p style="margin-top: 15px; color: #64748b; font-size: 0.9rem;">
                        📊 ${survey.responses.length} réponse${survey.responses.length > 1 ? 's' : ''}
                    </p>
                </div>
            `;
        })
        .join('');
}

function updateDashboard() {
    const totalResponses = surveys.reduce((sum, s) => sum + s.responses.length, 0);
    const ratingResponses = [];

    surveys.forEach(survey => {
        survey.responses.forEach(response => {
            Object.values(response.answers).forEach(answer => {
                if (answer.type === 'rating') {
                    ratingResponses.push(answer.value);
                }
            });
        });
    });

    const globalScore = ratingResponses.length > 0 
        ? Math.round((ratingResponses.reduce((a, b) => a + b, 0) / ratingResponses.length / 5) * 100)
        : 0;

    const satisfactionRate = ratingResponses.length > 0
        ? Math.round((ratingResponses.filter(r => r >= 4).length / ratingResponses.length) * 100)
        : 0;

    document.getElementById('globalScore').textContent = globalScore + '%';
    document.getElementById('surveyCount').textContent = surveys.length;
    document.getElementById('responseCount').textContent = totalResponses;
    document.getElementById('satisfactionRate').textContent = satisfactionRate + '%';
}

function calculateSurveyAverage(survey) {
    const ratingQuestions = survey.questions.filter(q => q.type === 'rating');

    if (ratingQuestions.length === 0 || survey.responses.length === 0) {
        return 0;
    }

    let totalRating = 0;
    let count = 0;

    survey.responses.forEach(response => {
        ratingQuestions.forEach(q => {
            const answer = response.answers[q.id];
            if (answer && answer.type === 'rating') {
                totalRating += answer.value;
                count++;
            }
        });
    });

    if (count === 0) return 0;

    const average = (totalRating / count / 5) * 100;
    return Math.round(average);
}

function saveSurveys() {
    localStorage.setItem('surveys', JSON.stringify(surveys));
}