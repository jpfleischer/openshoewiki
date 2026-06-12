<?php

class AttributeSeeder extends Seeder
{
    /**
     * The model to seed.
     *
     * @var string
     */
    protected static $model = App\Models\Attribute::class;

    /**
     * A list of attributes to seed.
     *
     * @var string[]
     */
    protected static $content = [
        'Heel Height',
        'Shaft Height',
        'Calf Circumference',
        'Toe Shape',
        'Closure',
        'Upper Material',
        'Lining Material',
        'Insole Material',
        'Sole Material',
        'Finish',
        'Width',
        'Weight',
        'Country of Origin',
        'Release Season',
        'Collaboration',
        'Price',
        'Retail Price',
        'Condition Notes',
        'Fit Notes',
        'Owner Notes',
    ];
}
