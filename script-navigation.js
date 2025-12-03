/* ===== SCRIPT NAVIGATION ===== */

// Changer d'application
function switchApp(appName) {
    // Masquer toutes les pages
    document.querySelectorAll('.app-page').forEach(page => {
        page.classList.remove('active');
    });

    // Retirer la classe active des boutons
    document.querySelectorAll('.nav-btn').forEach(btn => {
        btn.classList.remove('active');
    });

    // Afficher la page sélectionnée
    document.getElementById(appName).classList.add('active');
    document.querySelector(`[data-app="${appName}"]`).classList.add('active');

    // Réinitialiser les onglets de satisfaction si on la charge
    if (appName === 'satisfaction') {
        switchTab('dashboard');
    }
}

// Événement pour les clics sur les boutons de navigation
document.addEventListener('DOMContentLoaded', function() {
    // Les boutons de navigation sont déjà définis dans le HTML
});