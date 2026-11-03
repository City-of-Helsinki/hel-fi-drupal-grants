import { resolve } from 'node:path';
import tsconfigPaths from 'vite-tsconfig-paths';
import { defineConfig } from 'vitest/config';

// React and jotai are installed from hdbt, but .npmrc install-links copies
// them here. Resolve every import of them, including from hdbt's sources and
// packages, to these copies so the tests run a single React and jotai.
const nodeModules = resolve(__dirname, 'node_modules');

export default defineConfig({
  plugins: [tsconfigPaths({ projects: ['./tsconfig.json', '../../contrib/hdbt/tsconfig.json'] })],
  resolve: {
    // Prefer ESM builds, as CommonJS requires bypass the aliases below.
    mainFields: ['module', 'jsnext:main', 'jsnext', 'main'],
    alias: [
      { find: /^react(-dom)?$/, replacement: `${nodeModules}/react$1` },
      { find: /^react(-dom)?\/(.+)$/, replacement: `${nodeModules}/react$1/$2` },
      { find: /^jotai$/, replacement: `${nodeModules}/jotai/esm/index.mjs` },
      { find: /^jotai\/utils$/, replacement: `${nodeModules}/jotai/esm/utils.mjs` },
      { find: /^jotai\/(.+)$/, replacement: `${nodeModules}/jotai/esm/$1.mjs` },
    ],
  },
  test: {
    server: {
      deps: {
        // Inlined packages go through the aliases above. Packages loaded from
        // hdbt would otherwise import hdbt's own React.
        inline: ['@testing-library/react', /contrib\/hdbt\/node_modules\/(hds-react|@react-spring|react-popper)\//],
      },
    },
    coverage: {
      include: ['src/js/react/apps/forms-app/**/*.{js,jsx,ts,tsx}'],
      exclude: [
        'src/js/react/apps/forms-app/types',
        'src/js/react/apps/forms-app/testutils',
        'src/js/react/apps/forms-app/index.tsx',
      ],
    },
    environment: 'jsdom',
    exclude: ['node_modules'],
    globals: true,
    setupFiles: ['src/js/react/apps/forms-app/tests/setupTests.ts'],
  },
});
