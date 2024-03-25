<?php

declare(strict_types=1);

namespace Team64j\LaravelTarantool\Database\Schema\Grammars;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Grammars\Grammar;
use Illuminate\Support\Fluent;
use Team64j\LaravelTarantool\Database\Connection;

class TarantoolGrammar extends Grammar
{
    /**
     * The possible column serials.
     *
     * @var array
     */
    protected array $serials = ['integer'];

    /**
     * The possible column modifiers.
     *
     * @var array
     */
    protected $modifiers = ['Default', 'Nullable'];

    /**
     * Compile the query to determine if a table exists.
     *
     * @return string
     */
    public function compileTableExists(): string
    {
        return 'select * from "_space" where "name" = ?';
    }

    /**
     * @param array $columns
     *
     * @return string
     * @psalm-suppress all
     */
    private function autoAddPrimaryKey(array $columns): string
    {
        if (!empty($columns)) {
            $idColumnIndex = false;
            $primaryKeyExist = false;
            $autoIncrementExist = false;

            foreach ($columns as $index => $column) {
                if (!$primaryKeyExist) {
                    $searchPM = strripos($column, 'PRIMARY KEY');
                    if ($searchPM) {
                        $primaryKeyExist = true;
                    }
                }

                if (is_bool($idColumnIndex) && $idColumnIndex === false) {
                    $searchID = strripos($column, '"id"');
                    if ($searchID !== false) {
                        $idColumnIndex = $index;
                    }
                }

                if (!$autoIncrementExist) {
                    $searchAutoIncrement = strripos($column, 'autoincrement');
                    if ($searchAutoIncrement !== false) {
                        $autoIncrementExist = true;
                    }
                }
            }

            if (is_int($idColumnIndex) and $idColumnIndex !== false) {
                $textToAdd = '';

                if ($primaryKeyExist === false) {
                    $textToAdd = $textToAdd . ' PRIMARY KEY';
                }

                if ($autoIncrementExist === false) {
                    $textToAdd = $textToAdd . ' AUTOINCREMENT';
                }

                $columns[$idColumnIndex] = $columns[$idColumnIndex] . $textToAdd;
            }
        }

        return implode(', ', $columns);
    }

    /**
     * Compile a create table command.
     *
     * @param Blueprint $blueprint
     * @param Fluent $command
     * @param Connection $connection
     *
     * @return string
     */
    public function compileCreate(Blueprint $blueprint, Fluent $command, Connection $connection): string
    {
        $columns = $this->autoAddPrimaryKey($this->getColumns($blueprint));

        return 'CREATE TABLE IF NOT EXISTS ' . $this->wrapTable($blueprint) . " ($columns)";
    }

    /**
     * Compile a drop table command.
     *
     * @param Blueprint $blueprint
     * @param Fluent $command
     *
     * @return string
     */
    public function compileDrop(Blueprint $blueprint, Fluent $command): string
    {
        return 'drop table ' . $this->wrapTable($blueprint);
    }

    /**
     * Compile a primary key command.
     *
     * @param Blueprint $blueprint
     * @param Fluent $command
     *
     * @return string
     */
    public function compilePrimary(Blueprint $blueprint, Fluent $command): string
    {
        $command->name(null);

        return $this->compileKey($blueprint, $command, 'PRIMARY KEY');
    }

    /**
     * Compile a unique key command.
     *
     * @param Blueprint $blueprint
     * @param Fluent $command
     *
     * @return string
     */
    public function compileUnique(Blueprint $blueprint, Fluent $command): string
    {
        $columns = $this->columnize($command->columns);
        $table = $this->wrapTable($blueprint);

        return 'CREATE UNIQUE INDEX ' . strtoupper(substr((string) $command->index, 0, 31)) . " ON $table ($columns)";
    }

    /**
     * Compile a plain index key command.
     *
     * @param Blueprint $blueprint
     * @param Fluent $command
     *
     * @return string
     */
    public function compileIndex(Blueprint $blueprint, Fluent $command): string
    {
        $columns = $this->columnize($command->columns);
        $table = $this->wrapTable($blueprint);

        return 'CREATE INDEX ' . strtoupper(substr((string) $command->index, 0, 31)) . " ON $table ($columns)";
    }

    /**
     * Compile an index creation command.
     *
     * @param Blueprint $blueprint
     * @param Fluent $command
     * @param string $type
     *
     * @return string
     */
    protected function compileKey(Blueprint $blueprint, Fluent $command, $type): string
    {
        $columns = $this->columnize($command->columns);
        $table = $this->wrapTable($blueprint);

        return "alter table $table add $type ($columns)";
    }

    /**
     * Compile a foreign key command.
     *
     * @param Blueprint $blueprint
     * @param Fluent $command
     *
     * @return string
     */
    /*
    public function compileForeign(Blueprint $blueprint, Fluent $command)
    {
        $table = $this->wrapTable($blueprint);
        $on = $this->wrapTable($command->on);
        // We need to prepare several of the elements of the foreign key definition
        // before we can create the SQL, such as wrapping the tables and convert
        // an array of columns to comma-delimited strings for the SQL queries.
        $columns = $this->columnize($command->columns);
        $onColumns = $this->columnize((array) $command->references);
        $sql = "alter table {$table} add constraint ".strtoupper(substr($command->index, 0, 31))." ";
        $sql .= "foreign key ({$columns}) references {$on} ({$onColumns})";
        // Once we have the basic foreign key creation statement constructed we can
        // build out the syntax for what should happen on an update or delete of
        // the affected columns, which will get something like "cascade", etc.
        if ( ! is_null($command->onDelete))
        {
            $sql .= " on delete {$command->onDelete}";
        }
        if ( ! is_null($command->onUpdate))
        {
            $sql .= " on update {$command->onUpdate}";
        }
        return $sql;
    }
    */

    /**
     * Compile a drop foreign key command.
     *
     * @param Blueprint $blueprint
     * @param Fluent $command
     *
     * @return string
     */
    public function compileDropForeign(Blueprint $blueprint, Fluent $command): string
    {
        $table = $this->wrapTable($blueprint);

        return "alter table $table drop constraint $command->index";
    }

    /**
     * Get the SQL for a nullable column modifier.
     *
     * @param Blueprint $blueprint
     * @param Fluent $column
     *
     * @return string|null
     */
    protected function modifyNullable(Blueprint $blueprint, Fluent $column): ?string
    {
        return $column->nullable ? '' : ' not null';
    }

    /**
     * Get the SQL for a default column modifier.
     *
     * @param Blueprint $blueprint
     * @param Fluent $column
     *
     * @return string|null
     */
    protected function modifyDefault(Blueprint $blueprint, Fluent $column): ?string
    {
        if (!is_null($column->default)) {
            return ' default ' . $this->getDefaultValue($column->default);
        }

        return null;
    }

    /**
     * Create the column definition for a char type.
     *
     * @param Fluent $column
     *
     * @return string
     */
    protected function typeChar(Fluent $column): string
    {
        return 'TEXT';
    }

    /**
     * Create the column definition for a string type.
     *
     * @param Fluent $column
     *
     * @return string
     */
    protected function typeString(Fluent $column): string
    {
        return 'VARCHAR (' . $column->length . ')';
    }

    /**
     * Create the column definition for a text type.
     *
     * @param Fluent $column
     *
     * @return string
     */
    protected function typeText(Fluent $column): string
    {
        return 'TEXT';
    }

    /**
     * Create the column definition for a medium text type.
     *
     * @param Fluent $column
     *
     * @return string
     */
    protected function typeMediumText(Fluent $column): string
    {
        return 'TEXT';
    }

    /**
     * Create the column definition for a long text type.
     *
     * @param Fluent $column
     *
     * @return string
     */
    protected function typeLongText(Fluent $column): string
    {
        return 'TEXT';
    }

    /**
     * Create the column definition for a integer type.
     *
     * @param Fluent $column
     *
     * @return string
     */
    protected function typeInteger(Fluent $column): string
    {
        return 'INTEGER';
    }

    /**
     * Create the column definition for a big integer type.
     *
     * @param Fluent $column
     *
     * @return string
     */
    protected function typeBigInteger(Fluent $column): string
    {
        return 'INTEGER';
    }

    /**
     * Create the column definition for a medium integer type.
     *
     * @param Fluent $column
     *
     * @return string
     */
    protected function typeMediumInteger(Fluent $column): string
    {
        return 'INTEGER';
    }

    /**
     * Create the column definition for a tiny integer type.
     *
     * @param Fluent $column
     *
     * @return string
     */
    protected function typeTinyInteger(Fluent $column): string
    {
        return 'INTEGER';
    }

    /**
     * Create the column definition for a small integer type.
     *
     * @param Fluent $column
     *
     * @return string
     */
    protected function typeSmallInteger(Fluent $column): string
    {
        return 'INTEGER';
    }

    /**
     * Create the column definition for a float type.
     *
     * @param Fluent $column
     *
     * @return string
     */
    protected function typeFloat(Fluent $column): string
    {
        return 'NUMBER';
    }

    /**
     * Create the column definition for a double type.
     *
     * @param Fluent $column
     *
     * @return string
     */
    protected function typeDouble(Fluent $column): string
    {
        return 'NUMBER';
    }

    /**
     * Create the column definition for a decimal type.
     *
     * @param Fluent $column
     *
     * @return string
     */
    protected function typeDecimal(Fluent $column): string
    {
        return 'NUMBER';
    }

    /**
     * Create the column definition for a boolean type.
     *
     * @param Fluent $column
     *
     * @return string
     */
    protected function typeBoolean(Fluent $column): string
    {
        return 'SCALAR';
    }

    /**
     * Create the column definition for an enum type.
     *
     * @param Fluent $column
     *
     * @return string
     */
    protected function typeEnum(Fluent $column): string
    {
        return 'TEXT';
    }

    /**
     * Create the column definition for a json type.
     *
     * @param Fluent $column
     *
     * @return string
     */
    protected function typeJson(Fluent $column): string
    {
        return 'TEXT';
    }

    /**
     * Create the column definition for a date type.
     *
     * @param Fluent $column
     *
     * @return string
     */
    protected function typeDate(Fluent $column): string
    {
        return 'VARCHAR (10)';
    }

    /**
     * Create the column definition for a date-time type.
     *
     * @param Fluent $column
     *
     * @return string
     */
    protected function typeDateTime(Fluent $column): string
    {
        return 'VARCHAR (30)';
    }

    /**
     * Create the column definition for a time type.
     *
     * @param Fluent $column
     *
     * @return string
     */
    protected function typeTime(Fluent $column): string
    {
        return 'VARCHAR (10)';
    }

    /**
     * Create the column definition for a timestamp type.
     *
     * @param Fluent $column
     *
     * @return string
     */
    protected function typeTimestamp(Fluent $column): string
    {
        return 'VARCHAR (200)';
    }

    /**
     * Create the column definition for a binary type.
     *
     * @param Fluent $column
     *
     * @return string
     */
    protected function typeBinary(Fluent $column): string
    {
        return 'SCALAR';
    }
}
