import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, DefineComponent, h } from 'vue';
import { ZiggyVue } from 'ziggy-js';
import { 
  Folder, ListTodo, CheckCircle, Circle, 
  ArrowRight, Check, Plus, FolderPlus, ListPlus 
} from 'lucide-vue-next';

const appName = import.meta.env.VITE_APP_NAME || 'MyTask';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob<DefineComponent>('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .component('Folder', Folder)
            .component('ListTodo', ListTodo)
            .component('CheckCircle', CheckCircle)
            .component('Circle', Circle)
            .component('ArrowRight', ArrowRight)
            .component('Check', Check)
            .component('Plus', Plus)
            .component('FolderPlus', FolderPlus)
            .component('ListPlus', ListPlus)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
