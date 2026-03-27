import { test, expect } from '@playwright/test';

test.describe('Dashboard', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/');
  });

  test('displays KPI cards', async ({ page }) => {
    // Should have at least some visible cards/content
    const body = await page.textContent('body');
    expect(body).toBeTruthy();
    // Check for typical KPI keywords (from our fixtures data)
    const pageContent = body!.toLowerCase();
    expect(
      pageContent.includes('chiffre') ||
      pageContent.includes('projet') ||
      pageContent.includes('heure') ||
      pageContent.includes('impayé') ||
      pageContent.includes('€')
    ).toBeTruthy();
  });

  test('contains Chart.js canvases', async ({ page }) => {
    const canvases = page.locator('canvas');
    const count = await canvases.count();
    // Should have at least the CA chart and donut chart
    expect(count).toBeGreaterThanOrEqual(1);
  });

  test('dashboard page loads without server errors', async ({ page }) => {
    const response = await page.goto('/');
    expect(response?.status()).toBe(200);
    // Verify page has rendered meaningful content
    const body = await page.textContent('body');
    expect(body!.length).toBeGreaterThan(500);
  });
});
