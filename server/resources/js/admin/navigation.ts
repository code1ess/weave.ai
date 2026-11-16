import type { AdminIconName } from './icons';

export interface AdminNavItem {
  href: string;
  label: string;
  permission: string;
  group: string;
  icon: AdminIconName;
}

export const adminNavigation: AdminNavItem[] = [
  {
    href: '/admin',
    label: '概览',
    permission: 'console',
    group: '工作台',
    icon: 'home',
  },
  {
    href: '/admin/tasks',
    label: '生成任务',
    permission: 'tasks.view',
    group: '创作管理',
    icon: 'tasks',
  },
  {
    href: '/admin/templates',
    label: '模板管理',
    permission: 'templates.manage',
    group: '创作管理',
    icon: 'template',
  },
  {
    href: '/admin/generation-modes',
    label: '创作模式',
    permission: 'generation.manage',
    group: '创作管理',
    icon: 'image',
  },
  {
    href: '/admin/users',
    label: '用户查询',
    permission: 'users.view',
    group: '业务运营',
    icon: 'users',
  },
  {
    href: '/admin/support',
    label: '客服工单',
    permission: 'support.manage',
    group: '业务运营',
    icon: 'support',
  },
  {
    href: '/admin/wallet',
    label: '点数账本',
    permission: 'wallet.manage',
    group: '业务运营',
    icon: 'wallet',
  },
  {
    href: '/admin/content',
    label: '运营内容',
    permission: 'content.manage',
    group: '业务运营',
    icon: 'content',
  },
  {
    href: '/admin/reports',
    label: '运营报表',
    permission: 'reports.view',
    group: '业务运营',
    icon: 'chart',
  },
  {
    href: '/admin/accounts',
    label: '管理员',
    permission: 'accounts.manage',
    group: '系统管理',
    icon: 'shield',
  },
  {
    href: '/admin/audit',
    label: '操作审计',
    permission: 'audit.view',
    group: '系统管理',
    icon: 'audit',
  },
];

export function findAdminNav(url: string, items: AdminNavItem[]) {
  const path = url.split(/[?#]/)[0].replace(/\/$/, '');

  return items.find(
    (item) =>
      path === item.href ||
      (item.href !== '/admin' && path.startsWith(`${item.href}/`)),
  );
}
