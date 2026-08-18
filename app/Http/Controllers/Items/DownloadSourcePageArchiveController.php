<?php

namespace App\Http\Controllers\Items;

use App\Http\Controllers\Controller;
use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DownloadSourcePageArchiveController extends Controller
{
    public function __invoke(Request $request, Item $item): Response
    {
        abort_unless($request->user()?->can('viewRevisionHistory', $item), Response::HTTP_FORBIDDEN);

        $archive = $item->sourcePageArchive;

        abort_unless($archive !== null, Response::HTTP_NOT_FOUND);

        $payload = Storage::disk($archive->storage_disk)->get($archive->storage_path);
        $html = gzdecode($payload);

        abort_unless($html !== false, Response::HTTP_INTERNAL_SERVER_ERROR);

        $filename = Str::slug($item->english_name ?: 'source-page') . '.html';

        return response($html, Response::HTTP_OK, [
            'Content-Type' => 'application/octet-stream',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
