<?php

namespace Atellier2\PHPivot\Service;

use Symfony\Component\Translation\Translator as SymfonyTranslator;
use Symfony\Component\Translation\Loader\PhpFileLoader;

class Translator
{
    private static ?SymfonyTranslator $translator = null;
    private static string $translationsPath;
    private static string $defaultLocale = 'en';
    private static array $loadedLocales = [];

    /**
     * Initialize the Symfony translator
     */
    private static function init(): void
    {
        if (self::$translator !== null) {
            return;
        }

        self::$translationsPath = __DIR__ . '/../lang';
        self::$translator = new SymfonyTranslator(self::$defaultLocale);
        self::$translator->addLoader('php', new PhpFileLoader());
        self::$translator->setFallbackLocales(['en']);
    }

    /**
     * Configure the translator
     * 
     * @param string $defaultLocale Default locale
     * @param string|null $translationsPath Custom translations path
     * @param array $fallbackLocales Fallback locales
     */
    public static function configure(
        string $defaultLocale = 'en',
        ?string $translationsPath = null,
        array $fallbackLocales = ['en']
    ): void {
        self::$defaultLocale = $defaultLocale;
        
        if ($translationsPath !== null) {
            self::$translationsPath = $translationsPath;
        }
        
        self::init();
        self::$translator->setLocale($defaultLocale);
        self::$translator->setFallbackLocales($fallbackLocales);
    }

    /**
     * Load a locale if not already loaded
     */
    private static function loadLocale(string $locale): void
    {
        if (isset(self::$loadedLocales[$locale])) {
            return;
        }

        $file = self::$translationsPath . '/' . $locale . '.php';
        
        if (file_exists($file)) {
            self::$translator->addResource('php', $file, $locale, 'messages');
            self::$loadedLocales[$locale] = true;
        }
    }

    /**
     * Translate a message
     * 
     * @param string $id Translation key
     * @param array $parameters Parameters to replace (use %key% format)
     * @param string|null $domain Translation domain (default: 'messages')
     * @param string|null $locale Override locale
     * @return string Translated message
     */
    public static function trans(
        string $id,
        array $parameters = [],
        ?string $domain = 'messages',
        ?string $locale = null
    ): string {
        self::init();
        
        $locale = $locale ?? self::$translator->getLocale();
        self::loadLocale($locale);
        
        return self::$translator->trans($id, $parameters, $domain, $locale);
    }

    /**
     * Translate with pluralization
     * 
     * @param string $id Translation key
     * @param int $count Count for pluralization
     * @param array $parameters Additional parameters
     * @param string|null $domain Translation domain
     * @param string|null $locale Override locale
     * @return string Translated message
     */
    public static function transChoice(
        string $id,
        int $count,
        array $parameters = [],
        ?string $domain = 'messages',
        ?string $locale = null
    ): string {
        self::init();
        
        $locale = $locale ?? self::$translator->getLocale();
        self::loadLocale($locale);
        
        $parameters['%count%'] = $count;
        
        return self::$translator->trans($id, $parameters, $domain, $locale);
    }

    /**
     * Set the current locale
     * 
     * @param string $locale Locale code (e.g., 'en', 'fr')
     */
    public static function setLocale(string $locale): void
    {
        self::init();
        self::loadLocale($locale);
        self::$translator->setLocale($locale);
    }

    /**
     * Get the current locale
     * 
     * @return string Current locale
     */
    public static function getLocale(): string
    {
        self::init();
        return self::$translator->getLocale();
    }

    /**
     * Set fallback locales
     * 
     * @param array $locales Fallback locales
     */
    public static function setFallbackLocales(array $locales): void
    {
        self::init();
        self::$translator->setFallbackLocales($locales);
        
        // Précharger les locales de fallback
        foreach ($locales as $locale) {
            self::loadLocale($locale);
        }
    }

    /**
     * Get the Symfony translator instance (for advanced usage)
     * 
     * @return SymfonyTranslator
     */
    public static function getTranslator(): SymfonyTranslator
    {
        self::init();
        return self::$translator;
    }

    /**
     * Check if a translation exists
     * 
     * @param string $id Translation key
     * @param string|null $locale Locale to check
     * @param string $domain Translation domain
     * @return bool
     */
    public static function has(string $id, ?string $locale = null, string $domain = 'messages'): bool
    {
        self::init();
        
        $locale = $locale ?? self::$translator->getLocale();
        self::loadLocale($locale);
        
        $catalogue = self::$translator->getCatalogue($locale);
        return $catalogue->has($id, $domain);
    }

    /**
     * Load all available locales at once
     */
    public static function loadAllLocales(): void
    {
        self::init();
        
        $files = glob(self::$translationsPath . '/*.php');
        
        foreach ($files as $file) {
            $locale = basename($file, '.php');
            self::loadLocale($locale);
        }
    }
}
