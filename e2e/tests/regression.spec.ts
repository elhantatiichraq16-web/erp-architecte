import { test, expect } from '@playwright/test';

/**
 * Non-regression tests: ensure no page returns 500
 * and core features remain functional across deployments.
 */
test.describe('Non-Regression: All Pages No 500', () => {
  const pages = [
    '/',
    '/clients',
    '/clients/1',
    '/clients/new',
    '/clients/1/edit',
    '/projects',
    '/projects/1',
    '/projects/new',
    '/projects/1/edit',
    '/quotes',
    '/quotes/1',
    '/quotes/new',
    '/quotes/1/edit',
    '/invoices',
    '/invoices/1',
    '/invoices/new',
    '/invoices/1/edit',
    '/invoices/1/print',
    '/time',
    '/time/new',
    '/time/rapport',
    '/expenses',
    '/expenses/new',
    '/events',
    '/events/new',
    '/events/api/events',
    '/documents',
    '/documents/new',
    '/settings',
  ];

  for (const path of pages) {
    test(`GET ${path} should not return 500`, async ({ page }) => {
      const response = await page.goto(path);
      expect(response?.status(), `${path} returned ${response?.status()}`).not.toBe(500);
    });
  }

  test('GET /settings/export should not return 500', async ({ request }) => {
    const response = await request.get('/settings/export');
    expect(response.status()).not.toBe(500);
  });
});

test.describe('Non-Regression: Data Integrity', () => {
  test('fixture clients are present', async ({ page }) => {
    await page.goto('/clients');
    const body = await page.textContent('body');
    const clients = ['SCI Les Jardins', 'Mairie de Bordeaux', 'Dupont', 'Immo', 'Martin'];
    for (const client of clients) {
      expect(body, `Client "${client}" should be in the list`).toContain(client);
    }
  });

  test('fixture projects are present', async ({ page }) => {
    await page.goto('/projects');
    const body = await page.textContent('body');
    expect(body).toContain('PROJ-2026-001');
    expect(body).toContain('PROJ-2026-002');
  });

  test('fixture quotes are present', async ({ page }) => {
    await page.goto('/quotes');
    const body = await page.textContent('body');
    expect(body).toContain('DEV-2026-001');
  });

  test('fixture invoices are present', async ({ page }) => {
    await page.goto('/invoices');
    const body = await page.textContent('body');
    expect(body).toContain('FAC-2026-001');
  });
});

test.describe('Non-Regression: Invoice Creation (Bug Fixes)', () => {
  // Run sequentially to avoid duplicate invoice number conflicts
  test.describe.configure({ mode: 'serial' });

  test('invoice form columns are correctly aligned (no ghost Description column)', async ({ page }) => {
    await page.goto('/invoices/new');
    const headers = page.locator('#inv-lines-table thead th');
    const headerTexts = await headers.allTextContents();
    // "Description" column was removed — it had no matching form field and shifted all columns
    expect(headerTexts).not.toContain('Description');
    // Correct order: Désignation, Qté, Unité, Prix HT, Montant HT, (empty delete col)
    expect(headerTexts[0]).toBe('Désignation');
    expect(headerTexts[1]).toBe('Qté');
    expect(headerTexts[2]).toBe('Unité');
    expect(headerTexts[3]).toBe('Prix HT');
    expect(headerTexts[4]).toBe('Montant HT');
  });

  test('submitting a new invoice with a line does not return 500', async ({ page }) => {
    await page.goto('/invoices/new');

    // Fill required invoice fields
    await page.locator('select[name*="[client]"]').selectOption({ index: 1 });
    await page.fill('input[name*="[objet]"]', 'Test régression facture');

    // Add a line via the "Ajouter une ligne" button
    await page.click('#inv-add-line');

    // Fill the new line fields
    const row = page.locator('.inv-line-row').last();
    await row.locator('.inv-line-designation').fill('Prestation test');
    await row.locator('.inv-line-qty').fill('2');
    await row.locator('.inv-line-price').fill('150');

    // Submit the form
    const [response] = await Promise.all([
      page.waitForNavigation(),
      page.click('button:has-text("Créer la facture"), button[type="submit"]'),
    ]);

    // Should not get a 500 error (was failing due to null quantite/ordre setters)
    expect(response?.status()).not.toBe(500);
  });

  test('submitting invoice with empty line fields does not crash (nullable setters)', async ({ page }) => {
    await page.goto('/invoices/new');

    await page.locator('select[name*="[client]"]').selectOption({ index: 1 });
    await page.fill('input[name*="[objet]"]', 'Test nullable setters');

    // Add a line but leave quantite and prix empty
    await page.click('#inv-add-line');
    const row = page.locator('.inv-line-row').last();
    await row.locator('.inv-line-designation').fill('Ligne vide');
    // Leave qty and price empty — these used to cause TypeError on null

    // Submit and check the response status on the resulting page
    await page.click('button:has-text("Créer la facture"), button[type="submit"]');
    await page.waitForLoadState('networkidle');

    // The page must not show a 500 error (was crashing: setQuantite/setOrdre/setPrixUnitaireHT got null)
    // A validation error (re-rendered form) or redirect are both acceptable
    const content = await page.content();
    expect(content).not.toContain('Internal Server Error');
    expect(content).not.toContain('500 Internal');
  });
});

test.describe('Non-Regression: CSS & Assets', () => {
  test('CSS is loaded (page has styled content)', async ({ page }) => {
    await page.goto('/');
    // Check that our main stylesheet is loaded
    const links = page.locator('link[rel="stylesheet"]');
    expect(await links.count()).toBeGreaterThan(0);
  });

  test('JS is loaded (scripts present)', async ({ page }) => {
    await page.goto('/');
    const scripts = page.locator('script[src*="build"]');
    expect(await scripts.count()).toBeGreaterThan(0);
  });
});
