<?php

class CategorySeeder extends Seeder
{
    /**
     * The model to seed.
     *
     * @var string
     */
    protected static $model = App\Models\Category::class;

    /**
     * A listing of item categories.
     *
     * @var string[]
     */
    protected static $content = [
        'Sneakers',
        'Boots',
        'Ankle Boots',
        'Knee-High Boots',
        'Loafers',
        'Oxfords',
        'Derbies',
        'Heels',
        'Pumps',
        'Sandals',
        'Clogs',
        'Mules',
        'Flats',
        'Mary Janes',
        'Ballet Flats',
        'Platforms',
        'Wedges',
        'Slippers',
        'Athletic Shoes',
        'Dress Shoes',
        'Work Shoes',
        'Other',
    ];
}
