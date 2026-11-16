import { test } from 'node:test';
import assert from 'node:assert/strict';
import { effectScope, reactive, ref, nextTick } from 'vue';
import { synchronizeStudioSelection } from './studioSelection.ts';

function fixture(templateValue = null) {
  const template = ref(templateValue);
  const asset = ref(null);
  const mode = ref(null);
  const form = reactive({
    kind: templateValue?.kind ?? 'text_to_image',
    prompt: 'Unsubmitted draft',
    template_id: templateValue?.template_id ?? null,
    template_version: templateValue?.version ?? null,
    asset_id: null,
    idempotency_key: crypto.randomUUID(),
    clearErrors() {},
  });
  const scope = effectScope();
  scope.run(() =>
    synchronizeStudioSelection(
      form,
      () => template.value,
      () => asset.value,
      () => mode.value,
    ),
  );
  return { form, template, asset, mode, scope };
}

test('refreshing the same template preserves unsent edits and retry key', async () => {
  const selected = {
    template_id: 'template-a',
    version: 1,
    kind: 'text_to_image',
    prompt: 'Template prompt',
    title: 'A',
  };
  const state = fixture(selected);
  const key = state.form.idempotency_key;
  state.template.value = { ...selected };
  await nextTick();
  assert.equal(state.form.prompt, 'Unsubmitted draft');
  assert.equal(state.form.idempotency_key, key);
  state.scope.stop();
});

test('selecting a new template changes the input and submission key', async () => {
  const state = fixture();
  const key = state.form.idempotency_key;
  state.template.value = {
    template_id: 'template-b',
    version: 2,
    kind: 'image_to_video',
    prompt: 'Motion',
    title: 'B',
  };
  await nextTick();
  assert.equal(state.form.prompt, 'Motion');
  assert.equal(state.form.template_version, 2);
  assert.notEqual(state.form.idempotency_key, key);
  state.scope.stop();
});

test('reusing an asset works on the mounted page and edits get a new key', async () => {
  const state = fixture();
  state.asset.value = 'asset-one';
  await nextTick();
  assert.equal(state.form.asset_id, 'asset-one');
  assert.equal(state.form.kind, 'image_to_image');
  const key = state.form.idempotency_key;
  state.form.prompt = 'New description';
  assert.notEqual(state.form.idempotency_key, key);
  state.scope.stop();
});

test('changing output parameters gets a new key while refreshes preserve it', () => {
  const state = fixture();
  state.form.parameters = { aspect_ratio: '1:1', duration_seconds: null };
  const key = state.form.idempotency_key;
  state.form.parameters.aspect_ratio = '16:9';
  assert.notEqual(state.form.idempotency_key, key);
  state.scope.stop();
});

test('sidebar mode navigation updates the mounted studio without reloading', async () => {
  const state = fixture();
  state.mode.value = 'image_to_video';
  await nextTick();
  assert.equal(state.form.kind, 'image_to_video');
  state.scope.stop();
});
