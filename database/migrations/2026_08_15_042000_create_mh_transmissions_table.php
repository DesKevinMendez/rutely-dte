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
        Schema::create('mh_transmissions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->constrained('companies');
            $table->uuidMorphs('transmittable');
            $table->string('operation');
            $table->unsignedSmallInteger('attempt')->default(1);
            $table->json('request_json')->nullable();
            $table->json('response_json')->nullable();
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->string('status');
            $table->text('error')->nullable();
            $table->dateTime('sent_at')->nullable();
            $table->dateTime('responded_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mh_transmissions');
    }
};
