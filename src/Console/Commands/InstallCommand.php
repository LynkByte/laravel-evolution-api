<?php

declare(strict_types=1);

namespace Lynkbyte\EvolutionApi\Console\Commands;

use Illuminate\Console\Command;

/**
 * Command to install and configure the Evolution API package.
 */
class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'evolution-api:install 
                            {--force : Overwrite existing configuration}';

    /**
     * The console command description.
     */
    protected $description = 'Install and configure the Evolution API package';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Installing Evolution API package...');
        $this->newLine();

        // Publish configuration
        $this->publishConfiguration();

        // Publish migrations
        $this->publishMigrations();

        // Run migrations (optional)
        if ($this->confirm('Would you like to run migrations now?', true)) {
            $this->runMigrations();
        }

        // Configure environment
        $this->configureEnvironment();

        $this->newLine();
        $this->info('Evolution API package installed successfully!');
        $this->newLine();

        $this->displayNextSteps();

        return self::SUCCESS;
    }

    /**
     * Publish configuration file.
     */
    protected function publishConfiguration(): void
    {
        $this->comment('Publishing configuration...');

        $params = ['--tag' => 'evolution-api-config'];

        if ($this->option('force')) {
            $params['--force'] = true;
        }

        $this->call('vendor:publish', $params);
    }

    /**
     * Publish migration files.
     */
    protected function publishMigrations(): void
    {
        $this->comment('Publishing migrations...');

        $params = ['--tag' => 'evolution-api-migrations'];

        if ($this->option('force')) {
            $params['--force'] = true;
        }

        $this->call('vendor:publish', $params);
    }

    /**
     * Run database migrations.
     */
    protected function runMigrations(): void
    {
        $this->comment('Running migrations...');
        $this->call('migrate');
    }

    /**
     * Configure environment variables.
     */
    protected function configureEnvironment(): void
    {
        $this->comment('Configuring environment...');

        $envPath = base_path('.env');

        if (! file_exists($envPath)) {
            $this->warn('.env file not found. Please create one and add the Evolution API configuration.');

            return;
        }

        $envContent = file_get_contents($envPath);

        // Check if variables already exist
        if (str_contains($envContent, 'EVOLUTION_API_SERVER_URL')) {
            $this->info('Environment variables already configured.');

            return;
        }

        // Ask for configuration values
        $serverUrl = $this->ask(
            'Enter your Evolution API server URL',
            'http://localhost:8080'
        );

        $apiKey = $this->secret('Enter your Evolution API key (leave blank to skip)');

        $defaultInstance = $this->ask(
            'Enter your default instance name',
            'default'
        );

        // Append to .env file
        $envAdditions = "\n# Evolution API Configuration\n";
        $envAdditions .= "EVOLUTION_API_SERVER_URL={$serverUrl}\n";

        if ($apiKey) {
            $envAdditions .= "EVOLUTION_API_KEY={$apiKey}\n";
        }

        $envAdditions .= "EVOLUTION_API_DEFAULT_INSTANCE={$defaultInstance}\n";

        file_put_contents($envPath, $envContent.$envAdditions);

        $this->info('Environment variables added to .env file.');
    }

    /**
     * Display next steps to the user.
     */
    protected function displayNextSteps(): void
    {
        $this->info('Next steps:');
        $this->newLine();

        $this->line('1. Review and update your <comment>config/evolution-api.php</comment> file');
        $this->line('2. Ensure your <comment>.env</comment> file has the correct Evolution API credentials');
        $this->line('3. Configure webhooks in Evolution API to point to:');
        $this->line('   <comment>'.url('/api/evolution-api/webhook').'</comment>');
        $this->line('4. Test the connection with: <comment>php artisan evolution-api:health</comment>');

        $this->newLine();
        $this->info('Documentation: https://doc.evolution-api.com');
    }
}
