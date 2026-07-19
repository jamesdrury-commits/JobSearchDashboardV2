<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentDownloadController extends Controller
{
    public function __invoke(Request $request, Document $document): StreamedResponse
    {
        abort_unless($document->user_id === $request->user()->id, 404);
        Gate::forUser($request->user())->authorize('download', $document);

        abort_if(blank($document->path), 404, 'Generated file was not found.');
        abort_unless(Storage::disk($document->disk)->exists($document->path), 404, 'Generated file was not found.');

        return Storage::disk($document->disk)->download(
            $document->path,
            $document->display_filename,
        );
    }
}
