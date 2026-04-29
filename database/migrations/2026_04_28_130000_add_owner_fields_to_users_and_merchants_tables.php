<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('email');
            $table->string('profile_image')->nullable()->after('phone');
            $table->unique('phone');
        });

        Schema::table('merchants', function (Blueprint $table) {
            $table->string('id_card_document')->nullable()->after('cover_image');
            $table->enum('verification_status', ['Not Verified', 'Pending', 'Verified'])
                ->default('Pending')
                ->after('id_card_document');
        });
    }

    public function down(): void
    {
        Schema::table('merchants', function (Blueprint $table) {
            $table->dropColumn(['id_card_document', 'verification_status']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['phone']);
            $table->dropColumn(['phone', 'profile_image']);
        });
    }
};
