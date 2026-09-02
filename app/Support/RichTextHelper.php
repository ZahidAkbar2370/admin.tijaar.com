<?php

namespace App\Support;

class RichTextHelper
{
    /**
     * Remove empty paragraphs/lines left by Quill, CKEditor, etc.
     * e.g. <p><br></p>, <p>&nbsp;</p>, <div><br></div>
     */
    public static function cleanHtml(?string $html): string
    {
        if ($html === null || $html === '') {
            return '';
        }

        $patterns = [
            '/<p[^>]*>(?:\s|&nbsp;|&#160;|<br\s*\/?>|<span[^>]*>(?:\s|&nbsp;|&#160;|<br\s*\/?>)*<\/span>)*<\/p>/i',
            '/<div[^>]*>(?:\s|&nbsp;|&#160;|<br\s*\/?>)*<\/div>/i',
        ];

        $cleaned = $html;
        foreach ($patterns as $pattern) {
            $previous = '';
            while ($previous !== $cleaned) {
                $previous = $cleaned;
                $cleaned = preg_replace($pattern, '', $cleaned) ?? $cleaned;
            }
        }

        $cleaned = preg_replace('/(<br\s*\/?>\s*){3,}/i', '<br><br>', $cleaned) ?? $cleaned;

        return trim($cleaned);
    }
}
