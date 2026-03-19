import i18n from 'i18next';
import HttpBackend from 'i18next-http-backend';
import { initReactI18next } from 'react-i18next';

i18n.use(HttpBackend)
    .use(initReactI18next)
    .init({
        fallbackLng: 'sv',
        interpolation: { escapeValue: false },
        backend: {
            loadPath: '/api/translations/{{lng}}',
        },
    });

export default i18n;
