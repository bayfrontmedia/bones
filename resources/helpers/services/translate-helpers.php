<?php

use Bayfront\Bones\App;
use Bayfront\Container\NotFoundException;
use Bayfront\Translation\Translate;
use Bayfront\Translation\TranslationException;

/**
 * Get Translate instance from container.
 *
 * See: https://github.com/bayfrontmedia/translation
 *
 * @return Translate
 *
 * @throws NotFoundException
 */

function get_translate(): Translate
{
    return App::getFromContainer('translate');
}

/**
 * Get locale.
 *
 * See: https://github.com/bayfrontmedia/translation#getlocale
 *
 * @return string
 *
 * @throws NotFoundException
 */

function get_locale(): string
{
    return get_translate()->getLocale();
}

/**
 * Set locale.
 *
 * See: https://github.com/bayfrontmedia/translation#setlocale
 *
 * @param string $locale
 *
 * @return void
 *
 * @throws NotFoundException
 */

function set_locale(string $locale): void
{
    get_translate()->setLocale($locale);
}

/**
 * Return the translation for a given string.
 *
 * The string format is: id.key. Keys are in array dot notation, so they can be as deeply nested as needed.
 *
 * Replacement variables should be surrounded in {{ }} in the language value.
 *
 * If a translation is not found and $default = NULL, the original string is returned.
 *
 * Returned value is filtered through the "translate" hook.
 *
 * See: https://github.com/bayfrontmedia/translation#get
 *
 * @param string $string
 * @param array $replacements
 * @param null $default (Default value to return if translation is not found)
 *
 * @return mixed
 *
 * @throws NotFoundException
 * @throws TranslationException
 */

function translate(string $string, array $replacements = [], $default = NULL)
{
    return do_filter('translate', get_translate()->get($string, $replacements, $default));
}

/**
 * Echos the translation for a given string.
 *
 * Returned value is filtered through the "translate" hook.
 *
 * See: https://github.com/bayfrontmedia/translation#say
 *
 * @param string $string
 * @param array $replacements
 * @param null $default (Default value to return if translation is not found)
 *
 * @return void
 *
 * @throws NotFoundException
 * @throws TranslationException
 */

function say(string $string, array $replacements = [], $default = NULL): void
{
    echo translate($string, $replacements, $default);
}