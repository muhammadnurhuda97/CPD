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
        Schema::table('participants', function (Blueprint $table) {
            $table->boolean('notified_registered')->default(false);
            $table->boolean('notified_unpaid')->default(false);
            $table->boolean('notified_paid')->default(false);
            $table->boolean('reminder_scheduled')->default(false);
            $table->boolean('paid_reminder_scheduled')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('participants', function (Blueprint $table) {
            $table->dropColumn('notified_registered');
            $table->dropColumn('notified_unpaid');
            $table->dropColumn('notified_paid');
            $table->dropColumn('reminder_scheduled');
            $table->dropColumn('paid_reminder_scheduled');
        });
    }
};
