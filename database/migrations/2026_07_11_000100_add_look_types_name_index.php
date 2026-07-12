<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Discovery "Looks" filter index (talent-spec discovery; parallels ADR-6). The
 * model-scope Looks filter matches `look_types.name` — a translatable JSON column,
 * so a plain index is impossible; on **MySQL 8.0.13+** this adds a **functional
 * index** on the English name path (`name->>'$.en'`), which is what discovery
 * filters on.
 *
 * **MariaDB has no functional/expression indexes** (they require a generated
 * column), and Laravel reports MariaDB's PDO driver as `mysql`, so the old
 * `getDriverName() !== 'mysql'` guard did NOT catch it. We now inspect the real
 * server via `VERSION()` and **skip** the index on MariaDB / older MySQL. The Looks
 * filter still works without it — `look_types` is a tiny lookup table, so the
 * unindexed scan is negligible. Recorded in docs/schema.md.
 */
return new class extends Migration
{
    private const INDEX = 'look_types_name_en_index';

    public function up(): void
    {
        if (! $this->supportsFunctionalIndex()) {
            return; // MariaDB / older MySQL: skip — the Looks filter works unindexed.
        }

        DB::statement(
            "ALTER TABLE `look_types` ADD INDEX `".self::INDEX."` ((CAST(`name`->>'\$.en' AS CHAR(191))))"
        );
    }

    public function down(): void
    {
        if ($this->indexExists()) {
            DB::statement("ALTER TABLE `look_types` DROP INDEX `".self::INDEX."`");
        }
    }

    /**
     * True only on genuine MySQL >= 8.0.13 (which supports functional key parts).
     * Laravel reports MariaDB as the `mysql` driver, so VERSION() is inspected to
     * exclude it.
     */
    private function supportsFunctionalIndex(): bool
    {
        if (DB::getDriverName() !== 'mysql') {
            return false;
        }

        $version = (string) (DB::selectOne('select version() as v')->v ?? '');

        if (stripos($version, 'mariadb') !== false) {
            return false; // MariaDB: no functional/expression indexes
        }

        return preg_match('/(\d+\.\d+\.\d+)/', $version, $m) === 1
            && version_compare($m[1], '8.0.13', '>=');
    }

    /** Whether the functional index currently exists (so rollback is safe on any DB). */
    private function indexExists(): bool
    {
        if (DB::getDriverName() !== 'mysql') {
            return false;
        }

        return DB::selectOne(
            'select 1 from information_schema.statistics '
            .'where table_schema = database() and table_name = ? and index_name = ? limit 1',
            ['look_types', self::INDEX]
        ) !== null;
    }
};
