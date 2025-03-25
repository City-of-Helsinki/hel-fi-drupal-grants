import { defineConfig } from 'vitest/config';

export default defineConfig({
  test: {
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
