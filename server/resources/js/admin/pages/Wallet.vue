<script setup lang="ts">
import { useInterface } from '../../shared/interface';

import { computed, ref } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
  NCard,
  NInput,
  NInputNumber,
  NButton,
  NAlert,
  NFormItem,
  NDataTable,
} from 'naive-ui';

const { t } = useInterface();

const props = defineProps<{
  userId: number | null;
  wallet: { available: number; reserved: number } | null;
  ledger: {
    data: Record<string, string | number | null>[];
    prev_page_url: string | null;
    next_page_url: string | null;
  };
}>();
const filterId = ref(props.userId);
const form = useForm({
  user_id: props.userId as number | null,
  amount: null as number | null,
  reason: '',
  idempotency_key: crypto.randomUUID(),
});
const columns = computed(() =>
  [
    'id',
    'user_id',
    'amount',
    'available_after',
    'reason',
    'admin_user_id',
    'created_at',
  ].map((key) => ({ title: key, key })),
);

function filter() {
  router.get(
    '/admin/wallet',
    filterId.value ? { user_id: filterId.value } : {},
  );
}

function adjust() {
  form.post('/admin/wallet/adjustments', {
    onSuccess: () => {
      form.reset('amount', 'reason');
      form.idempotency_key = crypto.randomUUID();
    },
  });
}
</script>
<template>
  <Head :title="t('点数账本')" />
  <h1>{{ t('点数账本与调整') }}</h1>
  <NAlert type="warning" style="margin-bottom: 20px">{{
    t(
      '内部测试额度和人工调整均写入不可覆盖流水。错误通过新增反向调整修正。当前没有真实支付充值入口。',
    )
  }}</NAlert>
  <form
    style="display: flex; gap: 12px; margin-bottom: 24px"
    @submit.prevent="filter"
  >
    <NInputNumber
      v-model:value="filterId"
      :min="1"
      :precision="0"
      :placeholder="t('用户 ID')"
    /><NButton attr-type="submit">{{ t('查询用户') }}</NButton>
  </form>
  <p v-if="userId">
    {{ t('用户') }} {{ userId }} {{ t('· 可用') }} {{ wallet?.available ?? 0 }}
    {{ t('· 预占') }} {{ wallet?.reserved ?? 0 }}
  </p>
  <NCard :title="t('新增调整')" style="margin-bottom: 24px"
    ><form @submit.prevent="adjust">
      <NFormItem :label="t('用户 ID')"
        ><NInputNumber
          v-model:value="form.user_id"
          :min="1"
          :precision="0" /></NFormItem
      ><NFormItem :label="t('整数点数（正数增加，负数扣回）')"
        ><NInputNumber
          v-model:value="form.amount"
          :min="-1000000"
          :max="1000000"
          :precision="0" /></NFormItem
      ><NFormItem :label="t('调整原因')"
        ><NInput
          v-model:value="form.reason"
          type="textarea"
          :maxlength="1000" /></NFormItem
      ><NAlert v-for="error in form.errors" :key="error" type="error">{{
        error
      }}</NAlert
      ><NButton attr-type="submit" type="primary" :loading="form.processing">{{
        t('记录调整')
      }}</NButton>
    </form></NCard
  ><NDataTable :columns="columns" :data="ledger.data" :scroll-x="1200" />
  <nav style="display: flex; gap: 24px; margin-top: 20px">
    <Link v-if="ledger.prev_page_url" :href="ledger.prev_page_url">{{
      t('上一页')
    }}</Link
    ><Link v-if="ledger.next_page_url" :href="ledger.next_page_url">{{
      t('下一页')
    }}</Link>
  </nav>
</template>
