<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddReferralAndPaymentMethodFields extends Migration // Nama class disesuaikan
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Update tabel 'participants'
        Schema::table('participants', function (Blueprint $table) {
            // Kolom untuk referral dari peserta lain
            $table->foreignId('referred_by_participant_id')
                ->nullable()
                ->after('notification_id') // Sesuaikan posisi jika perlu
                ->constrained('participants')
                ->onDelete('set null');

            // Kolom untuk metode pembayaran yang dipilih
            $table->enum('payment_method', ['midtrans', 'cash'])
                ->nullable()
                ->after('is_paid'); // Sesuaikan posisi jika perlu

            // (Optional tapi direkomendasikan) Ubah kolom status untuk mengakomodasi status baru
            // Jika Anda menggunakan kolom 'payment_status', ubah definisinya di migrasi SEBELUMNYA
            // atau buat migrasi baru khusus untuk mengubah tipe kolom 'payment_status' menjadi string
            // atau enum yang lebih fleksibel, misal:
            // $table->string('payment_status')->default('pending')->change();
            // Untuk sekarang, kita asumsikan 'payment_status' sudah string atau bisa menampung 'pending_cash_verification'
        });

        // Update tabel 'notifications' (untuk event)
        Schema::table('notifications', function (Blueprint $table) {
            // Kolom untuk diskon peserta
            $table->decimal('referral_discount_amount', 10, 2)
                ->default(0)
                ->after('price'); // Sesuaikan posisi jika perlu

            // Kolom untuk komisi affiliate dari referral peserta
            $table->decimal('participant_referral_commission', 10, 2)
                ->default(0)
                ->after('referral_discount_amount');
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
            $table->dropForeign(['referred_by_participant_id']);
            $table->dropColumn('referred_by_participant_id');
            $table->dropColumn('payment_method');
            // Jika Anda mengubah 'payment_status', tambahkan rollbacknya di sini
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->dropColumn('referral_discount_amount');
            $table->dropColumn('participant_referral_commission');
        });
    }
}
