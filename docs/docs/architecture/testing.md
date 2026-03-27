---
sidebar_position: 4
---

# Stratégie de Tests

## Principe fondamental

> **La couverture de tests doit toujours être maintenue ou améliorée, jamais dégradée.**

Chaque nouvelle fonctionnalité, correction de bug ou refactoring **doit** s'accompagner de tests correspondants. Aucune PR ne doit être mergée si elle fait baisser la couverture de tests.

## Tests E2E — Playwright

L'ERP utilise **Playwright** pour les tests end-to-end qui vérifient le comportement réel de l'application dans un navigateur.

### Structure

```
e2e/
├── playwright.config.ts    # Configuration (baseURL, browsers, reporters)
├── package.json            # Dépendances et scripts npm
└── tests/
    ├── navigation.spec.ts       # Navigation sidebar et routes
    ├── dashboard.spec.ts        # Dashboard, KPIs, graphiques
    ├── clients.spec.ts          # CRUD clients complet
    ├── projects.spec.ts         # Projets, phases, badges
    ├── quotes-invoices.spec.ts  # Devis et factures
    ├── time-expenses.spec.ts    # Saisie d'heures et dépenses
    ├── calendar-documents.spec.ts # Calendrier API + Documents
    ├── settings.spec.ts         # Paramètres et export
    └── regression.spec.ts       # Non-régression (toutes pages, données)
```

### Ce qui est couvert (77 tests)

| Suite | Tests | Ce qui est vérifié |
|-------|-------|--------------------|
| Navigation | 3 | Sidebar, toutes les routes répondent 200, lien actif |
| Dashboard | 3 | KPIs affichés, canvases Chart.js, pas d'erreur serveur |
| Clients | 5 | Liste, détail, création, édition, boutons suppression |
| Projets | 5 | Liste avec références, détail avec onglets, badges statut, formulaire |
| Devis | 4 | Liste, détail avec montants, formulaire, badges |
| Factures | 5 | Liste, détail, impression, formulaire, badges |
| Heures | 3 | Liste, formulaire avec sélecteurs, rapport |
| Dépenses | 3 | Liste, formulaire, catégories affichées |
| Calendrier | 4 | Page calendrier, API JSON format, structure événement, formulaire |
| Documents | 2 | Liste avec catégories, formulaire |
| Paramètres | 3 | Page, données fixtures, endpoint export |
| Non-régression | 37 | **Toutes les pages != 500**, intégrité données fixtures, CSS/JS chargés |

### Commandes

```bash
cd e2e

# Lancer tous les tests
npm test

# Mode interactif (UI Playwright)
npm run test:ui

# Mode visible (browser affiché)
npm run test:headed

# Mode debug
npm run test:debug

# Voir le rapport HTML
npm run report
```

### Variable d'environnement

```bash
# Tester contre un autre serveur
BASE_URL=https://staging.example.com npm test
```

## Règles pour les contributeurs

### 1. Ajout de fonctionnalité

Quand tu ajoutes un nouveau module ou une nouvelle page :

- **Ajouter la route** dans `regression.spec.ts` → tableau `pages[]`
- **Créer un fichier de test** dédié dans `e2e/tests/`
- Vérifier au minimum :
  - La page index retourne 200
  - Le formulaire `new` se charge
  - Le formulaire `edit` se charge (si applicable)
  - Les données s'affichent correctement

### 2. Correction de bug

- **Écrire un test qui reproduit le bug** avant de le corriger
- Vérifier que le test échoue sans le fix, passe avec

### 3. Refactoring

- **Les tests existants doivent tous passer** après le refactoring
- Si un test doit changer (ex: renommage de route), le mettre à jour

### 4. Avant chaque merge/deploy

```bash
cd e2e && npm test
```

Si un seul test échoue → **ne pas merger**. Corriger d'abord.

## CI/CD

Les tests E2E sont exécutés automatiquement dans le pipeline GitHub Actions :

```yaml
# .github/workflows/ci.yml (extrait)
e2e:
  runs-on: ubuntu-latest
  needs: [build-docker]
  steps:
    - uses: actions/checkout@v4
    - name: Start services
      run: docker compose up -d
    - name: Wait for app
      run: sleep 30
    - name: Install Playwright
      working-directory: ./e2e
      run: npm ci && npx playwright install chromium
    - name: Run E2E tests
      working-directory: ./e2e
      run: npm test
      env:
        BASE_URL: http://localhost:8080
```

## Bonnes pratiques

1. **Nommer les tests clairement** — décrit le comportement attendu
2. **Un test = une assertion principale** — pas de tests "fourre-tout"
3. **Données de fixtures** — les tests dépendent des fixtures, les garder à jour
4. **Pas de sleep arbitraires** — utiliser `waitForURL`, `waitForSelector`
5. **Screenshots on failure** — activé par défaut dans la config
6. **Traces on retry** — pour debugger les échecs en CI
