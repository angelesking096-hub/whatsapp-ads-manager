<?php

namespace App\Services;

class SpintaxService
{
    /**
     * Resolves spintax patterns like {Bonjour|Salut|Hello} into a random choice.
     */
    public static function parse(string $text): string
    {
        return preg_replace_callback('/\{([^{}]+)\}/', function ($matches) {
            $options = explode('|', $matches[1]);
            return $options[array_rand($options)];
        }, $text);
    }

    /**
     * Personalizes template with recipient variables and resolves spintax.
     */
    public static function personalize(string $template, array $data = []): string
    {
        // First resolve variables
        $replacements = [
            '{nom}' => $data['name'] ?? 'Bonjour',
            '{contact}' => $data['name'] ?? '',
            '{entreprise}' => $data['business_name'] ?? ($data['name'] ?? 'votre entreprise'),
            '{ville}' => $data['city'] ?? 'votre région',
            '{besoin}' => $data['detected_need'] ?? 'développer vos ventes',
            '{secteur}' => $data['category'] ?? 'votre domaine',
        ];

        $rendered = str_replace(array_keys($replacements), array_values($replacements), $template);

        // Then resolve spintax
        return self::parse($rendered);
    }
}
