import { existsSync, mkdirSync, rmSync, writeFileSync } from 'node:fs';
import { dirname, resolve } from 'node:path';
import { defineConfig, type Plugin } from 'vite';

const host = 'localhost';
const port = 5173;
const hotFile = resolve(__dirname, 'public/build/hot');

function raccoonHotFile(): Plugin {
  let command: 'build' | 'serve' = 'serve';

  return {
    name: 'raccoon-hot-file',
    configResolved(config) {
      command = config.command;
    },
    configureServer(server) {
      mkdirSync(dirname(hotFile), { recursive: true });
      writeFileSync(hotFile, `http://${host}:${port}`, 'utf8');

      server.httpServer?.once('close', () => {
        if (existsSync(hotFile)) {
          rmSync(hotFile);
        }
      });
    },
    buildStart() {
      if (command === 'build' && existsSync(hotFile)) {
        rmSync(hotFile);
      }
    },
  };
}

export default defineConfig({
  plugins: [raccoonHotFile()],
  publicDir: false,
  server: {
    host,
    port,
    strictPort: true,
    cors: {
      origin: 'http://localhost:8000',
    },
  },
  build: {
    outDir: 'public/build',
    emptyOutDir: true,
    manifest: true,
    rollupOptions: {
      input: {
        styles: resolve(__dirname, 'resources/scss/app.scss'),
        app: resolve(__dirname, 'resources/js/app.ts'),
      },
    },
  },
});
