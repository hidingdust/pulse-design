import { defineConfig } from 'vite';

export default defineConfig({
  base: '/',
  define: {
    'import.meta.env.VITE_API_BASE': JSON.stringify('https://api-pulse.nobeladam.cn'),
  },
  build: {
    outDir: 'dist',
    emptyOutDir: true,
    sourcemap: false,
  },
  server: {
    host: '0.0.0.0',
    port: 5173,
    proxy: {
      '/api': 'http://localhost:8080',
    },
  },
});
