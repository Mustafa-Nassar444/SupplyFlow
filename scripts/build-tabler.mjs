import { cpSync, mkdirSync, readFileSync, writeFileSync } from 'node:fs';
import { resolve, join } from 'node:path';
import { spawnSync } from 'node:child_process';

const root = resolve(import.meta.dirname, '..');
const tools = join(root, '.tools/tabler-build');
const source = join(root, 'tablerViews/tabler--tabler-core-1.6.1');
const work = join(tools, 'source');
const output = join(root, 'public/vendor/tabler');
mkdirSync(work, { recursive: true });
for (const dir of ['core', '.build', 'shared/banner']) {
    cpSync(join(source, dir), join(work, dir), { recursive: true });
}
// Use this release's own CSS pipeline and Vite library configuration.
function run(args) {
    const result = spawnSync(process.execPath, args, { cwd: join(work, 'core'), stdio: 'inherit' });
    if (result.status !== 0) process.exit(result.status ?? 1);
}
run([join(tools, 'node_modules/tsx/dist/cli.mjs'), '../.build/build-css.ts', 'scss', 'dist/css', '--rtl', '--minify', '--banner']);
run([join(tools, 'node_modules/vite/bin/vite.js'), 'build', '--config', '.build/vite.config.mts']);
mkdirSync(output, { recursive: true });
for (const file of ['css/tabler.min.css', 'css/tabler.min.css.map', 'css/tabler.rtl.min.css', 'css/tabler.rtl.min.css.map', 'js/tabler.js', 'js/tabler.js.map']) {
    mkdirSync(join(output, file, '..'), { recursive: true });
    cpSync(join(work, 'core/dist', file), join(output, file));
}
cpSync(join(source, 'LICENSE'), join(output, 'LICENSE'));
cpSync(join(source, 'core/js/src/bootstrap/LICENSE'), join(output, 'BOOTSTRAP-LICENSE'));
cpSync(join(tools, 'node_modules/@popperjs/core/LICENSE.md'), join(output, 'POPPER-LICENSE.md'));
for (const [pkg, folder] of [['inter', 'inter'], ['noto-sans-arabic', 'noto-sans-arabic']]) {
    const font = join(tools, 'node_modules/@fontsource', pkg);
    const dest = join(output, 'fonts', folder);
    mkdirSync(dest, { recursive: true });
    // Publish only files referenced by these two weights, retaining relative URLs.
    mkdirSync(join(dest, 'files'), { recursive: true });
    for (const weight of [400, 600]) {
        const css = readFileSync(join(font, `${weight}.css`), 'utf8');
        writeFileSync(join(dest, `${weight}.css`), css);
        for (const match of css.matchAll(/url\(\.\/files\/([^\)]+)\)/g)) {
            cpSync(join(font, 'files', match[1]), join(dest, 'files', match[1]));
        }
    }
    cpSync(join(font, 'LICENSE'), join(dest, 'LICENSE'));
}
const icons = join(tools, 'node_modules/@tabler/icons');
mkdirSync(join(output, 'icons'), { recursive: true });
for (const name of ['layout-dashboard', 'list', 'forms']) {
    const svg = readFileSync(join(icons, 'icons/outline', `${name}.svg`), 'utf8');
    writeFileSync(join(root, 'resources/views/partials/dashboard', `icon-${name}.blade.php`), svg.replace('<svg ', '<svg aria-hidden="true" focusable="false" '));
}
cpSync(join(icons, 'LICENSE'), join(output, 'icons/LICENSE'));
console.log('Tabler local assets and icons published.');
