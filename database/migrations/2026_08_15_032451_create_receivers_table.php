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
        Schema::create('receivers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->constrained('companies');
            $table->string('document_type');
            $table->string('document_number');
            $table->string('nrc')->nullable();
            $table->string('name');
            $table->string('economic_activity_code')->nullable();
            $table->string('economic_activity_description')->nullable();
            $table->foreignUuid('departament_id')->nullable()->constrained();
            $table->foreignUuid('municipality_id')->nullable()->constrained();
            $table->foreignUuid('district_id')->nullable()->constrained();
            $table->string('address_complement')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'document_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('receivers');
    }
};
