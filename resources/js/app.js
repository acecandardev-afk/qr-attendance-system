import './bootstrap';
import Alpine from 'alpinejs';

window.Alpine = Alpine;
Alpine.start();

// Theme (light/dark) toggle
// - Persists in localStorage
// - Defaults to OS preference if not set
const THEME_KEY = 'theme_preference_v1';

function applyTheme(theme) {
    const isDark = theme === 'dark';
    document.documentElement.classList.toggle('dark', isDark);
    try {
        localStorage.setItem(THEME_KEY, theme);
    } catch (e) {
        // ignore storage issues
    }
}

function getPreferredTheme() {
    try {
        const saved = localStorage.getItem(THEME_KEY);
        if (saved === 'dark' || saved === 'light') return saved;
    } catch (e) {
        // ignore
    }
    return window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
}

window.setTheme = (theme) => applyTheme(theme);
window.toggleTheme = () => {
    const current = document.documentElement.classList.contains('dark') ? 'dark' : 'light';
    applyTheme(current === 'dark' ? 'light' : 'dark');
};

applyTheme(getPreferredTheme());
