<?php

use App\Modules\Identity\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
            $table->dropColumn('role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('staff')->index();
        });

        $administratorIds = DB::table('model_has_roles')
            ->where('model_type', (new User)->getMorphClass())
            ->whereIn('role_id', DB::table('roles')
                ->select('id')
                ->where('name', config('filament-shield.super_admin.name'))
                ->where('guard_name', 'web'))
            ->pluck('model_id');

        DB::table('users')->whereIn('id', $administratorIds)->update(['role' => 'admin']);
    }
};
