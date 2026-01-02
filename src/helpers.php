<?php

use Atellier2\PHPivot\Service\Translator;

if (!function_exists('__')) {
    /**
     * Translate the given message using Symfony Translation
     * 
     * @param string $id Translation key
     * @param array $parameters Parameters to replace (use %key% format)
     * @param string|null $domain Translation domain
     * @param string|null $locale Override the current locale
     * @return string Translated message
     */
    function __(string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): string
    {
        return Translator::trans($id, $parameters, $domain, $locale);
    }
}

if (!function_exists('trans')) {
    /**
     * Alias for __() - Translate using Symfony Translation
     * 
     * @param string $id Translation key
     * @param array $parameters Parameters to replace
     * @param string|null $domain Translation domain
     * @param string|null $locale Override locale
     * @return string Translated message
     */
    function trans(string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): string
    {
        return Translator::trans($id, $parameters, $domain, $locale);
    }
}

if (!function_exists('trans_choice')) {
    /**
     * Translate with pluralization using Symfony Translation
     * 
     * @param string $id Translation key
     * @param int $count Count for pluralization
     * @param array $parameters Additional parameters
     * @param string|null $domain Translation domain
     * @param string|null $locale Override locale
     * @return string Translated message
     */
    function trans_choice(string $id, int $count, array $parameters = [], ?string $domain = null, ?string $locale = null): string
    {
        return Translator::transChoice($id, $count, $parameters, $domain, $locale);
    }
}