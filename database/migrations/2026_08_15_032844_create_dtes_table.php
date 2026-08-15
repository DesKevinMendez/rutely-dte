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
            $table->string('receiver_document')->notNullable();
            $table->string('total_amount')->notNullable();
            $table->json('original_json')->notNullable();
            $table->json('signed_json')->notNullable();
            $table->text('recieved_seal');
            $table->text('pdf_url');
            $table->text('observations');
            $table->json('mh_response_json');
            $table->foreignUuid('receiver_id')->constrained('receivers');

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
