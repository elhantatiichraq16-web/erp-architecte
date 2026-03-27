import { test, expect } from '@playwright/test';

test.describe('Calendar Module', () => {
  test('calendar page loads', async ({ page }) => {
    const response = await page.goto('/events');
    expect(response?.status()).toBe(200);
  });

  test('events API returns JSON array', async ({ request }) => {
    const response = await request.get('/events/api/events');
    expect(response.status()).toBe(200);
    const contentType = response.headers()['content-type'];
    expect(contentType).toContain('json');
    const data = await response.json();
    expect(Array.isArray(data)).toBeTruthy();
    // Fixtures have 12+ events
    expect(data.length).toBeGreaterThan(0);
  });

  test('events API returns proper event format', async ({ request }) => {
    const response = await request.get('/events/api/events');
    expect(response.status()).toBe(200);
    const text = await response.text();
    const data = JSON.parse(text);
    expect(Array.isArray(data)).toBeTruthy();
    if (data.length > 0) {
      const event = data[0];
      expect(event).toHaveProperty('id');
      expect(event).toHaveProperty('title');
      expect(event).toHaveProperty('start');
    }
  });

  test('new event form loads', async ({ page }) => {
    const response = await page.goto('/events/new');
    expect(response?.status()).toBe(200);
  });
});

test.describe('Documents Module', () => {
  test('lists documents', async ({ page }) => {
    const response = await page.goto('/documents');
    expect(response?.status()).toBe(200);
    const body = await page.textContent('body');
    // Fixtures have documents with these categories
    expect(
      body!.includes('Plan') || body!.includes('plan') ||
      body!.includes('CCTP') || body!.includes('PV') ||
      body!.includes('Administratif') || body!.includes('administratif')
    ).toBeTruthy();
  });

  test('new document form loads', async ({ page }) => {
    const response = await page.goto('/documents/new');
    expect(response?.status()).toBe(200);
    await expect(page.locator('select[id$="_project"]').first()).toBeVisible();
    await expect(page.locator('select[id$="_categorie"]').first()).toBeVisible();
  });
});
