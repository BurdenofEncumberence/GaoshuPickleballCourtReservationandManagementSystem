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
        Schema::table('open_play_registrations', function (Blueprint $table) {
            $table->dropColumn(['player_name', 'player_email', 'player_phone']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('open_play_registrations', function (Blueprint $table) {
            $table->string('player_name')->nullable()->after('user_id');
            $table->string('player_email')->nullable()->after('player_name');
            $table->string('player_phone')->nullable()->after('player_email');
        });
    }
};
