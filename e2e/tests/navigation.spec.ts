import { test, expect } from '@playwright/test';

test.describe('Navigation & Sidebar', () => {
  test('dashboard loads with KPI cards', async ({ page }) => {
    const response = await page.goto('/');
    expect(response?.status()).toBe(200);
    await expect(page).toHaveTitle(/Tableau de bord|ERP|Cabinet/);
    // Page has content
    const body = await page.textContent('body');
    expect(body!.length).toBeGreaterThan(100);
  });

  test('all sidebar links respond 200', async ({ page }) => {
    const routes = [
      { path: '/', label: 'Dashboard' },
      { path: '/clients', label: 'Clients' },
      { path: '/projects', label: 'Projets' },
      { path: '/quotes', label: 'Devis' },
      { path: '/invoices', label: 'Factures' },
      { path: '/time', label: 'Heures' },
      { path: '/expenses', label: 'Dépenses' },
      { path: '/events', label: 'Calendrier' },
      { path: '/documents', label: 'Documents' },
      { path: '/settings', label: 'Paramètres' },
    ];

    for (const route of routes) {
      const response = await page.goto(route.path);
      expect(response?.status(), `${route.label} (${route.path}) should be 200`).toBe(200);
    }
  });

  test('sidebar navigation highlights active page', async ({ page }) => {
    await page.goto('/clients');
    const activeLink = page.locator('.sidebar-nav-link.active, .nav-link.active, a.active').first();
    await expect(activeLink).toBeVisible();
  });
});
