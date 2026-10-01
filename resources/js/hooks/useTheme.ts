import { useCallback, useEffect, useState } from 'react';

type Theme = 'light' | 'dark';

function getInitialTheme(): Theme {
    if (typeof document !== 'undefined') {
        return document.documentElement.classList.contains('dark')
            ? 'dark'
            : 'light';
    }
    return 'dark';
}

/**
 * Maneja el modo claro/oscuro persistiéndolo en localStorage y aplicando
 * la clase `dark` sobre <html>. El estado inicial lo fija el script inline
 * de app.blade.php para evitar parpadeo.
 */
export function useTheme() {
    const [theme, setTheme] = useState<Theme>(getInitialTheme);

    useEffect(() => {
        const root = document.documentElement;
        if (theme === 'dark') {
            root.classList.add('dark');
        } else {
            root.classList.remove('dark');
        }
        try {
            localStorage.setItem('theme', theme);
        } catch {
            // localStorage no disponible (modo privado, etc.)
        }
    }, [theme]);

    const toggleTheme = useCallback(() => {
        setTheme((prev) => (prev === 'dark' ? 'light' : 'dark'));
    }, []);

    return { theme, toggleTheme, isDark: theme === 'dark' };
}
