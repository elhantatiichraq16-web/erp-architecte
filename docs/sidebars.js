// @ts-check

/** @type {import('@docusaurus/plugin-content-docs').SidebarsConfig} */
const sidebars = {
  tutorialSidebar: [
    {
      type: 'doc',
      id: 'intro',
      label: 'Introduction',
    },
    {
      type: 'category',
      label: 'Prise en main',
      collapsed: false,
      items: [
        'getting-started/installation',
        'getting-started/configuration',
      ],
    },
    {
      type: 'category',
      label: 'Architecture',
      collapsed: false,
      items: [
        'architecture/stack',
        'architecture/entities',
        'architecture/services',
        'architecture/testing',
      ],
    },
    {
      type: 'category',
      label: 'Modules',
      collapsed: false,
      items: [
        'modules/dashboard',
        'modules/clients',
        'modules/projects',
        'modules/quotes',
        'modules/invoices',
        'modules/time-tracking',
        'modules/expenses',
        'modules/calendar',
        'modules/documents',
        'modules/settings',
      ],
    },
    {
      type: 'category',
      label: 'Déploiement',
      collapsed: false,
      items: [
        'deployment/docker',
        'deployment/ci-cd',
        'deployment/production',
      ],
    },
    {
      type: 'category',
      label: 'Référence API',
      collapsed: false,
      items: [
        'api/events-api',
      ],
    },
  ],
};

export default sidebars;
