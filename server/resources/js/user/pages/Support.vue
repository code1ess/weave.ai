<script setup lang="ts">
import { useInterface } from '../../shared/interface';

import { Head, Link, useForm } from '@inertiajs/vue3';

const { t, formatDate } = useInterface();

const props = defineProps<{
  taskId: string | null;
  tickets: {
    data: { id: string; subject: string; status: string; updated_at: string }[];
    prev_page_url: string | null;
    next_page_url: string | null;
  };
}>();
const form = useForm({
  subject: '',
  body: '',
  task_id: props.taskId,
  idempotency_key: crypto.randomUUID(),
});
const labels: Record<string, string> = {
  open: t('待处理'),
  in_progress: t('处理中'),
  resolved: t('已解决'),
};

function submit() {
  form.post('/support');
}
</script>
<template>
  <Head :title="t('帮助与反馈')" />
  <p class="text-sm text-orange-700">HERE TO HELP</p>
  <h1 class="mt-3 mb-8 text-4xl">{{ t('帮助与反馈') }}</h1>
  <div class="grid gap-8 md:grid-cols-2">
    <section class="rounded-3xl bg-white p-8">
      <h2 class="mb-5 text-xl">{{ t('提交问题') }}</h2>
      <p v-if="taskId" class="mb-5 break-all text-xs text-stone-500">
        {{ t('关联任务') }} {{ taskId }}
      </p>
      <form class="space-y-5" @submit.prevent="submit">
        <label class="block"
          >{{ t('问题标题')
          }}<input
            v-model="form.subject"
            required
            maxlength="160"
            class="mt-2 w-full rounded-lg border p-3" /></label
        ><label class="block"
          >{{ t('详细描述')
          }}<textarea
            v-model="form.body"
            required
            maxlength="5000"
            rows="6"
            class="mt-2 w-full rounded-lg border p-3"
          />
        </label>
        <p class="text-xs text-stone-500">
          {{ t('请勿发送密码、支付密钥或银行卡信息。') }}
        </p>
        <p
          v-for="error in form.errors"
          :key="error"
          role="alert"
          class="text-red-700"
        >
          {{ error }}
        </p>
        <button
          :disabled="form.processing"
          class="rounded-full bg-stone-900 px-6 py-3 text-white disabled:opacity-50"
        >
          {{ t('提交工单') }}
        </button>
      </form>
    </section>
    <section>
      <h2 class="mb-5 text-xl">{{ t('我的工单') }}</h2>
      <p v-if="!tickets.data.length" class="p-8 text-stone-500">
        {{ t('暂无工单。') }}
      </p>
      <Link
        v-for="ticket in tickets.data"
        :key="ticket.id"
        :href="`/support/${ticket.id}`"
        class="mb-4 block rounded-xl border bg-white p-5"
        ><span class="text-xs text-orange-700">{{
          labels[ticket.status]
        }}</span>
        <h3 class="mt-3">{{ ticket.subject }}</h3>
        <time class="mt-3 block text-xs text-stone-400">{{
          formatDate(ticket.updated_at)
        }}</time></Link
      >
      <nav class="mt-5 flex gap-6">
        <Link v-if="tickets.prev_page_url" :href="tickets.prev_page_url">{{
          t('上一页')
        }}</Link
        ><Link v-if="tickets.next_page_url" :href="tickets.next_page_url">{{
          t('下一页')
        }}</Link>
      </nav>
    </section>
  </div>
</template>
