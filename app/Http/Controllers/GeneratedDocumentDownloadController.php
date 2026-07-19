<?php

namespace App\Http\Controllers;

use App\Models\GeneratedDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GeneratedDocumentDownloadController extends Controller
{
    public function __invoke(Request $request, GeneratedDocument $generatedDocument): StreamedResponse
    {
        abort_unless($generatedDocument->user_id === $request->user()->id, 404);
        Gate::forUser($request->user())->authorize('download', $generatedDocument);

        $storedPath = $generatedDocument->stored_path ?: 'generated-documents/'.$generatedDocument->v1_reference;
        $friendlyName = $this->friendlyName($generatedDocument->v1_reference ?: $storedPath, $generatedDocument->document_type);

        abort_if(blank($storedPath), 404, 'Generated file was not found.');
        abort_unless(Storage::disk('local')->exists($storedPath), 404, 'Generated file was not found.');

        return Storage::disk('local')->download($storedPath, $friendlyName);
    }

    private function friendlyName(?string $value, string $fallback): string
    {
        $normalized = str_replace('\\', '/', trim((string) $value));
        $name = basename($normalized);

        return $name !== '' ? $name : str($fallback)->headline()->toString();
    }
}
