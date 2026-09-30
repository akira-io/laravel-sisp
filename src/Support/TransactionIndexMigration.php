<?php

declare(strict_types=1);

namespace Akira\Sisp\Support;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Override;

/**
 * Adds one index to the transactions table, the one table that grows with
 * every sale. On Postgres a plain CREATE INDEX holds an ACCESS EXCLUSIVE
 * lock for the whole build, during which no checkout can create a transaction
 * and no callback can record a payment, so there the index is built
 * CONCURRENTLY. That statement cannot run inside a transaction, hence
 * $withinTransaction, and a build that fails leaves an INVALID index behind,
 * which is dropped and rebuilt rather than taken as present. Inside a
 * transaction someone else opened (a test, a migrate call wrapped by the
 * application) the plain statement is used, as CONCURRENTLY would fail.
 */
abstract class TransactionIndexMigration extends Migration
{
    #[Override]
    public $withinTransaction = false;

    /**
     * @return list<string>
     */
    abstract protected function columns(): array;

    final public function up(): void
    {
        $table = $this->table();

        if (! Schema::hasTable($table)) {
            return;
        }

        if ($this->isPostgres()) {
            $this->dropInvalidIndex($table, $this->indexName($table));
        }

        if ($this->hasIndex($table)) {
            return;
        }

        $indexName = $this->indexName($table);

        if ($this->isPostgres()) {
            DB::statement(sprintf(
                'CREATE INDEX%s IF NOT EXISTS %s ON %s (%s)',
                $this->concurrently(),
                $this->wrap($indexName),
                $this->wrapTable($table),
                implode(', ', array_map($this->wrap(...), $this->columns())),
            ));

            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($indexName): void {
            $blueprint->index($this->columns(), $indexName);
        });
    }

    final public function down(): void
    {
        $table = $this->table();

        if (! Schema::hasTable($table)) {
            return;
        }

        $indexName = $this->indexName($table);

        if (! $this->hasIndex($table, $indexName)) {
            return;
        }

        if ($this->isPostgres()) {
            DB::statement(sprintf('DROP INDEX%s IF EXISTS %s', $this->concurrently(), $this->wrap($this->qualify($table, $indexName))));

            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($indexName): void {
            $blueprint->dropIndex($indexName);
        });
    }

    protected function table(): string
    {
        return config()->string('sisp.tables.transactions', 'sisp_transactions');
    }

    protected function indexName(string $table): string
    {
        return str_replace(['-', '.'], '_', mb_strtolower($table.'_'.implode('_', $this->columns()).'_index'));
    }

    protected function hasIndex(string $table, ?string $name = null): bool
    {
        foreach (Schema::getIndexes($table) as $index) {
            if ($index['columns'] !== $this->columns()) {
                continue;
            }

            if ($name === null || mb_strtolower((string) $index['name']) === $name) {
                return true;
            }
        }

        return false;
    }

    private function isPostgres(): bool
    {
        return DB::connection()->getDriverName() === 'pgsql';
    }

    /**
     * A CONCURRENTLY build that was interrupted leaves the index in place but
     * marked invalid: the planner ignores it and a second CREATE ... IF NOT
     * EXISTS would keep it. Drop it so the build below starts over. The lookup
     * names both the index and the table, in the table's schema, so an index
     * of the same name on another table is never touched.
     */
    private function dropInvalidIndex(string $table, string $indexName): void
    {
        $qualifiedIndex = $this->qualify($table, $indexName);

        $invalid = DB::selectOne(
            'SELECT 1 FROM pg_index WHERE indexrelid = to_regclass(?) AND indrelid = to_regclass(?) AND NOT indisvalid',
            [$this->wrap($qualifiedIndex), $this->wrapTable($table)],
        );

        if ($invalid === null) {
            return;
        }

        DB::statement(sprintf('DROP INDEX%s IF EXISTS %s', $this->concurrently(), $this->wrap($qualifiedIndex)));
    }

    /**
     * An index lives in its table's schema, so a table configured as
     * "schema.table" needs its index named the same way to be found again.
     */
    private function qualify(string $table, string $indexName): string
    {
        return str_contains($table, '.')
            ? mb_strstr($table, '.', true).'.'.$indexName
            : $indexName;
    }

    private function concurrently(): string
    {
        return DB::transactionLevel() === 0 ? ' CONCURRENTLY' : '';
    }

    private function wrap(string $identifier): string
    {
        return DB::connection()->getSchemaGrammar()->wrap($identifier);
    }

    private function wrapTable(string $table): string
    {
        return DB::connection()->getSchemaGrammar()->wrapTable($table);
    }
}
