<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            /* Every account that already exists was created when the panel had
               one role and everyone could do everything, so they become
               administrators — the alternative is a migration that quietly
               takes the site's settings away from whoever set them up.

               A string rather than a database enum: the roles are declared in
               App\Support\StaffRoles, and adding one there should not need a
               schema change on a live database. */
            $table->string('role', 20)->default('administrator')->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};
