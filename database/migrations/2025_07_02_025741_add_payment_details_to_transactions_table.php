<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPaymentDetailsToTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('payment_type')->nullable()->after('status');
            $table->string('payment_channel')->nullable()->after('payment_type');
            $table->string('va_number')->nullable()->after('payment_channel');
            $table->timestamp('expiry_time')->nullable()->after('va_number');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['payment_type', 'payment_channel', 'va_number', 'expiry_time']);
        });
    }
}
