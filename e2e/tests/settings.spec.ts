import { test, expect } from '@playwright/test';

test.describe('Settings Module', () => {
  test('settings page loads', async ({ page }) => {
    const response = await page.goto('/settings');
    expect(response?.status()).toBe(200);
  });

  test('settings page shows cabinet info from fixtures', async ({ page }) => {
    await page.goto('/settings');
    const body = await page.textContent('body');
    // Fixture settings
    expect(
      body!.includes('Mercier') || body!.includes('mercier') ||
      body!.includes('Bordeaux') || body!.includes('bordeaux') ||
      body!.includes('cabinet') || body!.includes('Cabinet')
    ).toBeTruthy();
  });

  test('settings export endpoint responds', async ({ request }) => {
    const response = await request.get('/settings/export');
    expect(response.status()).toBe(200);
  });
});
