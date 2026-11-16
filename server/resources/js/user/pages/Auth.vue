<script setup lang="ts">
import { useInterface } from '../../shared/interface';

import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';

const { t } = useInterface();

const register = ref(false);
const form = useForm({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
});

function submit() {
  form.post(register.value ? '/register' : '/login', {
    onFinish: () => form.reset('password', 'password_confirmation'),
  });
}
</script>
<template>
  <Head :title="t('账号')" />
  <section class="mx-auto max-w-md rounded-3xl bg-white p-8">
    <h1 class="mb-8 text-3xl">
      {{ register ? t('开启创作之旅') : t('欢迎回来') }}
    </h1>
    <form class="space-y-5" @submit.prevent="submit">
      <label v-if="register" class="block"
        >{{ t('昵称')
        }}<input
          v-model="form.name"
          required
          maxlength="100"
          autocomplete="name"
          class="mt-2 w-full rounded-lg border p-3" /></label
      ><label class="block"
        >{{ t('邮箱')
        }}<input
          v-model="form.email"
          type="email"
          required
          autocomplete="email"
          class="mt-2 w-full rounded-lg border p-3" /></label
      ><label class="block"
        >{{ t('密码')
        }}<input
          v-model="form.password"
          type="password"
          required
          :minlength="register ? 12 : 1"
          :autocomplete="register ? 'new-password' : 'current-password'"
          class="mt-2 w-full rounded-lg border p-3" /></label
      ><label v-if="register" class="block"
        >{{ t('确认密码')
        }}<input
          v-model="form.password_confirmation"
          type="password"
          required
          autocomplete="new-password"
          class="mt-2 w-full rounded-lg border p-3"
      /></label>
      <p
        v-for="error in form.errors"
        :key="error"
        role="alert"
        class="text-sm text-red-700"
      >
        {{ error }}
      </p>
      <button
        :disabled="form.processing"
        class="w-full rounded-full bg-stone-900 p-3 text-white disabled:opacity-50"
      >
        {{ register ? t('注册') : t('登录') }}
      </button>
    </form>
    <button
      class="mt-6 text-sm underline"
      @click="
        register = !register;
        form.clearErrors();
      "
    >
      {{ register ? t('已有账号？登录') : t('创建新账号') }}
    </button>
  </section>
</template>
