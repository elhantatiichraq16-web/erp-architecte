import { test, expect } from '@playwright/test';

test.describe('Time Entries Module', () => {
  test('lists time entries', async ({ page }) => {
    await page.goto('/time');
    const response = await page.goto('/time');
    expect(response?.status()).toBe(200);
    const body = await page.textContent('body');
    // Should contain some hours data from fixtures
    expect(body!.length).toBeGreaterThan(200);
  });

  test('new time entry form loads with selectors', async ({ page }) => {
    const response = await page.goto('/time/new');
    expect(response?.status()).toBe(200);
    // Should have collaborator and project dropdowns
    await expect(page.locator('select[id$="_collaborator"]').first()).toBeVisible();
    await expect(page.locator('select[id$="_project"]').first()).toBeVisible();
  });

  test('time rapport page loads', async ({ page }) => {
    const response = await page.goto('/time/rapport');
    expect(response?.status()).toBe(200);
  });
});

test.describe('Expenses Module', () => {
  test('lists expenses', async ({ page }) => {
    const response = await page.goto('/expenses');
    expect(response?.status()).toBe(200);
    const body = await page.textContent('body');
    expect(body!.length).toBeGreaterThan(200);
  });

  test('new expense form loads', async ({ page }) => {
    const response = await page.goto('/expenses/new');
    expect(response?.status()).toBe(200);
    await expect(page.locator('select[id$="_project"]').first()).toBeVisible();
    await expect(page.locator('select[id$="_categorie"]').first()).toBeVisible();
  });

  test('expenses show categories as badges', async ({ page }) => {
    await page.goto('/expenses');
    const body = await page.textContent('body');
    // Fixture data has these categories
    expect(
      body!.includes('placement') ||
      body!.includes('Impression') ||
      body!.includes('Logiciel') ||
      body!.includes('Fourniture') ||
      body!.includes('Divers') ||
      body!.includes('déplacement')
    ).toBeTruthy();
  });
});
