<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DocumentationArticle;
use App\Models\DocumentationVersion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class DocumentationManagementController extends Controller
{
    public function index()
    {
        $htmlVersions = DocumentationVersion::html()
            ->withCount('articles')
            ->with(['creator', 'updater'])
            ->orderByDesc('released_at')
            ->orderByDesc('id')
            ->get();

        $pdfVersions = DocumentationVersion::pdf()
            ->with(['creator', 'updater'])
            ->orderByDesc('released_at')
            ->orderByDesc('id')
            ->get();

        $stats = [
            'total_html_versions' => $htmlVersions->count(),
            'active_html_version' => $htmlVersions->firstWhere('is_active', true)?->version ?? '-',
            'total_html_articles' => DocumentationArticle::count(),
            'total_pdf_versions' => $pdfVersions->count(),
            'active_pdf_version' => $pdfVersions->firstWhere('is_active', true)?->version ?? '-',
        ];

        return view('admin.documentation.index', compact('htmlVersions', 'pdfVersions', 'stats'));
    }

    public function createVersion()
    {
        return view('admin.documentation.version-form', [
            'version' => new DocumentationVersion(),
            'isEdit' => false,
        ]);
    }

    public function storeVersion(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:html,pdf',
            'version' => 'required|string|max:50',
            'title' => 'required|string|max:255',
            'release_notes' => 'nullable|string',
            'released_at' => 'required|date',
            'is_active' => 'boolean',
            'pdf_file' => 'nullable|file|mimes:pdf|max:20480', // 20MB
        ]);

        $filePath = null;
        $fileSize = null;

        if ($validated['type'] === 'pdf' && $request->hasFile('pdf_file')) {
            $path = $request->file('pdf_file')->store('documents', 'public');
            $filePath = $path;
            $fileSize = $request->file('pdf_file')->getSize();
        }

        $isActive = $request->boolean('is_active');
        $shouldClone = $validated['type'] === 'html' && $request->boolean('clone_from_active');
        $sourceActiveVersion = $shouldClone ? DocumentationVersion::html()->where('is_active', true)->first() : null;

        DB::transaction(function () use ($validated, $filePath, $fileSize, $isActive, $shouldClone, $sourceActiveVersion) {
            if ($isActive) {
                DocumentationVersion::where('type', $validated['type'])->update(['is_active' => false]);
            }

            $version = DocumentationVersion::create([
                'type' => $validated['type'],
                'version' => $validated['version'],
                'title' => $validated['title'],
                'file_path' => $filePath,
                'file_size' => $fileSize,
                'release_notes' => $validated['release_notes'] ?? null,
                'released_at' => $validated['released_at'],
                'is_active' => $isActive,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            // If creating an HTML version and requested to clone articles from active version
            if ($shouldClone && $sourceActiveVersion) {
                foreach ($sourceActiveVersion->articles as $art) {
                    DocumentationArticle::create([
                        'documentation_version_id' => $version->id,
                        'category' => $art->category,
                        'title' => $art->title,
                        'slug' => $art->slug,
                        'icon' => $art->icon,
                        'order' => $art->order,
                        'content' => $art->content,
                    ]);
                }
            }
        });

        return redirect()->route('admin.documentation.index')->with('success', "Versi dokumentasi {$validated['version']} berhasil dibuat.");
    }

    public function editVersion(DocumentationVersion $version)
    {
        return view('admin.documentation.version-form', [
            'version' => $version,
            'isEdit' => true,
        ]);
    }

    public function updateVersion(Request $request, DocumentationVersion $version)
    {
        $validated = $request->validate([
            'version' => 'required|string|max:50',
            'title' => 'required|string|max:255',
            'release_notes' => 'nullable|string',
            'released_at' => 'required|date',
            'is_active' => 'boolean',
            'pdf_file' => 'nullable|file|mimes:pdf|max:20480',
        ]);

        $filePath = $version->file_path;
        $fileSize = $version->file_size;

        if ($version->type === 'pdf' && $request->hasFile('pdf_file')) {
            if ($version->file_path && Storage::disk('public')->exists($version->file_path)) {
                Storage::disk('public')->delete($version->file_path);
            }
            $filePath = $request->file('pdf_file')->store('documents', 'public');
            $fileSize = $request->file('pdf_file')->getSize();
        }

        $isActive = $request->boolean('is_active');

        DB::transaction(function () use ($version, $validated, $filePath, $fileSize, $isActive) {
            if ($isActive && !$version->is_active) {
                DocumentationVersion::where('type', $version->type)->where('id', '!=', $version->id)->update(['is_active' => false]);
            }

            $version->update([
                'version' => $validated['version'],
                'title' => $validated['title'],
                'file_path' => $filePath,
                'file_size' => $fileSize,
                'release_notes' => $validated['release_notes'] ?? null,
                'released_at' => $validated['released_at'],
                'is_active' => $isActive,
                'updated_by' => Auth::id(),
            ]);
        });

        return redirect()->route('admin.documentation.index')->with('success', "Versi dokumentasi {$version->version} berhasil diperbarui.");
    }

    public function destroyVersion(DocumentationVersion $version)
    {
        $versionNumber = $version->version;

        if ($version->file_path && Storage::disk('public')->exists($version->file_path)) {
            Storage::disk('public')->delete($version->file_path);
        }

        $version->delete();

        return redirect()->route('admin.documentation.index')->with('success', "Versi dokumentasi {$versionNumber} berhasil dihapus.");
    }

    public function setActive(DocumentationVersion $version)
    {
        DB::transaction(function () use ($version) {
            DocumentationVersion::where('type', $version->type)->update(['is_active' => false]);
            $version->update([
                'is_active' => true,
                'updated_by' => Auth::id(),
            ]);
        });

        return back()->with('success', "Versi {$version->version} ({$version->type}) telah diaktifkan sebagai versi utama.");
    }

    // Article Management for HTML Versions
    public function articles(DocumentationVersion $version)
    {
        if ($version->type !== 'html') {
            abort(400, 'Hanya versi bertipe HTML yang memiliki artikel panduan.');
        }

        $articles = $version->articles()->get()->groupBy('category');

        return view('admin.documentation.articles', compact('version', 'articles'));
    }

    public function createArticle(DocumentationVersion $version)
    {
        if ($version->type !== 'html') {
            abort(400, 'Hanya versi bertipe HTML yang memiliki artikel panduan.');
        }

        $existingCategories = DocumentationArticle::distinct()->pluck('category');

        return view('admin.documentation.article-form', [
            'version' => $version,
            'article' => new DocumentationArticle(),
            'isEdit' => false,
            'existingCategories' => $existingCategories,
        ]);
    }

    public function storeArticle(Request $request, DocumentationVersion $version)
    {
        if ($version->type !== 'html') {
            abort(400);
        }

        $validated = $request->validate([
            'category' => 'required|string|max:100',
            'title' => 'required|string|max:255',
            'slug' => [
                'required',
                'string',
                'max:255',
                Rule::unique('documentation_articles')->where('documentation_version_id', $version->id),
            ],
            'icon' => 'nullable|string|max:20',
            'order' => 'required|integer',
            'content' => 'required|string',
        ]);

        DocumentationArticle::create([
            'documentation_version_id' => $version->id,
            'category' => $validated['category'],
            'title' => $validated['title'],
            'slug' => Str::slug($validated['slug']),
            'icon' => $validated['icon'] ?? '📄',
            'order' => $validated['order'],
            'content' => $validated['content'],
        ]);

        return redirect()->route('admin.documentation.articles.index', $version)
            ->with('success', "Artikel \"{$validated['title']}\" berhasil ditambahkan.");
    }

    public function editArticle(DocumentationArticle $article)
    {
        $version = $article->version;
        $existingCategories = DocumentationArticle::distinct()->pluck('category');

        return view('admin.documentation.article-form', [
            'version' => $version,
            'article' => $article,
            'isEdit' => true,
            'existingCategories' => $existingCategories,
        ]);
    }

    public function updateArticle(Request $request, DocumentationArticle $article)
    {
        $version = $article->version;

        $validated = $request->validate([
            'category' => 'required|string|max:100',
            'title' => 'required|string|max:255',
            'slug' => [
                'required',
                'string',
                'max:255',
                Rule::unique('documentation_articles')->where('documentation_version_id', $version->id)->ignore($article->id),
            ],
            'icon' => 'nullable|string|max:20',
            'order' => 'required|integer',
            'content' => 'required|string',
        ]);

        $article->update([
            'category' => $validated['category'],
            'title' => $validated['title'],
            'slug' => Str::slug($validated['slug']),
            'icon' => $validated['icon'] ?? '📄',
            'order' => $validated['order'],
            'content' => $validated['content'],
        ]);

        return redirect()->route('admin.documentation.articles.index', $version)
            ->with('success', "Artikel \"{$validated['title']}\" berhasil diperbarui.");
    }

    public function destroyArticle(DocumentationArticle $article)
    {
        $version = $article->version;
        $title = $article->title;
        $article->delete();

        return redirect()->route('admin.documentation.articles.index', $version)
            ->with('success', "Artikel \"{$title}\" berhasil dihapus.");
    }
}
