import { build } from 'esbuild';
import { mkdir } from 'node:fs/promises';

await mkdir('public/assets', { recursive: true });
await build({ entryPoints: ['resources/css/app.css'], outfile: 'public/assets/app.css', minify: true, logLevel: 'info' });
await build({ entryPoints: ['resources/css/admin-stores.css'], outfile: 'public/assets/admin-stores.css', bundle: true, minify: true, logLevel: 'info' });
await build({ entryPoints: ['resources/js/admin-stores.js'], outfile: 'public/assets/admin-stores.js', bundle: true, minify: true, target: ['es2020'], logLevel: 'info' });

await build({ entryPoints: ['resources/css/theme.css'], outfile: 'public/assets/theme.css', minify: true });
await build({ entryPoints: ['resources/js/theme.js'], outfile: 'public/assets/theme.js', minify: true, target: ['es2020'] });
