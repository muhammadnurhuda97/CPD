<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPostEventReminderFlagToParticipantsTable extends Migration
{
    public function up()
    {
        Schema::table('participants', function (Blueprint $table) {
            // Tambahkan kolom ini (sesuaikan 'after' jika perlu)
            $table->boolean('post_event_reminder_scheduled')->default(false)->after('paid_reminder_scheduled');
        });
    }
    public function down()
    {
        Schema::table('participants', function (Blueprint $table) {
            $table->dropColumn('post_event_reminder_scheduled');
        });
    }
}
