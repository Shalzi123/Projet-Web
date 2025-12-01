

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
    answers: {}
};

const container = document.getElementById('quiz-container');
const prevBtn = document.getElementById('prev-btn');
const nextBtn = document.getElementById('next-btn');
const submitBtn = document.getElementById('submit-btn');
const restartBtn = document.getElementById('restart-btn');

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

    q.options.forEach((opt, i) => {
        const li = document.createElement('li');
        const id = `q${q.id}_opt${i}`;
        const input = document.createElement('input');
        input.type = q.type === 'multiple' ? 'checkbox' : 'radio';
        input.name = `q_${q.id}`;
        input.id = id;
        input.value = i;
        if (selected.includes(i)) input.checked = true;

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
        details.push({ id: q.id, correct, given, equal, text: q.text });
    });

    return { score, total: questions.length, details };
}

function showResults() {
    const res = calculateResults();
    container.innerHTML = '';

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
        givenText.textContent = 'Votre réponse(s): ' + (d.given.length ? d.given.map(i => questions.find(q=>q.id===d.id).options[i]).join(', ') : '(aucune)');
        el.appendChild(givenText);

        const correctText = document.createElement('div');
        correctText.textContent = 'Bonne(s) réponse(s): ' + d.correct.map(i => questions.find(q=>q.id===d.id).options[i]).join(', ');
        el.appendChild(correctText);

        summary.appendChild(el);
    });

    container.appendChild(summary);

    // masquer boutons navigation et afficher restart
    prevBtn.hidden = true;
    nextBtn.hidden = true;
    submitBtn.hidden = true;
    restartBtn.hidden = false;
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
    if (currentIndex > 0) {
        currentIndex--;
        renderQuestion(currentIndex);
    }
});

nextBtn.addEventListener('click', () => {
    if (currentIndex < questions.length - 1) {
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
