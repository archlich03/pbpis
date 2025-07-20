/**
 * Theme management utilities
 * Provides additional theme-related functionality
 */

// Theme constants
const THEMES = {
    LIGHT: 'light',
    DARK: 'dark'
};

// Get current theme
function getCurrentTheme() {
    return localStorage.getItem('theme') || 
           (document.cookie.includes('theme=dark') ? THEMES.DARK : THEMES.LIGHT);
}

// Set theme with proper persistence
function setTheme(theme) {
    if (!Object.values(THEMES).includes(theme)) {
        console.warn('Invalid theme:', theme);
        return;
    }
    
    const html = document.documentElement;
    
    if (theme === THEMES.DARK) {
        html.classList.add('dark');
    } else {
        html.classList.remove('dark');
    }
    
    // Persist theme preference
    localStorage.setItem('theme', theme);
    document.cookie = `theme=${theme}; path=/; max-age=31536000`; // 1 year
    
    // Dispatch custom event for other components
    document.dispatchEvent(new CustomEvent('theme-changed', { 
        detail: { theme, darkMode: theme === THEMES.DARK } 
    }));
}

// Initialize theme on DOM content loaded
document.addEventListener('DOMContentLoaded', function() {
    const currentTheme = getCurrentTheme();
    setTheme(currentTheme);
});

// Export for global use
window.ThemeManager = {
    THEMES,
    getCurrentTheme,
    setTheme,
    toggle: () => {
        const current = getCurrentTheme();
        const newTheme = current === THEMES.DARK ? THEMES.LIGHT : THEMES.DARK;
        setTheme(newTheme);
        return newTheme;
    }
};
