<script setup lang="ts">
import { useInterface } from '../../shared/interface';

import { ref } from 'vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { NCard, NInput, NButton, NTag, NAlert } from 'naive-ui';

const { t } = useInterface();

const props = defineProps<{
  search: string;
  users: {
    data: { id: number; name: string; email: string; active: boolean }[];
    prev_page_url: string | null;
    next_page_url: string | null;
  };
}>();
const page = usePage<{ admin: { permissions: string[] } }>();
const query = ref(props.search);
const form = useForm({ active: false, reason: '' });

function setStatus(id: number, active: boolean) {
  form.active = active;
  form.put(`/admin/users/${id}/status`, { preserveScroll: true });
}
</script>
<template>
  <Head :title="t('用户管理')" />
  <h1>{{ t('用户管理') }}</h1>
  <form
    style="display: flex; gap: 16px; margin-bottom: 24px"
    @submit.prevent="router.get('/admin/users', { search: query })"
  >
    <NInput
      v-model:value="query"
      :placeholder="t('搜索姓名或邮箱')"
      :maxlength="100"
    /><NButton attr-type="submit">{{ t('搜索') }}</NButton>
  </form>
  <div
    v-if="page.props.admin.permissions.includes('users.manage')"
    style="margin-bottom: 24px"
  >
    <label
      >{{ t('状态变更原因')
      }}<NInput
        v-model:value="form.reason"
        :maxlength="1000"
        :placeholder="t('必填，写入审计')" /></label
    ><NAlert v-for="error in form.errors" :key="error" type="error">{{
      error
    }}</NAlert>
  </div>
  <p v-if="!users.data.length">{{ t('暂无符合条件的用户。') }}</p>
  <NCard v-for="user in users.data" :key="user.id" style="margin-bottom: 16px"
    ><h2>
      {{ user.name }} <small>#{{ user.id }}</small>
    </h2>
    <p>{{ user.email }}</p>
    <NTag :type="user.active ? 'success' : 'error'">{{
      user.active ? t('正常') : t('已停用')
    }}</NTag
    ><NButton
      v-if="page.props.admin.permissions.includes('users.manage')"
      :disabled="form.processing"
      style="margin-left: 16px"
      @click="setStatus(user.id, !user.active)"
      >{{ user.active ? t('停用账号') : t('恢复账号') }}</NButton
    ></NCard
  >
  <nav style="display: flex; gap: 24px">
    <Link v-if="users.prev_page_url" :href="users.prev_page_url">{{
      t('上一页')
    }}</Link
    ><Link v-if="users.next_page_url" :href="users.next_page_url">{{
      t('下一页')
    }}</Link>
  </nav>
</template>
