<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNotificationIdToParticipantsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('participants', function (Blueprint $table) {
            // Menambahkan kolom notification_id setelah kolom affiliate_id
            $table->foreignId('notification_id')
                ->nullable()
                ->after('affiliate_id') // Opsional: untuk posisi kolom yang rapi
                ->constrained('notifications') // Membuat foreign key ke tabel notifications
                ->onDelete('set null'); // Jika event dihapus, data peserta tidak ikut terhapus
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('participants', function (Blueprint $table) {
            // Perintah untuk membatalkan migrasi (jika diperlukan)
            $table->dropForeign(['notification_id']);
            $table->dropColumn('notification_id');
        });
    }
}