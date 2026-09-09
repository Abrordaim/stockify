<?php

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
        Schema::table('stock_transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('stock_transactions', 'created_by')) {
                $table->foreignId('created_by')->nullable()->after('product_id')->constrained('users')->onDelete('cascade');
            }
            if (!Schema::hasColumn('stock_transactions', 'confirmed_by')) {
                $table->foreignId('confirmed_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('stock_transactions', 'confirmed_at')) {
                $table->timestamp('confirmed_at')->nullable()->after('confirmed_by');
            }
            if (!Schema::hasColumn('stock_transactions', 'stock_before')) {
                $table->integer('stock_before')->default(0)->after('quantity');
            }
            if (!Schema::hasColumn('stock_transactions', 'stock_after')) {
                $table->integer('stock_after')->default(0)->after('stock_before');
            }
        });

        // Migrate status column to string(30)
        // In PostgreSQL, drop check constraint created by enum first
        $connection = DB::connection()->getDriverName();
        if ($connection === 'pgsql') {
            DB::statement('ALTER TABLE stock_transactions DROP CONSTRAINT IF EXISTS stock_transactions_status_check;');
            DB::statement('ALTER TABLE stock_transactions ALTER COLUMN status TYPE VARCHAR(30);');
            DB::statement("ALTER TABLE stock_transactions ALTER COLUMN status SET DEFAULT 'Pending';");
        } else {
            try {
                Schema::table('stock_transactions', function (Blueprint $table) {
                    $table->string('status', 30)->default('Pending')->change();
                });
            } catch (\Throwable $e) {}
        }

        // Migrate existing rows data
        DB::table('stock_transactions')->whereNull('created_by')->update([
            'created_by' => DB::raw('user_id')
        ]);

        // Map old status values to standard 4 statuses
        DB::table('stock_transactions')->where('status', 'completed')->where('type', 'in')->update([
            'status' => 'Diterima',
            'confirmed_by' => DB::raw('user_id'),
            'confirmed_at' => DB::raw('created_at'),
            'stock_after' => DB::raw('quantity'),
        ]);

        DB::table('stock_transactions')->where('status', 'completed')->where('type', 'out')->update([
            'status' => 'Dikeluarkan',
            'confirmed_by' => DB::raw('user_id'),
            'confirmed_at' => DB::raw('created_at'),
        ]);

        DB::table('stock_transactions')->where('status', 'completed')->where('type', 'adjustment')->update([
            'status' => 'Diterima',
            'confirmed_by' => DB::raw('user_id'),
            'confirmed_at' => DB::raw('created_at'),
        ]);

        DB::table('stock_transactions')->where('status', 'pending')->update([
            'status' => 'Pending',
        ]);

        DB::table('stock_transactions')->where('status', 'cancelled')->update([
            'status' => 'Ditolak',
            'confirmed_by' => DB::raw('user_id'),
            'confirmed_at' => DB::raw('updated_at'),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_transactions', function (Blueprint $table) {
            if (Schema::hasColumn('stock_transactions', 'confirmed_by')) {
                $table->dropForeign(['confirmed_by']);
                $table->dropColumn('confirmed_by');
            }
            if (Schema::hasColumn('stock_transactions', 'created_by')) {
                $table->dropForeign(['created_by']);
                $table->dropColumn('created_by');
            }
            if (Schema::hasColumn('stock_transactions', 'confirmed_at')) {
                $table->dropColumn('confirmed_at');
            }
            if (Schema::hasColumn('stock_transactions', 'stock_before')) {
                $table->dropColumn('stock_before');
            }
            if (Schema::hasColumn('stock_transactions', 'stock_after')) {
                $table->dropColumn('stock_after');
            }
        });
    }
};
