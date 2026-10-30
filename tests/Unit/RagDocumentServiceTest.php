<?php

namespace Tests\Unit;

use App\Services\RagDocumentService;
use Exception;
use ReflectionClass;
use ReflectionMethod;
use Tests\TestCase;
use ZipArchive;

/**
 * Reading uploaded documents into passages the AI can search.
 */
class RagDocumentServiceTest extends TestCase
{
    private function service(): RagDocumentService
    {
        return (new ReflectionClass(RagDocumentService::class))->newInstanceWithoutConstructor();
    }

    private function callService(string $method, mixed ...$args): mixed
    {
        $m = new ReflectionMethod(RagDocumentService::class, $method);
        $m->setAccessible(true);

        return $m->invoke($this->service(), ...$args);
    }

    public function test_a_long_paragraph_without_breaks_is_cut_into_chunk_sized_pieces(): void
    {
        $text = trim(str_repeat('Lineshop helps shops sell more every day. ', 120)); // ~4,800 characters, one paragraph

        $pieces = $this->callService('splitLongText', $text);

        $this->assertGreaterThan(3, count($pieces));
        foreach ($pieces as $piece) {
            $this->assertLessThanOrEqual(1000, strlen($piece));
        }
        // Nothing is lost by the cutting.
        $this->assertSame(preg_replace('/\s+/', '', $text), preg_replace('/\s+/', '', implode(' ', $pieces)));
    }

    public function test_a_word_with_no_spaces_still_gets_cut(): void
    {
        $pieces = $this->callService('splitLongText', str_repeat('x', 2500));

        $this->assertCount(3, $pieces);
    }

    public function test_a_short_paragraph_is_left_alone(): void
    {
        $this->assertSame(['Short answer.'], $this->callService('splitLongText', 'Short answer.'));
    }

    public function test_word_documents_keep_their_paragraphs(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'rag') . '.docx';
        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::CREATE);
        $zip->addFromString('word/document.xml',
            '<w:document><w:body><w:p><w:r><w:t>First paragraph &amp; more.</w:t></w:r></w:p>'
            . '<w:p><w:r><w:t>Second paragraph.</w:t></w:r></w:p></w:body></w:document>');
        $zip->close();

        $text = $this->callService('extractFromWord', $path);
        unlink($path);

        $this->assertStringContainsString("First paragraph & more.\n\nSecond paragraph.", $text);
    }

    public function test_a_pdf_without_the_pdftotext_tool_fails_with_a_clear_message(): void
    {
        $tool = $this->callService('pdfToTextBinary');

        if ($tool !== null) {
            $this->markTestSkipped('pdftotext is installed here.');
        }

        try {
            $this->callService('extractFromPdf', __FILE__);
            $this->fail('Expected an exception');
        } catch (Exception $e) {
            $this->assertStringContainsString('pdftotext', $e->getMessage());
            $this->assertStringContainsString('poppler-utils', $e->getMessage());
        }
    }
}
