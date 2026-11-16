import { execFileSync, spawn } from 'node:child_process';
import { mkdirSync, writeFileSync } from 'node:fs';
import { resolve } from 'node:path';

function setup() {
  const database = resolve('storage/framework/testing/browser.sqlite');
  mkdirSync(resolve('storage/framework/testing'), { recursive: true });
  writeFileSync(database, '');
  const env = {
    ...process.env,
    APP_ENV: 'local',
    DB_CONNECTION: 'sqlite',
    DB_DATABASE: database,
    CACHE_STORE: 'array',
    XDEBUG_MODE: 'off',
  };
  execFileSync(
    'php',
    ['artisan', 'migrate', '--database=sqlite', '--force', '--no-interaction'],
    { env },
  );
  execFileSync('php', ['tests/Browser/seed.php'], { env });
}

setup();
const server = spawn(
  'php',
  [
    '-d',
    'variables_order=EGPCS',
    '-S',
    '127.0.0.1:8138',
    resolve(
      'vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php',
    ),
  ],
  { cwd: resolve('public'), env: process.env, stdio: 'inherit' },
);
process.once('SIGTERM', () => server.kill('SIGTERM'));
process.once('SIGINT', () => server.kill('SIGINT'));
server.once('exit', (code) => process.exit(code ?? 1));
