<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_accounts', function (Blueprint $table) {
            $table->string('account_name', 120)->nullable()->after('tenant_id');
            $table->string('phone_number', 40)->nullable()->after('account_name');
            $table->string('connection_type', 30)->default('cloud_api')->after('status'); // 'cloud_api' ou 'qr_session'
            $table->unsignedInteger('daily_limit')->default(250)->after('connection_type');
            $table->unsignedInteger('sent_today')->default(0)->after('daily_limit');
            $table->timestamp('last_sent_at')->nullable()->after('sent_today');
            $table->string('quality_rating', 20)->default('GREEN')->after('last_sent_at'); // GREEN, YELLOW, RED
            $table->boolean('is_active')->default(true)->after('quality_rating');
            $table->unsignedTinyInteger('slot_number')->default(1)->after('is_active');
            $table->string('session_status', 30)->default('connected')->after('slot_number');
        });

        Schema::table('tenants', function (Blueprint $table) {
            $table->string('subscription_plan', 40)->default('pro')->after('status');
            $table->unsignedSmallInteger('max_whatsapp_numbers')->default(10)->after('subscription_plan');
            $table->unsignedInteger('monthly_scraping_quota')->default(1000)->after('max_whatsapp_numbers');
            $table->unsignedInteger('scraping_used_this_month')->default(0)->after('monthly_scraping_quota');
            $table->unsignedInteger('daily_broadcast_quota')->default(2500)->after('scraping_used_this_month');
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_accounts', function (Blueprint $table) {
            $table->dropColumn([
                'account_name',
                'phone_number',
                'connection_type',
                'daily_limit',
                'sent_today',
                'last_sent_at',
                'quality_rating',
                'is_active',
                'slot_number',
                'session_status'
            ]);
        });

        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn([
                'subscription_plan',
                'max_whatsapp_numbers',
                'monthly_scraping_quota',
                'scraping_used_this_month',
                'daily_broadcast_quota'
            ]);
        });
    }
};
