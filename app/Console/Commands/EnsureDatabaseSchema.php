<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class EnsureDatabaseSchema extends Command
{
    /**
     * @var string
     */
    protected $signature = 'db:ensure-schema';

    /**
     * @var string
     */
    protected $description = 'Create the PostgreSQL schema named in DB_SEARCH_PATH (e.g. when sharing a Supabase project) before migrations run';

    public function handle(): int
    {
        $connection = DB::connection();
        if ($connection->getDriverName() !== 'pgsql') {
            $this->info('Not a PostgreSQL connection; nothing to do.');

            return self::SUCCESS;
        }

        $schema = trim(explode(',', (string) $connection->getConfig('search_path'))[0], " \"'");
        if ($schema === '' || $schema === 'public') {
            $this->info('Using the public schema; nothing to do.');

            return self::SUCCESS;
        }

        if (! preg_match('/^[a-z_][a-z0-9_]{0,62}$/', $schema)) {
            $this->error("Schema name \"{$schema}\" is not allowed. Use lowercase letters, numbers and underscores.");

            return self::FAILURE;
        }

        // Check first: a dedicated app user may use an existing schema without being allowed to create new ones.
        if (! $connection->selectOne('select 1 as found from pg_namespace where nspname = ?', [$schema])) {
            $connection->statement("create schema \"{$schema}\"");
        }
        $this->info("Schema \"{$schema}\" is ready.");

        return self::SUCCESS;
    }
}
