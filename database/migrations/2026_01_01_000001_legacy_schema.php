<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('setting_key', 100)->unique();
            $table->text('setting_value')->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('username', 80)->unique();
            $table->string('email', 190)->nullable()->unique();
            $table->boolean('email_verified')->default(true);
            $table->string('email_verification_token', 128)->nullable();
            $table->dateTime('email_verification_expires_at')->nullable();
            $table->string('password_reset_token', 128)->nullable();
            $table->dateTime('password_reset_expires_at')->nullable();
            $table->string('password_hash');
            $table->enum('role', ['super_admin', 'editor', 'member'])->default('member');
            $table->enum('status', ['active', 'inactive', 'banned'])->default('active');
            $table->boolean('two_factor_enabled')->default(false);
            $table->string('two_factor_secret')->nullable();
            $table->boolean('is_anonymous_allowed')->default(true);
            $table->timestamps();
            $table->index('status');
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('slug', 140)->unique();
            $table->text('description')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });

        Schema::create('menus', function (Blueprint $table) {
            $table->id();
            $table->string('title', 120);
            $table->string('url', 255);
            $table->enum('position', ['header', 'footer', 'sidebar'])->default('header');
            $table->integer('sort_order')->default(0);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamp('created_at')->nullable()->useCurrent();
        });

        Schema::create('news', function (Blueprint $table) {
            $table->id();
            $table->string('title', 255);
            $table->string('slug', 140)->unique();
            $table->longText('content');
            $table->text('excerpt')->nullable();
            $table->string('image', 255)->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('author_id')->nullable();
            $table->string('author_name', 80)->nullable();
            $table->boolean('is_anonymous')->default(false);
            $table->unsignedInteger('views')->default(0);
            $table->enum('status', ['draft', 'pending', 'published', 'archived'])->default('pending');
            $table->timestamps();
            $table->index(['status', 'created_at']);
            $table->index('category_id');
        });

        Schema::create('login_rate_limits', function (Blueprint $table) {
            $table->string('rate_key', 150)->primary();
            $table->unsignedInteger('failed_attempts')->default(0);
            $table->dateTime('blocked_until')->nullable();
            $table->dateTime('last_failed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('auth_challenges', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('purpose', 32);
            $table->char('code_hash', 64);
            $table->dateTime('expires_at');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->dateTime('used_at')->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->index(['user_id', 'purpose', 'expires_at']);
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('action', 100);
            $table->char('ip_hash', 64)->nullable();
            $table->text('details')->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('auth_challenges');
        Schema::dropIfExists('login_rate_limits');
        Schema::dropIfExists('news');
        Schema::dropIfExists('menus');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('users');
        Schema::dropIfExists('settings');
    }
};
