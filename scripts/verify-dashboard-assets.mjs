import { existsSync, readFileSync, readdirSync } from 'node:fs';
import { resolve, dirname, join } from 'node:path';

const root = resolve(import.meta.dirname, '../public');
const failures = [];
let checked = 0;
function scan(dir) {
    for (const entry of readdirSync(dir, { withFileTypes: true })) {
        const path = join(dir, entry.name);
        if (entry.isDirectory()) scan(path);
        else if (entry.name.endsWith('.css')) {
            for (const match of readFileSync(path, 'utf8').matchAll(/url\(\s*['"]?([^\s)'";]+)['"]?\s*\)/g)) {
                const url = match[1];
                if (url.startsWith('data:')) continue;
                checked++;
                if (/^(https?:)?\/\//.test(url) || !existsSync(resolve(dirname(path), url.split(/[?#]/)[0]))) failures.push(`${path}: ${url}`);
            }
        }
    }
}
scan(join(root, 'vendor/tabler'));
for (const file of ['vendor/tabler/js/tabler.js', 'css/dashboard.css', 'js/dashboard-example.js']) {
    if (!existsSync(join(root, file))) failures.push(file);
}
if (failures.length) throw new Error(failures.join('\n'));
console.log(`Dashboard assets verified: ${checked} local CSS references; no missing files or external URLs.`);
