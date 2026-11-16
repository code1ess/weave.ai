<script setup lang="ts">
import { computed, h, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import {
  NAvatar,
  NButton,
  NConfigProvider,
  NDrawer,
  NDrawerContent,
  NDropdown,
  NMenu,
  zhCN,
  enUS,
  deDE,
  jaJP,
  koKR,
  esES,
  arDZ,
  frFR,
  ptBR,
  idID,
  hiIN,
  viVN,
  nlNL,
  plPL,
  trTR,
  itIT,
  nbNO,
  filPH,
  type GlobalThemeOverrides,
  type MenuOption,
} from 'naive-ui';
import { useInterface } from '../shared/interface';
import AdminIcon from './AdminIcon.vue';
import { adminNavigation, findAdminNav } from './navigation';

const { t, locale } = useInterface();
const componentLocales = {
  en: enUS,
  de: deDE,
  ja: jaJP,
  ko: koKR,
  es: esES,
  ar: arDZ,
  fr: frFR,
  pt: ptBR,
  id: idID,
  hi: hiIN,
  vi: viVN,
  nl: nlNL,
  pl: plPL,
  tr: trTR,
  it: itIT,
  no: nbNO,
  fil: filPH,
  zh: zhCN,
};
const componentLocale = computed(
  () => componentLocales[locale.value as keyof typeof componentLocales] ?? enUS,
);
const page = usePage<{
  admin: { name: string; role: string; permissions: string[] } | null;
}>();
const collapsed = ref(false);
const mobile = ref(false);
const drawerOpen = ref(false);
const refreshing = ref(false);
const fullscreenAvailable = ref(false);
const tabs = ref<{ path: string; href: string; label: string }[]>([]);
const isLogin = computed(() => page.component === 'Admin/Login');
const navigation = computed(() =>
  adminNavigation.filter((item) =>
    page.props.admin?.permissions.includes(item.permission),
  ),
);
const activeItem = computed(() => findAdminNav(page.url, navigation.value));
const currentPath = computed(() =>
  page.url.split(/[?#]/)[0].replace(/\/$/, ''),
);
const currentTitle = computed(() => {
  if (page.component === 'Admin/TaskDetail') {
    return '任务详情';
  }
  if (page.component === 'Admin/SupportDetail') {
    return '工单详情';
  }

  return activeItem.value?.label ?? '工作台';
});
const roles: Record<string, string> = {
  superadmin: '超级管理员',
  operations: '运营人员',
  support: '客服人员',
  finance: '财务人员',
  reviewer: '审核人员',
};
const roleLabel = computed(() =>
  t(roles[page.props.admin?.role ?? ''] ?? '管理员'),
);
const themeOverrides: GlobalThemeOverrides = {
  common: {
    primaryColor: '#646cff',
    primaryColorHover: '#8187ff',
    primaryColorPressed: '#5158df',
    primaryColorSuppl: '#646cff',
    borderRadius: '8px',
    bodyColor: '#f5f7fb',
    cardColor: '#ffffff',
    textColorBase: '#333647',
    fontFamily:
      'Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", "PingFang SC", "Microsoft YaHei", sans-serif',
  },
  Card: {
    borderColor: '#eef0f5',
    paddingMedium: '22px',
    titleFontSizeMedium: '15px',
  },
  Menu: {
    itemHeight: '44px',
    itemColorActive: '#eef0ff',
    itemTextColorActive: '#646cff',
    itemIconColorActive: '#646cff',
    borderRadius: '7px',
  },
};
const menuOptions = computed<MenuOption[]>(() => {
  const groups = [...new Set(navigation.value.map((item) => item.group))];

  return groups.map((group) => ({
    type: 'group',
    key: group,
    label: t(group),
    children: navigation.value
      .filter((item) => item.group === group)
      .map((item) => ({
        key: item.href,
        props: {
          'aria-label': t(item.label),
          title: collapsed.value ? t(item.label) : undefined,
          tabindex: collapsed.value ? 0 : undefined,
          onKeydown: (event: KeyboardEvent) => {
            if (
              collapsed.value &&
              (event.key === 'Enter' || event.key === ' ')
            ) {
              event.preventDefault();
              navigateMenu(item.href);
            }
          },
        },
        label: () =>
          h(
            Link,
            {
              href: item.href,
              tabindex: collapsed.value ? -1 : undefined,
              'aria-current':
                activeItem.value?.href === item.href ? 'page' : undefined,
              onClick: (event: MouseEvent) => {
                event.stopPropagation();
                drawerOpen.value = false;
              },
            },
            () => t(item.label),
          ),
        icon: () => h(AdminIcon, { name: item.icon }),
      })),
  }));
});
const userOptions = computed(() => [
  {
    key: 'logout',
    label: () =>
      h(
        Link,
        {
          href: '/admin/logout',
          method: 'post',
          as: 'button',
          class: 'admin-logout',
        },
        () => t('退出'),
      ),
    icon: () => h(AdminIcon, { name: 'logout' }),
  },
]);

watch(
  [() => page.url, () => page.props.admin],
  () => {
    drawerOpen.value = false;
    if (isLogin.value || !page.props.admin) {
      tabs.value = [];
      return;
    }

    tabs.value = tabs.value.filter((tab) =>
      findAdminNav(tab.path, navigation.value),
    );
    if (!tabs.value.some((tab) => tab.path === '/admin')) {
      tabs.value.unshift({ path: '/admin', href: '/admin', label: '概览' });
    }
    const existing = tabs.value.find((tab) => tab.path === currentPath.value);
    if (existing) {
      existing.href = page.url;
      existing.label = currentTitle.value;
    } else if (activeItem.value) {
      tabs.value.push({
        path: currentPath.value,
        href: page.url,
        label: currentTitle.value,
      });
    }
    if (tabs.value.length > 12) {
      tabs.value.splice(1, 1);
    }
  },
  { immediate: true },
);

function closeTab(path: string) {
  if (path === '/admin') {
    return;
  }

  const index = tabs.value.findIndex((tab) => tab.path === path);
  tabs.value = tabs.value.filter((tab) => tab.path !== path);
  if (currentPath.value === path) {
    router.visit(tabs.value[Math.max(0, index - 1)]?.href ?? '/admin');
  }
}

function toggleSidebar() {
  if (mobile.value) {
    drawerOpen.value = !drawerOpen.value;
    return;
  }

  collapsed.value = !collapsed.value;
}

function navigateMenu(href: string) {
  if (!navigation.value.some((item) => item.href === href)) {
    return;
  }

  drawerOpen.value = false;
  router.visit(href);
}

function refreshPage() {
  if (refreshing.value) {
    return;
  }

  refreshing.value = true;
  router.reload({
    onFinish: () => {
      refreshing.value = false;
    },
  });
}

async function toggleFullscreen() {
  try {
    if (document.fullscreenElement) {
      await document.exitFullscreen();
    } else {
      await document.documentElement.requestFullscreen();
    }
  } catch {
    fullscreenAvailable.value = false;
  }
}

let mediaQuery: MediaQueryList | undefined;

function updateViewport() {
  mobile.value = mediaQuery?.matches ?? false;
  drawerOpen.value = false;
}

onMounted(() => {
  mediaQuery = window.matchMedia('(max-width: 900px)');
  updateViewport();
  mediaQuery.addEventListener('change', updateViewport);
  fullscreenAvailable.value = document.fullscreenEnabled;
});

onBeforeUnmount(() => {
  mediaQuery?.removeEventListener('change', updateViewport);
});
</script>

<template>
  <NConfigProvider
    :locale="componentLocale"
    :theme="null"
    :theme-overrides="themeOverrides"
  >
    <div v-if="isLogin" class="admin-app admin-auth">
      <div class="admin-auth-brand">
        <span class="admin-brand-mark">W</span><strong>WeaveAI</strong
        ><span>{{ t('管理后台') }}</span>
      </div>
      <main class="admin-auth-content"><slot /></main>
      <footer class="admin-auth-footer">
        WeaveAI · {{ t('创作运营工作台') }}
      </footer>
    </div>
    <div
      v-else
      class="admin-app admin-shell"
      :class="{ 'is-collapsed': collapsed }"
    >
      <a class="admin-skip" href="#admin-workspace">{{ t('跳到工作区') }}</a>
      <aside id="admin-sidebar" class="admin-sidebar" :aria-label="t('主菜单')">
        <Link href="/admin" class="admin-brand" :title="t('WeaveAI 管理后台')">
          <span class="admin-brand-mark">W</span
          ><span class="admin-brand-text"
            >WeaveAI <small>{{ t('管理后台') }}</small></span
          >
        </Link>
        <nav class="admin-menu-scroll" :aria-label="t('后台导航')">
          <NMenu
            :value="activeItem?.href"
            :options="menuOptions"
            :collapsed="collapsed"
            :collapsed-width="68"
            :collapsed-icon-size="20"
            :indent="18"
            @update:value="navigateMenu"
          />
        </nav>
        <div class="admin-sidebar-footer">
          <span class="admin-status-dot"></span
          ><span v-if="!collapsed">{{ t('创作运营工作台') }}</span>
        </div>
      </aside>
      <div class="admin-main">
        <header class="admin-header">
          <div class="admin-header-left">
            <NButton
              quaternary
              circle
              class="admin-collapse"
              :aria-label="
                t(mobile ? '打开菜单' : collapsed ? '展开菜单' : '收起菜单')
              "
              :aria-expanded="mobile ? drawerOpen : !collapsed"
              aria-controls="admin-sidebar"
              @click="toggleSidebar"
            >
              <AdminIcon :name="mobile || collapsed ? 'menu' : 'fold'" />
            </NButton>
            <nav class="admin-breadcrumb" :aria-label="t('当前位置')">
              <Link href="/admin">{{ t('工作台') }}</Link
              ><template v-if="activeItem && activeItem.group !== '工作台'"
                ><span>/</span><span>{{ t(activeItem.group) }}</span></template
              ><span>/</span><strong>{{ t(currentTitle) }}</strong>
            </nav>
            <span class="admin-mobile-brand">WeaveAI</span>
          </div>
          <div class="admin-header-right">
            <NButton
              v-if="fullscreenAvailable"
              quaternary
              circle
              class="admin-fullscreen"
              :title="t('切换全屏')"
              :aria-label="t('切换全屏')"
              @click="toggleFullscreen"
              ><AdminIcon name="expand"
            /></NButton>
            <a href="/" class="admin-site-link" :title="t('访问站点')"
              >{{ t('访问站点') }}<AdminIcon name="arrow"
            /></a>
            <span class="admin-header-divider"></span>
            <NDropdown
              trigger="click"
              :options="userOptions"
              placement="bottom-end"
            >
              <button
                class="admin-user"
                type="button"
                :aria-label="t('用户菜单')"
                aria-haspopup="menu"
              >
                <NAvatar
                  :size="34"
                  round
                  color="#eef0ff"
                  :style="{ color: '#646cff', fontWeight: '600' }"
                  >{{
                    page.props.admin?.name.slice(0, 1).toUpperCase()
                  }}</NAvatar
                >
                <span class="admin-user-info"
                  ><strong>{{ page.props.admin?.name }}</strong
                  ><small>{{ roleLabel }}</small></span
                ><AdminIcon name="down" />
              </button>
            </NDropdown>
          </div>
        </header>
        <div class="admin-tabs-bar">
          <nav class="admin-tabs" :aria-label="t('已打开的页面')">
            <div
              v-for="tab in tabs"
              :key="tab.path"
              class="admin-tab"
              :class="{ 'is-active': currentPath === tab.path }"
            >
              <Link
                :href="tab.href"
                :aria-current="currentPath === tab.path ? 'page' : undefined"
                ><AdminIcon v-if="tab.path === '/admin'" name="home" /><span
                  v-else
                  class="admin-tab-dot"
                ></span
                >{{ t(tab.label) }}</Link
              >
              <button
                v-if="tab.path !== '/admin'"
                type="button"
                :aria-label="`${t('关闭页面')}：${t(tab.label)}`"
                @click="closeTab(tab.path)"
              >
                <AdminIcon name="close" />
              </button>
            </div>
          </nav>
          <NButton
            quaternary
            circle
            size="small"
            :loading="refreshing"
            :title="t('刷新页面')"
            :aria-label="t('刷新页面')"
            @click="refreshPage"
            ><AdminIcon name="refresh"
          /></NButton>
        </div>
        <main
          id="admin-workspace"
          class="admin-workspace"
          tabindex="-1"
          scroll-region
        >
          <div class="admin-page"><slot /></div>
          <footer class="admin-footer">
            WeaveAI <span>·</span> {{ t('创作运营工作台') }}
          </footer>
        </main>
      </div>
      <NDrawer
        v-model:show="drawerOpen"
        :width="264"
        placement="left"
        :native-scrollbar="false"
      >
        <NDrawerContent
          :title="t('WeaveAI 管理后台')"
          closable
          :body-content-style="{ padding: '8px' }"
        >
          <nav :aria-label="t('移动端后台导航')">
            <NMenu
              :value="activeItem?.href"
              :options="menuOptions"
              :indent="18"
              @update:value="navigateMenu"
            />
          </nav>
        </NDrawerContent>
      </NDrawer>
    </div>
  </NConfigProvider>
</template>
