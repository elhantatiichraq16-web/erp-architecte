import { test, expect } from '@playwright/test';

test.describe('Quotes Module', () => {
  test('lists quotes with numbers', async ({ page }) => {
    await page.goto('/quotes');
    const body = await page.textContent('body');
    expect(body).toContain('DEV-2026');
  });

  test('quote detail shows lines and totals', async ({ page }) => {
    await page.goto('/quotes/1');
    const body = await page.textContent('body');
    // Should show amount
    expect(body).toMatch(/\d+[\s,.]?\d*\s*€/);
  });

  test('new quote form loads', async ({ page }) => {
    const response = await page.goto('/quotes/new');
    expect(response?.status()).toBe(200);
  });

  test('quote status badges displayed', async ({ page }) => {
    await page.goto('/quotes');
    const badges = page.locator('.badge');
    expect(await badges.count()).toBeGreaterThan(0);
  });
});

test.describe('Invoices Module', () => {
  test('lists invoices with numbers', async ({ page }) => {
    await page.goto('/invoices');
    const body = await page.textContent('body');
    expect(body).toContain('FAC-2026');
  });

  test('invoice detail shows lines and totals', async ({ page }) => {
    await page.goto('/invoices/1');
    const body = await page.textContent('body');
    expect(body).toMatch(/\d+[\s,.]?\d*\s*€/);
  });

  test('invoice print page renders', async ({ page }) => {
    const response = await page.goto('/invoices/1/print');
    expect(response?.status()).toBe(200);
    const body = await page.textContent('body');
    // Should contain invoice data
    expect(body).toMatch(/FAC-2026|facture/i);
  });

  test('invoice statuses are displayed with badges', async ({ page }) => {
    await page.goto('/invoices');
    const badges = page.locator('.badge');
    expect(await badges.count()).toBeGreaterThan(0);
  });

  test('new invoice form loads', async ({ page }) => {
    const response = await page.goto('/invoices/new');
    expect(response?.status()).toBe(200);
  });
});
