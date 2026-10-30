<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\ProductAttachment;
use App\Services\RagDocumentService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Make the AI read the documents that were uploaded but never read.
 *
 * Until now every document uploaded as "AI Training Documents" was stored as "completed" with no text read from it
 * (the page told the server not to process it), and the separate "Product Documentation" file was only ever kept on
 * the product. This reads both: legacy product files are added to the product's document list, and every document
 * with no searchable text is read now.
 *
 *   php artisan rag:backfill --dry-run
 *   php artisan rag:backfill
 *   php artisan rag:backfill --product=10000086
 */
class BackfillRagDocuments extends Command
{
    protected $signature = 'rag:backfill
                            {--product= : Only this product id}
                            {--dry-run : List what would be done without reading anything}
                            {--no-legacy : Do not add the old single "Product Documentation" files to the document list}';

    protected $description = 'Read the uploaded product documents the AI has never had access to';

    private const MIME_BY_EXTENSION = [
        'pdf'  => 'application/pdf',
        'txt'  => 'text/plain',
        'doc'  => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ];

    public function handle(RagDocumentService $rag): int
    {
        $productId = $this->option('product');
        $dry = (bool) $this->option('dry-run');

        if (!$this->option('no-legacy')) {
            $this->importLegacyFiles($productId, $dry);
        }

        $attachments = ProductAttachment::query()
            ->when($productId, fn ($q) => $q->where('product_id', $productId))
            ->where(fn ($q) => $q->whereNull('vector_count')->orWhere('vector_count', 0))
            ->orderBy('id')
            ->get();

        $this->info("Documents with no searchable text: {$attachments->count()}");

        $read = 0;
        $failed = 0;

        foreach ($attachments as $attachment) {
            $label = "#{$attachment->id} {$attachment->original_filename} (product {$attachment->product_id})";

            if (!$attachment->supportsRAG()) {
                $this->line("  skip   $label - {$attachment->mime_type} cannot be read");
                continue;
            }

            if ($dry) {
                $this->line("  would read $label");
                continue;
            }

            try {
                $result = $rag->processDocument($attachment);
                $read++;
                $this->line("  read   $label - {$result['vectors_created']} passages");
            } catch (Throwable $e) {
                $failed++;
                $this->error("  FAILED $label - " . $e->getMessage());
            }
        }

        if (!$dry) {
            $this->newLine();
            $this->info("Read: $read   Failed: $failed");
        }

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function importLegacyFiles(?string $productId, bool $dry): void
    {
        $products = Product::query()
            ->when($productId, fn ($q) => $q->where('id', $productId))
            ->whereNotNull('attachment_path')
            ->where('attachment_path', '!=', '')
            ->get();

        $added = 0;

        foreach ($products as $product) {
            $path = $product->attachment_path;

            if (ProductAttachment::where('product_id', $product->id)->where('file_path', $path)->exists()) {
                continue;
            }

            if (!Storage::disk('public')->exists($path)) {
                $this->warn("  legacy file missing for product {$product->id}: $path");
                continue;
            }

            $name = $product->attachment_original_name ?: basename($path);
            $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));

            if ($dry) {
                $this->line("  would add legacy file to the document list: $name (product {$product->id})");
                $added++;
                continue;
            }

            ProductAttachment::create([
                'product_id'        => $product->id,
                'attachment_type'   => 'documentation',
                'file_path'         => $path,
                'original_filename' => $name,
                'mime_type'         => self::MIME_BY_EXTENSION[$extension] ?? (Storage::disk('public')->mimeType($path) ?: 'application/octet-stream'),
                'file_size'         => Storage::disk('public')->size($path),
                'title'             => $name,
                'is_public'         => false,
                'display_order'     => (int) ProductAttachment::where('product_id', $product->id)->max('display_order') + 1,
                'is_processed'      => false,
                'processing_status' => 'pending',
                'vector_count'      => 0,
            ]);
            $added++;
        }

        $this->info("Legacy \"Product Documentation\" files added to document lists: $added");
    }
}
