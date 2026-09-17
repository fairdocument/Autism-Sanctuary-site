<?php
/**
 * Build 100% Native Divi 5 Home Revised Page (ID 692)
 *
 * Recreates the complete Home page using 100% native Divi 5 modules on Page 692 (/home-revised/):
 * - Section 0: Preserves the native Hero (video/image background, overlay, headings, native buttons)
 * - Section 1: "Our approach" -> 2-col (Image + native Eyebrow, Heading, Lede, Button)
 * - Section 2: "Our services" -> Header row + 5 native Blurb service cards (3 + 2 layout)
 * - Section 3: "Our home" (Edgefield) -> 2-col (Eyebrow, Heading, Text, Buttons + Image)
 * - Section 4: "Looking ahead" -> 2-col (Image + Eyebrow, Heading, Checklist, Button)
 * - Section 5: "Newsroom" -> Eyebrow, Heading, Lede, Native Blog, Native Button
 * - Section 6: "Get in touch" -> Native Heading and Buttons
 *
 * Scoped strictly to Page 692 (/home-revised/). Never touches live ID 532.
 */

require_once __DIR__ . '/../wp-load.php';

echo "=== BUILDING HOME REVISED (PAGE 692) - 100% NATIVE DIVI 5 ===\n";

global $wpdb;

$live_home = get_post(532);
if (!$live_home) {
    die("Error: Live home post 532 not found.\n");
}

$revised_home = get_post(692);
if (!$revised_home) {
    die("Error: Revised home post 692 not found.\n");
}

// Block building helper with proper Gutenberg innerContent null slots
function make_divi_block($name, $attrs = [], $inner_blocks = [], $innerHTML = '') {
    $count = count($inner_blocks);
    $innerContent = [];
    if ($count > 0) {
        $innerContent[] = "\n";
        for ($i = 0; $i < $count; $i++) {
            $innerContent[] = null;
            $innerContent[] = "\n";
        }
    }
    return [
        'blockName'    => $name,
        'attrs'        => $attrs,
        'innerBlocks'  => $inner_blocks,
        'innerHTML'    => $innerHTML,
        'innerContent' => $innerContent,
    ];
}

// Parse live 532 blocks to extract Hero section
$parsed_532 = parse_blocks($live_home->post_content);
$sections_532 = [];
foreach ($parsed_532 as $b) {
    if ($b['blockName'] === 'divi/placeholder') {
        foreach ($b['innerBlocks'] as $sb) {
            if ($sb['blockName'] === 'divi/section') {
                $sections_532[] = $sb;
            }
        }
    } else if ($b['blockName'] === 'divi/section') {
        $sections_532[] = $b;
    }
}

$native_sections = [];

// =========================================================================
// SECTION 0: HERO (Preserve exact native Divi 5 block from 532)
// =========================================================================
if (isset($sections_532[0])) {
    $native_sections[] = $sections_532[0];
    echo "Section 0 (Hero): Preserved native Divi 5 hero from 532.\n";
}

// =========================================================================
// SECTION 1: OUR APPROACH (2 Equal Columns: Image on left, Content on right)
// =========================================================================
$col1_left = make_divi_block('divi/column', [
    'builderVersion' => '5.11.1',
    'module' => [
        'advanced' => ['type' => ['desktop' => ['value' => '1_2']]],
        'decoration' => ['sizing' => ['desktop' => ['value' => ['flexType' => '12_24']]]]
    ]
], [
    make_divi_block('divi/image', [
        'builderVersion' => '5.11.1',
        'image' => [
            'innerContent' => ['desktop' => ['value' => [
                'src' => '/wp-content/uploads/2026/08/sanctuary-gardens.jpg',
                'alt' => 'Garden paths and plantings at Autism Sanctuary’s Virginia farm.'
            ]]]
        ],
        'module' => [
            'advanced' => [
                'htmlAttributes' => ['desktop' => ['value' => ['class' => 'as-native-image']]],
                'sizing' => ['desktop' => ['value' => ['height' => '400px']]]
            ]
        ]
    ])
]);

$col1_right = make_divi_block('divi/column', [
    'builderVersion' => '5.11.1',
    'module' => [
        'advanced' => ['type' => ['desktop' => ['value' => '1_2']]],
        'decoration' => ['sizing' => ['desktop' => ['value' => ['flexType' => '12_24']]]]
    ]
], [
    make_divi_block('divi/text', [
        'builderVersion' => '5.11.1',
        'content' => ['innerContent' => ['desktop' => ['value' => '<p class="as-eyebrow">Our approach</p>']]]
    ]),
    make_divi_block('divi/heading', [
        'builderVersion' => '5.11.1',
        'title' => [
            'innerContent' => ['desktop' => ['value' => 'Purpose and belonging, rooted in nature.']],
            'decoration' => ['font' => ['font' => ['desktop' => [
                'family' => 'Cormorant Garamond',
                'weight' => '400',
                'color'  => '#1E3D2C',
                'size'   => '2.25rem'
            ]]]]
        ]
    ]),
    make_divi_block('divi/text', [
        'builderVersion' => '5.11.1',
        'content' => ['innerContent' => ['desktop' => [
            'value' => '<p class="as-lede">People in our program engage with trails, animals, gardens, and staff who care. Days are designed around a sense of purpose and belonging, with an emphasis on growth and meaningful relationships.</p>'
        ]]]
    ]),
    make_divi_block('divi/button', [
        'builderVersion' => '5.11.1',
        'button' => [
            'innerContent' => ['desktop' => [
                'text' => 'About Autism Sanctuary',
                'link' => ['desktop' => ['value' => ['url' => '/about/']]]
            ]],
            'decoration' => [
                'background' => ['desktop' => ['value' => ['color' => '#2F5D43']]],
                'font' => ['font' => ['desktop' => ['color' => '#ffffff', 'weight' => '600']]],
                'border' => ['desktop' => ['value' => ['radius' => ['sync' => 'on', 'topLeft' => '6px', 'topRight' => '6px', 'bottomLeft' => '6px', 'bottomRight' => '6px']]]]
            ]
        ],
        'module' => [
            'advanced' => ['htmlAttributes' => ['desktop' => ['value' => ['class' => 'as-btn as-btn--primary']]]],
            'decoration' => ['spacing' => ['desktop' => ['value' => ['margin' => ['top' => '1.5rem']]]]]
        ]
    ])
]);

$sec1_row = make_divi_block('divi/row', [
    'builderVersion' => '5.11.1',
    'module' => [
        'advanced' => [
            'columnStructure' => ['desktop' => ['value' => '1_2,1_2']],
            'flexColumnStructure' => ['desktop' => ['value' => 'equal-columns_2']]
        ],
        'decoration' => [
            'sizing' => ['desktop' => ['value' => ['maxWidth' => '80rem', 'width' => '100%']]],
            'spacing' => ['desktop' => ['value' => ['padding' => ['top' => '60px', 'bottom' => '60px']]]],
            'layout' => ['desktop' => ['value' => ['flexWrap' => 'nowrap', 'alignItems' => 'center', 'columnGap' => '30px']]]
        ]
    ]
], [$col1_left, $col1_right]);

$native_sections[] = make_divi_block('divi/section', [
    'builderVersion' => '5.11.1',
    'module' => [
        'decoration' => [
            'spacing' => ['desktop' => ['value' => ['padding' => ['top' => '60px', 'bottom' => '60px']]]]
        ]
    ]
], [$sec1_row]);
echo "Section 1 (Our approach): Built native Divi 5 2-column section.\n";

// =========================================================================
// SECTION 2: OUR SERVICES (Header Row + 5 Blurb Cards)
// =========================================================================
function make_service_card($title, $text) {
    return make_divi_block('divi/blurb', [
        'builderVersion' => '5.11.1',
        'title' => [
            'innerContent' => ['desktop' => ['value' => $title]],
            'decoration' => ['font' => ['font' => ['desktop' => [
                'family' => 'Cormorant Garamond',
                'weight' => '600',
                'color'  => '#1E3D2C',
                'size'   => '1.35rem'
            ]]]]
        ],
        'content' => [
            'innerContent' => ['desktop' => ['value' => "<p>{$text}</p>"]],
            'decoration' => ['bodyFont' => ['body' => ['font' => ['desktop' => [
                'family' => 'Source Sans 3',
                'color'  => '#4A534C',
                'size'   => '0.95rem'
            ]]]]]
        ],
        'image' => ['advanced' => ['icon' => ['desktop' => ['value' => ['enable' => 'off']]]]],
        'module' => [
            'advanced' => ['htmlAttributes' => ['desktop' => ['value' => ['class' => 'as-native-service-card']]]],
            'decoration' => [
                'background' => ['desktop' => ['value' => ['color' => '#ffffff']]],
                'border' => ['desktop' => ['value' => [
                    'radius' => ['sync' => 'on', 'topLeft' => '8px', 'topRight' => '8px', 'bottomLeft' => '8px', 'bottomRight' => '8px'],
                    'styles' => ['all' => ['width' => '1px', 'color' => 'rgba(36,92,66,0.12)']]
                ]]],
                'spacing' => ['desktop' => ['value' => ['padding' => ['top' => '1.5rem', 'right' => '1.25rem', 'bottom' => '1.5rem', 'left' => '1.25rem']]]]
            ]
        ]
    ]);
}

// Row 0: Intro (2/3 col + 1/3 col)
$sec2_row0 = make_divi_block('divi/row', [
    'builderVersion' => '5.11.1',
    'module' => [
        'advanced' => ['columnStructure' => ['desktop' => ['value' => '2_3,1_3']]],
        'decoration' => [
            'sizing' => ['desktop' => ['value' => ['maxWidth' => '80rem', 'width' => '100%']]],
            'spacing' => ['desktop' => ['value' => ['padding' => ['top' => '0px', 'right' => '1.25rem', 'bottom' => '2rem', 'left' => '1.25rem']]]]
        ]
    ]
], [
    make_divi_block('divi/column', [
        'builderVersion' => '5.11.1',
        'module' => ['advanced' => ['type' => ['desktop' => ['value' => '2_3']]]]
    ], [
        make_divi_block('divi/text', [
            'builderVersion' => '5.11.1',
            'content' => ['innerContent' => ['desktop' => ['value' => '<p class="as-eyebrow">Our services</p>']]]
        ]),
        make_divi_block('divi/heading', [
            'builderVersion' => '5.11.1',
            'title' => [
                'innerContent' => ['desktop' => ['value' => 'Licensed support rooted in connection, growth, and belonging.']],
                'decoration' => ['font' => ['font' => ['desktop' => [
                    'family' => 'Cormorant Garamond',
                    'weight' => '400',
                    'color'  => '#1E3D2C',
                    'size'   => '2.25rem'
                ]]]]
            ]
        ]),
        make_divi_block('divi/text', [
            'builderVersion' => '5.11.1',
            'content' => ['innerContent' => ['desktop' => [
                'value' => '<p class="as-lede">Autism Sanctuary provides meaningful support through nature, community, and individualized services designed to help people thrive.</p>'
            ]]]
        ])
    ]),
    make_divi_block('divi/column', [
        'builderVersion' => '5.11.1',
        'module' => [
            'advanced' => ['type' => ['desktop' => ['value' => '1_3']]],
            'decoration' => ['spacing' => ['desktop' => ['value' => ['padding' => ['top' => '2rem']]]]]
        ]
    ], [
        make_divi_block('divi/button', [
            'builderVersion' => '5.11.1',
            'button' => [
                'innerContent' => ['desktop' => [
                    'text' => 'Full service details',
                    'link' => ['desktop' => ['value' => ['url' => '/programs/']]]
                ]],
                'decoration' => [
                    'background' => ['desktop' => ['value' => ['color' => '#2F5D43']]],
                    'font' => ['font' => ['desktop' => ['color' => '#ffffff', 'weight' => '600']]],
                    'border' => ['desktop' => ['value' => ['radius' => ['sync' => 'on', 'topLeft' => '6px', 'topRight' => '6px', 'bottomLeft' => '6px', 'bottomRight' => '6px']]]]
                ]
            ],
            'module' => ['advanced' => ['htmlAttributes' => ['desktop' => ['value' => ['class' => 'as-btn as-btn--primary']]]]]
        ])
    ])
]);

// Row 1: 3 cards
$sec2_row1 = make_divi_block('divi/row', [
    'builderVersion' => '5.11.1',
    'module' => [
        'advanced' => ['columnStructure' => ['desktop' => ['value' => '1_3,1_3,1_3']]],
        'decoration' => [
            'sizing' => ['desktop' => ['value' => ['maxWidth' => '80rem', 'width' => '100%']]],
            'spacing' => ['desktop' => ['value' => ['padding' => ['top' => '0px', 'right' => '1.25rem', 'bottom' => '1.5rem', 'left' => '1.25rem']]]]
        ]
    ]
], [
    make_divi_block('divi/column', ['builderVersion' => '5.11.1', 'module' => ['advanced' => ['type' => ['desktop' => ['value' => '1_3']]]]], [
        make_service_card('Group Day', 'On-site weekday support on the farm—animal care, gardens, trails, and skill-building in nature.')
    ]),
    make_divi_block('divi/column', ['builderVersion' => '5.11.1', 'module' => ['advanced' => ['type' => ['desktop' => ['value' => '1_3']]]]], [
        make_service_card('Community Coaching', '1:1 support in the community to build skills that open doors to everyday participation.')
    ]),
    make_divi_block('divi/column', ['builderVersion' => '5.11.1', 'module' => ['advanced' => ['type' => ['desktop' => ['value' => '1_3']]]]], [
        make_service_card('Community Engagement', '1:3 small-group support for meaningful outings and community life beyond the farm.')
    ])
]);

// Row 2: 2 cards
$sec2_row2 = make_divi_block('divi/row', [
    'builderVersion' => '5.11.1',
    'module' => [
        'advanced' => ['columnStructure' => ['desktop' => ['value' => '1_2,1_2']]],
        'decoration' => [
            'sizing' => ['desktop' => ['value' => ['maxWidth' => '80rem', 'width' => '100%']]],
            'spacing' => ['desktop' => ['value' => ['padding' => ['top' => '0px', 'right' => '1.25rem', 'bottom' => '0px', 'left' => '1.25rem']]]]
        ]
    ]
], [
    make_divi_block('divi/column', ['builderVersion' => '5.11.1', 'module' => ['advanced' => ['type' => ['desktop' => ['value' => '1_2']]]]], [
        make_service_card('Residential & Home-Based', 'Personalized supports in people’s homes and community settings where authorized.')
    ]),
    make_divi_block('divi/column', ['builderVersion' => '5.11.1', 'module' => ['advanced' => ['type' => ['desktop' => ['value' => '1_2']]]]], [
        make_service_card('Workplace Assistance', '1:1 on-the-job support that helps people succeed in meaningful employment.')
    ])
]);

$native_sections[] = make_divi_block('divi/section', [
    'builderVersion' => '5.11.1',
    'module' => [
        'decoration' => [
            'background' => ['desktop' => ['value' => ['color' => '#f5f2e8']]],
            'spacing' => ['desktop' => ['value' => ['padding' => ['top' => '60px', 'bottom' => '70px']]]]
        ],
        'advanced' => ['htmlAttributes' => ['desktop' => ['value' => ['class' => 'as-section as-services-section']]]]
    ]
], [$sec2_row0, $sec2_row1, $sec2_row2]);
echo "Section 2 (Our services): Built native Divi 5 header + 5 blurb cards.\n";

// =========================================================================
// SECTION 3: OUR HOME / EDGEFIELD (2 Equal Columns: Text/Buttons on left, Image on right)
// =========================================================================
$col3_left = make_divi_block('divi/column', [
    'builderVersion' => '5.11.1',
    'module' => [
        'advanced' => ['type' => ['desktop' => ['value' => '1_2']]],
        'decoration' => ['sizing' => ['desktop' => ['value' => ['flexType' => '12_24']]]]
    ]
], [
    make_divi_block('divi/text', [
        'builderVersion' => '5.11.1',
        'content' => ['innerContent' => ['desktop' => ['value' => '<p class="as-eyebrow">Our home</p>']]]
    ]),
    make_divi_block('divi/heading', [
        'builderVersion' => '5.11.1',
        'title' => [
            'innerContent' => ['desktop' => ['value' => 'Edgefield in the Blue Ridge foothills.']],
            'decoration' => ['font' => ['font' => ['desktop' => [
                'family' => 'Cormorant Garamond',
                'weight' => '400',
                'color'  => '#1E3D2C',
                'size'   => '2.25rem'
            ]]]]
        ]
    ]),
    make_divi_block('divi/text', [
        'builderVersion' => '5.11.1',
        'content' => ['innerContent' => ['desktop' => [
            'value' => '<p class="as-lede">Autism Sanctuary operates from Edgefield—an extraordinary property and house stewarded by Frances Lee-Vandell. A 1780 home built by William Watkins was dismantled board by board and rebuilt onsite.</p><p class="as-lede as-lede--follow">Today, the property features a range of animals, a robust garden, walking trails, and a variety of activity stations. Thanks to Frances’ incredible generosity, this property and land have become the foundation of our programs and have changed the lives of many.</p>'
        ]]]
    ]),
    // Buttons row
    make_divi_block('divi/row', [
        'builderVersion' => '5.11.1',
        'module' => [
            'advanced' => ['columnStructure' => ['desktop' => ['value' => '1_2,1_2']]],
            'decoration' => [
                'layout' => ['desktop' => ['value' => ['display' => 'flex', 'flexDirection' => 'row', 'flexWrap' => 'nowrap']]],
                'spacing' => ['desktop' => ['value' => ['padding' => ['top' => '1.5rem', 'bottom' => '0px', 'left' => '0px', 'right' => '0px']]]]
            ]
        ]
    ], [
        make_divi_block('divi/column', ['builderVersion' => '5.11.1', 'module' => ['advanced' => ['type' => ['desktop' => ['value' => '1_2']]]]], [
            make_divi_block('divi/button', [
                'builderVersion' => '5.11.1',
                'button' => [
                    'innerContent' => ['desktop' => ['text' => 'Explore the property', 'link' => ['desktop' => ['value' => ['url' => '/our-farm/']]]]],
                    'decoration' => [
                        'background' => ['desktop' => ['value' => ['color' => '#2F5D43']]],
                        'font' => ['font' => ['desktop' => ['color' => '#ffffff', 'weight' => '600']]],
                        'border' => ['desktop' => ['value' => ['radius' => ['sync' => 'on', 'topLeft' => '6px', 'topRight' => '6px', 'bottomLeft' => '6px', 'bottomRight' => '6px']]]]
                    ]
                ],
                'module' => ['advanced' => ['htmlAttributes' => ['desktop' => ['value' => ['class' => 'as-btn as-btn--primary']]]]]
            ])
        ]),
        make_divi_block('divi/column', ['builderVersion' => '5.11.1', 'module' => ['advanced' => ['type' => ['desktop' => ['value' => '1_2']]]]], [
            make_divi_block('divi/button', [
                'builderVersion' => '5.11.1',
                'button' => [
                    'innerContent' => ['desktop' => ['text' => 'Volunteer with us', 'link' => ['desktop' => ['value' => ['url' => '/contact/?intent=volunteer']]]]],
                    'decoration' => [
                        'background' => ['desktop' => ['value' => ['color' => 'transparent']]],
                        'font' => ['font' => ['desktop' => ['color' => '#2F5D43', 'weight' => '600']]],
                        'border' => ['desktop' => ['value' => ['radius' => ['sync' => 'on', 'topLeft' => '6px', 'topRight' => '6px', 'bottomLeft' => '6px', 'bottomRight' => '6px'], 'styles' => ['all' => ['width' => '1.5px', 'color' => '#2F5D43']]]]]
                    ]
                ],
                'module' => ['advanced' => ['htmlAttributes' => ['desktop' => ['value' => ['class' => 'as-btn as-btn--ghost']]]]]
            ])
        ])
    ])
]);

$col3_right = make_divi_block('divi/column', [
    'builderVersion' => '5.11.1',
    'module' => [
        'advanced' => ['type' => ['desktop' => ['value' => '1_2']]],
        'decoration' => ['sizing' => ['desktop' => ['value' => ['flexType' => '12_24']]]]
    ]
], [
    make_divi_block('divi/image', [
        'builderVersion' => '5.11.1',
        'image' => [
            'innerContent' => ['desktop' => ['value' => [
                'src' => '/wp-content/uploads/2026/08/edgefield.jpg',
                'alt' => 'Historic Edgefield farmhouse at Autism Sanctuary.'
            ]]]
        ],
        'module' => [
            'advanced' => [
                'htmlAttributes' => ['desktop' => ['value' => ['class' => 'as-native-image']]],
                'sizing' => ['desktop' => ['value' => ['height' => '400px']]]
            ]
        ]
    ])
]);

$sec3_row = make_divi_block('divi/row', [
    'builderVersion' => '5.11.1',
    'module' => [
        'advanced' => [
            'columnStructure' => ['desktop' => ['value' => '1_2,1_2']],
            'flexColumnStructure' => ['desktop' => ['value' => 'equal-columns_2']]
        ],
        'decoration' => [
            'sizing' => ['desktop' => ['value' => ['maxWidth' => '80rem', 'width' => '100%']]],
            'spacing' => ['desktop' => ['value' => ['padding' => ['top' => '60px', 'bottom' => '60px']]]],
            'layout' => ['desktop' => ['value' => ['flexWrap' => 'nowrap', 'alignItems' => 'center', 'columnGap' => '30px']]]
        ]
    ]
], [$col3_left, $col3_right]);

$native_sections[] = make_divi_block('divi/section', [
    'builderVersion' => '5.11.1',
    'module' => [
        'decoration' => [
            'spacing' => ['desktop' => ['value' => ['padding' => ['top' => '60px', 'bottom' => '60px']]]]
        ]
    ]
], [$sec3_row]);
echo "Section 3 (Our home): Built native Divi 5 2-column section.\n";

// =========================================================================
// SECTION 4: LOOKING AHEAD (2 Equal Columns: Image on left, Content on right)
// =========================================================================
$col4_left = make_divi_block('divi/column', [
    'builderVersion' => '5.11.1',
    'module' => [
        'advanced' => ['type' => ['desktop' => ['value' => '1_2']]],
        'decoration' => ['sizing' => ['desktop' => ['value' => ['flexType' => '12_24']]]]
    ]
], [
    make_divi_block('divi/image', [
        'builderVersion' => '5.11.1',
        'image' => [
            'innerContent' => ['desktop' => ['value' => [
                'src' => '/wp-content/uploads/uploaded_images_videos/IMG_5762-scaled.jpg',
                'alt' => 'Farm and animal program activities at Autism Sanctuary.'
            ]]]
        ],
        'module' => [
            'advanced' => [
                'htmlAttributes' => ['desktop' => ['value' => ['class' => 'as-native-image']]],
                'sizing' => ['desktop' => ['value' => ['height' => '400px']]]
            ]
        ]
    ])
]);

$col4_right = make_divi_block('divi/column', [
    'builderVersion' => '5.11.1',
    'module' => [
        'advanced' => ['type' => ['desktop' => ['value' => '1_2']]],
        'decoration' => ['sizing' => ['desktop' => ['value' => ['flexType' => '12_24']]]]
    ]
], [
    make_divi_block('divi/text', [
        'builderVersion' => '5.11.1',
        'content' => ['innerContent' => ['desktop' => ['value' => '<p class="as-eyebrow">Looking ahead</p>']]]
    ]),
    make_divi_block('divi/heading', [
        'builderVersion' => '5.11.1',
        'title' => [
            'innerContent' => ['desktop' => ['value' => 'Growing capacity for purpose and belonging.']],
            'decoration' => ['font' => ['font' => ['desktop' => [
                'family' => 'Cormorant Garamond',
                'weight' => '400',
                'color'  => '#1E3D2C',
                'size'   => '2.25rem'
            ]]]]
        ]
    ]),
    make_divi_block('divi/text', [
        'builderVersion' => '5.11.1',
        'content' => ['innerContent' => ['desktop' => [
            'value' => '<p class="as-lede">With your support, we are advancing capital and program priorities that strengthen life at Autism Sanctuary.</p><ul class="as-checklist"><li><strong>Activity Barn:</strong> a purpose-built program space that supports growth and moves day programming out of the historic house basement.</li><li><strong>Facility upgrades:</strong> improvements to existing campus resources that keep outdoor and indoor days safe, welcoming, and ready for more people.</li><li><strong>Workplace opportunities under development:</strong> expanding vocational pathways and Workplace Assistance so more people can build skills and meaningful work.</li></ul>'
        ]]]
    ]),
    make_divi_block('divi/button', [
        'builderVersion' => '5.11.1',
        'button' => [
            'innerContent' => ['desktop' => ['text' => 'Support this work', 'link' => ['desktop' => ['value' => ['url' => '/donate/']]]]],
            'decoration' => [
                'background' => ['desktop' => ['value' => ['color' => '#2F5D43']]],
                'font' => ['font' => ['desktop' => ['color' => '#ffffff', 'weight' => '600']]],
                'border' => ['desktop' => ['value' => ['radius' => ['sync' => 'on', 'topLeft' => '6px', 'topRight' => '6px', 'bottomLeft' => '6px', 'bottomRight' => '6px']]]]
            ]
        ],
        'module' => [
            'advanced' => ['htmlAttributes' => ['desktop' => ['value' => ['class' => 'as-btn as-btn--primary']]]],
            'decoration' => ['spacing' => ['desktop' => ['value' => ['margin' => ['top' => '1.5rem']]]]]
        ]
    ])
]);

$sec4_row = make_divi_block('divi/row', [
    'builderVersion' => '5.11.1',
    'module' => [
        'advanced' => [
            'columnStructure' => ['desktop' => ['value' => '1_2,1_2']],
            'flexColumnStructure' => ['desktop' => ['value' => 'equal-columns_2']]
        ],
        'decoration' => [
            'sizing' => ['desktop' => ['value' => ['maxWidth' => '80rem', 'width' => '100%']]],
            'spacing' => ['desktop' => ['value' => ['padding' => ['top' => '60px', 'bottom' => '60px']]]],
            'layout' => ['desktop' => ['value' => ['flexWrap' => 'nowrap', 'alignItems' => 'center', 'columnGap' => '30px']]]
        ]
    ]
], [$col4_left, $col4_right]);

$native_sections[] = make_divi_block('divi/section', [
    'builderVersion' => '5.11.1',
    'module' => [
        'decoration' => [
            'background' => ['desktop' => ['value' => ['color' => '#f5f2e8']]],
            'spacing' => ['desktop' => ['value' => ['padding' => ['top' => '60px', 'bottom' => '60px']]]]
        ]
    ]
], [$sec4_row]);
echo "Section 4 (Looking ahead): Built native Divi 5 2-column section.\n";

// =========================================================================
// SECTION 5: NEWSROOM (Intro, Native Blog Module, Native Button)
// =========================================================================
$sec5_row = make_divi_block('divi/row', [
    'builderVersion' => '5.11.1',
    'module' => [
        'decoration' => [
            'sizing' => ['desktop' => ['value' => ['maxWidth' => '56rem', 'width' => '100%']]],
            'spacing' => ['desktop' => ['value' => ['padding' => ['top' => '0px', 'right' => '1.25rem', 'bottom' => '0px', 'left' => '1.25rem']]]]
        ]
    ]
], [
    make_divi_block('divi/column', ['builderVersion' => '5.11.1', 'module' => ['advanced' => ['type' => ['desktop' => ['value' => '4_4']]]]], [
        make_divi_block('divi/text', [
            'builderVersion' => '5.11.1',
            'module' => [
                'advanced' => ['htmlAttributes' => ['desktop' => ['value' => ['class' => 'as-home-news-intro']]]],
                'decoration' => ['spacing' => ['desktop' => ['value' => ['margin' => ['bottom' => '1.5rem']]]]]
            ],
            'content' => ['innerContent' => ['desktop' => ['value' => "<p class=\"as-eyebrow\">Newsroom</p>\n<h2>Latest updates</h2>\n<p class=\"as-lede\">Farm stories, press, and program notes from Autism Sanctuary.</p>"]]]
        ]),
        make_divi_block('divi/blog', [
            'builderVersion' => '5.11.1',
            'module' => ['advanced' => ['htmlAttributes' => ['desktop' => ['value' => ['class' => 'as-news-blog as-home-news-blog']]]]],
            'fullwidth' => ['advanced' => ['fullwidth' => ['desktop' => ['value' => 'on']]]],
            'post' => [
                'advanced' => [
                    'number' => ['desktop' => ['value' => '3']],
                    'dateFormat' => ['desktop' => ['value' => 'F j, Y']],
                    'excerptContent' => ['desktop' => ['value' => 'off']],
                    'showExcerpt' => ['desktop' => ['value' => 'on']],
                    'excerptLength' => ['desktop' => ['value' => '22']],
                    'fullwidth' => ['desktop' => ['value' => 'on']],
                    'layout' => ['desktop' => ['value' => 'fullwidth']]
                ],
                'decoration' => ['border' => ['desktop' => ['value' => ['styles' => ['all' => ['width' => '0px']]]]]]
            ],
            'image' => ['advanced' => ['enable' => ['desktop' => ['value' => 'on']]]],
            'header' => [
                'advanced' => ['level' => ['desktop' => ['value' => 'h3']]],
                'decoration' => ['font' => ['font' => ['desktop' => ['family' => 'Cormorant Garamond', 'size' => '1.35rem', 'color' => '#1E3D2C']]]]
            ],
            'meta' => [
                'advanced' => ['showAuthor' => ['desktop' => ['value' => 'off']], 'showDate' => ['desktop' => ['value' => 'on']], 'showCategories' => ['desktop' => ['value' => 'off']], 'showComments' => ['desktop' => ['value' => 'off']]],
                'decoration' => ['font' => ['font' => ['desktop' => ['family' => 'Source Sans 3', 'size' => '0.9rem', 'color' => '#4A534C']]]]
            ],
            'content' => ['advanced' => ['showContent' => ['desktop' => ['value' => 'off']], 'showMore' => ['desktop' => ['value' => 'off']]]],
            'pagination' => ['advanced' => ['showPagination' => ['desktop' => ['value' => 'off']]]]
        ]),
        make_divi_block('divi/button', [
            'builderVersion' => '5.11.1',
            'button' => [
                'innerContent' => ['desktop' => ['text' => 'Read more news stories', 'link' => ['desktop' => ['value' => ['url' => '/news/']]]]],
                'decoration' => [
                    'background' => ['desktop' => ['value' => ['color' => 'transparent']]],
                    'font' => ['font' => ['desktop' => ['color' => '#2F5D43', 'weight' => '600']]],
                    'border' => ['desktop' => ['value' => ['radius' => ['sync' => 'on', 'topLeft' => '6px', 'topRight' => '6px', 'bottomLeft' => '6px', 'bottomRight' => '6px'], 'styles' => ['all' => ['width' => '1.5px', 'color' => '#2F5D43']]]]]
                ]
            ],
            'module' => [
                'advanced' => ['htmlAttributes' => ['desktop' => ['value' => ['class' => 'as-btn as-btn--ghost']]]],
                'decoration' => ['spacing' => ['desktop' => ['value' => ['margin' => ['top' => '2rem']]]]]
            ]
        ])
    ])
]);

$native_sections[] = make_divi_block('divi/section', [
    'builderVersion' => '5.11.1',
    'module' => [
        'advanced' => ['htmlAttributes' => ['desktop' => ['value' => ['class' => 'as-home-news']]]],
        'decoration' => [
            'background' => ['desktop' => ['value' => ['color' => 'rgba(255,255,255,0.45)']]],
            'spacing' => ['desktop' => ['value' => ['padding' => ['top' => '4rem', 'bottom' => '4.5rem']]]]
        ]
    ]
], [$sec5_row]);
echo "Section 5 (Newsroom): Built native Divi 5 news section with blog and button.\n";

// =========================================================================
// SECTION 6: GET IN TOUCH (Forest Background, Heading, Native Buttons)
// =========================================================================
$sec6_buttons_row = make_divi_block('divi/row', [
    'builderVersion' => '5.11.1',
    'module' => [
        'advanced' => ['columnStructure' => ['desktop' => ['value' => '1_2,1_2']]],
        'decoration' => [
            'layout' => ['desktop' => ['value' => ['display' => 'flex', 'flexDirection' => 'row', 'flexWrap' => 'nowrap', 'justifyContent' => 'center']]],
            'spacing' => ['desktop' => ['value' => ['padding' => ['top' => '1.5rem', 'bottom' => '0px']]]]
        ]
    ]
], [
    make_divi_block('divi/column', ['builderVersion' => '5.11.1', 'module' => ['advanced' => ['type' => ['desktop' => ['value' => '1_2']]]]], [
        make_divi_block('divi/button', [
            'builderVersion' => '5.11.1',
            'button' => [
                'innerContent' => ['desktop' => ['text' => 'Contact us', 'link' => ['desktop' => ['value' => ['url' => '/contact/']]]]],
                'decoration' => [
                    'background' => ['desktop' => ['value' => ['color' => '#C28B38']]],
                    'font' => ['font' => ['desktop' => ['color' => '#1E3D2C', 'weight' => '600']]],
                    'border' => ['desktop' => ['value' => ['radius' => ['sync' => 'on', 'topLeft' => '6px', 'topRight' => '6px', 'bottomLeft' => '6px', 'bottomRight' => '6px']]]]
                ]
            ],
            'module' => ['advanced' => ['htmlAttributes' => ['desktop' => ['value' => ['class' => 'as-btn as-btn--primary']]]]]
        ])
    ]),
    make_divi_block('divi/column', ['builderVersion' => '5.11.1', 'module' => ['advanced' => ['type' => ['desktop' => ['value' => '1_2']]]]], [
        make_divi_block('divi/button', [
            'builderVersion' => '5.11.1',
            'button' => [
                'innerContent' => ['desktop' => ['text' => 'See programs', 'link' => ['desktop' => ['value' => ['url' => '/programs/']]]]],
                'decoration' => [
                    'background' => ['desktop' => ['value' => ['color' => '#F5F2E8']]],
                    'font' => ['font' => ['desktop' => ['color' => '#1E3D2C', 'weight' => '600']]],
                    'border' => ['desktop' => ['value' => ['radius' => ['sync' => 'on', 'topLeft' => '6px', 'topRight' => '6px', 'bottomLeft' => '6px', 'bottomRight' => '6px']]]]
                ]
            ],
            'module' => ['advanced' => ['htmlAttributes' => ['desktop' => ['value' => ['class' => 'as-btn as-btn--ghost']]]]]
        ])
    ])
]);

$sec6_row = make_divi_block('divi/row', [
    'builderVersion' => '5.11.1',
    'module' => [
        'decoration' => [
            'sizing' => ['desktop' => ['value' => ['maxWidth' => '80rem', 'width' => '100%']]],
            'spacing' => ['desktop' => ['value' => ['padding' => ['top' => '0px', 'right' => '1.25rem', 'bottom' => '0px', 'left' => '1.25rem']]]]
        ]
    ]
], [
    make_divi_block('divi/column', ['builderVersion' => '5.11.1', 'module' => ['advanced' => ['type' => ['desktop' => ['value' => '4_4']]]]], [
        make_divi_block('divi/heading', [
            'builderVersion' => '5.11.1',
            'title' => [
                'innerContent' => ['desktop' => ['value' => 'Get in touch']],
                'decoration' => ['font' => ['font' => ['desktop' => [
                    'family' => 'Cormorant Garamond',
                    'weight' => '400',
                    'color'  => '#ffffff',
                    'size'   => '2.5rem'
                ]]]]
            ],
            'module' => ['decoration' => ['spacing' => ['desktop' => ['value' => ['margin' => ['bottom' => '1.5rem']]]]]]
        ]),
        $sec6_buttons_row
    ])
]);

$native_sections[] = make_divi_block('divi/section', [
    'builderVersion' => '5.11.1',
    'module' => [
        'advanced' => ['htmlAttributes' => ['desktop' => ['value' => ['class' => 'as-section as-section--forest']]]],
        'decoration' => [
            'spacing' => ['desktop' => ['value' => ['padding' => ['top' => '0px', 'bottom' => '4.5rem']]]]
        ]
    ]
], [$sec6_row]);
echo "Section 6 (Get in touch): Built native Divi 5 CTA section.\n";

// Serialize the native sections
$new_content = serialize_blocks($native_sections);

// Direct update on Page 692
$wpdb->update($wpdb->posts, ['post_content' => $new_content], ['ID' => 692]);
clean_post_cache(692);

update_post_meta(692, '_et_pb_use_builder', 'on');
update_post_meta(692, '_et_pb_built_for_post_type', 'page');
update_post_meta(692, '_et_builder_version', '5.11.1');

// Flush caches
if (class_exists('\Hummingbird\WP_Hummingbird')) {
    \Hummingbird\WP_Hummingbird::flush_cache(true, true);
    echo "Hummingbird cache flushed.\n";
}
wp_cache_flush();
echo "WordPress object cache flushed.\n";

echo "=== SUCCESS: Page 692 (Home Revised) 100% Native Divi 5 build complete! ===\n";
