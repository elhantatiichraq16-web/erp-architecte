// @ts-check
// `@type` JSDoc annotations allow editor autocompletion and type checking
// (when paired with `@ts-check`).
// There are various equivalent ways to declare your Docusaurus config.
// See: https://docusaurus.io/docs/api/docusaurus-config

import { themes as prismThemes } from 'prism-react-renderer';

/** @type {import('@docusaurus/types').Config} */
const config = {
  title: 'ERP Architecte — Documentation',
  tagline: 'Documentation technique et fonctionnelle',
  favicon: 'img/favicon.ico',

  // Set the production url of your site here
  url: 'https://elhantatiichraq16-web.github.io',
  // Set the /<baseUrl>/ pathname under which your site is served
  baseUrl: '/erp-architecte/',

  // GitHub pages deployment config
  organizationName: 'elhantatiichraq16-web',
  projectName: 'erp-architecte',
  trailingSlash: false,

  onBrokenLinks: 'warn',
  onBrokenMarkdownLinks: 'warn',

  // Internationalization
  i18n: {
    defaultLocale: 'fr',
    locales: ['fr'],
    localeConfigs: {
      fr: {
        label: 'Français',
        direction: 'ltr',
        htmlLang: 'fr-FR',
      },
    },
  },

  presets: [
    [
      'classic',
      /** @type {import('@docusaurus/preset-classic').Options} */
      ({
        docs: {
          sidebarPath: './sidebars.js',
          routeBasePath: '/',
          showLastUpdateTime: true,
          showLastUpdateAuthor: true,
        },
        blog: false,
        theme: {
          customCss: './src/css/custom.css',
        },
      }),
    ],
  ],

  themeConfig:
    /** @type {import('@docusaurus/preset-classic').ThemeConfig} */
    ({
      // Replace with your project's social card
      image: 'img/erp-social-card.png',

      colorMode: {
        defaultMode: 'light',
        disableSwitch: false,
        respectPrefersColorScheme: true,
      },

      navbar: {
        title: 'ERP Architecte',
        logo: {
          alt: 'ERP Architecte Logo',
          src: 'img/logo.svg',
        },
        items: [
          {
            type: 'docSidebar',
            sidebarId: 'tutorialSidebar',
            position: 'left',
            label: 'Docs',
          },
          {
            to: '/api/events-api',
            label: 'API',
            position: 'left',
          },
          {
            href: 'https://github.com/erp-architecte/erp-archi',
            label: 'GitHub',
            position: 'right',
          },
        ],
      },

      footer: {
        style: 'dark',
        links: [
          {
            title: 'Documentation',
            items: [
              {
                label: 'Introduction',
                to: '/',
              },
              {
                label: 'Installation',
                to: '/getting-started/installation',
              },
              {
                label: 'Architecture',
                to: '/architecture/stack',
              },
            ],
          },
          {
            title: 'Modules',
            items: [
              {
                label: 'Dashboard',
                to: '/modules/dashboard',
              },
              {
                label: 'Projets',
                to: '/modules/projects',
              },
              {
                label: 'Facturation',
                to: '/modules/invoices',
              },
            ],
          },
          {
            title: 'Déploiement',
            items: [
              {
                label: 'Docker',
                to: '/deployment/docker',
              },
              {
                label: 'CI/CD',
                to: '/deployment/ci-cd',
              },
              {
                label: 'Production',
                to: '/deployment/production',
              },
            ],
          },
          {
            title: 'Liens',
            items: [
              {
                label: 'GitHub',
                href: 'https://github.com/erp-architecte/erp-archi',
              },
              {
                label: 'Application (Dev)',
                href: 'http://localhost:8080',
              },
              {
                label: 'phpMyAdmin',
                href: 'http://localhost:8081',
              },
            ],
          },
        ],
        copyright: `Copyright © ${new Date().getFullYear()} ERP Architecte. Documentation générée avec Docusaurus.`,
      },

      prism: {
        theme: prismThemes.github,
        darkTheme: prismThemes.dracula,
        additionalLanguages: ['php', 'bash', 'yaml', 'json', 'nginx', 'sql'],
      },

      tableOfContents: {
        minHeadingLevel: 2,
        maxHeadingLevel: 4,
      },
    }),
};

export default config;
