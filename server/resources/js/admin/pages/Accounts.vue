<script setup lang="ts">
import { useInterface } from '../../shared/interface';

import { Head, Link, useForm } from '@inertiajs/vue3';
import {
  NCard,
  NInput,
  NSelect,
  NButton,
  NSwitch,
  NFormItem,
  NAlert,
} from 'naive-ui';
import { ref } from 'vue';

const { t } = useInterface();

interface Account {
  id: number;
  name: string;
  email: string;
  role: string;
  active: boolean;
}
defineProps<{
  accounts: {
    data: Account[];
    prev_page_url: string | null;
    next_page_url: string | null;
  };
  roles: string[];
}>();
const selected = ref<Account | null>(null);
const form = useForm({ role: '', active: true, reason: '' });

function edit(account: Account) {
  selected.value = account;
  form.role = account.role;
  form.active = account.active;
  form.reason = '';
  form.clearErrors();
}

function save() {
  if (!selected.value) {
    return;
  }

  form.put(`/admin/accounts/${selected.value.id}`, {
    onSuccess: () => {
      selected.value = null;
      form.reset();
    },
  });
}
</script>
<template>
  <Head :title="t('管理员管理')" />
  <h1>{{ t('管理员与角色') }}</h1>
  <NAlert type="info" style="margin-bottom: 24px">{{
    t(
      '账号创建使用命令行 admin:create。此处可变更其他管理员的角色和状态；不能更改自己的权限。每次变更必须填写原因。',
    )
  }}</NAlert
  ><NCard
    v-if="selected"
    :title="t('编辑 {0}', { 0: selected.name })"
    style="margin-bottom: 24px"
    ><form @submit.prevent="save">
      <NFormItem :label="t('角色')"
        ><NSelect
          v-model:value="form.role"
          :options="
            roles.map((value) => ({ label: value, value }))
          " /></NFormItem
      ><NFormItem :label="t('账号启用')"
        ><NSwitch v-model:value="form.active" /></NFormItem
      ><NFormItem :label="t('变更原因')"
        ><NInput v-model:value="form.reason" :maxlength="1000" /></NFormItem
      ><NAlert v-for="error in form.errors" :key="error" type="error">{{
        error
      }}</NAlert
      ><NButton type="primary" attr-type="submit" :loading="form.processing">{{
        t('保存变更')
      }}</NButton
      ><NButton style="margin-left: 12px" @click="selected = null">{{
        t('取消')
      }}</NButton>
    </form></NCard
  ><NCard
    v-for="account in accounts.data"
    :key="account.id"
    :title="account.name"
    style="margin-bottom: 16px"
    ><p>
      {{ account.email }} · {{ account.role }} ·
      {{ account.active ? t('启用') : t('停用') }}
    </p>
    <NButton @click="edit(account)">{{ t('编辑角色与状态') }}</NButton></NCard
  >
  <nav style="display: flex; gap: 24px">
    <Link v-if="accounts.prev_page_url" :href="accounts.prev_page_url">{{
      t('上一页')
    }}</Link
    ><Link v-if="accounts.next_page_url" :href="accounts.next_page_url">{{
      t('下一页')
    }}</Link>
  </nav>
</template>
