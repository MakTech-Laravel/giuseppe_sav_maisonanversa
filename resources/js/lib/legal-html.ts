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
    'EM',
    'H2',
    'H3',
    'H4',
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
    A: new Set(['href', 'rel', 'target', 'class']),
    H2: new Set(['class', 'style']),
    H3: new Set(['class', 'style']),
    H4: new Set(['class', 'style']),
    MARK: new Set(['class']),
    P: new Set(['class', 'style']),
    SPAN: new Set(['class']),
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

function isSafeStyle(value: string): boolean {
    return /^\s*text-align:\s*(left|center|right|justify)\s*;?\s*$/i.test(
        value,
    );
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
    const names = Array.from(element.attributes).map((attribute) => attribute.name);

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

        if (
            (lower === 'colspan' || lower === 'rowspan') &&
            !/^\d+$/.test(value)
        ) {
            element.removeAttribute(name);
            continue;
        }

        if (lower === 'style' && !isSafeStyle(value)) {
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
    const text = html.replace(/<[^>]+>/g, '').replace(/&nbsp;/g, ' ').trim();

    return text === '' && !/<(hr|table|ul|ol)\b/i.test(html);
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
                const text = (node.textContent ?? '').replace(/\s+/g, ' ').trim();

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
