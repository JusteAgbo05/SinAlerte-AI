<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->string('code', 16)->unique();

            // Saisi par le citoyen : low | medium | high
            $table->string('perceived_severity', 10);

            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->unsignedInteger('gps_accuracy_m')->nullable();
            $table->string('location_hint', 160)->nullable();

            // Fichiers privés (disque "local"), servis uniquement aux opérateurs.
            $table->string('photo_path')->nullable();
            $table->string('audio_path')->nullable();
            $table->string('audio_lang', 5)->nullable(); // fr | fon

            // received | prioritized | dispatched | resolved
            $table->string('status', 20)->default('received')->index();

            // Priorité calculée (voir App\Services\ReportScorer)
            $table->unsignedTinyInteger('score')->default(0);
            $table->string('level', 10)->default('low')->index(); // low | medium | high
            $table->json('score_reasons')->nullable();

            $table->foreignId('team_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('resolved_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
