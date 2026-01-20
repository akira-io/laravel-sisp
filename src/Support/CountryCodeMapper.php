<?php

declare(strict_types=1);

namespace Akira\Sisp\Support;

final class CountryCodeMapper
{
    /**
     * ISO 3166-1 alpha-2 to numeric code mapping.
     *
     * @var array<string, string>
     */
    private const array MAPPING = [
        'CV' => '132', // Cabo Verde
        'PT' => '620', // Portugal
        'BR' => '076', // Brasil
        'ES' => '724', // Espanha
        'FR' => '250', // França
        'DE' => '276', // Alemanha
        'GB' => '826', // Reino Unido
        'US' => '840', // Estados Unidos
        'AO' => '024', // Angola
        'MZ' => '508', // Moçambique
        'ST' => '678', // São Tomé e Príncipe
        'GW' => '624', // Guiné-Bissau
        'NL' => '528', // Países Baixos
        'IT' => '380', // Itália
        'LU' => '442', // Luxemburgo
        'CH' => '756', // Suíça
        'BE' => '056', // Bélgica
        'SN' => '686', // Senegal
    ];

    public static function toNumeric(string $alpha2Code): string
    {
        return self::MAPPING[mb_strtoupper($alpha2Code)] ?? '132';
    }
}
