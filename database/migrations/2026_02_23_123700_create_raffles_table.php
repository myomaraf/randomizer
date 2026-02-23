<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('raffles', function (Blueprint $table) {
            $table->id();
            $table->string('raffle_id')->unique();
            $table->string('uuids_sha256');
            $table->unsignedInteger('count');
            $table->string('selected_uuid');
            $table->string('algorithm_version');
            $table->string('digest_sha256');
            $table->unsignedInteger('index_selected');
            $table->string('nonce_hex');
            $table->dateTime('timestamp_utc');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('raffles');
    }
};
