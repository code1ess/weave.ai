import { createServer } from 'vite';
import vue from '@vitejs/plugin-vue';
import { createSSRApp, h } from 'vue';
import { renderToString } from 'vue/server-renderer';
import { createInertiaApp } from '@inertiajs/vue3';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { setup as setupCssRender } from '@css-render/vue3-ssr';

const server = await createServer({
  configFile: false,
  envDir: false,
  plugins: [vue()],
  server: { middlewareMode: true, hmr: false, ws: false },
  appType: 'custom',
});
const empty = { data: [], prev_page_url: null, next_page_url: null };
const task = {
  id: 'task-one',
  kind: 'text_to_image',
  status: 'cancelled',
  moderation_status: 'pending',
  prompt: 'A mountain',
  asset_id: null,
  parameters: { aspect_ratio: '16:9', duration_seconds: null },
  mode_version: 1,
  created_at: '2026-01-01T12:00:00Z',
};
const metadataTask = {
  id: task.id,
  user_id: 1,
  kind: task.kind,
  status: task.status,
  moderation_status: task.moderation_status,
};
const modes = ['text_to_image', 'image_to_image', 'image_to_video'].map(
  (kind) => ({
    kind,
    active: false,
    aspect_ratios: ['1:1', '16:9'],
    pause_message: '模式维护中',
    version: 1,
  }),
);
const cases = [
  {
    page: 'Home',
    props: {
      announcements: [
        { id: 'notice', title: '公告', body: '<script>alert(1)</script>' },
      ],
    },
    text: 'Start creating',
    escaped: true,
  },
  { page: 'Auth', props: {}, text: 'Welcome back' },
  {
    page: 'Studio',
    props: {
      selectedTemplate: null,
      selectedAsset: null,
      tasks: empty,
      modes,
      pendingLimit: 10,
    },
    text: '模式维护中',
    disabled: true,
  },
  {
    page: 'Works',
    props: { tasks: { ...empty, data: [task] }, filters: {} },
    text: 'Cancelled',
  },
  {
    page: 'WorkDetail',
    props: { task, template: null, review: null, events: [] },
    text: 'Aspect ratio 16:9',
  },
  {
    page: 'Assets',
    props: {
      assets: {
        ...empty,
        data: [
          {
            id: 'asset',
            status: 'deleting',
            size: 1024,
            task_count: 0,
            created_at: task.created_at,
          },
        ],
      },
    },
    text: 'Retry deletion',
  },
  {
    page: 'Templates',
    props: { templates: empty, filters: {}, categories: [] },
    text: 'No published templates match your filters.',
  },
  {
    page: 'TemplateDetail',
    props: {
      template: {
        id: 'template',
        title: 'Template',
        description: 'Description',
        prompt: 'Prompt',
        kind: 'text_to_image',
        category: 'Scenes',
        version: 1,
        tags: ['自然'],
        cover_url: '/templates/template/cover',
      },
    },
    text: 'Use this template',
  },
  {
    page: 'Wallet',
    props: { wallet: { available: 100, reserved: 0 }, ledger: empty },
    text: 'My credits',
  },
  {
    page: 'Support',
    props: { taskId: null, tickets: empty },
    text: 'Submit ticket',
  },
  {
    page: 'SupportDetail',
    props: {
      ticket: {
        id: 'ticket',
        subject: 'Help',
        body: 'Question',
        status: 'resolved',
        task_id: null,
        created_at: task.created_at,
      },
      messages: empty,
    },
    text: 'Replying will reopen this resolved ticket.',
  },
  { page: 'Settings', props: {}, text: 'Change password' },
  { page: 'Help', props: { articles: empty }, text: 'Help center' },
  {
    page: 'HelpDetail',
    props: {
      article: {
        title: 'Help',
        body: '<script>alert(1)</script>',
        published_at: task.created_at,
      },
    },
    text: 'Back to help',
    escaped: true,
  },
  {
    page: 'Admin/Login',
    props: { admin: null },
    text: 'Sign in to administration',
    login: true,
  },
  {
    page: 'Admin/Dashboard',
    props: { counts: { users: 1, tasks: 1, pending: 1 } },
    text: 'Administration overview',
  },
  {
    page: 'Admin/Tasks',
    props: { search: '', moderation: '', records: empty },
    text: 'Generation tasks',
  },
  {
    page: 'Admin/TaskDetail',
    props: { task: metadataTask, review: null, events: [] },
    text: 'Your role can view task metadata only.',
  },
  {
    page: 'Admin/Templates',
    props: { templates: empty },
    text: 'New template',
  },
  { page: 'Admin/Content', props: { contents: empty }, text: 'New content' },
  {
    page: 'Admin/Users',
    props: { search: '', users: empty },
    text: 'User management',
  },
  {
    page: 'Admin/Accounts',
    props: { accounts: empty, roles: ['superadmin', 'support'] },
    text: 'Administrators & roles',
  },
  {
    page: 'Admin/GenerationModes',
    props: { modes, pendingLimit: 10 },
    text: 'Creation modes & intake',
  },
  {
    page: 'Admin/Support',
    props: { status: '', tickets: empty },
    text: 'Support tickets',
  },
  {
    page: 'Admin/SupportDetail',
    props: {
      ticket: {
        id: 'ticket',
        subject: 'Help',
        body: 'Question',
        status: 'open',
        task_id: null,
        created_at: task.created_at,
      },
      messages: empty,
    },
    text: 'Support response',
  },
  {
    page: 'Admin/Wallet',
    props: { userId: null, wallet: null, ledger: empty },
    text: 'Credit ledger & adjustments',
  },
  {
    page: 'Admin/Reports',
    props: {
      filters: { from: '2026-01-01', to: '2026-01-01' },
      timezone: 'UTC',
      summary: { registrations: 1, tasks: 1 },
      byStatus: [],
      byKind: [],
      byModeration: [],
      daily: [],
      financial: {
        increased: '9007199254740993',
        decreased: '0',
        net: '9007199254740993',
        available: '9007199254740993',
        reserved: '0',
      },
    },
    text: '9,007,199,254,740,993',
  },
  {
    page: 'Admin/Records',
    props: { title: 'Audit log', search: null, records: empty },
    text: 'Audit log',
  },
];

const chineseAdminUi = {
  scope: 'admin',
  locale: 'zh',
  theme: 'dark',
  direction: 'ltr',
  locales: { zh: '简体中文' },
  messages: JSON.parse(readFileSync('lang/zh.json', 'utf8')),
};
cases.push(
  {
    page: 'Admin/Login',
    url: '/admin/login',
    props: { admin: null, ui: chineseAdminUi },
    text: '登录后台',
    login: true,
  },
  {
    page: 'Admin/Dashboard',
    url: '/admin',
    props: {
      counts: { users: 0, tasks: 0, pending: 0 },
      ui: chineseAdminUi,
      admin: {
        name: '<script>Admin</script>',
        role: 'reviewer',
        permissions: ['console', 'tasks.view'],
      },
    },
    text: '快捷入口',
    hiddenLinks: [
      '/admin/templates',
      '/admin/wallet',
      '/admin/accounts',
      '/admin/support',
    ],
    visibleLinks: ['/admin/tasks'],
    escaped: true,
  },
  {
    page: 'Admin/Dashboard',
    url: '/admin',
    props: {
      counts: { users: 2, tasks: 4, pending: 1 },
      ui: chineseAdminUi,
      admin: {
        name: 'Support Admin',
        role: 'support',
        permissions: ['console', 'tasks.view', 'users.view', 'support.manage'],
      },
    },
    text: '客服人员',
    hiddenLinks: [
      '/admin/templates',
      '/admin/wallet',
      '/admin/accounts',
      '/admin/audit',
    ],
    visibleLinks: ['/admin/tasks', '/admin/users', '/admin/support'],
  },
  {
    page: 'Admin/TaskDetail',
    url: '/admin/tasks/task-one?from=history',
    props: { task: metadataTask, review: null, events: [], ui: chineseAdminUi },
    text: '任务详情',
    activeLink: '/admin/tasks',
  },
);

const adminPaths = {
  Dashboard: '/admin',
  Tasks: '/admin/tasks',
  TaskDetail: '/admin/tasks/task-one',
  Templates: '/admin/templates',
  Content: '/admin/content',
  Users: '/admin/users',
  Accounts: '/admin/accounts',
  GenerationModes: '/admin/generation-modes',
  Support: '/admin/support',
  SupportDetail: '/admin/support/ticket',
  Wallet: '/admin/wallet',
  Reports: '/admin/reports',
  Records: '/admin/audit',
  Login: '/admin/login',
};

for (const locale of [
  'en',
  'de',
  'ja',
  'ko',
  'es',
  'ar',
  'fr',
  'pt',
  'id',
  'hi',
  'vi',
  'nl',
  'pl',
  'tr',
  'it',
  'no',
  'fil',
  'zh',
]) {
  const messages = JSON.parse(readFileSync(`lang/${locale}.json`, 'utf8'));
  for (const theme of ['light', 'dark']) {
    cases.push({
      page: 'Home',
      props: {
        announcements: [],
        ui: {
          locale,
          theme,
          direction: locale === 'ar' ? 'rtl' : 'ltr',
          locales: { [locale]: locale },
          messages,
        },
      },
      text: messages['Create an image.'],
      theme,
    });
  }
}

try {
  for (const scenario of cases) {
    const admin = scenario.page.startsWith('Admin/');
    const directory = admin ? 'admin' : 'user';
    const name = scenario.page.replace('Admin/', '');
    const component = (
      await server.ssrLoadModule(`/resources/js/${directory}/pages/${name}.vue`)
    ).default;
    component.layout = (
      await server.ssrLoadModule(`/resources/js/${directory}/Layout.vue`)
    ).default;
    const warnings = [];
    const result = await createInertiaApp({
      page: {
        component: scenario.page,
        props: {
          errors: {},
          auth: {
            user: { id: 1, name: 'Render User', email: 'render@example.test' },
          },
          admin: admin
            ? {
                name: 'Render Admin',
                role: 'superadmin',
                permissions: [
                  'console',
                  'users.manage',
                  'tasks.view',
                  'support.manage',
                  'accounts.manage',
                  'templates.manage',
                  'content.manage',
                  'reports.view',
                  'wallet.manage',
                  'generation.manage',
                  'audit.view',
                ],
              }
            : null,
          ...scenario.props,
        },
        url: scenario.url ?? (admin ? adminPaths[name] : '/'),
        version: null,
      },
      render: renderToString,
      resolve: () => component,
      setup({ App, props, plugin }) {
        const app = createSSRApp({ render: () => h(App, props) }).use(plugin);
        setupCssRender(app);
        app.config.warnHandler = (warning) => warnings.push(warning);
        return app;
      },
    });
    assert.ok(
      result.body.includes(scenario.text.replaceAll('&', '&amp;')),
      `${scenario.page}: expected visible text`,
    );
    assert.deepEqual(warnings, [], `${scenario.page}: Vue rendering warnings`);
    if (admin) {
      assert.ok(
        !result.body.includes('interface-controls'),
        'Admin has no appearance or locale configuration controls',
      );
      assert.ok(
        !result.body.includes('NaN'),
        'Empty task counts must not produce invalid chart values',
      );
      if (scenario.login) {
        assert.ok(
          !result.body.includes('admin-sidebar'),
          'Login is separate from the authenticated workspace',
        );
      } else {
        assert.ok(
          result.body.includes('id="admin-workspace"'),
          'Admin pages render inside the workspace',
        );
      }
      for (const href of scenario.hiddenLinks ?? []) {
        assert.ok(
          !result.body.includes(`href="${href}"`),
          `Unauthorized navigation must be hidden: ${href}`,
        );
      }
      for (const href of scenario.visibleLinks ?? []) {
        assert.ok(
          result.body.includes(`href="${href}"`),
          `Authorized navigation must remain available: ${href}`,
        );
      }
      if (scenario.activeLink) {
        const anchors = result.body.match(/<a\b[^>]*>/g) ?? [];
        assert.ok(
          anchors.some(
            (anchor) =>
              anchor.includes(`href="${scenario.activeLink}"`) &&
              anchor.includes('aria-current="page"'),
          ),
          'Detail pages highlight their parent menu',
        );
      }
    }
    if (scenario.escaped) {
      assert.ok(
        result.body.includes('&lt;script&gt;'),
        `${scenario.page}: content must be escaped`,
      );
      assert.ok(!result.body.includes('<script>alert(1)</script>'));
    }
    if (scenario.theme) {
      const messages = scenario.props.ui.messages;
      const title =
        scenario.theme === 'dark' ? messages['明亮主题'] : messages['深色主题'];
      assert.ok(
        result.body.includes(`aria-label="${title}"`),
        `${scenario.props.ui.locale}: correct theme control`,
      );
    }
    if (scenario.disabled) {
      assert.match(result.body, /<button[^>]*disabled/);
    }
    console.log(`Rendered ${scenario.page}`);
  }
  console.log(`${cases.length} Vue pages rendered without runtime warnings.`);
} finally {
  await server.close();
}
