<?php

declare(strict_types=1);

/*
 * Deep audit 8.0, gap G6.
 *
 * TranslatableMessage lost __toString() in 8.0. Printed without |trans, the
 * object now makes rendering fail; 7.4 printed the raw message. On Twig 3.22,
 * the version the syllabus examines, the failure is a plain PHP Error:
 * Template::display() echoes what the template yields outside the try/catch
 * that wraps exceptions in a Twig RuntimeError. Twig 3.30 wraps it.
 */

use Symfony\Bridge\Twig\Extension\TranslationExtension;
use Symfony\Component\Translation\Loader\ArrayLoader;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Translation\Translator;

return [
    'translation.translatable-not-stringable' => static function (): ?string {
        $translator = new Translator('fr');
        $translator->addLoader('array', new ArrayLoader());
        $translator->addResource('array', ['hello' => 'bonjour'], 'fr');
        $twig = new Twig\Environment(new Twig\Loader\ArrayLoader([
            'raw' => '{{ m }}',
            'trans' => '{{ m|trans }}',
        ]));
        $twig->addExtension(new TranslationExtension($translator));
        $message = new TranslatableMessage('hello');
        if ($message instanceof Stringable) {
            return 'TranslatableMessage is still Stringable';
        }
        try {
            $twig->render('raw', ['m' => $message]);

            return 'Twig printed a TranslatableMessage without |trans';
        } catch (Twig\Error\Error $e) {
            return 'Twig wrapped the failure in '.$e::class.'; the content teaches a plain PHP Error on Twig 3.22';
        } catch (\Error $e) {
            if (!str_contains($e->getMessage(), 'could not be converted to string')) {
                return 'unexpected Error: '.$e->getMessage();
            }
        }

        return 'bonjour' === $twig->render('trans', ['m' => $message]) ? null : 'the |trans filter did not translate the message';
    },

];
