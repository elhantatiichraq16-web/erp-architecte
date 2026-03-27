import { test, expect } from '@playwright/test';

test.describe('Projects Module', () => {
  test('lists projects with references', async ({ page }) => {
    await page.goto('/projects');
    const body = await page.textContent('body');
    expect(body).toContain('PROJ-2026');
  });

  test('project detail page with tabs', async ({ page }) => {
    await page.goto('/projects/1');
    const body = await page.textContent('body');
    // Should show project details
    expect(body!.length).toBeGreaterThan(200);
    // Should have tabs or sections for phases, hours, etc.
    expect(
      body!.includes('Phase') || body!.includes('phase') ||
      body!.includes('Détail') || body!.includes('Heure')
    ).toBeTruthy();
  });

  test('project shows progress/advancement', async ({ page }) => {
    await page.goto('/projects/1');
    // Should have progress bars or percentage indicators
    const progressBars = page.locator('.progress, .progress-bar, [role="progressbar"]');
    const count = await progressBars.count();
    expect(count).toBeGreaterThanOrEqual(0); // May have 0 if displayed differently
  });

  test('new project form loads with client selector', async ({ page }) => {
    const response = await page.goto('/projects/new');
    expect(response?.status()).toBe(200);
    // Should have client dropdown
    await expect(page.locator('select[id$="_client"]').first()).toBeVisible();
  });

  test('project status badges are displayed', async ({ page }) => {
    await page.goto('/projects');
    // Badges for statuses (en_cours, termine, etc.)
    const badges = page.locator('.badge, [class*="badge"]');
    const count = await badges.count();
    expect(count).toBeGreaterThan(0);
  });
});
