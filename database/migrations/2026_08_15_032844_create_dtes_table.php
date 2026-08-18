<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Environment;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('dtes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->constrained();
            $table->string('generation_code')->notNullable()->unique();
            $table->string('control_number')->notNullable();
            $table->string('dte_type')->notNullable();
            $table->string('version')->notNullable();
            $table->string('environment')->default(Environment::SANDBOX->value);
            $table->string('status')->notNullable();
            $table->string('issuer_nit')->notNullable();
            $table->string('receiver_document')->nullable();
            $table->integer('total_amount')->notNullable();
            $table->json('original_json')->notNullable();
            $table->text('signed_json')->nullable();
            $table->text('received_seal')->nullable();
            $table->text('pdf_url')->nullable();
            $table->text('observations')->nullable();
            $table->json('mh_response_json')->nullable();
            $table->foreignUuid('receiver_id')->nullable()->constrained('receivers');

            $table->timestamps();

            $table->index(['company_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dtes');
    }
};
