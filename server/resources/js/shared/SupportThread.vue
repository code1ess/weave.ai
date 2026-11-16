<script setup lang="ts">
import { useInterface } from './interface';

import { Head, Link, useForm } from '@inertiajs/vue3';
import type { SupportThreadProps } from './support';

const { t, formatDate } = useInterface();

const props = defineProps<SupportThreadProps>();
const form = useForm({
  body: '',
  idempotency_key: crypto.randomUUID(),
  ...(props.staffView ? { status: 'in_progress' } : {}),
});
const labels: Record<string, string> = {
  open: t('待处理'),
  in_progress: t('处理中'),
  resolved: t('已解决'),
};

function reply() {
  form.post(
    `${props.staffView ? '/admin' : ''}/support/${props.ticket.id}/messages`,
    {
      onSuccess: () => {
        form.reset('body');
        form.idempotency_key = crypto.randomUUID();
      },
    },
  );
}
</script>
<template>
  <Head :title="ticket.subject" />
  <div class="thread">
    <Link :href="staffView ? '/admin/support' : '/support'">{{
      t('← 返回工单列表')
    }}</Link>
    <h1>{{ ticket.subject }}</h1>
    <p>
      {{ labels[ticket.status] }} ·
      {{ formatDate(ticket.created_at) }}
    </p>
    <p v-if="ticket.task_id" class="meta">
      {{ t('关联任务：') }}{{ ticket.task_id }}
    </p>
    <article>
      <p class="body">{{ ticket.body }}</p>
    </article>
    <h2>{{ t('沟通记录（最新在前）') }}</h2>
    <p v-if="!messages.data.length">{{ t('暂无回复。') }}</p>
    <article v-for="message in messages.data" :key="message.id">
      <strong>{{
        message.admin_user_id ? t('客服回复') : t('用户回复')
      }}</strong
      ><time>{{ formatDate(message.created_at) }}</time>
      <p class="body">{{ message.body }}</p>
    </article>
    <nav>
      <Link v-if="messages.prev_page_url" :href="messages.prev_page_url">{{
        t('较新消息')
      }}</Link
      ><Link v-if="messages.next_page_url" :href="messages.next_page_url">{{
        t('较早消息')
      }}</Link>
    </nav>
    <form @submit.prevent="reply">
      <h2>{{ staffView ? t('客服处理') : t('补充问题') }}</h2>
      <p v-if="!staffView && ticket.status === 'resolved'">
        {{ t('补充回复将重新打开工单。') }}
      </p>
      <label
        >{{ t('回复内容')
        }}<textarea
          v-model="form.body"
          required
          maxlength="5000"
          rows="5"
        /></label
      ><label v-if="staffView"
        >{{ t('处理状态')
        }}<select v-model="form.status">
          <option value="in_progress">{{ t('处理中') }}</option>
          <option value="resolved">{{ t('已解决') }}</option>
        </select></label
      >
      <p v-for="error in form.errors" :key="error" role="alert" class="error">
        {{ error }}
      </p>
      <button :disabled="form.processing">
        {{ form.processing ? t('提交中…') : t('提交回复') }}
      </button>
    </form>
  </div>
</template>
<style scoped>
.thread {
  max-width: 900px;
  margin: auto;
  font-family: system-ui, sans-serif;
}
h1 {
  font-size: 28px;
  margin: 24px 0;
}
h2 {
  font-size: 20px;
  margin: 24px 0 16px;
}
article,
form {
  background: var(--surface, #fff);
  border: 1px solid var(--line, #deded8);
  border-radius: 16px;
  padding: 24px;
  margin: 16px 0;
}
.body {
  white-space: pre-wrap;
  overflow-wrap: anywhere;
  line-height: 1.8;
}
time {
  display: block;
  font-size: 12px;
  color: var(--muted, #777);
  margin-top: 8px;
}
label {
  display: block;
  margin: 16px 0;
}
textarea,
select {
  display: block;
  margin-top: 8px;
  padding: 12px;
  width: 100%;
  box-sizing: border-box;
  border: 1px solid var(--line, #aaa);
  background: var(--surface, #fff);
  color: var(--text, #222);
  border-radius: 8px;
  font: inherit;
}
button {
  background: var(--button, #252924);
  color: var(--button-text, #fff);
  padding: 12px 24px;
  border: 0;
  border-radius: 24px;
  cursor: pointer;
}
button:disabled {
  opacity: 0.5;
}
nav {
  display: flex;
  gap: 24px;
}
.error {
  color: var(--error, #b42318);
}
.meta {
  font-size: 12px;
  overflow-wrap: anywhere;
}
</style>
