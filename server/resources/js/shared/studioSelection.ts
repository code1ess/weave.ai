import { watch } from 'vue';

export interface TemplateSelection {
  template_id: string;
  version: number;
  title: string;
  kind: string;
  prompt: string;
}

interface StudioSelectionForm {
  kind: string;
  prompt: string;
  template_id: string | null;
  template_version: number | null;
  asset_id: string | null;
  idempotency_key: string;
  parameters?: { aspect_ratio: string; duration_seconds: number | null };
  clearErrors: () => void;
}

export function synchronizeStudioSelection(
  form: StudioSelectionForm,
  template: () => TemplateSelection | null,
  asset: () => string | null,
  mode: () => string | null = () => null,
) {
  watch(
    () => {
      const selected = template();
      return selected ? `${selected.template_id}:${selected.version}` : null;
    },
    () => {
      const selected = template();
      form.kind =
        selected?.kind ??
        mode() ??
        (asset() ? 'image_to_image' : 'text_to_image');
      form.prompt = selected?.prompt ?? '';
      form.template_id = selected?.template_id ?? null;
      form.template_version = selected?.version ?? null;
      form.clearErrors();
    },
  );
  watch(asset, (selected) => {
    if (!selected) {
      return;
    }

    form.asset_id = selected;
    if (!form.template_id) {
      form.kind = 'image_to_image';
    }
    form.clearErrors();
  });
  watch(mode, (selected) => {
    if (selected && !template()) {
      form.kind = selected;
      form.clearErrors();
    }
  });
  watch(
    () => [
      form.kind,
      form.prompt,
      form.asset_id,
      form.template_id,
      form.template_version,
      form.parameters?.aspect_ratio,
      form.parameters?.duration_seconds,
    ],
    () => {
      form.idempotency_key = crypto.randomUUID();
    },
    { flush: 'sync' },
  );
}
