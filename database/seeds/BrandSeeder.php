<?php

use App\Models\Brand;
use App\Models\Image;
use Illuminate\Database\QueryException;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BrandSeeder extends Seeder
{
    /**
     * A list of brands to seed.
     *
     * @var string[]
     */
    protected const BRANDS = [
        'nike' => 'Nike',
        'adidas' => 'Adidas',
        'new-balance' => 'New Balance',
        'converse' => 'Converse',
        'vans' => 'Vans',
        'puma' => 'Puma',
        'reebok' => 'Reebok',
        'asics' => 'ASICS',
        'salomon' => 'Salomon',
        'hoka' => 'HOKA',
        'on' => 'On',
        'dr-martens' => 'Dr. Martens',
        'birkenstock' => 'Birkenstock',
        'crocs' => 'Crocs',
        'clarks' => 'Clarks',
        'timberland' => 'Timberland',
        'red-wing' => 'Red Wing',
        'cole-haan' => 'Cole Haan',
        'allen-edmonds' => 'Allen Edmonds',
        'common-projects' => 'Common Projects',
        'jimmy-choo' => 'Jimmy Choo',
        'manolo-blahnik' => 'Manolo Blahnik',
        'christian-louboutin' => 'Christian Louboutin',
        'steve-madden' => 'Steve Madden',
        'independent' => 'Independent Brand',
        'offbrand' => 'Offbrand',
    ];

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        foreach (static::BRANDS as $name => $brand) {
            $slug = Str::slug($brand);

            if (Brand::where('slug', $slug)->exists()) {
                continue;
            }

            $image = Image::firstOrCreate([
                'name' => $brand.' icon picture',
                'filename' => "{$slug}.png",
            ]);

            Brand::create([
                'slug' => Str::slug($brand),
                'name' => $brand,
                'short_name' => $name,
                'image_id' => $image->id,
            ]);
        }
    }
}
