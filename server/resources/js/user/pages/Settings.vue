<script setup lang="ts">
import { useInterface } from '../../shared/interface';

import { Head, useForm, usePage } from '@inertiajs/vue3';

const { t } = useInterface();

const page = usePage<{ auth: { user: { name: string; email: string } } }>();
const profile = useForm({ name: page.props.auth.user.name });
const password = useForm({
  current_password: '',
  password: '',
  password_confirmation: '',
});

function savePassword() {
  password.put('/settings/password', { onFinish: () => password.reset() });
}
</script>
<template>
  <Head :title="t('账号设置')" />
  <h1 class="mb-8 text-4xl">{{ t('账号设置') }}</h1>
  <div class="grid gap-8 md:grid-cols-2">
    <section class="rounded-3xl bg-white p-8">
      <h2 class="mb-6 text-xl">{{ t('个人资料') }}</h2>
      <p class="mb-6 break-all text-sm text-stone-500">
        {{ t('登录邮箱：') }}{{ page.props.auth.user.email }}
      </p>
      <form class="space-y-5" @submit.prevent="profile.put('/settings')">
        <label class="block"
          >{{ t('昵称')
          }}<input
            v-model="profile.name"
            required
            maxlength="100"
            autocomplete="name"
            class="mt-2 w-full rounded-lg border p-3"
        /></label>
        <p
          v-for="error in profile.errors"
          :key="error"
          role="alert"
          class="text-red-700"
        >
          {{ error }}
        </p>
        <p
          v-if="profile.recentlySuccessful"
          role="status"
          class="text-green-700"
        >
          {{ t('资料已保存。') }}
        </p>
        <button
          :disabled="profile.processing"
          class="rounded-full bg-stone-900 px-6 py-3 text-white disabled:opacity-50"
        >
          {{ t('保存资料') }}
        </button>
      </form>
    </section>
    <section class="rounded-3xl bg-white p-8">
      <h2 class="mb-6 text-xl">{{ t('修改密码') }}</h2>
      <form class="space-y-5" @submit.prevent="savePassword">
        <label class="block"
          >{{ t('当前密码')
          }}<input
            v-model="password.current_password"
            type="password"
            required
            autocomplete="current-password"
            class="mt-2 w-full rounded-lg border p-3" /></label
        ><label class="block"
          >{{ t('新密码（至少 12 位）')
          }}<input
            v-model="password.password"
            type="password"
            required
            minlength="12"
            autocomplete="new-password"
            class="mt-2 w-full rounded-lg border p-3" /></label
        ><label class="block"
          >{{ t('确认新密码')
          }}<input
            v-model="password.password_confirmation"
            type="password"
            required
            minlength="12"
            autocomplete="new-password"
            class="mt-2 w-full rounded-lg border p-3"
        /></label>
        <p
          v-for="error in password.errors"
          :key="error"
          role="alert"
          class="text-red-700"
        >
          {{ error }}
        </p>
        <p
          v-if="password.recentlySuccessful"
          role="status"
          class="text-green-700"
        >
          {{ t('密码已更新，其他会话访问时将退出。') }}
        </p>
        <button
          :disabled="password.processing"
          class="rounded-full bg-stone-900 px-6 py-3 text-white disabled:opacity-50"
        >
          {{ t('更新密码') }}
        </button>
      </form>
    </section>
  </div>
</template>
