<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scraped_leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('search_query', 180);
            $table->string('business_name', 180);
            $table->string('contact_name', 120)->nullable();
            $table->string('phone', 40);
            $table->string('email', 160)->nullable();
            $table->string('website', 255)->nullable();
            $table->string('address', 255)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('country', 100)->nullable();
            $table->string('category', 100)->nullable();
            $table->text('detected_need')->nullable();
            $table->unsignedTinyInteger('qualification_score')->default(85); // 0-100
            $table->text('ai_pitch')->nullable();
            $table->string('status', 30)->default('new'); // new, imported, contacted
            $table->boolean('is_imported')->default(false);
            $table->foreignId('imported_contact_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->timestamps();

            $table->index(['tenant_id', 'is_imported']);
            $table->index(['tenant_id', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scraped_leads');
    }
};
