/**
 * Extract English static UI copy used on the customer-facing website,
 * grouped by page/section. Writes CSV + Markdown under docs/translation-extract/.
 *
 * Usage: node scripts/extract-en-static.mjs
 */
import { mkdir, readFile, readdir, writeFile, stat } from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const jsRoot = path.join(root, 'resources', 'js');
const outDir = path.join(root, 'docs', 'translation-extract');

/** @typedef {{ id: string, title: string, route: string, patterns: string[] }} Section */

/** Customer-facing sections (admin excluded). Paths are relative to resources/js. */
const SECTIONS = /** @type {Section[]} */ ([
    {
        id: '00-shared',
        title: 'Shared shell (nav, footer, modals, cookie)',
        route: '(all public + member pages)',
        patterns: [
            'layouts/frontend-layout.tsx',
            'layouts/member-layout.tsx',
            'layouts/auth-layout.tsx',
            'layouts/auth/**',
            'lib/maison-navigation.ts',
            'components/maison/shell/**',
            'components/maison/modals/**',
            'components/maison/cookie-consent-banner.tsx',
            'components/maison/heritage-letter-form.tsx',
            'components/maison/page-scaffold.tsx',
            'components/maison/placeholder-image.tsx',
            'components/maison/seo/**',
            'components/member/member-nav.tsx',
            'components/member/member-topbar.tsx',
            'components/gender-select.tsx',
        ],
    },
    {
        id: '01-home-opening',
        title: 'Home + Opening',
        route: '/{locale}/',
        patterns: [
            'pages/maison/home.tsx',
            'components/maison/home/**',
            'components/maison/opening/**',
        ],
    },
    {
        id: '02-house',
        title: 'Het Huis',
        route: '/{locale}/huis',
        patterns: [
            'pages/maison/house.tsx',
            'components/maison/house/**',
        ],
    },
    {
        id: '03-products',
        title: 'Products',
        route: '/{locale}/products',
        patterns: [
            'pages/maison/products/**',
            'components/maison/product/**',
        ],
    },
    {
        id: '04-story',
        title: 'Ons Verhaal (UI chrome; body is CMS)',
        route: '/{locale}/story',
        patterns: [
            'pages/maison/story.tsx',
            'components/maison/story/**',
        ],
    },
    {
        id: '05-circle',
        title: 'Founding Circle',
        route: '/{locale}/circle',
        patterns: [
            'pages/maison/circle.tsx',
            'pages/maison/founding-circle/**',
            'components/maison/circle/**',
        ],
    },
    {
        id: '06-dressing',
        title: 'Kleedkamer',
        route: '/{locale}/dressing',
        patterns: [
            'pages/maison/dressing.tsx',
            'pages/maison/dressing-show.tsx',
            'components/maison/dressing/**',
        ],
    },
    {
        id: '07-journal',
        title: 'Journal (UI chrome; articles are CMS)',
        route: '/{locale}/journal',
        patterns: [
            'pages/maison/journal.tsx',
            'pages/maison/journal/**',
            'components/maison/journal/**',
        ],
    },
    {
        id: '08-community',
        title: 'Community hub + feed',
        route: '/{locale}/community',
        patterns: [
            'pages/maison/community.tsx',
            'components/maison/community/**',
        ],
    },
    {
        id: '09-events-sessions-clubs',
        title: 'Community events / sessions / clubs',
        route: '/{locale}/community/{events|sessions|clubs}',
        patterns: [
            'pages/maison/events/**',
            'pages/maison/sessions/**',
            'pages/maison/clubs/**',
        ],
    },
    {
        id: '10-corner',
        title: 'Club Corner',
        route: '/{locale}/corner',
        patterns: ['pages/maison/corner.tsx'],
    },
    {
        id: '11-contact',
        title: 'Contact',
        route: '/{locale}/contact',
        patterns: [
            'pages/maison/contact.tsx',
            'components/maison/contact/**',
        ],
    },
    {
        id: '12-legal',
        title: 'Legal wrappers (privacy, terms, shipping, care; body is CMS)',
        route: '/{locale}/{privacy|terms|shipping|care}',
        patterns: [
            'pages/maison/legal/**',
            'components/maison/legal/**',
        ],
    },
    {
        id: '13-commerce',
        title: 'Returns, checkout, verify, guest returns, newsletter',
        route: '/{locale}/returns|checkout|verify|guest-returns|heritage-letter',
        patterns: [
            'pages/maison/returns.tsx',
            'pages/maison/checkout-success.tsx',
            'pages/maison/checkout-cancel.tsx',
            'pages/maison/verify.tsx',
            'pages/maison/guest-returns/**',
            'pages/maison/newsletter-unsubscribed.tsx',
        ],
    },
    {
        id: '14-member',
        title: 'Member area',
        route: '/{locale}/member/*',
        patterns: [
            'pages/member/**',
            'components/member/**',
        ],
    },
    {
        id: '15-auth',
        title: 'Auth full pages (reset / verify / confirm)',
        route: '/{locale}/reset-password|email/verify|confirm-password',
        patterns: ['pages/auth/**'],
    },
    {
        id: '16-settings',
        title: 'Settings',
        route: '/{locale}/settings/*',
        patterns: [
            'pages/settings/**',
            'layouts/settings/**',
        ],
    },
    {
        id: '17-errors',
        title: 'Error pages',
        route: '404 / 403',
        patterns: ['pages/errors/**'],
    },
]);

async function exists(filePath) {
    try {
        await stat(filePath);
        return true;
    } catch {
        return false;
    }
}

async function walkDir(dir) {
    if (!(await exists(dir))) {
        return [];
    }

    const entries = await readdir(dir, { withFileTypes: true });
    const files = [];

    for (const entry of entries) {
        const full = path.join(dir, entry.name);

        if (entry.isDirectory()) {
            files.push(...(await walkDir(full)));
            continue;
        }

        if (/\.(tsx?|jsx?)$/.test(entry.name)) {
            files.push(full);
        }
    }

    return files;
}

async function expandPattern(pattern) {
    const abs = path.join(jsRoot, pattern.replaceAll('/', path.sep));

    if (pattern.endsWith('/**')) {
        const dir = abs.slice(0, -3);
        return walkDir(dir);
    }

    if (pattern.includes('*')) {
        const dir = path.dirname(abs);
        const base = path.basename(pattern);
        const re = new RegExp(
            `^${base.replaceAll('.', '\\.').replaceAll('*', '.*')}$`,
        );
        if (!(await exists(dir))) {
            return [];
        }
        const entries = await readdir(dir);
        return entries
            .filter((name) => re.test(name) && /\.(tsx?|jsx?)$/.test(name))
            .map((name) => path.join(dir, name));
    }

    if (await exists(abs)) {
        return [abs];
    }

    return [];
}

/** Collect static t('…') / t("…") / t(`…`) keys (no ${} interpolations). */
function extractTKeys(source) {
    const keys = new Set();
    const re = /\bt\(\s*(['"`])((?:(?!\1)[^\\]|\\.)*)\1/g;
    let match;

    while ((match = re.exec(source)) !== null) {
        const key = match[2]
            .replaceAll('\\\'', "'")
            .replaceAll('\\"', '"')
            .replaceAll('\\`', '`')
            .replaceAll('\\n', '\n');

        if (key.includes('${')) {
            continue;
        }

        keys.add(key);
    }

    return keys;
}

/** Hardcoded aria-label="…" that look like English UI (not pure Dutch t keys). */
function extractAriaLiterals(source) {
    const labels = new Set();
    const re = /aria-label=\{?\s*(['"`])((?:(?!\1)[^\\]|\\.)*)\1\s*\}?/g;
    let match;

    while ((match = re.exec(source)) !== null) {
        const value = match[2];
        if (value.includes('${') || value.length < 2) {
            continue;
        }
        // Skip if this is clearly a t() call nearby — plain literals only.
        labels.add(value);
    }

    return labels;
}

/** Labels in PRIMARY_NAV / FOOTER_COLUMNS: label: '…' */
function extractNavLabels(source) {
    const keys = new Set();
    const re = /\blabel:\s*(['"`])((?:(?!\1)[^\\]|\\.)*)\1/g;
    let match;

    while ((match = re.exec(source)) !== null) {
        keys.add(match[2]);
    }

    const headingRe = /\bheading:\s*(['"`])((?:(?!\1)[^\\]|\\.)*)\1/g;
    while ((match = headingRe.exec(source)) !== null) {
        keys.add(match[2]);
    }

    return keys;
}

/**
 * Flatten string literals from OPENING_COPY.en and OPENING_ROOMS[].text.en.
 */
function extractOpeningEnglish(source) {
    /** @type {{ english: string, dutchKey: string, kind: string, note: string }[]} */
    const rows = [];

    const copyMatch = source.match(
        /export const OPENING_COPY[\s\S]*?\ben:\s*\{([\s\S]*?)\n\s{4}\},\s*\n\s{4}fr:/,
    );

    if (copyMatch) {
        const block = copyMatch[1];
        const fieldRe =
            /(\w+):\s*(?:'((?:\\'|[^'])*)'|"((?:\\"|[^"])*)"|`((?:\\`|[^`])*)`|\{([\s\S]*?)\n\s{8}\})/g;
        let field;

        while ((field = fieldRe.exec(block)) !== null) {
            const name = field[1];
            if (name === 'greeting' && field[5]) {
                const moodRe =
                    /(\w+):\s*(?:'((?:\\'|[^'])*)'|"((?:\\"|[^"])*)")/g;
                let mood;
                while ((mood = moodRe.exec(field[5])) !== null) {
                    const en = (mood[2] ?? mood[3] ?? '').replaceAll(
                        "\\'",
                        "'",
                    );
                    rows.push({
                        english: en,
                        dutchKey: `(opening.${name}.${mood[1]})`,
                        kind: 'opening',
                        note: `OPENING_COPY.en.greeting.${mood[1]}`,
                    });
                }
                continue;
            }

            const en = (field[2] ?? field[3] ?? field[4] ?? '').replaceAll(
                "\\'",
                "'",
            );
            if (!en) {
                continue;
            }
            rows.push({
                english: en,
                dutchKey: `(opening.${name})`,
                kind: 'opening',
                note: `OPENING_COPY.en.${name}`,
            });
        }
    }

    const roomRe =
        /text:\s*\{[\s\S]*?\ben:\s*\{\s*name:\s*'((?:\\'|[^'])*)',\s*subtitle:\s*'((?:\\'|[^'])*)'/g;
    let room;
    let index = 0;

    while ((room = roomRe.exec(source)) !== null) {
        index += 1;
        rows.push({
            english: room[1].replaceAll("\\'", "'"),
            dutchKey: `(opening.room.${index}.name)`,
            kind: 'opening',
            note: `OPENING_ROOMS[${index - 1}].text.en.name`,
        });
        rows.push({
            english: room[2].replaceAll("\\'", "'"),
            dutchKey: `(opening.room.${index}.subtitle)`,
            kind: 'opening',
            note: `OPENING_ROOMS[${index - 1}].text.en.subtitle`,
        });
    }

    return rows;
}

function csvEscape(value) {
    const text = String(value ?? '');
    if (/[",\n\r]/.test(text)) {
        return `"${text.replaceAll('"', '""')}"`;
    }
    return text;
}

async function main() {
    const enJson = JSON.parse(
        await readFile(path.join(root, 'lang', 'en.json'), 'utf8'),
    );

    /** @type {Map<string, { sectionIds: Set<string>, files: Set<string>, kind: string }>} */
    const keyMeta = new Map();

    /** @type {Map<string, Set<string>>} */
    const sectionFiles = new Map();

    for (const section of SECTIONS) {
        const files = new Set();
        for (const pattern of section.patterns) {
            for (const file of await expandPattern(pattern)) {
                files.add(file);
            }
        }
        sectionFiles.set(section.id, files);

        for (const file of files) {
            const source = await readFile(file, 'utf8');
            const rel = path.relative(jsRoot, file).replaceAll('\\', '/');
            const keys = extractTKeys(source);

            if (rel === 'lib/maison-navigation.ts') {
                for (const label of extractNavLabels(source)) {
                    keys.add(label);
                }
            }

            for (const key of keys) {
                let meta = keyMeta.get(key);
                if (!meta) {
                    meta = {
                        sectionIds: new Set(),
                        files: new Set(),
                        kind: 'ui',
                    };
                    keyMeta.set(key, meta);
                }
                meta.sectionIds.add(section.id);
                meta.files.add(rel);
            }

            // Hardcoded aria-labels that are not t() keys — keep if they look like prose
            // and are not already Dutch dictionary keys used via t.
            for (const label of extractAriaLiterals(source)) {
                // Skip expressions that are clearly component variables or Dutch keys already tracked
                if (keyMeta.has(label)) {
                    continue;
                }
                if (/^[A-Za-z][A-Za-z0-9 .,'’\-–—Nº°/&]+$/.test(label)) {
                    const ariaKey = `__aria__:${label}`;
                    let meta = keyMeta.get(ariaKey);
                    if (!meta) {
                        meta = {
                            sectionIds: new Set(),
                            files: new Set(),
                            kind: 'aria',
                        };
                        keyMeta.set(ariaKey, meta);
                    }
                    meta.sectionIds.add(section.id);
                    meta.files.add(rel);
                }
            }
        }
    }

    // Opening hardcoded EN → home-opening
    const openingPath = path.join(
        jsRoot,
        'components',
        'maison',
        'opening',
        'opening-data.ts',
    );
    const openingSource = await readFile(openingPath, 'utf8');
    const openingRows = extractOpeningEnglish(openingSource);

    /** @type {Map<string, { sectionId: string, dutchKey: string, english: string, kind: string, sourceFile: string, note: string }[]>} */
    const bySection = new Map(SECTIONS.map((s) => [s.id, []]));

    for (const [key, meta] of keyMeta) {
        const isAria = meta.kind === 'aria';
        const dutchKey = isAria ? '' : key;
        let english;
        let noteExtra = '';

        if (isAria) {
            english = key.replace(/^__aria__:/, '');
        } else if (Object.hasOwn(enJson, key)) {
            english = enJson[key];
        } else {
            // No en.json entry → EN locale shows the Dutch key as-is.
            english = key;
            noteExtra = 'MISSING_EN (not in lang/en.json; EN UI shows this key)';
        }

        // Place in Shared if used in shared section OR in 2+ sections
        let sectionId = '00-shared';
        if (
            !meta.sectionIds.has('00-shared') &&
            meta.sectionIds.size === 1
        ) {
            sectionId = [...meta.sectionIds][0];
        }

        const files = [...meta.files].sort();
        const also =
            files.length > 1 ? `also: ${files.slice(1).join('; ')}` : '';
        bySection.get(sectionId).push({
            sectionId,
            dutchKey: dutchKey || '(hardcoded aria-label)',
            english,
            kind: meta.kind,
            sourceFile: files[0] ?? '',
            note: [noteExtra, also].filter(Boolean).join(' | '),
        });
    }

    for (const row of openingRows) {
        bySection.get('01-home-opening').push({
            sectionId: '01-home-opening',
            dutchKey: row.dutchKey,
            english: row.english,
            kind: row.kind,
            sourceFile: 'components/maison/opening/opening-data.ts',
            note: row.note,
        });
    }

    // Sort rows within each section
    for (const rows of bySection.values()) {
        rows.sort((a, b) =>
            a.english.localeCompare(b.english, 'en') ||
            a.dutchKey.localeCompare(b.dutchKey, 'nl'),
        );
    }

    await mkdir(outDir, { recursive: true });

    // CSV
    const csvLines = [
        'Section,PageTitle,Route,SourceFile,DutchKey,English,Kind,Notes',
    ];
    const sectionById = new Map(SECTIONS.map((s) => [s.id, s]));

    let total = 0;
    let missing = 0;
    const counts = [];

    for (const section of SECTIONS) {
        const rows = bySection.get(section.id) ?? [];
        // Dedupe by english+dutchKey within section
        const seen = new Set();
        const unique = [];
        for (const row of rows) {
            const sig = `${row.kind}|${row.dutchKey}|${row.english}`;
            if (seen.has(sig)) {
                continue;
            }
            seen.add(sig);
            unique.push(row);
        }
        bySection.set(section.id, unique);

        counts.push({ id: section.id, title: section.title, count: unique.length });
        total += unique.length;

        for (const row of unique) {
            if (row.note.includes('MISSING_EN')) {
                missing += 1;
            }
            csvLines.push(
                [
                    section.id,
                    section.title,
                    section.route,
                    row.sourceFile,
                    row.dutchKey,
                    row.english,
                    row.kind,
                    row.note,
                ]
                    .map(csvEscape)
                    .join(','),
            );
        }
    }

    await writeFile(
        path.join(outDir, 'english-by-page.csv'),
        csvLines.join('\n') + '\n',
        'utf8',
    );

    // Markdown
    const md = [];
    md.push('# English static UI copy (customer-facing)');
    md.push('');
    md.push(
        'Strings that change when switching language (NL ↔ EN ↔ FR). Values are the **English** the visitor sees. Dutch key is the translation ID in `lang/en.json` (except opening/aria rows).',
    );
    md.push('');
    md.push(`- Generated: ${new Date().toISOString().slice(0, 10)}`);
    md.push(`- Total rows: **${total}**`);
    md.push(`- Missing English in en.json: **${missing}**`);
    md.push('- Excluded: admin UI, CMS/DB bodies (hero, story, legal, journal articles, product copy)');
    md.push('');
    md.push('## Counts by section');
    md.push('');
    for (const c of counts) {
        md.push(`- **${c.title}**: ${c.count}`);
    }
    md.push('');

    for (const section of SECTIONS) {
        const rows = bySection.get(section.id) ?? [];
        md.push(`## ${section.title}`);
        md.push('');
        md.push(`- Route: \`${section.route}\``);
        md.push(`- Count: ${rows.length}`);
        md.push('');
        if (rows.length === 0) {
            md.push('_No static UI keys found in scanned files._');
            md.push('');
            continue;
        }
        for (const row of rows) {
            const missingMark = row.note.includes('MISSING_EN')
                ? ' _(missing from en.json)_'
                : '';
            md.push(`- **${row.english}**${missingMark}`);
            if (row.dutchKey && !row.dutchKey.startsWith('(opening')) {
                md.push(`  - Key (NL): ${row.dutchKey}`);
            } else if (row.dutchKey.startsWith('(opening')) {
                md.push(
                    `  - Source: ${row.note.split(' | ')[0] || row.dutchKey}`,
                );
            }
            if (row.kind === 'aria') {
                md.push(`  - Kind: hardcoded aria-label`);
            }
            if (row.note.includes('MISSING_EN')) {
                md.push(`  - Note: ${row.note}`);
            }
            md.push(`  - File: \`${row.sourceFile}\``);
        }
        md.push('');
    }

    await writeFile(
        path.join(outDir, 'english-by-page.md'),
        md.join('\n'),
        'utf8',
    );

    // Summary JSON for quick stats
    await writeFile(
        path.join(outDir, 'summary.json'),
        JSON.stringify(
            {
                generatedAt: new Date().toISOString(),
                total,
                missing,
                sections: counts,
            },
            null,
            2,
        ) + '\n',
        'utf8',
    );

    console.log(`Wrote ${total} rows (${missing} MISSING_EN) to ${outDir}`);
    for (const c of counts) {
        console.log(`  ${c.id}: ${c.count}`);
    }
}

main().catch((error) => {
    console.error(error);
    process.exit(1);
});
