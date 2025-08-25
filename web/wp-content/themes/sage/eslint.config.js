import js from '@eslint/js';
import globals from 'globals';
import importPlugin from 'eslint-plugin-import';
import reactPlugin from 'eslint-plugin-react';

export default [
  js.configs.recommended,
  {
    ignores: [
      '**/resources/scripts/admin/**/*',
      '**/resources/scripts/lib/**/*'
    ],
  },
  {
    files: ['**/*.js', '**/*.jsx', '**/*.mjs', '**/*.cjs'],
    plugins: {
      import: importPlugin,
      react: reactPlugin,
    },
    languageOptions: {
      ecmaVersion: 2020,
      sourceType: 'module',
      globals: {
        ...globals.node,
        ...globals.browser,
        ...globals.amd,
        ...globals.jquery,
        wp: 'readonly',
      },
      parserOptions: {
        ecmaFeatures: {
          // Allow JSX syntax in .js files (used with Gutenberg wp.element)
          jsx: true,
          globalReturn: true,
          generators: false,
          objectLiteralDuplicateProperties: false,
          experimentalObjectRestSpread: true,
        },
      },
    },
    settings: {
      'import/core-modules': [],
      'import/ignore': [
        'node_modules',
        '\\.(coffee|scss|css|less|hbs|svg|json)$',
      ],
    },
    rules: {
      'no-console': 0,
      // Count JSX identifiers as usages to avoid false no-unused-vars errors
      'react/jsx-uses-vars': 'warn',
      'react/jsx-uses-react': 'off',
      'react/react-in-jsx-scope': 'off',
      'quotes': ['warn', 'single', { avoidEscape: true }],
      'comma-dangle': [
        'error',
        {
          'arrays': 'ignore',
          'objects': 'ignore',
          'imports': 'ignore',
          'exports': 'ignore',
          'functions': 'ignore',
        },
      ],
    },
  },
];
