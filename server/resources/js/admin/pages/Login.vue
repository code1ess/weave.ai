<script setup lang="ts">
import { useInterface } from '../../shared/interface';

import { Head, useForm } from '@inertiajs/vue3';
import { NCard, NInput, NButton, NFormItem, NAlert } from 'naive-ui';

const { t } = useInterface();

const form = useForm({ email: '', password: '' });

function submit() {
  form.post('/admin/login', { onFinish: () => form.reset('password') });
}
</script>
<template>
  <Head :title="t('管理员登录')" /><NCard
    :title="t('管理员登录')"
    class="admin-login-card"
    :bordered="false"
    ><p class="admin-login-intro">{{ t('登录以管理您的创作平台') }}</p>
    <form @submit.prevent="submit">
      <NFormItem :label="t('邮箱')"
        ><NInput
          v-model:value="form.email"
          :placeholder="t('管理员邮箱')"
          :input-props="{
            type: 'email',
            autocomplete: 'username',
            required: true,
          }" /></NFormItem
      ><NFormItem :label="t('密码')"
        ><NInput
          v-model:value="form.password"
          type="password"
          :input-props="{
            autocomplete: 'current-password',
            required: true,
          }" /></NFormItem
      ><NAlert v-for="error in form.errors" :key="error" type="error">{{
        error
      }}</NAlert
      ><NButton
        attr-type="submit"
        type="primary"
        size="large"
        block
        :loading="form.processing"
        :disabled="form.processing"
        >{{ t('登录后台') }}</NButton
      >
    </form></NCard
  >
</template>

<style scoped>
.admin-login-card {
  width: min(420px, calc(100vw - 40px));
  box-shadow: 0 12px 60px #464d8f12;
  border-radius: 14px;
  padding: 12px;
}
.admin-login-intro {
  color: #9297a7;
  margin: -8px 0 28px;
  font-size: 13px;
}
.admin-login-card :deep(.n-card-header__main) {
  font-size: 22px;
  font-weight: 600;
}
.admin-login-card :deep(.n-alert) {
  margin-bottom: 16px;
}
</style>
