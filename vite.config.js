import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { viteStaticCopy } from 'vite-plugin-static-copy';

export default defineConfig({
  plugins: [
    laravel({
      input: ['src/resources/js/index.js', 'src/resources/scss/gjs.scss'],
      buildDirectory: 'assets',
      // Ensure manifest is created for package asset resolution
      manifest: true,
    }),
    viteStaticCopy({
      targets: [
        {
          src: 'node_modules/grapesjs/dist/fonts/*',
          dest: 'fonts',
        },
        {
          src: 'src/resources/js/plugins/image-editor/svg/*',
          dest: 'svg',
        },
      ],
    }),
  ],
  build: {
    outDir: 'dist',
    emptyOutDir: true,
    sourcemap: false,
    // Ensure manifest is generated in root dist directory
    manifest: 'manifest.json',
    rollupOptions: {
      output: {
        entryFileNames: 'assets/[name].js',
        chunkFileNames: 'assets/[name].js',
        assetFileNames: 'assets/[name].[ext]',
      },
    },
  },
  css: {
    preprocessorOptions: {
      scss: {
        // Disable URL processing to match Mix behavior
        api: 'modern-compiler',
      },
    },
  },
});
