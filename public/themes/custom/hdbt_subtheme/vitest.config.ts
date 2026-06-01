import { resolve } from 'node:path';
import tsconfigPaths from 'vite-tsconfig-paths';
import { defineConfig } from 'vitest/config';

const hdbtNodeModules = resolve(__dirname, '../../contrib/hdbt/node_modules');

export default defineConfig({
  plugins: [tsconfigPaths({ projects: ['./tsconfig.json', '../../contrib/hdbt/tsconfig.json'] })],
  resolve: {
    alias: [
      { find: /^jotai$/, replacement: `${hdbtNodeModules}/jotai/esm/index.mjs` },
      { find: /^jotai\/utils$/, replacement: `${hdbtNodeModules}/jotai/esm/utils.mjs` },
      { find: /^jotai\/(.+)$/, replacement: `${hdbtNodeModules}/jotai/esm/$1.mjs` },
    ],
  },
  test: {
    server: {
      deps: {
        inline: ['@testing-library/react'],
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
  }
});
