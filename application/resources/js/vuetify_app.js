import './bootstrap';
import '../css/vuetify_app.css';

import {createApp, h} from 'vue';
import {createInertiaApp} from '@inertiajs/vue3';
import {resolvePageComponent} from 'laravel-vite-plugin/inertia-helpers';
import {ZiggyVue} from '../../vendor/tightenco/ziggy';
import ToastPlugin from 'vue-toast-notification';
import 'vue-toast-notification/dist/theme-bootstrap.css';
import VuetifyUseDialog from 'vuetify-use-dialog'

import '@mdi/font/css/materialdesignicons.css'
import 'vuetify/styles'
import {createVuetify} from 'vuetify'
import * as components from 'vuetify/components'
import * as directives from 'vuetify/directives'

const theme = {
    defaultTheme: 'light',
    options: {
        customProperties: true,
    },
    themes: {
        light: {
            colors: {
                primary: '#1ED980',
                background: '#F7F8FA'
            }
        },
        dark: {
            colors: {
                primary: '#1ED980'
            }
        }
    }
};

const Vuetify = createVuetify({
    icons: {
        defaultSet: 'mdi'
    },
    theme,
    components: {
        ...components,
        // VNumberInput,
    },
    directives: {
        ...directives,
    },
});

const appName = import.meta.env.VITE_APP_NAME || 'RewardEngine';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({el, App, props, plugin}) {
        return createApp({render: () => h(App, props)})
            .use(plugin)
            .use(ZiggyVue)
            .use(ToastPlugin)
            .use(Vuetify)
            .use(VuetifyUseDialog)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
