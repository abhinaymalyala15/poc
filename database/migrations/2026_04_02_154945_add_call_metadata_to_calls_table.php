<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('calls', function (Blueprint $table) {
            $table->string('call_sid')->nullable()->after('id');
            $table->text('response_audio_url')->nullable()->after('ai_response');
            $table->text('error_message')->nullable()->after('response_audio_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('calls', function (Blueprint $table) {
            $table->dropColumn(['call_sid', 'response_audio_url', 'error_message']);
        });
    }
};
