<?php

return [

    'intro' => 'Download SmelterWorks logo marks. Sizes are in pixels.',

    'groups' => [
        [
            'title' => 'Master marks',
            'description' => 'Full-resolution sources.',
            'marks' => [
                ['basename' => 'SmelterWorks', 'size' => 1024, 'preview' => 'solid'],
                ['basename' => 'SmelterWorks-transparent', 'size' => 1024, 'preview' => 'checker'],
            ],
        ],
        [
            'title' => 'Logo mark (solid)',
            'description' => 'Square mark on the default site background.',
            'preview' => 'solid',
            'basename' => 'SmelterWorks',
            'sizes' => [64, 128, 256, 512],
        ],
        [
            'title' => 'Logo mark (transparent)',
            'description' => 'Alpha channel preserved for dark or photo backgrounds.',
            'preview' => 'checker',
            'basename' => 'SmelterWorks-transparent',
            'sizes' => [64, 128, 256, 512],
        ],
    ],

];
