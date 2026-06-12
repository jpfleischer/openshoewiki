<?php

class StyleSeeder extends Seeder
{
    /**
     * A model to use for seeding.
     *
     * @var string
     */
    protected static $model = App\Models\Style::class;

    /**
     * A list of footwear style groupings to seed.
     *
     * @var string[]
     */
    protected static $content = [
        'Minimalist',
        'Retro',
        'Performance',
        'Outdoor',
        'Formal',
        'Casual',
        'Streetwear',
        'Luxury',
        'Workwear',
        'Vintage Inspired',
        'Athleisure',
        'Utility',
        'Chunky',
        'Classic',
        'Technical',
    ];
}
