import { test, expect } from '@playwright/test';

test.describe('Clients Module', () => {
  test('lists clients from fixtures', async ({ page }) => {
    await page.goto('/clients');
    const body = await page.textContent('body');
    // Fixture clients should be visible
    expect(body).toContain('SCI Les Jardins');
    expect(body).toContain('Mairie de Bordeaux');
  });

  test('show client detail page', async ({ page }) => {
    await page.goto('/clients/1');
    const response = await page.goto('/clients/1');
    expect(response?.status()).toBe(200);
    const body = await page.textContent('body');
    expect(body!.length).toBeGreaterThan(100);
  });

  test('new client form loads', async ({ page }) => {
    const response = await page.goto('/clients/new');
    expect(response?.status()).toBe(200);
    // Form should have email and nom fields
    await expect(page.locator('input[name*="email"], input[id*="email"]').first()).toBeVisible();
    await expect(page.locator('input[name*="nom"], input[id*="nom"]').first()).toBeVisible();
  });

  test('create a new client via form', async ({ page }) => {
    await page.goto('/clients/new');

    // Fill required fields — use any visible input matching the pattern
    const nomInput = page.locator('input[id$="_nom"], input[name*="[nom]"]').first();
    const emailInput = page.locator('input[id$="_email"], input[name*="[email]"]').first();
    await nomInput.fill('TestEntreprise');
    await emailInput.fill(`test-${Date.now()}@example.com`);

    // Fill optional fields if visible
    const prenomInput = page.locator('input[id$="_prenom"], input[name*="[prenom]"]').first();
    if (await prenomInput.isVisible()) await prenomInput.fill('Jean');

    // Submit
    await page.locator('button[type="submit"], input[type="submit"]').first().click();

    // Should redirect to either client list or show page
    await page.waitForURL(/\/clients/, { timeout: 10000 });
  });

  test('edit client form loads', async ({ page }) => {
    const response = await page.goto('/clients/1/edit');
    expect(response?.status()).toBe(200);
    // Form fields should be pre-filled
    const nomValue = await page.locator('input[id$="_nom"]').first().inputValue();
    expect(nomValue.length).toBeGreaterThan(0);
  });

  test('delete buttons exist on client list', async ({ page }) => {
    await page.goto('/clients');
    // Verify delete forms/buttons are present
    const deleteForms = page.locator('form[action*="/delete"]');
    const count = await deleteForms.count();
    expect(count).toBeGreaterThan(0);
  });
});
