<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_inbox_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('whatsapp_account_id')->nullable()->constrained('whatsapp_accounts')->nullOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->string('phone_number', 40);
            $table->string('contact_name', 120)->nullable();
            $table->enum('direction', ['inbound', 'outbound'])->default('inbound');
            $table->text('message');
            $table->string('status', 30)->default('received'); // received, sent, delivered, read
            $table->boolean('is_read')->default(false);
            $table->timestamps();

            $table->index(['tenant_id', 'phone_number']);
            $table->index(['tenant_id', 'is_read']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_inbox_messages');
    }
};
