const quizzes = [
    {
        id: 1,
        title: 'Géographie',
        description: 'Testez vos connaissances en géographie mondiale',
        icon: '🌍',
        questions: [
            {
                id: 1,
                type: 'single',
                text: 'Quelle est la capitale de la France ?',
                options: ['Paris', 'Lyon', 'Marseille', 'Toulouse'],
                answer: [0]
            },
            {
                id: 2,
                type: 'single',
                text: 'Quel est le plus haut sommet du monde ?',
                options: ['K2', 'Everest', 'Kilimanjaro', 'Mont-Blanc'],
                answer: [1]
            },
            {
                id: 3,
                type: 'multiple',
                text: 'Parmi les pays suivants, lesquels sont en Afrique ?',
                options: ['Afrique du Sud', 'Australie', 'Égypte', 'Brésil', 'Kenya'],
                answer: [0, 2, 4]
            }
        ]
    },
    {
        id: 2,
        title: 'Informatique',
        description: 'Maîtrisez les concepts fondamentaux du web et de la programmation',
        icon: '💻',
        questions: [
            {
                id: 1,
                type: 'multiple',
                text: 'Parmi les propositions suivantes, quelles sont des langages frontend ?',
                options: ['HTML', 'Python', 'CSS', 'JavaScript', 'Java'],
                answer: [0, 2, 3]
            },
            {
                id: 2,
                type: 'single',
                text: 'Quel langage est utilisé pour le style des pages web ?',
                options: ['JavaScript', 'CSS', 'HTML', 'Python'],
                answer: [1]
            },
            {
                id: 3,
                type: 'single',
                text: 'Que signifie HTML ?',
                options: ['Hyper Text Markup Language', 'High Tech Modern Language', 'Home Tool Markup Language', 'Hyperlinks and Text Markup Language'],
                answer: [0]
            }
        ]
    },
    {
        id: 3,
        title: 'Culture Générale',
        description: 'Enrichissez vos connaissances avec des questions variées',
        icon: '📚',
        questions: [
            {
                id: 1,
                type: 'single',
                text: 'En quelle année l\'homme a-t-il marché sur la Lune pour la première fois ?',
                options: ['1965', '1969', '1972', '1975'],
                answer: [1]
            },
            {
                id: 2,
                type: 'single',
                text: 'Qui a peint la Joconde ?',
                options: ['Michelangelo', 'Léonard de Vinci', 'Raphaël', 'Donatello'],
                answer: [1]
            },
            {
                id: 3,
                type: 'multiple',
                text: 'Parmi les musiciens suivants, qui sont des compositeurs classiques ?',
                options: ['Mozart', 'Beethoven', 'Elvis Presley', 'Bach', 'The Beatles'],
                answer: [0, 1, 3]
            }
        ]
    },
    {
        id: 4,
        title: 'Sciences',
        description: 'Explorez les merveilles de la science et de la nature',
        icon: '🔬',
        questions: [
            {
                id: 1,
                type: 'single',
                text: 'Combien de faces a un cube ?',
                options: ['4', '6', '8', '12'],
                answer: [1]
            },
            {
                id: 2,
                type: 'single',
                text: 'Quel est le plus grand organe du corps humain ?',
                options: ['Le cœur', 'Le cerveau', 'La peau', 'Le foie'],
                answer: [2]
            },
            {
                id: 3,
                type: 'multiple',
                text: 'Parmi les gaz suivants, lesquels constituent l\'atmosphère terrestre ?',
                options: ['Oxygène', 'Azote', 'Hydrogène', 'Dioxyde de carbone', 'Hélium'],
                answer: [0, 1, 3]
            }
        ]
    },
    {
        id: 5,
        title: 'Histoire',
        description: 'Voyagez à travers les grands événements historiques',
        icon: '📜',
        questions: [
            {
                id: 1,
                type: 'single',
                text: 'En quelle année la Révolution française a-t-elle eu lieu ?',
                options: ['1776', '1789', '1799', '1815'],
                answer: [1]
            },
            {
                id: 2,
                type: 'single',
                text: 'Quel roi français a construit le château de Versailles ?',
                options: ['Henri IV', 'Louis XIII', 'Louis XIV', 'Louis XV'],
                answer: [2]
            },
            {
                id: 3,
                type: 'multiple',
                text: 'Quels sont les continents qui existaient lors du Moyen Âge ?',
                options: ['Europe', 'Afrique', 'Asie', 'Amérique', 'Océanie'],
                answer: [0, 1, 2]
            }
        ]
    },
    {
        id: 6,
        title: 'Mathématiques',
        description: 'Aiguisez vos compétences mathématiques',
        icon: '🧮',
        questions: [
            {
                id: 1,
                type: 'single',
                text: 'Quelle est la racine carrée de 144 ?',
                options: ['11', '12', '13', '14'],
                answer: [1]
            },
            {
                id: 2,
                type: 'single',
                text: 'Quel est le résultat de 15 × 8 ?',
                options: ['110', '115', '120', '125'],
                answer: [2]
            },
            {
                id: 3,
                type: 'multiple',
                text: 'Parmi les nombres suivants, lesquels sont des nombres premiers ?',
                options: ['2', '3', '4', '7', '9'],
                answer: [0, 1, 3]
            }
        ]
    }
];

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
            <button class="btn primary" onclick="startQuiz(${quiz.id})">Commencer</button>
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

// Initialisation
renderQuizzes();
