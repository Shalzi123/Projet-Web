function switchApp(appName) {
    document.querySelectorAll('.app-page').forEach(page => {
        page.classList.remove('active');
    });

    document.querySelectorAll('.nav-btn').forEach(btn => {
        btn.classList.remove('active');
    });

    document.getElementById(appName).classList.add('active');
    document.querySelector(`[data-app="${appName}"]`).classList.add('active');

    if (appName === 'satisfaction') {
        switchTab('dashboard');
    }
}

document.addEventListener('DOMContentLoaded', function() {
});