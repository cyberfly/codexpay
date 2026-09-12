<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;

final class ProductDescription
{
    /** @var array<int, string> */
    private const AllowedTags = [
        'blockquote', 'br', 'code', 'em', 'h2', 'h3', 'h4', 'hr', 'li', 'ol', 'p', 'pre', 's', 'strong', 'ul',
    ];

    /** @var array<int, string> */
    private const RemovedTags = [
        'embed', 'iframe', 'math', 'object', 'script', 'style', 'svg', 'template',
    ];

    /**
     * Return rich-text HTML that is safe to store and render in a product page.
     */
    public static function sanitize(string $html): string
    {
        if (trim($html) === '') {
            return '';
        }

        if (strip_tags($html) === $html) {
            return '<p>'.nl2br(e($html), false).'</p>';
        }

        $previousUseInternalErrors = libxml_use_internal_errors(true);

        try {
            $document = new DOMDocument;
            $document->loadHTML(
                '<!DOCTYPE html><html><head><meta charset="utf-8"></head><body><div id="product-description">'.$html.'</div></body></html>',
                LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD,
            );

            $container = $document->getElementById('product-description');

            if ($container === null) {
                return '';
            }

            self::sanitizeNodes($container);

            if (preg_replace('/[\s\x{00A0}]+/u', '', $container->textContent ?? '') === '') {
                return '';
            }

            $sanitizedHtml = '';

            foreach (iterator_to_array($container->childNodes) as $childNode) {
                $sanitizedHtml .= $document->saveHTML($childNode);
            }

            return trim($sanitizedHtml);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previousUseInternalErrors);
        }
    }

    /**
     * Convert rich-text HTML to plain text for product-card excerpts.
     */
    public static function plainText(string $html): string
    {
        return trim((string) preg_replace(
            '/\s+/',
            ' ',
            html_entity_decode(strip_tags(self::sanitize($html)), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
        ));
    }

    /**
     * Remove unsupported elements and all element attributes from the document.
     */
    private static function sanitizeNodes(DOMNode $parentNode): void
    {
        foreach (iterator_to_array($parentNode->childNodes) as $childNode) {
            if (! $childNode instanceof DOMElement) {
                continue;
            }

            self::sanitizeNodes($childNode);

            $tagName = strtolower($childNode->tagName);

            if (in_array($tagName, self::RemovedTags, true)) {
                $childNode->parentNode?->removeChild($childNode);

                continue;
            }

            if (! in_array($tagName, self::AllowedTags, true)) {
                self::unwrap($childNode);

                continue;
            }

            while ($childNode->attributes->length > 0) {
                $childNode->removeAttributeNode($childNode->attributes->item(0));
            }
        }
    }

    /**
     * Preserve an unsupported element's safe child nodes while dropping the element.
     */
    private static function unwrap(DOMElement $element): void
    {
        $parentNode = $element->parentNode;

        if ($parentNode === null) {
            return;
        }

        while ($element->firstChild !== null) {
            $parentNode->insertBefore($element->firstChild, $element);
        }

        $parentNode->removeChild($element);
    }
}
