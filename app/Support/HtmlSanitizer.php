<?php

declare(strict_types=1);

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;

/**
 * Strict allowlist HTML sanitizer for rich content (blog articles, AI output).
 *
 * Unknown tags are unwrapped (their text is kept), dangerous tags are dropped
 * with their content, every attribute not on the list is removed, and links
 * may only use http, https, mailto or relative URLs.
 */
final class HtmlSanitizer
{
    private const ALLOWED_TAGS = [
        'p' => [], 'br' => [], 'hr' => [], 'span' => [], 'div' => [],
        'h1' => [], 'h2' => [], 'h3' => [], 'h4' => [], 'h5' => [], 'h6' => [],
        'strong' => [], 'b' => [], 'em' => [], 'i' => [], 'u' => [], 's' => [], 'mark' => [],
        'sub' => [], 'sup' => [], 'small' => [], 'blockquote' => ['cite'], 'q' => ['cite'],
        'ul' => [], 'ol' => ['start'], 'li' => [], 'dl' => [], 'dt' => [], 'dd' => [],
        'code' => [], 'pre' => [], 'kbd' => [], 'figure' => [], 'figcaption' => [],
        'table' => [], 'thead' => [], 'tbody' => [], 'tfoot' => [], 'tr' => [],
        'th' => ['colspan', 'rowspan', 'scope'], 'td' => ['colspan', 'rowspan'], 'caption' => [],
        'a' => ['href', 'title', 'target'],
        'img' => ['src', 'alt', 'title', 'width', 'height', 'loading'],
    ];

    private const DROP_WITH_CONTENT = [
        'script', 'style', 'iframe', 'frame', 'frameset', 'object', 'embed', 'applet',
        'form', 'input', 'button', 'select', 'textarea', 'option', 'svg', 'math',
        'template', 'noscript', 'link', 'meta', 'base', 'title', 'head',
    ];

    private const URL_ATTRIBUTES = ['href', 'src', 'cite'];

    private const SAFE_SCHEMES = ['http', 'https', 'mailto'];

    public static function clean(?string $html): string
    {
        if ($html === null || trim($html) === '') {
            return '';
        }

        $doc = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="UTF-8"?><div>'.$html.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = self::firstElement($doc);
        if ($root === null) {
            return '';
        }

        self::cleanChildren($root);

        $output = '';
        foreach ($root->childNodes as $child) {
            $output .= $doc->saveHTML($child);
        }

        return $output;
    }

    private static function firstElement(DOMDocument $doc): ?DOMElement
    {
        foreach ($doc->childNodes as $node) {
            if ($node instanceof DOMElement) {
                return $node;
            }
        }

        return null;
    }

    private static function cleanChildren(DOMNode $parent): void
    {
        foreach (iterator_to_array($parent->childNodes) as $child) {
            self::cleanNode($child);
        }
    }

    private static function cleanNode(DOMNode $node): void
    {
        if ($node instanceof DOMText && $node->nodeType === XML_TEXT_NODE) {
            return;
        }

        $tag = $node instanceof DOMElement ? strtolower($node->nodeName) : '';
        if ($tag === '' || in_array($tag, self::DROP_WITH_CONTENT, true)) {
            $node->parentNode?->removeChild($node);

            return;
        }

        self::cleanChildren($node);

        if (! isset(self::ALLOWED_TAGS[$tag])) {
            self::unwrap($node);

            return;
        }

        self::cleanAttributes($node, self::ALLOWED_TAGS[$tag]);
    }

    /**
     * @param  list<string>  $allowed
     */
    private static function cleanAttributes(DOMElement $element, array $allowed): void
    {
        foreach (iterator_to_array($element->attributes) as $attribute) {
            $name = strtolower($attribute->nodeName);
            $keep = in_array($name, $allowed, true)
                && (! in_array($name, self::URL_ATTRIBUTES, true) || self::isSafeUrl($attribute->nodeValue ?? ''));

            if (! $keep) {
                $element->removeAttribute($attribute->nodeName);
            }
        }

        if ($element->hasAttribute('target')) {
            $element->setAttribute('target', '_blank');
            $element->setAttribute('rel', 'noopener noreferrer nofollow');
        }
    }

    private static function isSafeUrl(string $url): bool
    {
        $compact = (string) preg_replace('/[\x00-\x20\x7F]+/', '', $url);

        if (! preg_match('/^([a-z][a-z0-9+.\-]*):/i', $compact, $match)) {
            return true;
        }

        return in_array(strtolower($match[1]), self::SAFE_SCHEMES, true);
    }

    private static function unwrap(DOMNode $node): void
    {
        $parent = $node->parentNode;
        if ($parent === null) {
            return;
        }

        while ($node->firstChild !== null) {
            $parent->insertBefore($node->firstChild, $node);
        }

        $parent->removeChild($node);
    }
}
