<?php

namespace Tests\Feature;

use App\Models\Feature;
use App\Models\FeatureTranslation;
use App\Models\Traits\Collection as ModelCollection;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CacheSerializationTest extends TestCase
{
    public function test_file_cache_can_restore_translatable_models_from_the_allow_list(): void
    {
        $key = 'tests:cache-serialization:'.uniqid('', true);

        $feature = new Feature(['slug' => 'retro']);
        $feature->setRelation('translations', new EloquentCollection([
            new FeatureTranslation(['locale' => 'en', 'name' => 'Retro']),
        ]));

        $cache = Cache::store('file');

        try {
            $cache->put($key, new ModelCollection([$feature]), 60);

            $restored = $cache->get($key);

            $this->assertInstanceOf(ModelCollection::class, $restored);
            $this->assertInstanceOf(Feature::class, $restored->first());
            $this->assertInstanceOf(
                FeatureTranslation::class,
                $restored->first()->getRelation('translations')->first()
            );
            $this->assertSame(
                'Retro',
                $restored->first()->getRelation('translations')->first()->name
            );
        } finally {
            $cache->forget($key);
        }
    }
}
