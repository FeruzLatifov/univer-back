<?php

namespace Tests\Unit\Services\Student;

use App\Models\EDocument;
use App\Models\EStudent;
use App\Services\Student\DocumentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Student DocumentService Unit Tests
 * 
 * Tests the Student Document Service business logic
 * 
 * @group unit
 * @group student
 * @group documents
 */
class DocumentServiceTest extends TestCase
{
    use RefreshDatabase;

    protected DocumentService $documentService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->documentService = new DocumentService();
        Storage::fake('local');
    }

    /**
     * Test getting student documents
     */
    public function test_get_student_documents(): void
    {
        $student = EStudent::factory()->create();
        
        // Create some documents
        EDocument::factory()->count(3)->create([
            '_student' => $student->id,
        ]);

        $result = $this->documentService->getDocuments($student->id);

        $this->assertIsArray($result);
        $this->assertCount(3, $result);
    }

    /**
     * Test document upload
     */
    public function test_upload_document(): void
    {
        $student = EStudent::factory()->create();
        $file = UploadedFile::fake()->create('document.pdf', 1024);

        $documentData = [
            'title' => 'Test Document',
            'type' => 'certificate',
            'file' => $file,
        ];

        $result = $this->documentService->uploadDocument($student->id, $documentData);

        $this->assertIsArray($result);
        $this->assertEquals('Test Document', $result['title']);
        $this->assertEquals('certificate', $result['type']);
        Storage::disk('local')->assertExists($result['file_path']);
    }

    /**
     * Test document download
     */
    public function test_download_document(): void
    {
        $student = EStudent::factory()->create();
        
        $document = EDocument::factory()->create([
            '_student' => $student->id,
            'file_path' => 'documents/test.pdf',
        ]);

        Storage::disk('local')->put('documents/test.pdf', 'test content');

        $result = $this->documentService->downloadDocument($student->id, $document->id);

        $this->assertNotNull($result);
    }

    /**
     * Test document deletion
     */
    public function test_delete_document(): void
    {
        $student = EStudent::factory()->create();
        
        $document = EDocument::factory()->create([
            '_student' => $student->id,
            'file_path' => 'documents/test.pdf',
        ]);

        Storage::disk('local')->put('documents/test.pdf', 'test content');

        $result = $this->documentService->deleteDocument($student->id, $document->id);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('e_documents', ['id' => $document->id]);
        Storage::disk('local')->assertMissing('documents/test.pdf');
    }

    /**
     * Test cannot access other student's documents
     */
    public function test_cannot_access_other_students_documents(): void
    {
        $student1 = EStudent::factory()->create();
        $student2 = EStudent::factory()->create();
        
        $document = EDocument::factory()->create([
            '_student' => $student2->id,
        ]);

        $this->expectException(\Exception::class);

        $this->documentService->downloadDocument($student1->id, $document->id);
    }

    /**
     * Test document types validation
     */
    public function test_validates_document_types(): void
    {
        $student = EStudent::factory()->create();
        $file = UploadedFile::fake()->create('document.pdf', 1024);

        $validTypes = ['certificate', 'transcript', 'id_card', 'application'];

        foreach ($validTypes as $type) {
            $documentData = [
                'title' => 'Test Document',
                'type' => $type,
                'file' => $file,
            ];

            $result = $this->documentService->uploadDocument($student->id, $documentData);
            $this->assertEquals($type, $result['type']);
        }
    }

    /**
     * Test file size validation
     */
    public function test_validates_file_size(): void
    {
        $student = EStudent::factory()->create();
        
        // File larger than allowed limit (20MB, exceeds typical 10MB limit)
        $maxFileSizeMb = 20; // Above typical limit
        $largeFile = UploadedFile::fake()->create('large.pdf', $maxFileSizeMb * 1024);

        $documentData = [
            'title' => 'Large Document',
            'type' => 'certificate',
            'file' => $largeFile,
        ];

        $this->expectException(\Exception::class);

        $this->documentService->uploadDocument($student->id, $documentData);
    }

    /**
     * Test supported file types
     */
    public function test_validates_supported_file_types(): void
    {
        $student = EStudent::factory()->create();

        $supportedTypes = ['pdf', 'doc', 'docx', 'jpg', 'png'];

        foreach ($supportedTypes as $type) {
            $file = UploadedFile::fake()->create("document.{$type}", 1024);

            $documentData = [
                'title' => "Test {$type} Document",
                'type' => 'certificate',
                'file' => $file,
            ];

            $result = $this->documentService->uploadDocument($student->id, $documentData);
            $this->assertIsArray($result);
        }
    }

    /**
     * Test document listing with filters
     */
    public function test_filter_documents_by_type(): void
    {
        $student = EStudent::factory()->create();
        
        EDocument::factory()->create([
            '_student' => $student->id,
            'type' => 'certificate',
        ]);
        
        EDocument::factory()->create([
            '_student' => $student->id,
            'type' => 'transcript',
        ]);

        $result = $this->documentService->getDocuments($student->id, ['type' => 'certificate']);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('certificate', $result[0]['type']);
    }
}
