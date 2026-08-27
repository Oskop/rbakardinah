<?php

namespace Tests\Feature\Admin;

use App\Models\ActivityLog;
use App\Models\DocumentationArticle;
use App\Models\DocumentationVersion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentationManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $operator;
    private DocumentationVersion $version;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'Administrator']);
        $this->operator = User::factory()->create(['role' => 'Operator']);

        $this->version = DocumentationVersion::create([
            'type' => 'html',
            'version' => 'v1.0.0',
            'title' => 'Buku Panduan Awal',
            'release_notes' => 'Catatan awal',
            'released_at' => now(),
            'is_active' => true,
            'created_by' => $this->admin->id,
        ]);
    }

    public function test_admin_can_view_documentation_management_dashboard()
    {
        $response = $this->actingAs($this->admin)->get(route('admin.documentation.index'));
        $response->assertStatus(200);
        $response->assertSee('Kelola Dokumentasi & Manual Book');
        $response->assertSee('v1.0.0');
    }

    public function test_non_admin_cannot_access_documentation_management()
    {
        $response = $this->actingAs($this->operator)->get(route('admin.documentation.index'));
        $response->assertStatus(403);
    }

    public function test_admin_can_create_new_html_version_with_clone_articles()
    {
        DocumentationArticle::create([
            'documentation_version_id' => $this->version->id,
            'category' => '🚀 Pengenalan',
            'title' => 'Panduan Awal',
            'slug' => 'panduan-awal',
            'icon' => '📄',
            'order' => 1,
            'content' => '<h2>Konten v1.0.0</h2>',
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.documentation.versions.store'), [
            'type' => 'html',
            'version' => 'v1.1.0',
            'title' => 'Buku Panduan Versi 1.1',
            'released_at' => '2026-09-01',
            'release_notes' => 'Pembaruan fitur pagu',
            'is_active' => 1,
            'clone_from_active' => 1,
        ]);

        $response->assertRedirect(route('admin.documentation.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('documentation_versions', [
            'version' => 'v1.1.0',
            'type' => 'html',
            'is_active' => 1,
        ]);

        // Old version should now be inactive
        $this->assertFalse($this->version->fresh()->is_active);

        // Cloned article should exist in new version
        $newVersion = DocumentationVersion::where('version', 'v1.1.0')->first();
        $this->assertEquals(1, $newVersion->articles()->count());
        $this->assertEquals('Panduan Awal', $newVersion->articles()->first()->title);
    }

    public function test_admin_can_create_new_pdf_version_with_file_upload()
    {
        Storage::fake('public');
        $pdf = UploadedFile::fake()->create('manual_v1_1.pdf', 800, 'application/pdf');

        $response = $this->actingAs($this->admin)->post(route('admin.documentation.versions.store'), [
            'type' => 'pdf',
            'version' => 'v1.1.0',
            'title' => 'Manual Book PDF v1.1',
            'released_at' => '2026-09-01',
            'release_notes' => 'Edisi cetak v1.1',
            'is_active' => 1,
            'pdf_file' => $pdf,
        ]);

        $response->assertRedirect(route('admin.documentation.index'));
        $this->assertDatabaseHas('documentation_versions', [
            'version' => 'v1.1.0',
            'type' => 'pdf',
            'is_active' => 1,
        ]);

        $pdfVersion = DocumentationVersion::where('type', 'pdf')->where('version', 'v1.1.0')->first();
        $this->assertNotNull($pdfVersion->file_path);
        Storage::disk('public')->assertExists($pdfVersion->file_path);
    }

    public function test_admin_can_switch_active_version()
    {
        $v2 = DocumentationVersion::create([
            'type' => 'html',
            'version' => 'v2.0.0',
            'title' => 'Buku Panduan v2',
            'released_at' => now(),
            'is_active' => false,
            'created_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.documentation.versions.set-active', $v2));
        $response->assertSessionHas('success');

        $this->assertTrue($v2->fresh()->is_active);
        $this->assertFalse($this->version->fresh()->is_active);
    }

    public function test_admin_can_create_and_update_article()
    {
        // 1. Create Article
        $responseCreate = $this->actingAs($this->admin)->post(route('admin.documentation.articles.store', $this->version), [
            'category' => '📝 Panduan Operator',
            'title' => 'Input Latar Belakang',
            'slug' => 'input-latar-belakang',
            'icon' => '✍️',
            'order' => 1,
            'content' => '<h2>Latar Belakang</h2><p>Penjelasan pengisian latar belakang.</p>',
        ]);

        $responseCreate->assertRedirect(route('admin.documentation.articles.index', $this->version));
        $this->assertDatabaseHas('documentation_articles', [
            'documentation_version_id' => $this->version->id,
            'title' => 'Input Latar Belakang',
            'slug' => 'input-latar-belakang',
        ]);

        $article = DocumentationArticle::where('slug', 'input-latar-belakang')->first();

        // 2. Update Article
        $responseUpdate = $this->actingAs($this->admin)->put(route('admin.documentation.articles.update', $article), [
            'category' => '📝 Panduan Operator',
            'title' => 'Input Latar Belakang Unit',
            'slug' => 'input-latar-belakang-unit',
            'icon' => '📝',
            'order' => 2,
            'content' => '<h2>Latar Belakang Unit</h2><p>Penjelasan yang telah diperbarui.</p>',
        ]);

        $responseUpdate->assertRedirect(route('admin.documentation.articles.index', $this->version));
        $this->assertEquals('Input Latar Belakang Unit', $article->fresh()->title);
        $this->assertEquals('input-latar-belakang-unit', $article->fresh()->slug);
        $this->assertEquals(2, $article->fresh()->order);
    }

    public function test_admin_can_delete_article()
    {
        $article = DocumentationArticle::create([
            'documentation_version_id' => $this->version->id,
            'category' => 'Pengenalan',
            'title' => 'Artikel yang Akan Dihapus',
            'slug' => 'artikel-hapus',
            'order' => 1,
            'content' => '<p>Konten</p>',
        ]);

        $response = $this->actingAs($this->admin)->delete(route('admin.documentation.articles.destroy', $article));
        $response->assertRedirect(route('admin.documentation.articles.index', $this->version));

        $this->assertDatabaseMissing('documentation_articles', [
            'id' => $article->id,
        ]);
    }

    public function test_activity_logging_records_documentation_operations()
    {
        $initialLogCount = ActivityLog::count();

        $this->actingAs($this->admin)->post(route('admin.documentation.versions.store'), [
            'type' => 'html',
            'version' => 'v3.0.0',
            'title' => 'Versi Log Test',
            'released_at' => '2026-10-01',
            'is_active' => 0,
        ]);

        $this->assertGreaterThan($initialLogCount, ActivityLog::count());
        $latestLog = ActivityLog::latest()->first();
        $this->assertStringContainsString('DocumentationVersion', $latestLog->model_type);
        $this->assertEquals('created', $latestLog->action);
    }
}
