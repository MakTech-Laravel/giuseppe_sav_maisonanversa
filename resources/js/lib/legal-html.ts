const DROP_TAGS = new Set([
    'SCRIPT',
    'STYLE',
    'IFRAME',
    'OBJECT',
    'EMBED',
    'FORM',
    'INPUT',
    'TEXTAREA',
    'BUTTON',
    'LINK',
    'META',
    'SVG',
    'MATH',
    'VIDEO',
    'AUDIO',
    'SOURCE',
    'CANVAS',
    'NOSCRIPT',
    'TEMPLATE',
    'IMG',
]);

const ALLOWED_TAGS = new Set([
    'A',
    'BLOCKQUOTE',
    'BR',
    'DETAILS',
    'EM',
    'H1',
    'H2',
    'H3',
    'H4',
    'H5',
    'H6',
    'HR',
    'I',
    'LI',
    'MARK',
    'OL',
    'P',
    'S',
    'SPAN',
    'STRONG',
    'B',
    'SUB',
    'SUMMARY',
    'SUP',
    'TABLE',
    'TBODY',
    'TD',
    'TH',
    'THEAD',
    'TR',
    'U',
    'UL',
]);

const ALLOWED_ATTRIBUTES: Record<string, Set<string>> = {
    A: new Set(['href', 'rel', 'target', 'class', 'id']),
    DETAILS: new Set(['class', 'style', 'id', 'open']),
    H1: new Set(['class', 'style', 'id']),
    H2: new Set(['class', 'style', 'id']),
    H3: new Set(['class', 'style', 'id']),
    H4: new Set(['class', 'style', 'id']),
    H5: new Set(['class', 'style', 'id']),
    H6: new Set(['class', 'style', 'id']),
    MARK: new Set(['class', 'style', 'data-color']),
    P: new Set(['class', 'style', 'id']),
    SPAN: new Set(['class', 'style']),
    SUMMARY: new Set(['class', 'style', 'id']),
    TD: new Set(['class', 'colspan', 'rowspan', 'style']),
    TH: new Set(['class', 'colspan', 'rowspan', 'style']),
};

function isSafeHref(href: string): boolean {
    const value = href.trim();

    if (value === '') {
        return false;
    }

    const lower = value.toLowerCase();

    if (
        lower.startsWith('javascript:') ||
        lower.startsWith('vbscript:') ||
        lower.startsWith('data:')
    ) {
        return false;
    }

    if (
        value.startsWith('#') ||
        value.startsWith('/') ||
        lower.startsWith('mailto:')
    ) {
        return true;
    }

    return /^https?:\/\//i.test(value);
}

function isSafeClass(value: string): boolean {
    return /^[a-zA-Z0-9_\-\s]+$/.test(value);
}

function isSafeId(value: string): boolean {
    return /^[A-Za-z][A-Za-z0-9_.:-]*$/.test(value);
}

const SAFE_FONT_FAMILIES = new Set([
    'montserrat',
    'baskervville',
    'georgia',
    'ui-sans-serif',
    'sans-serif',
    'serif',
    'system-ui',
]);

function isUnsafeCssValue(value: string): boolean {
    return /url\s*\(|expression\s*\(|javascript:|!important/i.test(value);
}

function isSafeCssColor(value: string): boolean {
    return (
        /^#(?:[0-9a-f]{3}|[0-9a-f]{6})$/i.test(value) ||
        /^rgb\(\s*(?:[01]?\d{1,2}|2[0-4]\d|25[0-5])\s*,\s*(?:[01]?\d{1,2}|2[0-4]\d|25[0-5])\s*,\s*(?:[01]?\d{1,2}|2[0-4]\d|25[0-5])\s*\)$/i.test(
            value,
        )
    );
}

function isSafeFontSize(value: string): boolean {
    const px = /^(\d+(?:\.\d+)?)px$/i.exec(value);

    if (px) {
        const size = Number(px[1]);

        return size >= 10 && size <= 32;
    }

    const em = /^(\d+(?:\.\d+)?)(?:rem|em)$/i.exec(value);

    if (em) {
        const size = Number(em[1]);

        return size >= 0.75 && size <= 2.5;
    }

    return false;
}

function isSafeFontFamily(value: string): boolean {
    return value.split(',').every((family) => {
        const token = family
            .trim()
            .replace(/^['"]|['"]$/g, '')
            .toLowerCase();

        return token !== '' && SAFE_FONT_FAMILIES.has(token);
    });
}

function sanitizeStyle(style: string): string {
    const safe: string[] = [];

    for (const declaration of style.split(';')) {
        const trimmed = declaration.trim();
        const separator = trimmed.indexOf(':');

        if (trimmed === '' || separator === -1) {
            continue;
        }

        const property = trimmed.slice(0, separator).trim().toLowerCase();
        const value = trimmed.slice(separator + 1).trim();

        if (isUnsafeCssValue(value)) {
            continue;
        }

        let kept: string | null = null;

        if (
            property === 'text-align' &&
            /^(left|center|right|justify)$/i.test(value)
        ) {
            kept = value.toLowerCase();
        } else if (property === 'color') {
            kept =
                /^inherit$/i.test(value) || isSafeCssColor(value)
                    ? value
                    : null;
        } else if (property === 'background-color') {
            kept = isSafeCssColor(value) ? value : null;
        } else if (property === 'font-size') {
            kept = isSafeFontSize(value) ? value : null;
        } else if (property === 'font-family') {
            kept = isSafeFontFamily(value) ? value : null;
        } else if (
            property === 'line-height' &&
            /^[1-3](\.\d+)?$/.test(value)
        ) {
            kept = value;
        }

        if (kept !== null) {
            safe.push(`${property}: ${kept}`);
        }
    }

    return safe.join('; ');
}

function sanitizeElement(element: Element): void {
    const children = Array.from(element.childNodes);

    for (const child of children) {
        if (child.nodeType === Node.TEXT_NODE) {
            continue;
        }

        if (child.nodeType !== Node.ELEMENT_NODE) {
            child.parentNode?.removeChild(child);
            continue;
        }

        const el = child as Element;
        const tag = el.tagName.toUpperCase();

        if (DROP_TAGS.has(tag)) {
            el.parentNode?.removeChild(el);
            continue;
        }

        if (!ALLOWED_TAGS.has(tag)) {
            sanitizeElement(el);
            unwrap(el);
            continue;
        }

        sanitizeAttributes(el, tag);
        sanitizeElement(el);
    }
}

function unwrap(element: Element): void {
    const parent = element.parentNode;

    if (!parent) {
        return;
    }

    while (element.firstChild) {
        parent.insertBefore(element.firstChild, element);
    }

    parent.removeChild(element);
}

function sanitizeAttributes(element: Element, tag: string): void {
    const allowed = ALLOWED_ATTRIBUTES[tag] ?? new Set<string>();
    const names = Array.from(element.attributes).map(
        (attribute) => attribute.name,
    );

    for (const name of names) {
        const lower = name.toLowerCase();

        if (lower.startsWith('on') || !allowed.has(lower)) {
            element.removeAttribute(name);
            continue;
        }

        const value = element.getAttribute(name) ?? '';

        if (lower === 'href' && !isSafeHref(value)) {
            element.removeAttribute(name);
            continue;
        }

        if (lower === 'target' && value !== '_blank' && value !== '_self') {
            element.removeAttribute(name);
            continue;
        }

        if (lower === 'rel') {
            element.setAttribute('rel', 'noopener noreferrer');
            continue;
        }

        if (lower === 'class' && !isSafeClass(value)) {
            element.removeAttribute(name);
            continue;
        }

        if (lower === 'id' && !isSafeId(value)) {
            element.removeAttribute(name);
            continue;
        }

        if (
            lower === 'open' &&
            value !== '' &&
            value.toLowerCase() !== 'open'
        ) {
            element.removeAttribute(name);
            continue;
        }

        if (
            (lower === 'colspan' || lower === 'rowspan') &&
            !/^\d+$/.test(value)
        ) {
            element.removeAttribute(name);
            continue;
        }

        if (lower === 'style') {
            const clean = sanitizeStyle(value);

            if (clean === '') {
                element.removeAttribute(name);
            } else {
                element.setAttribute('style', clean);
            }

            continue;
        }

        if (lower === 'data-color' && !isSafeCssColor(value)) {
            element.removeAttribute(name);
        }
    }

    if (
        tag === 'A' &&
        element.hasAttribute('href') &&
        element.getAttribute('target') === '_blank'
    ) {
        element.setAttribute('rel', 'noopener noreferrer');
    }
}

export function isBlankLegalHtml(html: string): boolean {
    const text = html
        .replace(/<[^>]+>/g, '')
        .replace(/&nbsp;/g, ' ')
        .trim();

    return text === '' && !/<(hr|table|ul|ol|details)\b/i.test(html);
}

export function sanitizeLegalHtml(html: string): string {
    const trimmed = html.trim();

    if (trimmed === '' || typeof document === 'undefined') {
        return trimmed === '' ? '' : html;
    }

    const template = document.createElement('template');
    template.innerHTML = trimmed;
    const root = document.createElement('div');
    root.append(...Array.from(template.content.childNodes));
    sanitizeElement(root);

    return root.innerHTML.trim();
}

export function prettyPrintLegalHtml(html: string): string {
    const sanitized = sanitizeLegalHtml(html);

    if (sanitized === '' || typeof document === 'undefined') {
        return sanitized;
    }

    const template = document.createElement('template');
    template.innerHTML = sanitized;

    return formatNodes(Array.from(template.content.childNodes), 0).trim();
}

function formatNodes(nodes: Node[], depth: number): string {
    const indent = '  '.repeat(depth);

    return nodes
        .map((node) => {
            if (node.nodeType === Node.TEXT_NODE) {
                const text = (node.textContent ?? '')
                    .replace(/\s+/g, ' ')
                    .trim();

                return text === '' ? '' : `${indent}${text}\n`;
            }

            if (node.nodeType !== Node.ELEMENT_NODE) {
                return '';
            }

            const el = node as Element;
            const tag = el.tagName.toLowerCase();
            const attrs = Array.from(el.attributes)
                .map((attribute) => ` ${attribute.name}="${attribute.value}"`)
                .join('');

            if (['br', 'hr'].includes(tag)) {
                return `${indent}<${tag}${attrs}>\n`;
            }

            const inner = formatNodes(Array.from(el.childNodes), depth + 1);

            if (inner.trim() === '') {
                return `${indent}<${tag}${attrs}></${tag}>\n`;
            }

            return `${indent}<${tag}${attrs}>\n${inner}${indent}</${tag}>\n`;
        })
        .join('');
}
