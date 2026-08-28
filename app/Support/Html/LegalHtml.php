<?php

namespace App\Support\Html;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;

class LegalHtml
{
    /**
     * @var list<string>
     */
    private const DROP_TAGS = [
        'script', 'style', 'iframe', 'object', 'embed', 'form', 'input',
        'textarea', 'button', 'link', 'meta', 'svg', 'math', 'video', 'audio',
        'source', 'canvas', 'noscript', 'template', 'img',
    ];

    /**
     * @var list<string>
     */
    private const ALLOWED_TAGS = [
        'a', 'blockquote', 'br', 'details', 'em', 'h1', 'h2', 'h3', 'h4', 'h5',
        'h6', 'hr', 'i', 'li', 'mark', 'ol', 'p', 's', 'span', 'strong', 'b',
        'sub', 'summary', 'sup', 'table', 'tbody', 'td', 'th', 'thead', 'tr', 'u',
        'ul',
    ];

    /**
     * @var array<string, list<string>>
     */
    private const ALLOWED_ATTRIBUTES = [
        'a' => ['href', 'rel', 'target', 'class', 'id'],
        'details' => ['class', 'style', 'id', 'open'],
        'h1' => ['class', 'style', 'id'],
        'h2' => ['class', 'style', 'id'],
        'h3' => ['class', 'style', 'id'],
        'h4' => ['class', 'style', 'id'],
        'h5' => ['class', 'style', 'id'],
        'h6' => ['class', 'style', 'id'],
        'mark' => ['class', 'style', 'data-color'],
        'p' => ['class', 'style', 'id'],
        'span' => ['class', 'style'],
        'summary' => ['class', 'style', 'id'],
        'td' => ['class', 'colspan', 'rowspan', 'style'],
        'th' => ['class', 'colspan', 'rowspan', 'style'],
    ];

    public static function sanitize(string $html): string
    {
        $html = trim($html);

        if ($html === '') {
            return '';
        }

        if (! self::looksLikeHtml($html)) {
            $html = self::fromLegacyMarkdown($html);
        }

        $previous = libxml_use_internal_errors(true);

        $document = new DOMDocument('1.0', 'UTF-8');
        $wrapped = '<?xml encoding="UTF-8"><div id="legal-root">'.$html.'</div>';
        $loaded = $document->loadHTML($wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        if ($loaded === false) {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);

            return '';
        }

        $root = $document->getElementById('legal-root');

        if ($root === null) {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);

            return '';
        }

        self::sanitizeNode($root);

        $output = '';

        foreach ($root->childNodes as $child) {
            $output .= $document->saveHTML($child);
        }

        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return trim($output);
    }

    public static function isBlank(string $html): bool
    {
        $text = trim(html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        return $text === '' && ! preg_match('/<(hr|table|ul|ol|details)\b/i', $html);
    }

    public static function looksLikeHtml(string $value): bool
    {
        return (bool) preg_match('/<[a-z][\s\S]*>/i', $value);
    }

    public static function forEditor(string $value): string
    {
        return self::sanitize($value);
    }

    public static function fromLegacyMarkdown(string $markdown): string
    {
        $markdown = str_replace(["\r\n", "\r"], "\n", $markdown);
        $blocks = preg_split("/\n{2,}/", trim($markdown)) ?: [];
        $html = [];

        foreach ($blocks as $block) {
            $block = trim($block);

            if ($block === '') {
                continue;
            }

            if (preg_match('/^##\s+(.+)$/', $block, $matches) === 1 && ! str_contains($block, "\n")) {
                $html[] = '<h2>'.e($matches[1]).'</h2>';

                continue;
            }

            $lines = explode("\n", $block);
            $isList = $lines !== [] && collect($lines)->every(
                fn (string $line): bool => (bool) preg_match('/^-\s+/', $line),
            );

            if ($isList) {
                $items = array_map(
                    function (string $line): string {
                        $text = preg_replace('/^-\s+/', '', $line) ?? $line;

                        return '<li>'.e($text).'</li>';
                    },
                    $lines,
                );
                $html[] = '<ul>'.implode('', $items).'</ul>';

                continue;
            }

            $html[] = '<p>'.nl2br(e($block), false).'</p>';
        }

        return implode('', $html);
    }

    private static function sanitizeNode(DOMNode $node): void
    {
        $children = [];

        foreach ($node->childNodes as $child) {
            $children[] = $child;
        }

        foreach ($children as $child) {
            if ($child instanceof DOMText) {
                continue;
            }

            if (! $child instanceof DOMElement) {
                $child->parentNode?->removeChild($child);

                continue;
            }

            $tag = strtolower($child->tagName);

            if (in_array($tag, self::DROP_TAGS, true)) {
                $child->parentNode?->removeChild($child);

                continue;
            }

            if (! in_array($tag, self::ALLOWED_TAGS, true)) {
                self::sanitizeNode($child);
                self::unwrap($child);

                continue;
            }

            self::sanitizeAttributes($child, $tag);
            self::sanitizeNode($child);
        }
    }

    private static function sanitizeAttributes(DOMElement $element, string $tag): void
    {
        $allowed = self::ALLOWED_ATTRIBUTES[$tag] ?? [];
        $names = [];

        foreach ($element->attributes ?? [] as $attribute) {
            $names[] = $attribute->name;
        }

        foreach ($names as $name) {
            $lower = strtolower($name);

            if (str_starts_with($lower, 'on')) {
                $element->removeAttribute($name);

                continue;
            }

            if (! in_array($lower, $allowed, true)) {
                $element->removeAttribute($name);

                continue;
            }

            $value = $element->getAttribute($name);

            if ($lower === 'href' && ! self::isSafeHref($value)) {
                $element->removeAttribute($name);

                continue;
            }

            if ($lower === 'target' && ! in_array($value, ['_blank', '_self'], true)) {
                $element->removeAttribute($name);

                continue;
            }

            if ($lower === 'rel') {
                $element->setAttribute('rel', 'noopener noreferrer');

                continue;
            }

            if ($lower === 'class' && ! self::isSafeClass($value)) {
                $element->removeAttribute($name);

                continue;
            }

            if ($lower === 'id' && ! self::isSafeId($value)) {
                $element->removeAttribute($name);

                continue;
            }

            if ($lower === 'open' && ! in_array(strtolower($value), ['', 'open'], true)) {
                $element->removeAttribute($name);

                continue;
            }

            if (in_array($lower, ['colspan', 'rowspan'], true) && ! ctype_digit($value)) {
                $element->removeAttribute($name);

                continue;
            }

            if ($lower === 'style') {
                $clean = self::sanitizeStyle($value);

                if ($clean === '') {
                    $element->removeAttribute($name);

                    continue;
                }

                $element->setAttribute('style', $clean);

                continue;
            }

            if ($lower === 'data-color' && ! self::isSafeCssColor($value)) {
                $element->removeAttribute($name);
            }
        }

        if ($tag === 'a' && $element->hasAttribute('href') && $element->getAttribute('target') === '_blank') {
            $element->setAttribute('rel', 'noopener noreferrer');
        }
    }

    private static function unwrap(DOMElement $element): void
    {
        $parent = $element->parentNode;

        if ($parent === null) {
            return;
        }

        while ($element->firstChild) {
            $parent->insertBefore($element->firstChild, $element);
        }

        $parent->removeChild($element);
    }

    private static function isSafeHref(string $href): bool
    {
        $href = trim($href);

        if ($href === '') {
            return false;
        }

        $lower = strtolower($href);

        if (str_starts_with($lower, 'javascript:')
            || str_starts_with($lower, 'vbscript:')
            || str_starts_with($lower, 'data:')) {
            return false;
        }

        if (str_starts_with($href, '#')
            || str_starts_with($href, '/')
            || str_starts_with($lower, 'mailto:')) {
            return true;
        }

        return (bool) preg_match('#^https?://#i', $href);
    }

    private static function isSafeClass(string $class): bool
    {
        return (bool) preg_match('/^[a-zA-Z0-9_\-\s]+$/', $class);
    }

    private static function isSafeId(string $id): bool
    {
        return (bool) preg_match('/^[A-Za-z][A-Za-z0-9_.:-]*$/', $id);
    }

    private static function sanitizeStyle(string $style): string
    {
        $safe = [];

        foreach (explode(';', $style) as $declaration) {
            $declaration = trim($declaration);

            if ($declaration === '' || ! str_contains($declaration, ':')) {
                continue;
            }

            [$property, $value] = array_map('trim', explode(':', $declaration, 2));
            $property = strtolower($property);

            if (preg_match('/url\s*\(|expression\s*\(|javascript:|!important/i', $value) === 1) {
                continue;
            }

            $kept = match ($property) {
                'text-align' => preg_match('/^(left|center|right|justify)$/i', $value) === 1 ? strtolower($value) : null,
                'color' => strcasecmp($value, 'inherit') === 0 || self::isSafeCssColor($value) ? $value : null,
                'background-color' => self::isSafeCssColor($value) ? $value : null,
                'font-size' => self::isSafeFontSize($value) ? $value : null,
                'font-family' => self::isSafeFontFamily($value) ? $value : null,
                'line-height' => preg_match('/^[1-3](\.\d+)?$/', $value) === 1 ? $value : null,
                default => null,
            };

            if ($kept !== null) {
                $safe[] = $property.': '.$kept;
            }
        }

        return implode('; ', $safe);
    }

    private static function isSafeCssColor(string $value): bool
    {
        return (bool) preg_match('/^#(?:[0-9a-f]{3}|[0-9a-f]{6})$/i', $value)
            || (bool) preg_match('/^rgb\(\s*(?:[01]?\d{1,2}|2[0-4]\d|25[0-5])\s*,\s*(?:[01]?\d{1,2}|2[0-4]\d|25[0-5])\s*,\s*(?:[01]?\d{1,2}|2[0-4]\d|25[0-5])\s*\)$/i', $value);
    }

    private static function isSafeFontSize(string $value): bool
    {
        if (preg_match('/^(\d+(?:\.\d+)?)px$/i', $value, $matches) === 1) {
            $px = (float) $matches[1];

            return $px >= 10 && $px <= 32;
        }

        if (preg_match('/^(\d+(?:\.\d+)?)(?:rem|em)$/i', $value, $matches) === 1) {
            $em = (float) $matches[1];

            return $em >= 0.75 && $em <= 2.5;
        }

        return false;
    }

    private static function isSafeFontFamily(string $value): bool
    {
        $allowed = ['montserrat', 'baskervville', 'georgia', 'ui-sans-serif', 'sans-serif', 'serif', 'system-ui'];

        foreach (explode(',', $value) as $family) {
            $token = strtolower(trim($family, " \t\"'"));

            if ($token === '' || ! in_array($token, $allowed, true)) {
                return false;
            }
        }

        return true;
    }
}
