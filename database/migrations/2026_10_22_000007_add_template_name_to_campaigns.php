<?php
use Illuminate\Database\Migrations\Migration; use Illuminate\Database\Schema\Blueprint; use Illuminate\Support\Facades\Schema;
return new class extends Migration { public function up(): void { Schema::table('campaigns', function (Blueprint $table) { $table->string('template_name',160)->nullable()->after('content'); }); } public function down(): void { Schema::table('campaigns', function (Blueprint $table) { $table->dropColumn('template_name'); }); } };
