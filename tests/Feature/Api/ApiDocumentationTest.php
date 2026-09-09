<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiDocumentationTest extends TestCase
{
    use RefreshDatabase;

    public function test_openapi_json_specification_is_accessible_and_valid(): void
    {
        $response = $this->getJson('/api/v1/openapi.json');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'openapi',
            'info' => [
                'title',
                'description',
                'version',
                'contact' => ['name', 'url'],
            ],
            'servers',
            'tags',
            'paths' => [
                '/ping' => ['get'],
                '/pagu' => [
                    'get' => [
                        'tags',
                        'summary',
                        'description',
                        'security',
                        'parameters',
                        'responses',
                    ],
                ],
            ],
            'components' => [
                'securitySchemes' => [
                    'BearerAuth',
                    'ApiKeyAuth',
                ],
            ],
        ]);

        $json = $response->json();
        $this->assertEquals('3.0.3', $json['openapi']);
        $this->assertStringContainsString('SIPAKAR REST API', $json['info']['title']);

        // Pastikan parameter query penting ada
        $paramNames = collect($json['paths']['/pagu']['get']['parameters'])->pluck('name')->toArray();
        $this->assertContains('year', $paramNames);
        $this->assertContains('period', $paramNames);
        $this->assertContains('status', $paramNames);
        $this->assertContains('kode_kelompok_belanja', $paramNames);
        $this->assertContains('kode_rekening', $paramNames);
        $this->assertContains('per_page', $paramNames);
    }

    public function test_api_documentation_page_renders_swagger_by_default(): void
    {
        $response = $this->get(route('api.documentation'));

        $response->assertStatus(200);
        $response->assertSee('swagger-ui', false);
        $response->assertSee('SwaggerUIBundle', false);
        $response->assertSee('REST API SIPAKAR', false);
        $response->assertSee(route('api.openapi.json'), false);
    }

    public function test_api_documentation_page_renders_redoc_when_requested(): void
    {
        $response = $this->get(route('api.documentation', ['view' => 'redoc']));

        $response->assertStatus(200);
        $response->assertSee('<redoc', false);
        $response->assertSee('redoc.standalone.js', false);
        $response->assertSee(route('api.openapi.json'), false);
    }

    public function test_documentation_api_alias_redirects_to_api_documentation(): void
    {
        $response = $this->get('/documentation/api');

        $response->assertRedirect(route('api.documentation'));
    }

    public function test_main_documentation_page_contains_link_to_api_docs(): void
    {
        $response = $this->get('/documentation');

        $response->assertStatus(200);
        $response->assertSee(route('api.documentation'), false);
        $response->assertSee('REST API', false);
    }
}
