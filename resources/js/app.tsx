import '../css/app.css';
import './bootstrap';

import { notify } from '@/lib/notify';
import { createInertiaApp, router } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';

const appName = import.meta.env.VITE_APP_NAME || 'FactuFlow';

// Base de datos caída durante una petición de Inertia: en vez del modal con
// la respuesta cruda, se muestra un aviso y el usuario sigue en la misma
// pantalla sin perder lo que estaba haciendo (p. ej. el carrito del POS).
router.on('invalid', (event) => {
    const response = event.detail.response;
    if (response.status !== 503 || !response.headers['x-database-unavailable']) return;

    event.preventDefault();
    notify('error', response.data?.message ?? 'Sin conexión con la base de datos. Intenta de nuevo.');
});

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.tsx`,
            import.meta.glob('./Pages/**/*.tsx'),
        ),
    setup({ el, App, props }) {
        const root = createRoot(el);

        root.render(<App {...props} />);
    },
    progress: {
        color: '#4B5563',
    },
});
