import { test, expect } from '@playwright/test';

test('user logs in, records a task, views history and cancels it', async ({
  page,
}) => {
  const errors: string[] = [];
  page.on('pageerror', (error) => errors.push(error.message));
  await page.goto('/login');
  await page.getByLabel('Email', { exact: true }).fill('browser@example.test');
  await page
    .getByLabel('Password', { exact: true })
    .fill('Browser-test-password-123');
  await page.getByRole('button', { name: 'Sign in', exact: true }).click();
  await expect(page).toHaveURL(/\/studio$/);
  await page
    .getByLabel('Describe your idea')
    .fill('Browser flow: mountain sunrise');
  await page.getByRole('button', { name: 'Record creative request' }).click();
  await expect(page.getByText('Browser flow: mountain sunrise')).toBeVisible();
  await page.getByRole('link', { name: 'My creations', exact: true }).click();
  await page
    .getByRole('link')
    .filter({ hasText: 'Browser flow: mountain sunrise' })
    .click();
  await expect(
    page.getByRole('heading', { name: 'Creation details', exact: true }),
  ).toBeVisible();
  await page.getByRole('button', { name: 'Cancel task', exact: true }).click();
  await expect(page.getByText('Cancelled', { exact: true })).toBeVisible();
  expect(errors).toEqual([]);
});

test('administrator uses the sidebar, workspace tabs and mobile menu', async ({
  page,
}, testInfo) => {
  const errors: string[] = [];
  page.on('pageerror', (error) => errors.push(error.message));
  await page.goto('/admin/login');
  await expect(page.locator('html')).toHaveAttribute('lang', 'zh');
  await page.getByPlaceholder('管理员邮箱').fill('admin@example.test');
  await page
    .locator('input[type="password"]')
    .fill('Browser-test-password-123');
  await page.getByRole('button', { name: '登录后台' }).click();
  await expect(page).toHaveURL(/\/admin$/);
  await expect(
    page.getByRole('heading', { name: '管理概览', exact: true }),
  ).toBeVisible();
  const navigation = page.getByRole('navigation', {
    name: '后台导航',
    exact: true,
  });
  const tabs = page.getByRole('navigation', { name: '已打开的页面' });
  await expect(page.getByRole('button', { name: '用户菜单' })).toContainText(
    'Browser Admin',
  );
  await expect(page.locator('.interface-controls')).toHaveCount(0);
  await page.screenshot({
    path: testInfo.outputPath('admin-desktop.png'),
    fullPage: true,
  });
  await page.getByRole('button', { name: '收起菜单', exact: true }).click();
  await expect(page.locator('.admin-shell')).toHaveClass(/is-collapsed/);
  await navigation
    .getByRole('menuitem', { name: '生成任务', exact: true })
    .click();
  await expect(page).toHaveURL(/\/admin\/tasks$/);
  await page.getByRole('button', { name: '展开菜单', exact: true }).click();
  for (const [name, heading] of [
    ['模板管理', '模板管理'],
    ['运营内容', '公告与帮助内容'],
    ['用户查询', '用户管理'],
    ['客服工单', '客服工单'],
  ]) {
    await navigation.getByRole('link', { name, exact: true }).click();
    await expect(
      page.getByRole('heading', { name: heading, exact: true }).first(),
    ).toBeVisible();
    await expect(tabs.getByRole('link', { name, exact: true })).toHaveAttribute(
      'aria-current',
      'page',
    );
  }
  await page
    .getByRole('button', { name: '关闭页面：客服工单', exact: true })
    .click();
  await expect(page).toHaveURL(/\/admin\/users$/);
  await expect(
    tabs.getByRole('link', { name: '客服工单', exact: true }),
  ).toHaveCount(0);
  await page.getByRole('button', { name: '刷新页面', exact: true }).click();
  await expect(
    page.getByRole('heading', { name: '用户管理', exact: true }),
  ).toBeVisible();
  await page.setViewportSize({ width: 390, height: 844 });
  await expect(page.locator('.admin-sidebar')).not.toBeVisible();
  await page.getByRole('button', { name: '打开菜单', exact: true }).click();
  const mobileNavigation = page.getByRole('navigation', {
    name: '移动端后台导航',
  });
  await expect(mobileNavigation).toBeVisible();
  await mobileNavigation
    .getByRole('link', { name: '生成任务', exact: true })
    .click();
  await expect(page).toHaveURL(/\/admin\/tasks$/);
  await expect(mobileNavigation).not.toBeVisible();
  await expect(
    page.getByRole('heading', { name: '生成任务', exact: true }),
  ).toBeVisible();
  expect(
    await page.evaluate(
      () => document.documentElement.scrollWidth <= window.innerWidth,
    ),
  ).toBe(true);
  await page.screenshot({
    path: testInfo.outputPath('admin-mobile.png'),
    fullPage: true,
  });
  await page.getByRole('button', { name: '用户菜单' }).click();
  await page.getByRole('button', { name: '退出', exact: true }).click();
  await expect(page).toHaveURL(/\/admin\/login$/);
  await expect(page.locator('.admin-sidebar')).toHaveCount(0);
  expect(errors).toEqual([]);
});
