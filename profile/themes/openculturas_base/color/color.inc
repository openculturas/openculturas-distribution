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
    'color_interaction' => t('Interaction (Secondary)'),
    'color_text' => t('Text default'),
    'color_text_muted' => t('Text muted'),
    'color_text_inverted' => t('Text inverted'),
    'color_headline' => t('Headlines'),
    'color_link' => t('Links'),
    'color_neutral_canvas' => t('Page background (neutlal-canvas)'),
    'color_bg_dark' => t('Background Dark'),
    'color_bg_slight_contrast' => t('Background medium (slight-contrast)'),
    'color_bg_light' => t('Background light'),
    'color_divider_light' => t('Divider light'),
    'color_divider_dark' => t('Divider dark'),
    'color_danger' => t('Error'),
    'color_info' => t('Info'),
    'color_success' => t('Success'),
    'color_warning' => t('Warning'),
  ],
  // Pre_defined color schemes.
  'schemes' => [
    'default' => [
      'title' => t('openculturas default'),
      'colors' => [
        'color_primary' => '#a1167f',
        'color_interaction' => '#483d8b',
        'color_text' => '#1f1f1f',
        'color_text_muted' => '#7b7d85',
        'color_text_inverted' => '#ffffff',
        'color_headline' => '#1f1f1f',
        'color_link' => '#a1167f',
        'color_neutral_canvas' => '#f6f6f6',
        'color_bg_dark' => '#303236',
        'color_bg_slight_contrast' => '#e8e9eb',
        'color_bg_light' => '#ffffff',
        'color_divider_light' => '#e8e9eb',
        'color_divider_dark' => '#7b7d85',
        'color_danger' => '#d51317',
        'color_info' => '#1f71b8',
        'color_success' => '#188009',
        'color_warning' => '#ffc400',
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
