import '../../css/app.css';
import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import Layout from './Layout.vue';

const pages = import.meta.glob('./pages/*.vue', { eager: true });

createInertiaApp({
  resolve: (name) => {
    const page = (pages[`./pages/${name}.vue`] as any).default;
    page.layout = Layout;
    return page;
  },
  setup({ el, App, props, plugin }) {
    createApp({ render: () => h(App, props) })
      .use(plugin)
      .mount(el!);
  },
});
