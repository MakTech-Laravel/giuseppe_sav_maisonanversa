import { mkdir, readdir, rm, stat, writeFile } from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import sharp from 'sharp';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const publicImages = path.join(root, 'public', 'images');
const widths = [768, 1280, 2560];
const skipDirs = new Set(['optimized', 'images']);

async function walk(dir) {
    const entries = await readdir(dir, { withFileTypes: true });
    const files = [];

    for (const entry of entries) {
        const full = path.join(dir, entry.name);

        if (entry.isDirectory()) {
            if (skipDirs.has(entry.name)) {
                continue;
            }

            files.push(...(await walk(full)));
            continue;
        }

        if (
            /\.(png|jpe?g)$/i.test(entry.name) &&
            !entry.name.startsWith('__test-')
        ) {
            files.push(full);
        }
    }

    return files;
}

async function optimize(file) {
    const relative = path.relative(publicImages, file).replaceAll('\\', '/');
    const parsed = path.parse(relative);
    const outputDir = path.join(publicImages, 'optimized', parsed.dir);
    await mkdir(outputDir, { recursive: true });

    const source = sharp(file, { failOn: 'none' });
    const metadata = await source.metadata();
    const longEdge = Math.max(metadata.width ?? 0, metadata.height ?? 0);
    const produced = [];
    const prefix = parsed.dir ? `${parsed.dir}/` : '';

    for (const width of widths) {
        if (longEdge > 0 && width > longEdge && width !== widths[0]) {
            continue;
        }

        const resized = source.clone().resize({
            width,
            height: width,
            fit: 'inside',
            withoutEnlargement: true,
        });

        const stem = `${parsed.name}-${width}`;
        const webpPath = path.join(outputDir, `${stem}.webp`);
        const avifPath = path.join(outputDir, `${stem}.avif`);

        await resized.clone().webp({ quality: 68, effort: 6 }).toFile(webpPath);
        await resized.clone().avif({ quality: 45, effort: 4 }).toFile(avifPath);

        produced.push({
            width,
            webp: `images/optimized/${prefix}${stem}.webp`,
            avif: `images/optimized/${prefix}${stem}.avif`,
        });
    }

    const size = (await stat(file)).size;
    console.log(
        `${relative} → ${produced.length} widths (${(size / 1024).toFixed(0)} KB source)`,
    );

    return { source: `images/${relative}`, variants: produced };
}

await rm(path.join(publicImages, 'images'), { recursive: true, force: true });

const manifest = {};

for (const file of await walk(publicImages)) {
    const entry = await optimize(file);
    manifest[entry.source] = entry.variants;
}

await writeFile(
    path.join(publicImages, 'optimized', 'manifest.json'),
    JSON.stringify(manifest, null, 4) + '\n',
);
