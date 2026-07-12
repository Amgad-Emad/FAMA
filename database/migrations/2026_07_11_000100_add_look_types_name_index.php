<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Discovery "Looks" filter index (talent-spec discovery; parallels ADR-6). The
 * model-scope Looks filter matches `look_types.name` — a translatable JSON column,
 * so a plain index is impossible; this adds a **functional index** on the English
 * name path (`name->>'$.en'`), which is what discovery filters on. Recorded in
 * docs/schema.md.
 */
return new class extends Migration
{
    private const INDEX = 'look_types_name_en_index';

    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return; // functional index is MySQL-specific (dev + tests run on MySQL)
        }

        DB::statement(
            "ALTER TABLE `look_types` ADD INDEX `".self::INDEX."` ((CAST(`name`->>'\$.en' AS CHAR(191))))"
        );
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE `look_types` DROP INDEX `".self::INDEX."`");
    }
};
