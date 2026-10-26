<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('multi_number_broadcasts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title', 160);
            $table->text('message_template');
            $table->json('selected_account_ids')->nullable(); // pool of 1 to 10 WhatsApp accounts
            $table->string('target_audience', 60)->default('all_contacts'); // 'all_contacts', 'scraped_leads'
            $table->unsignedInteger('total_recipients')->default(0);
            $table->unsignedInteger('sent_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->unsignedSmallInteger('anti_ban_delay_seconds')->default(25);
            $table->boolean('enable_spintax')->default(true);
            $table->boolean('enable_ai_customization')->default(false);
            $table->string('status', 30)->default('draft'); // draft, running, completed, paused, failed
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });

        Schema::create('broadcast_dispatches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('broadcast_id')->constrained('multi_number_broadcasts')->cascadeOnDelete();
            $table->foreignId('whatsapp_account_id')->nullable()->constrained('whatsapp_accounts')->nullOnDelete();
            $table->string('recipient_phone', 40);
            $table->string('recipient_name', 120)->nullable();
            $table->text('final_message');
            $table->string('status', 30)->default('queued'); // queued, sent, failed
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['broadcast_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('broadcast_dispatches');
        Schema::dropIfExists('multi_number_broadcasts');
    }
};
