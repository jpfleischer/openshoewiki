<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSourcePageArchiveRequest;
use App\Models\SourcePageArchive;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class SourcePageArchiveController extends Controller
{
    public function store(StoreSourcePageArchiveRequest $request): JsonResponse
    {
        $html = $request->string('html')->toString();
        $sourceUrl = $request->string('source_url')->toString();
        $contentHash = hash('sha256', $html);
        $domain = parse_url($sourceUrl, PHP_URL_HOST) ?: null;
        $storagePath = 'source-pages/' . now()->format('Y/m/d') . '/' . $contentHash . '.html.gz';

        if (! Storage::disk('local')->exists($storagePath)) {
            Storage::disk('local')->put($storagePath, gzencode($html, 9));
        }

        $archive = SourcePageArchive::create([
            'user_id' => $request->user()->getKey(),
            'source_url' => $sourceUrl,
            'domain' => $domain,
            'title' => $request->input('title'),
            'storage_disk' => 'local',
            'storage_path' => $storagePath,
            'content_hash' => $contentHash,
            'content_bytes' => strlen($html),
            'mime_type' => 'text/html',
            'compression' => 'gzip',
            'captured_at' => $request->date('captured_at'),
        ]);

        return response()->json([
            'id' => $archive->getKey(),
            'content_hash' => $archive->content_hash,
            'source_url' => $archive->source_url,
        ]);
    }
}
