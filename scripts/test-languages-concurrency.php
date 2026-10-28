<?php

// Creates/drops only its own random MySQL test database, never the working database.
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
use App\Models\Language;
use App\Services\Languages\UpdateLanguage;
use Database\Seeders\LanguagesSeeder;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

if (! in_array(config('app.env'), ['local', 'testing'], true)) {
    throw new RuntimeException('Local/test environment required.');
}
$worker = ($argv[1] ?? '') === '--worker';
$name = $worker ? getenv('LANGUAGES_CONCURRENCY_DB') : 'supplyflow_languages_test_'.bin2hex(random_bytes(6));
if (! preg_match('/^supplyflow_languages_test_[a-f0-9]{12}$/', $name)) {
    throw new RuntimeException('Unsafe test database name.');
}
if (! $worker && ($argv[1] ?? '') !== '--create-isolated') {
    throw new RuntimeException('Pass --create-isolated to create a disposable test database.');
}
$workingDatabase = config('database.connections.mysql.database');
if ($name === $workingDatabase) {
    throw new RuntimeException('Working database refused.');
}
if (! $worker) {
    DB::connection('mysql')->statement('CREATE DATABASE '.$name.' CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
}
config(['database.default' => 'mysql', 'database.connections.mysql.database' => $name]);
DB::purge('mysql');
if ($worker) {
    $until = (float) $argv[5];
    while (microtime(true) < $until) {
        usleep(1000);
    }
    try {
        app(UpdateLanguage::class)->execute(Language::where('code', $argv[2])->value('id'), $argv[3], (bool) $argv[4]);
        echo "committed\n";
    } catch (ValidationException) {
        echo "rejected\n";
    }
    exit(0);
}
try {
    (require __DIR__.'/../database/migrations/2026_10_27_150000_create_languages_tables.php')->up();
    (new LanguagesSeeder)->run();
    putenv('LANGUAGES_CONCURRENCY_DB='.$name);
    $pairs = [
        [['ar', 'default', '1'], ['en', 'default', '1']],
        [['ar', 'active', '0'], ['ar', 'default', '1']],
    ];
    foreach ($pairs as $pair) {
        for ($round = 0; $round < 3; $round++) {
            app(UpdateLanguage::class)->execute(Language::where('code', 'en')->value('id'), 'default', true);
            app(UpdateLanguage::class)->execute(Language::where('code', 'ar')->value('id'), 'active', true);
            $start = microtime(true) + 0.7;
            $jobs = [];
            foreach ($pair as $args) {
                $pipes = [];
                $process = proc_open([PHP_BINARY, __FILE__, '--worker', ...$args, (string) $start], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, dirname(__DIR__));
                if (! is_resource($process)) {
                    throw new RuntimeException('Could not start worker.');
                }
                fclose($pipes[0]);
                $jobs[] = [$process, $pipes];
            }
            foreach ($jobs as [$process, $pipes]) {
                stream_get_contents($pipes[1]);
                stream_get_contents($pipes[2]);
                fclose($pipes[1]);
                fclose($pipes[2]);
                if (proc_close($process) !== 0) {
                    throw new RuntimeException('Concurrency worker failed (no credentials logged).');
                }
            }
            $row = DB::table('language_settings')->join('languages', 'languages.id', '=', 'language_settings.default_language_id')->select('languages.active')->first();
            if (DB::table('language_settings')->count() !== 1 || ! $row?->active) {
                throw new RuntimeException('Default invariant failed.');
            }
        }
    }
    echo "PASS: 6 races, 12 independent MySQL connections; one active default preserved.\n";
} finally {
    DB::disconnect('mysql');
    config(['database.connections.mysql.database' => $workingDatabase]);
    DB::purge('mysql');
    DB::connection('mysql')->statement('DROP DATABASE '.$name);
    putenv('LANGUAGES_CONCURRENCY_DB');
}
