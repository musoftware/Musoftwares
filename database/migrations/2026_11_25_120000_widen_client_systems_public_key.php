<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * License keys moved from Ed25519 (sodium, missing on the live server) to ECDSA P-256 through OpenSSL.
 * The public key is now a PEM block (~180 characters), longer than the old 64-character column.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('client_systems', function (Blueprint $table) {
            $table->text('signing_public_key')->change();
        });
    }

    public function down(): void
    {
        Schema::table('client_systems', function (Blueprint $table) {
            $table->string('signing_public_key', 64)->change();
        });
    }
};
