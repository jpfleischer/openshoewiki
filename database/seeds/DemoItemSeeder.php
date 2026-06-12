<?php

use App\Models\Attribute;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Color;
use App\Models\Feature;
use App\Models\Item;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class DemoItemSeeder extends Seeder
{
    public function run()
    {
        $submitter = User::query()
            ->where('username', 'testuser')
            ->orWhere('email', 'testuser@example.com')
            ->first();

        if ($submitter) {
            $submitter->forceFill([
                'username' => 'testuser',
                'email' => 'testuser@example.com',
                'name' => 'testuser',
                'level' => User::REGULAR,
            ])->save();
        } else {
            $submitter = User::create([
                'username' => 'testuser',
                'email' => 'testuser@example.com',
                'password' => bcrypt(Str::random(64)),
                'name' => 'testuser',
                'level' => User::REGULAR,
            ]);
        }

        $publisher = User::query()
            ->where('email', config('site.admin.email') ?? 'admin@example.com')
            ->first();

        $items = [
            [
                'slug' => 'nike-pegasus-trail-5-gtx',
                'brand_slug' => 'nike',
                'brand_name' => 'Nike',
                'category_slug' => 'athletic-shoes',
                'category_name' => 'Athletic Shoes',
                'english_name' => 'Nike Pegasus Trail 5 GTX',
                'foreign_name' => null,
                'year' => 2025,
                'product_number' => 'NIK-PEGTR5-GTX',
                'price' => '18000',
                'currency' => 'usd',
                'notes' => implode("\n", [
                    '<ul>',
                    '<li>Waterproof trail runner built for mixed terrain and wet-weather use.</li>',
                    '<li>Uses a lugged outsole, cushioned midsole, and quick-lace hiking inspired upper.</li>',
                    '<li>Good sample record for performance footwear filters and technical attributes.</li>',
                    '</ul>',
                ]),
                'image' => '/images/default.png',
                'published_at' => '2025-02-03 12:00:00',
                'features' => ['waterproof', 'lug-sole', 'cushioned-insole', 'lace-up', 'pull-tab'],
                'colors' => ['black', 'gray', 'green'],
                'tags' => [
                    'trail' => 'Use: Trail',
                    'running' => 'Use: Running',
                    'breathable' => 'Performance: Breathable',
                    'lightweight' => 'Performance: Lightweight',
                ],
                'attributes' => [
                    'upper-material' => 'Engineered mesh with synthetic overlays',
                    'lining-material' => 'Waterproof membrane lining',
                    'sole-material' => 'Rubber trail outsole',
                    'release-season' => 'Spring 2025',
                    'retail-price' => '$180',
                    'fit-notes' => 'True to size with a secure midfoot fit',
                ],
            ],
            [
                'slug' => 'dr-martens-1460-mono-smooth',
                'brand_slug' => 'dr-martens',
                'brand_name' => 'Dr. Martens',
                'category_slug' => 'boots',
                'category_name' => 'Boots',
                'english_name' => 'Dr. Martens 1460 Mono Smooth',
                'foreign_name' => null,
                'year' => 2024,
                'product_number' => 'DM-1460-MONO',
                'price' => '17000',
                'currency' => 'usd',
                'notes' => implode("\n", [
                    '<ul>',
                    '<li>Eight-eye leather boot with monochrome hardware and classic air-cushioned sole profile.</li>',
                    '<li>Useful sample for lifestyle boots, leather uppers, and durable sole construction.</li>',
                    '</ul>',
                ]),
                'image' => '/images/default.png',
                'published_at' => '2025-02-10 12:00:00',
                'features' => ['lace-up', 'chunky-sole', 'contrast-stitching'],
                'colors' => ['black'],
                'tags' => [
                    'leather' => 'Material: Leather',
                    'casual' => 'Use: Casual',
                    'workwear' => 'Use: Workwear',
                    'monochrome' => 'Color: Monochrome',
                ],
                'attributes' => [
                    'shaft-height' => '8 in',
                    'upper-material' => 'Smooth leather',
                    'sole-material' => 'Air-cushioned PVC sole',
                    'finish' => 'Matte',
                    'retail-price' => '$170',
                    'country-of-origin' => 'Thailand',
                ],
            ],
            [
                'slug' => 'birkenstock-boston-soft-footbed',
                'brand_slug' => 'birkenstock',
                'brand_name' => 'Birkenstock',
                'category_slug' => 'clogs',
                'category_name' => 'Clogs',
                'english_name' => 'Birkenstock Boston Soft Footbed',
                'foreign_name' => null,
                'year' => 2025,
                'product_number' => 'BK-BOSTON-SFB',
                'price' => '15800',
                'currency' => 'usd',
                'notes' => implode("\n", [
                    '<ul>',
                    '<li>Backless clog with contoured footbed and adjustable buckle strap.</li>',
                    '<li>Useful sample for comfort footwear, suede finishes, and casual everyday filtering.</li>',
                    '</ul>',
                ]),
                'image' => '/images/default.png',
                'published_at' => '2025-02-17 12:00:00',
                'features' => ['buckle-closure', 'suede-finish', 'cushioned-insole'],
                'colors' => ['brown', 'beige'],
                'tags' => [
                    'suede' => 'Material: Suede',
                    'casual' => 'Use: Casual',
                    'minimalist' => 'Style: Minimalist',
                    'orthotic-friendly' => 'Fit: Orthotic Friendly',
                ],
                'attributes' => [
                    'upper-material' => 'Suede',
                    'lining-material' => 'Soft suede lining',
                    'insole-material' => 'Cork-latex footbed',
                    'sole-material' => 'EVA outsole',
                    'retail-price' => '$158',
                    'fit-notes' => 'Relaxed fit with roomy toe box',
                ],
            ],
        ];

        foreach ($items as $index => $data) {
            $brand = Brand::firstOrCreate(
                ['slug' => $data['brand_slug']],
                [
                    'name' => $data['brand_name'],
                    'short_name' => $data['brand_slug'],
                    'image' => null,
                ]
            );

            $category = Category::firstOrCreate(
                ['slug' => $data['category_slug']],
                ['name' => $data['category_name']]
            );

            $item = Item::withoutEvents(function () use ($brand, $category, $publisher, $submitter, $data) {
                $item = Item::firstOrNew(['slug' => $data['slug']]);

                if (! $item->exists) {
                    $item->id = uuid4();
                }

                $item->fill([
                    'english_name' => $data['english_name'],
                    'foreign_name' => $data['foreign_name'],
                    'year' => $data['year'],
                    'product_number' => $data['product_number'],
                    'price' => $data['price'],
                    'currency' => $data['currency'],
                    'notes' => $data['notes'],
                    'internal_notes' => 'Seeded demo shoe record for local development.',
                    'image' => $data['image'],
                    'images' => [],
                ]);

                $item->brand()->associate($brand);
                $item->category()->associate($category);
                $item->user_id = $submitter->getKey();

                if ($publisher !== null) {
                    $item->publisher_id = $publisher->getKey();
                }

                $item->slug = $data['slug'];
                $item->save();
                $item->newQuery()
                    ->whereKey($item->getKey())
                    ->update([
                        'status' => Item::PUBLISHED,
                        'published_at' => Carbon::parse($data['published_at'], 'UTC'),
                    ]);

                return $item->fresh();
            });

            $item->categories()->sync([$category->id]);
            $item->features()->sync($this->idsFor(Feature::class, $data['features']));
            $item->colors()->sync($this->idsFor(Color::class, $data['colors']));
            $item->tags()->sync($this->tagIds($data['tags']));
            $item->attributes()->sync($this->attributeValues($data['attributes']));
        }
    }

    protected function idsFor(string $modelClass, array $slugs): array
    {
        return $modelClass::query()
            ->whereIn('slug', $slugs)
            ->pluck('id')
            ->all();
    }

    protected function tagIds(array $tags): array
    {
        $ids = [];

        foreach ($tags as $slug => $name) {
            $tag = Tag::firstOrCreate(
                ['slug' => $slug],
                ['name' => $name]
            );

            $ids[] = $tag->id;
        }

        return $ids;
    }

    protected function attributeValues(array $attributes): array
    {
        $values = [];

        foreach ($attributes as $slug => $value) {
            $attribute = Attribute::firstOrCreate(
                ['slug' => $slug],
                ['name' => Str::title(str_replace('-', ' ', $slug))]
            );

            $values[$attribute->id] = ['value' => $value];
        }

        return $values;
    }
}
