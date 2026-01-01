<?php

namespace Atellier2\PHPivot\Utils;

class ColorUtils
{
    /**
     * Convert hex color to RGB array
     */
    public static function hexToRGB(string $hex): array
    {
        $hex = str_replace('#', '', $hex);
        return [
            'r' => hexdec(substr($hex, 0, 2)),
            'g' => hexdec(substr($hex, 2, 2)),
            'b' => hexdec(substr($hex, 4, 2))
        ];
    }

    /**
     * Convert RGB array to hex color
     */
    public static function toHexColor(array $rgb): string
    {
        return sprintf('%02x%02x%02x', $rgb['r'], $rgb['g'], $rgb['b']);
    }


    /**
     * Validate hex color format
     */
    public static function isValidHexColor(string $color): bool
    {
        return preg_match('/^#[0-9A-Fa-f]{6}$/', $color) === 1;
    }
}