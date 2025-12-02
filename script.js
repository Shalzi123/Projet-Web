
// Simulation de base de données utilisateurs
// En production, utiliser une véritable authentification serveur
const users = {
    'eleve1': { password: 'pass123', role: 'student', name: 'Jean Dupont' },
    'prof1': { password: 'prof123', role: 'teacher', name: 'Mme Martin' },
    'admin1': { password: 'admin123', role: 'admin', name: 'Administrateur' }
};

// État de session
let currentUser = null;

const questions = [
    {
        id: 1,
        type: 'single', // 'single' ou 'multiple'
        text: 'Quelle est la capitale de la France ?',
        options: ['Paris', 'Lyon', 'Marseille', 'Toulouse'],
        answer: [0] // index(es) des bonnes réponses
    },
    {
        id: 2,
        type: 'multiple',
        text: 'Parmi les propositions suivantes, quelles sont des langages frontend ?',
        options: ['HTML', 'Python', 'CSS', 'JavaScript'],
        answer: [0,2,3]
    },
    {
        id: 3,
        type: 'single',
        text: 'Combien de faces a un cube ?',
        options: ['4','6','8','12'],
        answer: [1]
    }
];

let currentIndex = 0;
const state = {
    // stocke les sélections par question (tableau d'indices sélectionnés)
    answers: {},
    quizSubmitted: false
};

// === LOGIN SYSTEM ===
const loginScreen = document.getElementById('login-screen');
const loginForm = document.getElementById('login-form');
const quizApp = document.getElementById('quiz-app');

loginForm.addEventListener('submit', (e) => {
    e.preventDefault();
    const username = document.getElementById('username').value;
    const password = document.getElementById('password').value;
    const role = document.getElementById('role').value;

    // Vérifier les identifiants
    if (users[username] && users[username].password === password) {
        const user = users[username];
        // Vérifier que le rôle sélectionné correspond
        if (user.role === role) {
            currentUser = { username, ...user };
            loginScreen.hidden = true;
            quizApp.hidden = false;
            document.getElementById('user-display').textContent = `${currentUser.name} (${getRoleName(currentUser.role)})`;
            initQuiz();
        } else {
            alert('Rôle incorrect pour cet utilisateur');
        }
    } else {
        alert('Identifiants incorrects');
    }
});

function getRoleName(role) {
    const names = {
        'student': 'Élève',
        'teacher': 'Professeur',
        'admin': 'Administrateur'
    };
    return names[role] || role;
}

document.getElementById('logout-btn').addEventListener('click', () => {
    currentUser = null;
    state.answers = {};
    state.quizSubmitted = false;
    currentIndex = 0;
    loginForm.reset();
    loginScreen.hidden = false;
    quizApp.hidden = true;
});

// === QUIZ SYSTEM ===
const container = document.getElementById('quiz-container');
const prevBtn = document.getElementById('prev-btn');
const nextBtn = document.getElementById('next-btn');
const submitBtn = document.getElementById('submit-btn');
const restartBtn = document.getElementById('restart-btn');

function initQuiz() {
    currentIndex = 0;
    state.answers = {};
    state.quizSubmitted = false;
    prevBtn.hidden = false;
    nextBtn.hidden = false;
    submitBtn.hidden = false;
    restartBtn.hidden = true;
    renderQuestion(currentIndex);
}

function isTeacherOrAdmin() {
    return currentUser && (currentUser.role === 'teacher' || currentUser.role === 'admin');
}

function renderQuestion(index) {
    const q = questions[index];
    container.innerHTML = '';

    const qEl = document.createElement('div');
    qEl.className = 'question-block';

    const title = document.createElement('div');
    title.className = 'question';
    title.textContent = `Q${index + 1}. ${q.text}`;
    qEl.appendChild(title);

    const list = document.createElement('ul');
    list.className = 'options';

    const selected = state.answers[q.id] || [];

    // Si le quiz est soumis et que c'est un élève, désactiver les options
    const isDisabled = state.quizSubmitted && currentUser.role === 'student';

    q.options.forEach((opt, i) => {
        const li = document.createElement('li');
        const id = `q${q.id}_opt${i}`;
        const input = document.createElement('input');
        input.type = q.type === 'multiple' ? 'checkbox' : 'radio';
        input.name = `q_${q.id}`;
        input.id = id;
        input.value = i;
        if (selected.includes(i)) input.checked = true;
        input.disabled = isDisabled;

        input.addEventListener('change', () => {
            updateAnswer(q.id, i, input.checked, q.type);
        });

        const label = document.createElement('label');
        label.className = 'option-label';
        label.htmlFor = id;
        label.appendChild(input);
        const span = document.createElement('span');
        span.textContent = opt;
        label.appendChild(span);

        li.appendChild(label);
        list.appendChild(li);
    });

    qEl.appendChild(list);

    // zone pour feedback local
    const feedback = document.createElement('div');
    feedback.id = 'local-feedback';
    qEl.appendChild(feedback);

    container.appendChild(qEl);

    // update buttons
    prevBtn.disabled = index === 0;
    nextBtn.disabled = index === questions.length - 1;
}

function updateAnswer(qId, optionIndex, checked, qType) {
    if (!state.answers[qId]) state.answers[qId] = [];
    if (qType === 'single') {
        state.answers[qId] = [optionIndex];
        // Uncheck other radios visually handled by browser
    } else {
        const arr = state.answers[qId];
        if (checked) {
            if (!arr.includes(optionIndex)) arr.push(optionIndex);
        } else {
            const idx = arr.indexOf(optionIndex);
            if (idx >= 0) arr.splice(idx, 1);
        }
    }
}

function calculateResults() {
    let score = 0;
    const details = [];

    questions.forEach(q => {
        const given = (state.answers[q.id] || []).slice().sort((a,b)=>a-b);
        const correct = (q.answer || []).slice().sort((a,b)=>a-b);
        const equal = given.length === correct.length && given.every((v,i)=>v === correct[i]);
        if (equal) score++;
        details.push({ id: q.id, correct, given, equal, text: q.text, options: q.options });
    });

    return { score, total: questions.length, details };
}

function showResults() {
    const res = calculateResults();
    container.innerHTML = '';

    // Afficher un message d'information selon le rôle
    if (currentUser.role === 'student') {
        const message = document.createElement('div');
        message.className = 'restricted-message';
        message.textContent = 'Votre QCM a été soumis. Vous n\'avez plus accès à vos réponses et à la correction.';
        container.appendChild(message);

        const summary = document.createElement('div');
        summary.className = 'card';
        const h = document.createElement('h2');
        h.textContent = `Résultat : ${res.score} / ${res.total}`;
        summary.appendChild(h);

        const info = document.createElement('p');
        info.style.color = '#666';
        info.textContent = 'Contactez votre professeur pour obtenir la correction détaillée.';
        summary.appendChild(info);

        container.appendChild(summary);
    } else {
        // Les professeurs et admins voient tout
        const summary = document.createElement('div');
        summary.className = 'card';
        const h = document.createElement('h2');
        h.textContent = `Résultat : ${res.score} / ${res.total}`;
        summary.appendChild(h);

        res.details.forEach(d => {
            const el = document.createElement('div');
            el.className = 'result ' + (d.equal ? 'correct' : 'incorrect');
            
            const qTitle = document.createElement('div');
            qTitle.textContent = d.text;
            qTitle.style.fontWeight = '600';
            el.appendChild(qTitle);

            const givenText = document.createElement('div');
            givenText.textContent = 'Réponse(s) donnée(s): ' + (d.given.length ? d.given.map(i => d.options[i]).join(', ') : '(aucune)');
            el.appendChild(givenText);

            const correctText = document.createElement('div');
            correctText.textContent = 'Bonne(s) réponse(s): ' + d.correct.map(i => d.options[i]).join(', ');
            el.appendChild(correctText);

            summary.appendChild(el);
        });

        container.appendChild(summary);
    }

    state.quizSubmitted = true;

    // Masquer boutons navigation et afficher restart (seulement pour les profs/admins)
    prevBtn.hidden = true;
    nextBtn.hidden = true;
    submitBtn.hidden = true;
    if (isTeacherOrAdmin()) {
        restartBtn.hidden = false;
    }
}

function restartQuiz() {
    currentIndex = 0;
    state.answers = {};
    state.quizSubmitted = false;
    prevBtn.hidden = false;
    nextBtn.hidden = false;
    submitBtn.hidden = false;
    restartBtn.hidden = true;
    renderQuestion(currentIndex);
}

function restartQuiz() {
    currentIndex = 0;
    state.answers = {};
    prevBtn.hidden = false;
    nextBtn.hidden = false;
    submitBtn.hidden = false;
    restartBtn.hidden = true;
    renderQuestion(currentIndex);
}

// boutons
prevBtn.addEventListener('click', () => {
    if (currentIndex > 0 && !state.quizSubmitted) {
        currentIndex--;
        renderQuestion(currentIndex);
    }
});

nextBtn.addEventListener('click', () => {
    if (currentIndex < questions.length - 1 && !state.quizSubmitted) {
        currentIndex++;
        renderQuestion(currentIndex);
    }
});

submitBtn.addEventListener('click', () => {
    // simple validation : au moins une réponse par question recommandée
    showResults();
});

restartBtn.addEventListener('click', restartQuiz);

// initial render
renderQuestion(currentIndex);

// Expliquer rapidement comment ajouter des questions :
// - chaque question: { id, type: 'single'|'multiple', text, options: [...], answer: [indices] }
// - pour 'single', mettre un seul indice dans 'answer'.
