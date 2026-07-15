<?php

namespace App\Support\Html;

use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Sanitizes rich-text application briefs to a strict allowlist — this is the ONLY
 * user HTML Fama ever renders un-escaped (in the contract-room timeline). It keeps
 * basic formatting + mention chips and strips every attribute (except
 * class="mention" on spans) plus any disallowed tag, unwrapping to its text.
 */
class BriefSanitizer
{
    /** Formatting tags we allow through. */
    private const ALLOWED = ['p', 'br', 'b', 'strong', 'i', 'em', 'u', 'ul', 'ol', 'li', 'span', 'div'];

    /**
     * Return sanitized HTML, or '' when the brief has no meaningful content.
     */
    public static function clean(string $html): string
    {
        $html = trim($html);
        if ($html === '') {
            return '';
        }

        $doc = new DOMDocument();
        $prev = libxml_use_internal_errors(true);
        // The XML PI pins UTF-8; NOIMPLIED avoids an implied <html>/<body> wrapper.
        $doc->loadHTML(
            '<?xml encoding="UTF-8"?><div id="fama-brief-root">'.$html.'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD,
        );
        libxml_clear_errors();
        libxml_use_internal_errors($prev);

        $root = null;
        foreach ($doc->childNodes as $node) {
            if ($node instanceof DOMElement && strtolower($node->nodeName) === 'div') {
                $root = $node;
                break;
            }
        }
        if ($root === null) {
            return '';
        }

        self::sanitizeChildren($root);

        $out = '';
        foreach (iterator_to_array($root->childNodes) as $child) {
            $out .= $doc->saveHTML($child);
        }
        $out = trim($out);

        // Blank once tags are stripped (e.g. "<p></p>", "<br>") and no mention chip.
        return (trim(strip_tags($out)) === '' && ! str_contains($out, 'class="mention"')) ? '' : $out;
    }

    private static function sanitizeChildren(DOMNode $node): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child instanceof DOMElement) {
                self::sanitizeElement($child);
            } elseif ($child->nodeType !== XML_TEXT_NODE) {
                // Comments / CDATA / PIs → drop.
                $node->removeChild($child);
            }
        }
    }

    private static function sanitizeElement(DOMElement $el): void
    {
        $tag = strtolower($el->nodeName);

        // Sanitize descendants first so an unwrap keeps clean children.
        self::sanitizeChildren($el);

        if (! in_array($tag, self::ALLOWED, true)) {
            // Disallowed tag → unwrap (keep its already-sanitized children as text).
            while ($el->firstChild) {
                $el->parentNode->insertBefore($el->firstChild, $el);
            }
            $el->parentNode->removeChild($el);

            return;
        }

        // Strip every attribute except class="mention" on a <span>.
        foreach (iterator_to_array($el->attributes ?? []) as $attr) {
            $keep = $tag === 'span' && strtolower($attr->name) === 'class' && trim($attr->value) === 'mention';
            if (! $keep) {
                $el->removeAttribute($attr->name);
            }
        }
    }
}
