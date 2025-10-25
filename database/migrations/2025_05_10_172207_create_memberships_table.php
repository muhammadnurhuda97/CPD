<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMembershipsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('membership_type', ['basic', 'premium', 'ultimate'])->default('basic');
            $table->decimal('commission_rate', 5, 2)->default(30);  // Persentase komisi
            $table->timestamp('start_date')->useCurrent();  // Tanggal mulai membership
            $table->timestamp('end_date')->nullable();  // Tanggal berakhir membership
            $table->enum('status', ['active', 'expired'])->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('memberships');
    }
}
