export const taskStatusLabels: Record<string, string> = {
  pending: '等待处理',
  cancelled: '已取消',
  rejected: '审核拒绝',
  processing: '处理中',
  succeeded: '已完成',
  failed: '失败',
};
export const taskKindLabels: Record<string, string> = {
  text_to_image: '文字生图',
  image_to_image: '图片编辑',
  image_to_video: '照片动画',
};
export interface TaskRecord {
  parameters: { aspect_ratio: string; duration_seconds: number | null } | null;
  mode_version: number | null;
  id: string;
  kind: string;
  status: string;
  moderation_status: string;
  prompt: string;
  asset_id: string | null;
  created_at: string;
}
