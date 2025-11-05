<?php

namespace Tests\Feature\Employee;

use App\Models\EAdmin;
use App\Models\EDocument;
use App\Models\EDocumentSigner;
use App\Models\EEmployee;
use App\Models\EEmployeeMeta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

/**
 * E-Document Signing Feature Tests
 *
 * Tests for Employee Document Signing functionality
 * Endpoint: /api/v1/employee/documents/*
 */
class DocumentSigningTest extends TestCase
{
    use WithFaker;

    protected $employee;
    protected $admin;
    protected $employeeMeta;
    protected $token;

    /**
     * Setup test environment
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create test employee and admin
        $this->employee = EEmployee::where('id', 70)->first(); // jora_kuvandikov
        $this->admin = EAdmin::where('login', 'jora_kuvandikov')->first();
        $this->employeeMeta = EEmployeeMeta::where('_employee', $this->employee->id)
            ->where('active', true)
            ->first();

        // Generate JWT token
        $this->token = JWTAuth::fromUser($this->admin);
    }

    /**
     * Test: Get documents to sign - success
     */
    public function test_get_documents_to_sign_success()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->getJson('/api/v1/employee/documents/sign');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'items' => [
                        '*' => [
                            'id',
                            'document_hash',
                            'document_title',
                            'document_type',
                            'status',
                            'type',
                            'priority',
                            'employee_name',
                            'employee_position',
                            'created_at',
                        ]
                    ],
                    'pagination' => [
                        'current_page',
                        'last_page',
                        'per_page',
                        'total',
                    ]
                ]
            ])
            ->assertJson([
                'success' => true,
            ]);
    }

    /**
     * Test: Get documents with search filter
     */
    public function test_get_documents_with_search_filter()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->getJson('/api/v1/employee/documents/sign?search=TEST');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $items = $response->json('data.items');
        foreach ($items as $item) {
            $this->assertStringContainsStringIgnoringCase(
                'TEST',
                $item['document']['document_title'] ?? $item['employee_name'] ?? ''
            );
        }
    }

    /**
     * Test: Get documents with status filter
     */
    public function test_get_documents_with_status_filter()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->getJson('/api/v1/employee/documents/sign?status=pending');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $items = $response->json('data.items');
        foreach ($items as $item) {
            $this->assertEquals('pending', $item['status']);
        }
    }

    /**
     * Test: Get documents with type filter
     */
    public function test_get_documents_with_type_filter()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->getJson('/api/v1/employee/documents/sign?type=approver');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $items = $response->json('data.items');
        foreach ($items as $item) {
            $this->assertEquals('approver', $item['type']);
        }
    }

    /**
     * Test: Get documents with pagination
     */
    public function test_get_documents_with_pagination()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->getJson('/api/v1/employee/documents/sign?per_page=2');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $pagination = $response->json('data.pagination');
        $this->assertEquals(2, $pagination['per_page']);
    }

    /**
     * Test: View specific document by hash
     */
    public function test_view_document_by_hash()
    {
        // Get a test document
        $document = EDocument::where('document_title', 'like', 'TEST:%')->first();

        if (!$document) {
            $this->markTestSkipped('No test documents available');
        }

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->getJson("/api/v1/employee/documents/{$document->hash}/view");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'hash',
                    'document_title',
                    'document_type',
                    'status',
                    'provider',
                    'signers' => [
                        '*' => [
                            'id',
                            'employee_name',
                            'employee_position',
                            'type',
                            'priority',
                            'status',
                        ]
                    ],
                    'created_at',
                ]
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'hash' => $document->hash,
                ]
            ]);
    }

    /**
     * Test: Get document status
     */
    public function test_get_document_status()
    {
        $document = EDocument::where('document_title', 'like', 'TEST:%')->first();

        if (!$document) {
            $this->markTestSkipped('No test documents available');
        }

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->getJson("/api/v1/employee/documents/{$document->hash}/status");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'status',
                    'can_sign',
                    'already_signed',
                    'provider',
                    'signed_count',
                    'total_signers',
                ]
            ])
            ->assertJson(['success' => true]);
    }

    /**
     * Test: Sign document - local provider success
     */
    public function test_sign_document_local_provider_success()
    {
        // Find a pending local document for this employee
        $signer = EDocumentSigner::whereHas('document', function($q) {
            $q->where('provider', EDocument::PROVIDER_LOCAL)
              ->where('status', EDocument::STATUS_PENDING);
        })
        ->whereHas('employeeMeta', function($q) {
            $q->where('_employee', $this->employee->id);
        })
        ->where('status', EDocumentSigner::STATUS_PENDING)
        ->first();

        if (!$signer) {
            $this->markTestSkipped('No signable documents available');
        }

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->postJson("/api/v1/employee/documents/{$signer->document->hash}/sign");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Hujjat muvaffaqqiyatli imzolandi',
            ]);

        // Verify in database
        $this->assertDatabaseHas('e_document_signer', [
            'id' => $signer->id,
            'status' => EDocumentSigner::STATUS_SIGNED,
        ]);
    }

    /**
     * Test: Sign document - webimzo provider should fail
     */
    public function test_sign_document_webimzo_provider_fails()
    {
        $document = EDocument::where('provider', EDocument::PROVIDER_WEBIMZO)
            ->where('status', EDocument::STATUS_PENDING)
            ->first();

        if (!$document) {
            $this->markTestSkipped('No WebImzo documents available');
        }

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->postJson("/api/v1/employee/documents/{$document->hash}/sign");

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
            ]);

        $this->assertStringContainsString('WebImzo', $response->json('message'));
    }

    /**
     * Test: Sign document - already signed should fail
     */
    public function test_sign_document_already_signed_fails()
    {
        $signer = EDocumentSigner::whereHas('employeeMeta', function($q) {
            $q->where('_employee', $this->employee->id);
        })
        ->where('status', EDocumentSigner::STATUS_SIGNED)
        ->first();

        if (!$signer) {
            $this->markTestSkipped('No signed documents available');
        }

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->postJson("/api/v1/employee/documents/{$signer->document->hash}/sign");

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
            ]);
    }

    /**
     * Test: Access without authentication
     */
    public function test_access_without_authentication()
    {
        $response = $this->getJson('/api/v1/employee/documents/sign');

        $response->assertStatus(401);
    }

    /**
     * Test: Document hash accessor works
     */
    public function test_document_hash_accessor()
    {
        $signer = EDocumentSigner::with('document')->first();

        if (!$signer) {
            $this->markTestSkipped('No signers available');
        }

        $this->assertNotNull($signer->document_hash);
        $this->assertEquals($signer->document->hash, $signer->document_hash);
    }

    /**
     * Test: Multiple filters combined
     */
    public function test_multiple_filters_combined()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->getJson('/api/v1/employee/documents/sign?status=pending&type=approver&per_page=5');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $items = $response->json('data.items');
        foreach ($items as $item) {
            $this->assertEquals('pending', $item['status']);
            $this->assertEquals('approver', $item['type']);
        }

        $pagination = $response->json('data.pagination');
        $this->assertEquals(5, $pagination['per_page']);
    }
}
