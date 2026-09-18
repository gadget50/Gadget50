<?php
use Illuminate\Database\Migrations\Migration; use Illuminate\Database\Schema\Blueprint; use Illuminate\Support\Facades\Schema;
return new class extends Migration { public function up(): void { Schema::create('laravel_migration_marker', function(Blueprint $table){$table->id();$table->string('name')->unique();$table->timestamps();}); } public function down(): void { Schema::dropIfExists('laravel_migration_marker'); } };
