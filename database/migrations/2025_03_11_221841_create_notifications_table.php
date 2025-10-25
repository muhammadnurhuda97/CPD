<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNotificationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');  // Foreign key ke user
            $table->timestamps();
            $table->string('event');       // Kolom event
            $table->string('event_type');  // Kolom jenis event
            $table->date('event_date');   // Kolom tanggal event
            $table->time('event_time');   // Kolom waktu event
            $table->string('zoom')->nullable();        // Kolom link zoom (nullable)
            $table->string('location')->nullable();   // Kolom link lokasi (nullable)
            $table->string('location_name')->nullable(); // Nama lokasi (nullable)
            $table->string('location_address')->nullable(); // Alamat lokasi (nullable)
            $table->boolean('is_paid')->default(false); // Kolom is_paid
            $table->decimal('price', 10, 2)->nullable()->default(null); // Kolom price

            // Definisikan foreign key jika ada
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('notifications');
    }
}
