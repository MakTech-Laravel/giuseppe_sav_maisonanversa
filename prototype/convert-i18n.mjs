/*
 * One-off converter: prototype/i18n.js -> lang/en.json + lang/fr.json.
 *
 * The prototype keys every translation by its Dutch source string, which is
 * also how Laravel's JSON translation files work, so the mapping is direct.
 * Entries whose translation was truncated in the prototype fall back to the
 * Dutch text and are reported in docs/i18n-gaps.md for the client to complete.
 *
 * Usage: node prototype/convert-i18n.mjs [--analyse]
 */

import { mkdirSync, readFileSync, writeFileSync } from 'node:fs';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = resolve(dirname(fileURLToPath(import.meta.url)), '..');
const source = readFileSync(resolve(root, 'prototype/i18n.js'), 'utf8');

const open = source.indexOf('const I18N = {');
const close = source.indexOf('\n};', open);

if (open === -1 || close === -1) {
    throw new Error('Could not locate the I18N object literal.');
}

const literal = source.slice(open + 'const I18N = '.length, close + 2);
const dictionary = new Function(`return ${literal}`)();
const entries = Object.entries(dictionary);

/**
 * A translation is treated as truncated when the Dutch source ends mid-sentence
 * but the translation does not, or vice versa. The prototype cut a number of
 * long values at roughly 120 characters, which leaves the translation ending
 * abruptly while the Dutch key still reads as a complete fragment.
 */
function isTruncated(nl, value) {
    if (!value || typeof value !== 'string') {
        return true;
    }

    // The prototype's cut-off point. Shorter values were never at risk.
    if (value.length < 100) {
        return false;
    }

    const endsCleanly = (text) => /[.!?:;,)\u2192\u2014"'\s]$/.test(text);

    return !endsCleanly(value) && endsCleanly(nl);
}

const gaps = [];
const dictionaries = { en: {}, fr: {} };

for (const [nl, value] of entries) {
    for (const locale of ['en', 'fr']) {
        const translation = value?.[locale];

        if (isTruncated(nl, translation)) {
            // Fall back to Dutch so the page never renders a half sentence.
            dictionaries[locale][nl] = nl;
            gaps.push({
                locale,
                nl,
                translation: translation ?? '',
                reason:
                    !translation || translation.trim() === ''
                        ? 'missing'
                        : 'truncated',
            });

            continue;
        }

        dictionaries[locale][nl] = translation;
    }
}

if (process.argv.includes('--analyse')) {
    const pairs = entries.flatMap(([nl, v]) =>
        ['en', 'fr'].map((locale) => ({
            nl,
            locale,
            value: v?.[locale] ?? '',
        })),
    );
    const missing = pairs.filter((p) => p.value.trim() === '');
    const identical = pairs.filter(
        (p) => p.value.trim() !== '' && p.value === p.nl,
    );
    const cut = pairs.filter((p) => isTruncated(p.nl, p.value));
    const keyEndsOpen = entries.filter(([nl]) => /[a-z] $/.test(nl));

    console.log(`entries:                       ${entries.length}`);
    console.log(`translation pairs (en+fr):     ${pairs.length}`);
    console.log(`missing / empty:               ${missing.length}`);
    console.log(`identical to Dutch:            ${identical.length}`);
    console.log(`detected as cut mid-sentence:  ${cut.length}`);
    console.log(`NL keys ending mid-phrase:     ${keyEndsOpen.length}`);

    for (const p of missing) {
        console.log(`  empty [${p.locale}]: ${p.nl}`);
    }

    process.exit(0);
}

for (const locale of ['en', 'fr']) {
    const sorted = Object.fromEntries(
        Object.entries(dictionaries[locale]).sort(([a], [b]) =>
            a.localeCompare(b, 'nl'),
        ),
    );

    mkdirSync(resolve(root, 'lang'), { recursive: true });
    writeFileSync(
        resolve(root, `lang/${locale}.json`),
        `${JSON.stringify(sorted, null, 4)}\n`,
    );
}

const byLocale = (locale) => gaps.filter((gap) => gap.locale === locale);

const truncatedCount = gaps.filter((g) => g.reason === 'truncated').length;
const missingCount = gaps.filter((g) => g.reason === 'missing').length;

const report = `# Translation gaps

Generated from the prototype dictionary (\`prototype/i18n.js\`) by
\`node prototype/convert-i18n.mjs\`. Regenerate after any change to that file.

Of ${entries.length * 2} translations (${entries.length} Dutch source strings x EN + FR),
${gaps.length} are unusable: ${truncatedCount} cut off mid-sentence, ${missingCount} empty.
Each one falls back to the Dutch source text, so a page never renders half a
sentence. These need a human translation before launch.

The remaining ${entries.length * 2 - gaps.length} translations are complete.

${['en', 'fr']
    .map(
        (locale) => `## ${locale.toUpperCase()} (${byLocale(locale).length})

${
    byLocale(locale).length === 0
        ? '_None._'
        : byLocale(locale)
              .map(
                  (gap) =>
                      `- **Dutch source:** ${gap.nl}\n  - **${gap.reason === 'missing' ? 'Missing' : 'Cut off'}:** ${gap.reason === 'missing' ? '_(empty)_' : gap.translation}`,
              )
              .join('\n')
}`,
    )
    .join('\n\n')}
`;

mkdirSync(resolve(root, 'docs'), { recursive: true });
writeFileSync(resolve(root, 'docs/i18n-gaps.md'), report);

console.log(`${entries.length} entries -> lang/en.json, lang/fr.json`);
console.log(`${gaps.length} truncated translations -> docs/i18n-gaps.md`);
