<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

/**
 * Osnova za testove kojima treba stvarna datoteka baze.
 *
 * Ostali testovi rade nad `:memory:` bazom i unutar transakcije, a backup pravi
 * `VACUUM INTO` i zamjenjuje sam fajl baze — ni jedno ni drugo tamo nije moguće.
 * Zato svaki test ovdje dobija svoju SQLite datoteku i svježe migracije.
 */
abstract class DatabaseFileTestCase extends BaseTestCase
{
    protected string $databasePath;

    protected function setUp(): void
    {
        $this->databasePath = sys_get_temp_dir().'/kalkulatron-test-'.uniqid().'.sqlite';
        touch($this->databasePath);

        parent::setUp();

        config()->set('database.connections.sqlite.database', $this->databasePath);
        DB::purge('sqlite');

        Artisan::call('migrate', ['--force' => true]);
    }

    protected function tearDown(): void
    {
        DB::disconnect();

        parent::tearDown();

        foreach (['', '-wal', '-shm'] as $suffix) {
            @unlink($this->databasePath.$suffix);
        }
    }
}
