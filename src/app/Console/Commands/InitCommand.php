<?php

namespace App\Console\Commands;

use App\Console\Commands\Traits\AskForPassword;
use App\Models\User;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Kernel as Artisan;
use Illuminate\Contracts\Hashing\Hasher	as Hash;
use Illuminate\Database\DatabaseManager as DB;

class InitCommand extends Command
{
    use AskForPassword;

    protected $signature = 'devhub:init {--no-assets}';
    protected $description = 'Install or upgrade DevHub';

    private $mediaCacheService;
    private $artisan;
    private $dotenvEditor;
    private $hash;
    private $db;
    private $settingRepository;

    public function __construct(
        Artisan $artisan,
        Hash $hash,
        DB $db
    ) {
        parent::__construct();

        $this->artisan = $artisan;
        $this->hash = $hash;
        $this->db = $db;
    }

    public function handle(): void
    {
        $this->comment('Attempting to install or upgrade DevHub.');

        if ($this->inNoInteractionMode()) {
            $this->info('Running in no-interaction mode');
        }

        try {
            $this->maybeGenerateAppKey();
//            $this->maybeSetUpDatabase();
//            $this->migrateDatabase();
//            $this->maybeSeedDatabase();
            $this->maybeCompileFrontEndAssets();
        } catch (Exception $e) {
            $this->error("Oops! DevHub installation or upgrade didn't finish successfully.");
            $this->error('Please try again');
            $this->error('😥 Sorry for this. You deserve better.');
            $this->error($e);

            return;
        }

        $this->comment(PHP_EOL.'🎆  Success! Koel can now be run from localhost with `php artisan serve`.');

        $this->comment('Again, visit 📙 '.config('koel.misc.docs_url').' for the official documentation.');
        $this->comment(
            "Feeling generous and want to support Koel's development? Check out "
            .config('koel.misc.sponsor_github_url')
            .' 🤗'
        );
        $this->comment('Thanks for using Koel. You rock! 🤘');
    }

    /**
     * Prompt user for valid database credentials and set up the database.
     */
    private function setUpDatabase(): void
    {
        $config = [
            'DB_CONNECTION' => '',
            'DB_HOST' => '',
            'DB_PORT' => '',
            'DB_DATABASE' => '',
            'DB_USERNAME' => '',
            'DB_PASSWORD' => '',
        ];

        $config['DB_CONNECTION'] = $this->choice(
            'Your DB driver of choice',
            [
                'mysql' => 'MySQL/MariaDB',
                'pgsql' => 'PostgreSQL',
                'sqlsrv' => 'SQL Server',
                'sqlite-e2e' => 'SQLite',
            ],
            'mysql'
        );

        if ($config['DB_CONNECTION'] === 'sqlite-e2e') {
            $config['DB_DATABASE'] = $this->ask('Absolute path to the DB file');
        } else {
            $config['DB_HOST'] = $this->anticipate('DB host', ['127.0.0.1', 'localhost']);
            $config['DB_PORT'] = (string) $this->ask('DB port (leave empty for default)');
            $config['DB_DATABASE'] = $this->anticipate('DB name', ['koel']);
            $config['DB_USERNAME'] = $this->anticipate('DB user', ['koel']);
            $config['DB_PASSWORD'] = (string) $this->ask('DB password');
        }

        foreach ($config as $key => $value) {
            $this->dotenvEditor->setKey($key, $value);
        }

        $this->dotenvEditor->save();

        // Set the config so that the next DB attempt uses refreshed credentials
        config([
            'database.default' => $config['DB_CONNECTION'],
            "database.connections.{$config['DB_CONNECTION']}.host" => $config['DB_HOST'],
            "database.connections.{$config['DB_CONNECTION']}.port" => $config['DB_PORT'],
            "database.connections.{$config['DB_CONNECTION']}.database" => $config['DB_DATABASE'],
            "database.connections.{$config['DB_CONNECTION']}.username" => $config['DB_USERNAME'],
            "database.connections.{$config['DB_CONNECTION']}.password" => $config['DB_PASSWORD'],
        ]);
    }

    private function inNoInteractionMode(): bool
    {
        return (bool) $this->option('no-interaction');
    }

    private function inNoAssetsMode(): bool
    {
        return (bool) $this->option('no-assets');
    }

    private function setUpAdminAccount(): void
    {
        $this->info("Let's create the admin account.");

        [$name, $email, $password] = $this->gatherAdminAccountCredentials();

        User::create([
            'name' => $name,
            'email' => $email,
            'password' => $this->hash->make($password),
            'is_admin' => true,
        ]);
    }

    private function maybeGenerateAppKey(): void
    {
        if (!config('app.key')) {
            $this->info('Generating app key');
            $this->artisan->call('key:generate');
        } else {
            $this->comment('App key exists -- skipping');
        }
    }

    private function maybeSeedDatabase(): void
    {
        if (!User::count()) {
            $this->setUpAdminAccount();
            $this->info('Seeding initial data');
            $this->artisan->call('db:seed', ['--force' => true]);
        } else {
            $this->comment('Data seeded -- skipping');
        }
    }

    private function maybeSetUpDatabase(): void
    {
        while (true) {
            try {
                // Make sure the config cache is cleared before another attempt.
                $this->artisan->call('config:clear');
                $this->db->reconnect()->getPdo();

                break;
            } catch (Exception $e) {
                $this->error($e->getMessage());
                $this->warn(PHP_EOL."Koel cannot connect to the database. Let's set it up.");
                $this->setUpDatabase();
            }
        }
    }

    private function migrateDatabase(): void
    {
        $this->info('Migrating database');
        $this->artisan->call('migrate', ['--force' => true]);

        // Clear the media cache, just in case we did any media-related migration
        $this->mediaCacheService->clear();
    }

    private function maybeCompileFrontEndAssets(): void
    {
        if ($this->inNoAssetsMode()) {
            return;
        }

        $this->info('Now to front-end stuff');

        // We need to run several yarn commands:
        // - The first to install node_modules in the resources/assets submodule
        // - The second and third for the root folder, to build Koel's front-end assets with Mix.

        chdir('./resources/assets');
        $this->info('├── Installing Node modules in resources/assets directory');

        $runOkOrThrow = static function (string $command): void {
            passthru($command, $status);
            throw_if((bool) $status, InstallationFailedException::class);
        };

        $runOkOrThrow('yarn install --colors');

        chdir('../..');
        $this->info('└── Compiling assets');

        $runOkOrThrow('yarn install --colors');
        $runOkOrThrow('yarn production --colors');
    }

    /** @return array<string> */
    private function gatherAdminAccountCredentials(): array
    {
        if ($this->inNoInteractionMode()) {
            return [config('koel.admin.name'), config('koel.admin.email'), config('koel.admin.password')];
        }

        $name = $this->ask('Your name', config('koel.admin.name'));
        $email = $this->ask('Your email address', config('koel.admin.email'));
        $password = $this->askForPassword();

        return [$name, $email, $password];
    }

    private function isValidMediaPath(string $path): bool
    {
        return is_dir($path) && is_readable($path);
    }

    private function setMediaPathFromEnvFile(): void
    {
        with(config('koel.media_path'), function (?string $path): void {
            if (!$path) {
                return;
            }

            if ($this->isValidMediaPath($path)) {
                Setting::set('media_path', $path);
            } else {
                $this->warn(sprintf('The path %s does not exist or not readable. Skipping.', $path));
            }
        });
    }
}
