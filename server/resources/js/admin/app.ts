import '../../css/admin.css';
import { createApp, h, type Component, type DefineComponent } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import Layout from './Layout.vue';
const pages = import.meta.glob<{
  default: DefineComponent & { layout?: Component };
}>('./pages/*.vue');

createInertiaApp({
  resolve: async (name) => {
    const page = (await pages[`./pages/${name.replace('Admin/', '')}.vue`]())
      .default;
    page.layout = Layout;
    return page;
  },
  setup({ el, App, props, plugin }) {
    createApp({ render: () => h(App, props) })
      .use(plugin)
      .mount(el!);
  },
});
