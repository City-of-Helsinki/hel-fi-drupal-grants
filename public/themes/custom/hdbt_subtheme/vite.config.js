import { defineConfig, loadEnv } from 'vite';
import path from 'path';
import { globSync } from 'glob';
import react from '@vitejs/plugin-react';
import analyzer from 'vite-bundle-analyzer';

export default defineConfig(({ command, mode }) => {
  const { VITE_HOST, VITE_ORIGIN, VITE_PORT, } = loadEnv(mode, process.cwd(), '');

  return {
    base: '',
    css: {
      postcss: {
        to: 'css/styles.min.css',
      },
      preprocessorOptions: {
        scss: {
          additionalData: `$debug_mode: false;`,
        },
      },
    },
    build: {
      cssCodeSplit: false,
      manifest: false,
      outDir: path.resolve(__dirname, 'dist'),
      manualChunks: false,
      rollupOptions: {
        input: {
          'forms-app': './src/js/react/apps/forms-app/index.tsx',
          styles: './src/scss/styles.scss',
          ...Object.fromEntries(
            globSync('src/**/*.js').map(file => [
              path.parse(file).name,
              `./${file}`,
            ])
          ),
        },
        output: {
          entryFileNames: 'js/[name].min.js',
        },
      },
    },
    plugins: [
      // analyzer(),
      react()
    ],
    publicDir: false,
    resolve: {
      alias: [
        {
          '@/react/common': path.resolve(__dirname, '../../contrib/hdbt/src/js/react/common/'),
          find: /^~(.*)$/,
          replacement: '$1',
        },
      ],
    },
  }
})