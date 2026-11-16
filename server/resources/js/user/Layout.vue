<script setup lang="ts">
import { ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { useInterface } from '../shared/interface';
import InterfaceControls from '../shared/InterfaceControls.vue';

const { t } = useInterface();

const page = usePage<{ auth: { user: { name: string } | null } }>();
const menuOpen = ref(false);
const primary = [
  { href: '/', key: '发现', icon: '◈' },
  { href: '/templates', key: '模板库', icon: '▦' },
  { href: '/works', key: '我的创作', icon: '▧' },
  { href: '/assets', key: '素材库', icon: '▤' },
];
const tools = [
  { href: '/studio?kind=text_to_image', key: '文字生图', icon: '✧' },
  { href: '/studio?kind=image_to_image', key: '图片编辑', icon: '◩' },
  { href: '/studio?kind=image_to_video', key: '照片动画', icon: '▷' },
];

function active(href: string) {
  return href === '/'
    ? page.url === '/'
    : page.url.split('?')[0] === href.split('?')[0] &&
        (href.includes('?') ? page.url.includes(href.split('?')[1]) : true);
}
</script>
<template>
  <div class="weave-shell">
    <div v-if="menuOpen" class="sidebar-scrim" @click="menuOpen = false" />
    <aside
      class="weave-sidebar"
      :class="{ 'is-open': menuOpen }"
      :aria-label="t('Main navigation')"
    >
      <Link href="/" class="weave-logo" dir="ltr" @click="menuOpen = false"
        ><span class="logo-mark" aria-hidden="true">w</span>Weave<span
          class="logo-ai"
          >AI</span
        ></Link
      >
      <Link href="/studio" class="sidebar-create" @click="menuOpen = false"
        ><span>＋</span>{{ t('开始创作')
        }}<span aria-hidden="true">↗</span></Link
      >
      <nav class="sidebar-links">
        <Link
          v-for="item in primary"
          :key="item.href"
          :href="item.href"
          :class="{ active: active(item.href) }"
          @click="menuOpen = false"
          ><span class="nav-icon" aria-hidden="true">{{ item.icon }}</span
          >{{ t(item.key) }}</Link
        >
      </nav>
      <p class="sidebar-caption">{{ t('创作工具') }}</p>
      <nav class="sidebar-links">
        <Link
          v-for="item in tools"
          :key="item.href"
          :href="item.href"
          :class="{ active: active(item.href) }"
          @click="menuOpen = false"
          ><span class="nav-icon" aria-hidden="true">{{ item.icon }}</span
          >{{ t(item.key) }}</Link
        >
      </nav>
      <div class="sidebar-bottom">
        <nav class="sidebar-links">
          <Link href="/wallet" @click="menuOpen = false"
            ><span class="nav-icon" aria-hidden="true">◇</span
            >{{ t('钱包') }}</Link
          ><Link href="/help" @click="menuOpen = false"
            ><span class="nav-icon" aria-hidden="true">ⓘ</span
            >{{ t('帮助') }}</Link
          ><Link
            v-if="page.props.auth.user"
            href="/settings"
            @click="menuOpen = false"
            ><span class="nav-icon" aria-hidden="true">⚙</span
            >{{ t('设置') }}</Link
          >
        </nav>
        <div class="sidebar-note">
          <span class="status-dot" />{{
            t('Recorded requests only. No generation or charges yet.')
          }}
        </div>
      </div>
    </aside>
    <div class="weave-workspace">
      <header class="weave-topbar">
        <div class="topbar-start">
          <button
            type="button"
            class="mobile-menu"
            :aria-label="t('Toggle navigation')"
            :aria-expanded="menuOpen"
            @click="menuOpen = !menuOpen"
          >
            ☰</button
          ><span class="topbar-wordmark">{{ t('Your creative space') }}</span>
        </div>
        <div class="topbar-actions">
          <InterfaceControls /><template v-if="page.props.auth.user"
            ><span class="user-avatar" :title="page.props.auth.user.name">{{
              page.props.auth.user.name.slice(0, 1).toUpperCase()
            }}</span
            ><Link
              href="/logout"
              method="post"
              as="button"
              class="topbar-login"
              >{{ t('退出') }}</Link
            ></template
          ><Link v-else href="/login" class="topbar-login"
            >{{ t('登录') }}<span aria-hidden="true">↗</span></Link
          >
        </div>
      </header>
      <main class="weave-main"><slot /></main>
      <footer class="weave-footer">
        <span>© {{ new Date().getFullYear() }} WeaveAI</span
        ><Link href="/help">{{ t('帮助中心') }}</Link
        ><Link href="/support">{{ t('联系支持') }}</Link>
      </footer>
    </div>
  </div>
</template>
