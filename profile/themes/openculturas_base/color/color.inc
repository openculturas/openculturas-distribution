<?php

/**
 * @file
 * Lists available colors and color schemes for the IITB DJ theme.
 *
 * Theme requires some colors as variants.
 *  primary           => --primary
 *  primary__contrast => --primary--contrast.
 */

$info = [
  // Available colors and color labels used in theme.
  'fields' => [
    'color_primary' => t('Primary'),
    'color_secondary' => t('Socondary'),
    'color_success' => t('Success'),
    'color_info' => t('Info'),
    'color_warning' => t('Warning'),
    'color_danger' => t('Error'),
    'color_bg_light' => t('Background light'),
    'color_bg_medium' => t('Background medium'),
    'color_bg_dark' => t('Background Dark'),
    'color_border_light' => t('Border light'),
    'color_border_dark' => t('Border dark'),
    'color_text' => t('Text default'),
    'color_text_muted' => t('Text muted'),
    'color_text_inverted' => t('Text inverted'),
  ],
  // Pre_defined color schemes.
  'schemes' => [
    'default' => [
      'title' => t('openculturas default'),
      'colors' => [
        'color_primary' => '#a1167f',
        'color_secondary' => '#483d8b',
        'color_success' => '#188009',
        'color_info' => '#17f1b8',
        'color_warning' => '#ee6507',
        'color_danger' => '#d51317',
        'color_bg_light' => '#ffffff',
        'color_bg_medium' => '#e8e9eb',
        'color_bg_dark' => '#303236',
        'color_border_light' => '#e8e9eb',
        'color_border_dark' => '#7b7d85',
        'color_text' => '#1f1f1f',
        'color_text_muted' => '#7b7d85',
        'color_text_inverted' => '#ffffff',
      ],
    ],
  ],
  //    'crazy' => [
  //      'title' => t('Crazy colors'),
  //      'colors' => [
  //
  //             ...
  //
  //      ],
  //    ],
  //  ],
];
