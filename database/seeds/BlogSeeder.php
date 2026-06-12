<?php

use App\Models\Post;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class BlogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $user = '00000000-0000-0000-0000-000000000000';

        if (! Post::where('slug', 'catalog-open-source')->exists()) {
            $body = <<<'BODY'
This catalog is designed to be easy to adapt and contribute to.
<br><br>
If you're a developer working with PHP, Laravel, or frontend tooling, you can use this codebase as the starting point for your own footwear archive or collection tracker.
BODY;

            Post::forceCreate([
                'slug' => 'catalog-open-source',
                'title' => 'The catalog is open source',
                'user_id' => $user,
                'preview' => $body,
                'body' => $body,
                'created_at' => Carbon::parse('2018-06-11T16:00:00+00:00'),
                'published_at' => Carbon::parse('2018-06-11T16:30:00+00:00'),
            ]);
        }

        if (! Post::where('slug', 'support-the-archive')->exists()) {
            $body = <<<'BODY'
Running a media-rich catalog takes ongoing maintenance and hosting resources.
<br>
If you adapt this project for a real community, consider setting up a support channel so users can help fund hosting and development.
BODY;

            Post::forceCreate([
                'slug' => 'support-the-archive',
                'title' => 'Supporting the archive',
                'user_id' => $user,
                'preview' => $body,
                'body' => $body,
                'created_at' => Carbon::parse('2018-06-11T16:00:00+00:00'),
                'published_at' => Carbon::parse('2018-06-11T16:00:00+00:00'),
            ]);
        }

        if (! Post::where('slug', 'welcome-to-shoe-archive')->exists()) {
            $preview = <<<'BODY'
Welcome to the catalog. This app is built to index and explore shoes across brands, categories, materials, and release eras.
<br><br>
Use it as a reference library, a personal collection tracker, or the base for a larger footwear community.
BODY;

            $body = <<<'BODY'
Welcome to the catalog. This app is built to index and explore shoes across brands, categories, materials, and release eras.
<br><br>
Use it as a reference library, a personal collection tracker, or the base for a larger footwear community.
<br><br>
As you adapt the project, update the branding, imagery, and support links so the public experience matches your own fork.
BODY;

            Post::forceCreate([
                'slug' => 'welcome-to-shoe-archive',
                'title' => 'Welcome to Shoe Archive',
                'user_id' => $user,
                'preview' => $preview,
                'body' => $body,
                'created_at' => Carbon::parse('2018-06-11T15:00:00+00:00'),
                'published_at' => Carbon::parse('2018-06-11T15:00:00+00:00'),
            ]);
        }
    }
}
