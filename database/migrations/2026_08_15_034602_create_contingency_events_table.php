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
        Schema::create('contingency_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->constrained();
            $table->string('generation_code')->notNullable()->unique();
            $table->string('environment')->default(Environment::SANDBOX->value);
            $table->string('contingency_type')->notNullable();
            $table->text('reason')->notNullable();
            $table->datetime('start_date_at')->notNullable();
            $table->datetime('end_date_at')->nullable();
            $table->json('original_json')->notNullable();
            $table->text('signed_json')->nullable();
            $table->text('received_seal')->nullable();
            $table->string('status');
            $table->string('observations')->nullable();

            $table->timestamps();
        });

        Schema::table('dtes', function (Blueprint $table) {
            $table->foreignUuid('contingency_event_id')
                ->nullable()
                ->constrained('contingency_events');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dtes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('contingency_event_id');
        });

        Schema::dropIfExists('contingency_events');
    }
};
