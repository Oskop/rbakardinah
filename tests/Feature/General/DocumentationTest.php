<?php

namespace Tests\Feature\General;

use App\Models\DocumentationArticle;
use App\Models\DocumentationVersion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentationTest extends TestCase
{
    use RefreshDatabase;

    private User $operator;
    private User $supervisor;
    private User $admin;
    private DocumentationVersion $htmlVersion;
    private DocumentationVersion $pdfVersion;
    private DocumentationArticle $article;

    protected function setUp(): void
    {
        parent::setUp();

        $this->operator = User::factory()->create(['role' => 'Operator']);
        $this->supervisor = User::factory()->create(['role' => 'Supervisor']);
        $this->admin = User::factory()->create(['role' => 'Administrator']);

        // Seed an active HTML version
        $this->htmlVersion = DocumentationVersion::create([
            'type' => 'html',
            'version' => 'v1.0.0',
            'title' => 'Buku Panduan Sistem RBA RSUD Kardinah',
            'release_notes' => 'Rilis perdana',
            'released_at' => now(),
            'is_active' => true,
            'created_by' => $this->admin->id,
        ]);

        $this->article = DocumentationArticle::create([
            'documentation_version_id' => $this->htmlVersion->id,
            'category' => '🚀 Pengenalan',
            'title' => 'Tentang Aplikasi RBA',
            'slug' => 'tentang-aplikasi-rba',
            'icon' => '🏢',
            'order' => 1,
            'content' => '<h2>Gambaran Umum</h2><p>Selamat datang di sistem RBA RSUD Kardinah.</p>',
        ]);

        // Seed an active PDF version
        Storage::fake('public');
        $pdfFile = UploadedFile::fake()->create('manual_book.pdf', 500, 'application/pdf');
        $pdfPath = $pdfFile->store('documents', 'public');

        $this->pdfVersion = DocumentationVersion::create([
            'type' => 'pdf',
            'version' => 'v1.0.0',
            'title' => 'Manual Book PDF Resmi',
            'file_path' => $pdfPath,
            'file_size' => 500 * 1024,
            'release_notes' => 'Rilis PDF cetak',
            'released_at' => now(),
            'is_active' => true,
            'created_by' => $this->admin->id,
        ]);
    }

    public function test_unauthenticated_guest_can_access_documentation_reader()
    {
        $response = $this->get(route('documentation.index'));
        $response->assertStatus(200);
        $response->assertSee('Dokumentasi & Buku Panduan Sistem');
        $response->assertSee('Tentang Aplikasi RBA');
        $response->assertSee('Selamat datang di sistem RBA RSUD Kardinah.');
        $response->assertSee('Masuk ke Akun');
        $response->assertDontSee('Kelola Dokumentasi');
    }

    public function test_all_authenticated_roles_can_access_documentation_reader()
    {
        // 1. Operator
        $resOperator = $this->actingAs($this->operator)->get(route('documentation.index'));
        $resOperator->assertStatus(200);
        $resOperator->assertSee('Dokumentasi & Buku Panduan Sistem');
        $resOperator->assertSee('Tentang Aplikasi RBA');
        $resOperator->assertDontSee('Kelola Dokumentasi');

        // 2. Supervisor
        $resSupervisor = $this->actingAs($this->supervisor)->get(route('documentation.index'));
        $resSupervisor->assertStatus(200);
        $resSupervisor->assertSee('Tentang Aplikasi RBA');
        $resSupervisor->assertDontSee('Kelola Dokumentasi');

        // 3. Admin
        $resAdmin = $this->actingAs($this->admin)->get(route('documentation.index'));
        $resAdmin->assertStatus(200);
        $resAdmin->assertSee('Kelola Dokumentasi');
    }

    public function test_guest_can_switch_article_and_view_specific_slug()
    {
        $article2 = DocumentationArticle::create([
            'documentation_version_id' => $this->htmlVersion->id,
            'category' => '📝 Panduan Operator',
            'title' => 'Input Usulan Belanja',
            'slug' => 'input-usulan-belanja',
            'icon' => '✍️',
            'order' => 2,
            'content' => '<h2>Formulir Usulan</h2><p>Tata cara mengisi rincian biaya.</p>',
        ]);

        $response = $this->get(route('documentation.index', [
            'version' => 'v1.0.0',
            'article' => 'input-usulan-belanja',
        ]));

        $response->assertStatus(200);
        $response->assertSee('Input Usulan Belanja');
        $response->assertSee('Tata cara mengisi rincian biaya.');
    }

    public function test_guest_can_access_pdf_manual_book_tab_and_download_pdf()
    {
        $response = $this->get(route('documentation.index', ['tab' => 'pdf']));
        $response->assertStatus(200);
        $response->assertSee('Manual Book PDF Resmi');
        $response->assertSee('Unduh Berkas PDF');

        // Test download without authentication
        $downloadRes = $this->get(route('documentation.pdf.download', $this->pdfVersion));
        $downloadRes->assertStatus(200);
        $downloadRes->assertHeader('content-disposition', 'attachment; filename=Manual_Book_RBA_v1.0.0.pdf');
    }

    public function test_guest_can_preview_pdf_inline()
    {
        $previewRes = $this->get(route('documentation.pdf.preview', $this->pdfVersion));
        $previewRes->assertStatus(200);
        $previewRes->assertHeader('content-type', 'application/pdf');
    }
}
