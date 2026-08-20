<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Persist campaign attachment file paths.
 *
 * Previously campaign uploads were stored to disk and turned into a text
 * `attachment_context` for the AI, but the actual file paths were never saved,
 * so the send job (ScheduleMessageSendJob) had nothing to attach and documents
 * were silently dropped. This column stores the uploaded files so they can be
 * delivered alongside the message.
 *
 * Shape: [{ "path": "attachments/2026/08/x.pdf", "original_name": "intro.pdf",
 *           "mime_type": "application/pdf", "size": 12345 }, ...]
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('campaigns') || Schema::hasColumn('campaigns', 'attachments')) {
            return;
        }

        Schema::table('campaigns', function (Blueprint $table) {
            $table->json('attachments')->nullable()->after('has_attachments');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('campaigns') && Schema::hasColumn('campaigns', 'attachments')) {
            Schema::table('campaigns', function (Blueprint $table) {
                $table->dropColumn('attachments');
            });
        }
    }
};
