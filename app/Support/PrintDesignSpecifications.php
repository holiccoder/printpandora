<?php

namespace App\Support;

final class PrintDesignSpecifications
{
    /**
     * The design specification block currently used on the business-card
     * product pages. Other paper products reuse the same presentation and
     * downloadable template set so the guidance stays consistent site-wide.
     *
     * @return array<string, mixed>
     */
    public static function businessCards(): array
    {
        return [
            'heading' => 'Design Specifications',
            'diagram' => [
                'bleed' => [
                    'label' => 'Bleed Area',
                    'dimensions' => '3.66" x 2.16"',
                    'description' => 'Make sure that your background extends to fill the bleed to avoid your Business Cards having white edges when trimmed.',
                ],
                'trim' => [
                    'label' => 'Trim',
                    'dimensions' => '3.50" x 2.0"',
                    'description' => 'This is where we aim to cut your cards.',
                ],
                'safe_area' => [
                    'label' => 'Safe Area',
                    'dimensions' => '3.34" x 1.84"',
                    'description' => 'Make sure any important aspects of your design such as text and logos are inside of the safe area, otherwise they may be cut off.',
                ],
            ],
            'downloads' => [
                [
                    'id' => 'pdf',
                    'label' => 'PDF',
                    'extension' => '.pdf',
                    'href' => '/templates/pdf.zip',
                    'color' => '#dc2626',
                ],
                [
                    'id' => 'illustrator',
                    'label' => 'Illustrator',
                    'extension' => '.ai',
                    'href' => '/templates/ai.zip',
                    'color' => '#f97316',
                ],
                [
                    'id' => 'indesign',
                    'label' => 'InDesign',
                    'extension' => '.indd',
                    'href' => '/templates/indd.zip',
                    'color' => '#ec4899',
                ],
                [
                    'id' => 'jpeg',
                    'label' => 'Jpeg',
                    'extension' => '.jpg',
                    'href' => '/templates/jpg.zip',
                    'color' => '#0f766e',
                ],
            ],
        ];
    }
}
