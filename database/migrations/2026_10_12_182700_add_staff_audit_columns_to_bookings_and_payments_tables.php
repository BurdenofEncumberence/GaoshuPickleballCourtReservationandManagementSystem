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
        Schema::table('payments', function (Blueprint $table) {
            if (! Schema::hasColumn('payments', 'processed_by')) {
                $table->foreignId('processed_by')->nullable()->after('change_amount')->constrained('users')->nullOnDelete();
            }
        });

        Schema::table('bookings', function (Blueprint $table) {
            if (! Schema::hasColumn('bookings', 'status_updated_by')) {
                $table->foreignId('status_updated_by')->nullable()->after('booking_type')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('bookings', 'status_updated_at')) {
                $table->timestamp('status_updated_at')->nullable()->after('status_updated_by');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (Schema::hasColumn('bookings', 'status_updated_by')) {
                $table->dropForeign(['status_updated_by']);
                $table->dropColumn(['status_updated_by']);
            }
            if (Schema::hasColumn('bookings', 'status_updated_at')) {
                $table->dropColumn(['status_updated_at']);
            }
        });

        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'processed_by')) {
                $table->dropForeign(['processed_by']);
                $table->dropColumn(['processed_by']);
            }
        });
    }
};
