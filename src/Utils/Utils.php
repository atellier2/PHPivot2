<?php

namespace Atellier2\PHPivot\Utils;

class Utils
{
    /**
     * Escape HTML special characters to prevent XSS
     * 
     * @param mixed $value The value to escape
     * @return string The escaped value
     */
    public static function escapeHtml($value)
    {
        if (is_null($value)) {
            return '';
        }
        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
