<script setup lang="ts">
import { useInterface } from '../../shared/interface';

import { Head, Link } from '@inertiajs/vue3';

const { t, formatDate, formatNumber } = useInterface();

defineProps<{
  wallet: { available: number; reserved: number };
  ledger: {
    data: {
      id: string;
      amount: number;
      available_after: number;
      created_at: string;
    }[];
    prev_page_url: string | null;
    next_page_url: string | null;
  };
}>();
</script>
<template>
  <Head :title="t('我的钱包')" />
  <p class="text-sm text-orange-700">YOUR CREATIVE BALANCE</p>
  <h1 class="mt-3 text-4xl">{{ t('我的点数') }}</h1>
  <section class="my-8 grid gap-6 md:grid-cols-2">
    <article class="rounded-3xl bg-stone-900 p-8 text-white">
      <h2 class="text-sm text-stone-300">{{ t('可用点数') }}</h2>
      <p class="mt-5 text-5xl">{{ formatNumber(wallet.available) }}</p>
    </article>
    <article class="rounded-3xl border border-stone-200 bg-white p-8">
      <h2 class="text-sm text-stone-500">{{ t('预占点数') }}</h2>
      <p class="mt-5 text-5xl">{{ formatNumber(wallet.reserved) }}</p>
    </article>
  </section>
  <p class="mb-8 text-sm leading-6 text-stone-500">
    {{
      t(
        '充值尚未开放。当前额度仅用于内部测试；提交生成任务仅记录，不预占、不扣费。点数不代表现金余额。',
      )
    }}
  </p>
  <h2 class="mb-5 text-xl">{{ t('点数流水') }}</h2>
  <p
    v-if="!ledger.data.length"
    class="rounded-xl border border-dashed p-8 text-stone-500"
  >
    {{ t('暂无点数变动。') }}
  </p>
  <div v-else class="overflow-x-auto rounded-xl bg-white p-5">
    <table class="w-full text-left text-sm">
      <thead>
        <tr>
          <th class="p-3">{{ t('时间') }}</th>
          <th class="p-3">{{ t('变动') }}</th>
          <th class="p-3">{{ t('变动后余额') }}</th>
          <th class="p-3">{{ t('流水编号') }}</th>
        </tr>
      </thead>
      <tbody>
        <tr
          v-for="entry in ledger.data"
          :key="entry.id"
          class="border-t border-stone-100"
        >
          <td class="p-3 whitespace-nowrap">
            {{ formatDate(entry.created_at) }}
          </td>
          <td class="p-3">
            {{ entry.amount > 0 ? '+' : '' }}{{ entry.amount }}
          </td>
          <td class="p-3">{{ entry.available_after }}</td>
          <td class="p-3 font-mono text-xs">{{ entry.id }}</td>
        </tr>
      </tbody>
    </table>
  </div>
  <nav class="mt-6 flex gap-6">
    <Link v-if="ledger.prev_page_url" :href="ledger.prev_page_url">{{
      t('上一页')
    }}</Link
    ><Link v-if="ledger.next_page_url" :href="ledger.next_page_url">{{
      t('下一页')
    }}</Link>
  </nav>
</template>
