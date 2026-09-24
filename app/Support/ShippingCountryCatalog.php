<?php

namespace App\Support;

final class ShippingCountryCatalog
{
    /**
     * Keep this list aligned with resources/js/data/countries.ts.
     *
     * @var array<string, list<string>>
     */
    private const STATES = [
        'US' => [
            'AL', 'AK', 'AZ', 'AR', 'CA', 'CO', 'CT', 'DE', 'DC', 'FL',
            'GA', 'HI', 'ID', 'IL', 'IN', 'IA', 'KS', 'KY', 'LA', 'ME',
            'MD', 'MA', 'MI', 'MN', 'MS', 'MO', 'MT', 'NE', 'NV', 'NH',
            'NJ', 'NM', 'NY', 'NC', 'ND', 'OH', 'OK', 'OR', 'PA', 'RI',
            'SC', 'SD', 'TN', 'TX', 'UT', 'VT', 'VA', 'WA', 'WV', 'WI',
            'WY',
        ],
        'CA' => [
            'AB', 'BC', 'MB', 'NB', 'NL', 'NS', 'ON', 'PE', 'QC', 'SK',
            'NT', 'NU', 'YT',
        ],
        'AU' => ['ACT', 'NSW', 'NT', 'QLD', 'SA', 'TAS', 'VIC', 'WA'],
        'NZ' => [
            'NTL', 'AUK', 'WKO', 'BOP', 'GIS', 'HKB', 'TKI', 'MWT',
            'WGN', 'TAS', 'NSN', 'MBH', 'WTC', 'CAN', 'OTA', 'STL',
            'CIT',
        ],
        'GB' => ['ENG', 'SCT', 'WLS', 'NIR'],
        'DE' => [
            'BW', 'BY', 'BE', 'BB', 'HB', 'HH', 'HE', 'MV', 'NI', 'NW',
            'RP', 'SL', 'SN', 'ST', 'SH', 'TH',
        ],
        'FR' => [
            'ARA', 'BFC', 'BRE', 'CVL', 'COR', 'GES', 'HDF', 'IDF', 'NOR',
            'NAQ', 'OCC', 'PDL', 'PAC',
        ],
        'MX' => [
            'AGU', 'BCN', 'BCS', 'CAM', 'CHP', 'CHH', 'CMX', 'COA', 'COL',
            'DUR', 'GUA', 'GRO', 'HID', 'JAL', 'MEX', 'MIC', 'MOR', 'NAY',
            'NLE', 'OAX', 'PUE', 'QUE', 'ROO', 'SLP', 'SIN', 'SON', 'TAB',
            'TAM', 'TLA', 'VER', 'YUC', 'ZAC',
        ],
        'JP' => [
            '01', '02', '03', '04', '05', '06', '07', '08', '09', '10',
            '11', '12', '13', '14', '15', '16', '17', '18', '19', '20',
            '21', '22', '23', '24', '25', '26', '27', '28', '29', '30',
            '31', '32', '33', '34', '35', '36', '37', '38', '39', '40',
            '41', '42', '43', '44', '45', '46', '47',
        ],
    ];

    /**
     * @return list<string>
     */
    public static function codes(): array
    {
        return array_map('strval', array_keys(self::STATES));
    }

    /**
     * @return list<string>
     */
    public static function stateCodesFor(?string $country): array
    {
        $country = strtoupper(trim((string) $country));

        return self::STATES[$country] ?? [];
    }
}
