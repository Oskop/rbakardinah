<?php

namespace App\Http\Controllers\General;

use App\Http\Controllers\Controller;
use App\Models\DocumentationArticle;
use App\Models\DocumentationVersion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DocumentationController extends Controller
{
    public function index(Request $request)
    {
        $versionParam = $request->query('version');
        $slugParam = $request->query('article');
        $tab = $request->query('tab', 'web');

        // All HTML & PDF versions for version switcher & archives
        $htmlVersions = DocumentationVersion::html()->orderByDesc('released_at')->orderByDesc('id')->get();
        $pdfVersions = DocumentationVersion::pdf()->orderByDesc('released_at')->orderByDesc('id')->get();

        // Selected or Active HTML Version
        if ($versionParam) {
            $selectedHtmlVersion = DocumentationVersion::html()->where('version', $versionParam)->first()
                ?? DocumentationVersion::html()->active()->first()
                ?? $htmlVersions->first();
        } else {
            $selectedHtmlVersion = DocumentationVersion::html()->active()->first() ?? $htmlVersions->first();
        }

        // Active PDF Version (or selected if passed)
        $activePdfVersion = DocumentationVersion::pdf()->active()->first() ?? $pdfVersions->first();

        // Articles for selected HTML version
        $articles = $selectedHtmlVersion ? $selectedHtmlVersion->articles : collect();
        $groupedArticles = $articles->groupBy('category');

        // Determine current active article
        $currentArticle = null;
        if ($slugParam && $articles->isNotEmpty()) {
            $currentArticle = $articles->firstWhere('slug', $slugParam);
        }
        if (!$currentArticle && $articles->isNotEmpty()) {
            $currentArticle = $articles->first();
        }

        // Determine Prev & Next Articles
        $prevArticle = null;
        $nextArticle = null;
        if ($currentArticle && $articles->isNotEmpty()) {
            $currentIndex = $articles->search(fn($item) => $item->id === $currentArticle->id);
            if ($currentIndex !== false) {
                if ($currentIndex > 0) {
                    $prevArticle = $articles->get($currentIndex - 1);
                }
                if ($currentIndex < $articles->count() - 1) {
                    $nextArticle = $articles->get($currentIndex + 1);
                }
            }
        }

        // Prepare Search Index for Ctrl + K
        $searchIndex = $articles->map(function ($art) {
            return [
                'id' => $art->id,
                'category' => $art->category,
                'title' => $art->title,
                'slug' => $art->slug,
                'icon' => $art->icon ?? '📄',
                'snippet' => strip_tags(mb_substr($art->content, 0, 160)) . '...',
            ];
        });

        return view('documentation.index', [
            'tab' => $tab,
            'htmlVersions' => $htmlVersions,
            'pdfVersions' => $pdfVersions,
            'selectedHtmlVersion' => $selectedHtmlVersion,
            'activePdfVersion' => $activePdfVersion,
            'groupedArticles' => $groupedArticles,
            'currentArticle' => $currentArticle,
            'prevArticle' => $prevArticle,
            'nextArticle' => $nextArticle,
            'searchIndex' => $searchIndex,
        ]);
    }

    public function article(string $version, string $slug)
    {
        return redirect()->route('documentation.index', [
            'version' => $version,
            'article' => $slug,
            'tab' => 'web'
        ]);
    }

    public function previewPdf(DocumentationVersion $version)
    {
        if ($version->type !== 'pdf') {
            abort(404, 'Dokumen bukan bertipe PDF.');
        }

        if (!$version->file_path || !Storage::disk('public')->exists($version->file_path)) {
            // Check if mock/placeholder or file in public storage
            $fullPath = storage_path('app/public/' . $version->file_path);
            if (!file_exists($fullPath)) {
                return back()->with('error', 'Berkas PDF fisik belum diunggah atau tidak ditemukan di server.');
            }
            return response()->file($fullPath, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . basename($version->file_path) . '"'
            ]);
        }

        return response()->file(Storage::disk('public')->path($version->file_path), [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . basename($version->file_path) . '"'
        ]);
    }

    public function downloadPdf(DocumentationVersion $version)
    {
        if ($version->type !== 'pdf') {
            abort(404, 'Dokumen bukan bertipe PDF.');
        }

        if (!$version->file_path || !Storage::disk('public')->exists($version->file_path)) {
            $fullPath = storage_path('app/public/' . $version->file_path);
            if (!file_exists($fullPath)) {
                return back()->with('error', 'Berkas PDF fisik tidak ditemukan di server.');
            }
            return response()->download($fullPath, "Manual_Book_RBA_{$version->version}.pdf");
        }

        return Storage::disk('public')->download($version->file_path, "Manual_Book_RBA_{$version->version}.pdf");
    }
}
