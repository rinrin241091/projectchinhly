import '../css/app.css';
import './bootstrap';
import './custom-print';
import React from 'react';
import { createRoot } from 'react-dom/client';
import { createInertiaApp } from '@inertiajs/react';

createInertiaApp({
    title: (title) => (title ? `${title} - DocManager` : 'DocManager'),
    resolve: (name) => {
        const pages = import.meta.glob('./Pages/**/*.jsx');
        const resolvePage = pages[`./Pages/${name}.jsx`];

        if (!resolvePage) {
            throw new Error(`Page not found: ${name}`);
        }

        return resolvePage();
    },
    setup({ el, App, props }) {
        createRoot(el).render(<App {...props} />);
    },
    progress: {
        color: '#0b74c7',
    },
});
