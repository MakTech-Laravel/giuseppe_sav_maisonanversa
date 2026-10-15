/**
 * Renders a Maison Anversa match card to PNG.
 *
 * Input: the JSON payload from App\Support\MatchCardPresenter, on stdin or
 * from the file given as the second argument.
 * Output: the PNG written to the path given as the first argument.
 *
 * Satori only understands flexbox: every element with more than one child
 * needs `display: flex`.
 */
import { readFileSync, writeFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';
import { Resvg } from '@resvg/resvg-js';
import satori from 'satori';

const root = dirname(fileURLToPath(import.meta.url));

const font = (file) => readFileSync(join(root, 'fonts', file));
const dataUri = (file) =>
    `data:image/png;base64,${readFileSync(join(root, 'assets', file)).toString('base64')}`;

const fonts = [
    {
        name: 'Baskervville',
        data: font('Baskervville-Regular.ttf'),
        weight: 400,
        style: 'normal',
    },
    {
        name: 'Baskervville',
        data: font('Baskervville-Italic.ttf'),
        weight: 400,
        style: 'italic',
    },
    {
        name: 'Montserrat',
        data: font('Montserrat-Light.ttf'),
        weight: 300,
        style: 'normal',
    },
    {
        name: 'Montserrat',
        data: font('Montserrat-Regular.ttf'),
        weight: 400,
        style: 'normal',
    },
    {
        name: 'Montserrat',
        data: font('Montserrat-Medium.ttf'),
        weight: 500,
        style: 'normal',
    },
];

function h(type, props, ...children) {
    const flat = children
        .flat()
        .filter(
            (child) => child !== null && child !== false && child !== undefined,
        );

    return {
        type,
        props: { ...props, children: flat.length === 1 ? flat[0] : flat },
    };
}

/** Story and Post share one layout; only the scale and palette differ. */
const SCALE = {
    story: {
        padding: 150,
        inset: 80,
        monogram: 132,
        wordmark: 34,
        eyebrow: 24,
        club: 96,
        date: 26,
        circle: 156,
        initials: 54,
        name: 36,
        edition: 20,
        resultLabel: 22,
        result: 84,
        closing: 46,
        footer: 20,
        gap: 80,
    },
    post: {
        padding: 104,
        inset: 90,
        monogram: 92,
        wordmark: 26,
        eyebrow: 20,
        club: 72,
        date: 21,
        circle: 128,
        initials: 44,
        name: 30,
        edition: 17,
        resultLabel: 18,
        result: 60,
        closing: 36,
        footer: 17,
        gap: 44,
    },
};

const sans = (size, color, weight = 400, spacing = 0.32) => ({
    fontFamily: 'Montserrat',
    fontWeight: weight,
    fontSize: size,
    color,
    letterSpacing: size * spacing,
    textTransform: 'uppercase',
});

const serif = (size, color, italic = false) => ({
    fontFamily: 'Baskervville',
    fontStyle: italic ? 'italic' : 'normal',
    fontSize: size,
    color,
    lineHeight: 1.15,
});

function rule(color, width = 64) {
    return h('div', {
        style: { width, height: 1, backgroundColor: color, opacity: 0.7 },
    });
}

function player(card, scale, palette, person) {
    const crowded = card.players.length > 4;
    const size = crowded ? Math.round(scale.circle * 0.78) : scale.circle;
    const gold = person.edition !== null;

    return h(
        'div',
        {
            style: {
                display: 'flex',
                flexDirection: 'column',
                alignItems: 'center',
                width: size + 40,
                marginBottom: 24,
            },
        },
        h(
            'div',
            {
                style: {
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                    width: size,
                    height: size,
                    borderRadius: size,
                    border: gold
                        ? `2px solid ${palette.gold}`
                        : `1px solid ${palette.eyebrow}`,
                    ...serif(
                        crowded
                            ? Math.round(scale.initials * 0.8)
                            : scale.initials,
                        palette.text,
                    ),
                },
            },
            person.initials,
        ),
        person.first_name
            ? h(
                  'div',
                  {
                      style: {
                          marginTop: 20,
                          maxWidth: size + 40,
                          textAlign: 'center',
                          ...serif(
                              crowded
                                  ? Math.round(scale.name * 0.82)
                                  : scale.name,
                              palette.text,
                          ),
                      },
                  },
                  person.first_name,
              )
            : null,
        gold
            ? h(
                  'div',
                  {
                      style: {
                          marginTop: 10,
                          ...sans(scale.edition, palette.gold, 500, 0.24),
                      },
                  },
                  person.edition,
              )
            : null,
    );
}

function card(payload) {
    const scale = SCALE[payload.format];
    const palette = payload.palette;
    const isStory = payload.format === 'story';

    const background = isStory
        ? {
              backgroundColor: palette.background,
              backgroundImage: `radial-gradient(circle at 50% 36%, ${palette.highlight} 0%, ${palette.background} 62%)`,
          }
        : { backgroundColor: palette.background };

    return h(
        'div',
        {
            style: {
                position: 'relative',
                display: 'flex',
                width: payload.width,
                height: payload.height,
                ...background,
            },
        },
        h('img', {
            src: dataUri('house.png'),
            width: payload.width * 0.96,
            height: payload.width * 0.96 * (1168 / 1516),
            style: {
                position: 'absolute',
                left: payload.width * 0.02,
                bottom: isStory ? 260 : 70,
                opacity: isStory ? 0.12 : 0.14,
            },
        }),
        palette.frame
            ? h('div', {
                  style: {
                      position: 'absolute',
                      top: 40,
                      left: 40,
                      right: 40,
                      bottom: 40,
                      border: `2px solid ${palette.frame}`,
                  },
              })
            : null,
        h(
            'div',
            {
                style: {
                    position: 'absolute',
                    top: scale.padding,
                    bottom: scale.padding,
                    left: scale.inset,
                    right: scale.inset,
                    display: 'flex',
                    flexDirection: 'column',
                    alignItems: 'center',
                    justifyContent: 'space-between',
                },
            },
            h(
                'div',
                {
                    style: {
                        display: 'flex',
                        flexDirection: 'column',
                        alignItems: 'center',
                    },
                },
                h('img', {
                    src: dataUri(
                        isStory ? 'monogram-light.png' : 'monogram-dark.png',
                    ),
                    width: scale.monogram,
                    height: scale.monogram,
                }),
                h(
                    'div',
                    {
                        style: {
                            marginTop: 8,
                            ...serif(scale.wordmark, palette.text),
                            letterSpacing: scale.wordmark * 0.3,
                            textTransform: 'uppercase',
                        },
                    },
                    'Maison Anversa',
                ),
            ),
            h(
                'div',
                {
                    style: {
                        display: 'flex',
                        flexDirection: 'column',
                        alignItems: 'center',
                        width: '100%',
                    },
                },
                h(
                    'div',
                    { style: sans(scale.eyebrow, palette.eyebrow, 500, 0.4) },
                    payload.eyebrow,
                ),
                h(
                    'div',
                    {
                        style: {
                            marginTop: scale.gap * 0.45,
                            textAlign: 'center',
                            ...serif(scale.club, palette.text),
                        },
                    },
                    payload.club,
                ),
                h(
                    'div',
                    {
                        style: {
                            marginTop: scale.gap * 0.4,
                            ...sans(scale.date, palette.eyebrow, 300, 0.22),
                        },
                    },
                    payload.date_line,
                ),
                h(
                    'div',
                    { style: { display: 'flex', marginTop: scale.gap * 0.7 } },
                    rule(palette.gold),
                ),
                h(
                    'div',
                    {
                        style: {
                            display: 'flex',
                            flexWrap: 'wrap',
                            justifyContent: 'center',
                            width: '100%',
                            marginTop: scale.gap * 0.8,
                        },
                    },
                    payload.players.map((person) =>
                        player(payload, scale, palette, person),
                    ),
                ),
                payload.result
                    ? h(
                          'div',
                          {
                              style: {
                                  display: 'flex',
                                  flexDirection: 'column',
                                  alignItems: 'center',
                                  marginTop: scale.gap * 0.5,
                              },
                          },
                          h(
                              'div',
                              {
                                  style: sans(
                                      scale.resultLabel,
                                      palette.eyebrow,
                                      500,
                                      0.4,
                                  ),
                              },
                              payload.result.label,
                          ),
                          h(
                              'div',
                              {
                                  style: {
                                      marginTop: 14,
                                      ...serif(scale.result, palette.text),
                                  },
                              },
                              payload.result.line,
                          ),
                      )
                    : null,
            ),
            h(
                'div',
                {
                    style: {
                        display: 'flex',
                        flexDirection: 'column',
                        alignItems: 'center',
                    },
                },
                h(
                    'div',
                    { style: serif(scale.closing, palette.closing, true) },
                    payload.closing,
                ),
                h(
                    'div',
                    {
                        style: {
                            marginTop: scale.gap * 0.6,
                            ...sans(scale.footer, palette.footer, 400, 0.35),
                        },
                    },
                    payload.footer,
                ),
            ),
        ),
    );
}

async function main() {
    const [output, input] = process.argv.slice(2);

    if (!output) {
        throw new Error(
            'Usage: node render.mjs <output.png> [payload.json] (payload on stdin when omitted)',
        );
    }

    const payload = JSON.parse(readFileSync(input ?? 0, 'utf8'));
    const svg = await satori(card(payload), {
        width: payload.width,
        height: payload.height,
        fonts,
    });
    const png = new Resvg(svg, {
        fitTo: { mode: 'width', value: payload.width },
    })
        .render()
        .asPng();

    writeFileSync(output, png);
}

main().catch((error) => {
    process.stderr.write(`${error?.stack ?? error}\n`);
    process.exit(1);
});
