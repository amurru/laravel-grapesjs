<?php

namespace Dotlogics\Grapesjs\App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MigratePluginsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'grapesjs:migrate-plugins {--rollback : Rollback the migration}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate custom plugins from script-based loading to ES6 module loading for Vite compatibility';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($this->option('rollback')) {
            return $this->rollback();
        }

        $this->info('Starting Laravel GrapesJS plugin migration...');

        // Check if migration has already been done
        if ($this->isAlreadyMigrated()) {
            $this->warn('Migration appears to have been completed already. Use --rollback to undo.');
            return;
        }

        // Step 1: Find plugin files in public/js/
        $publicPlugins = $this->findPublicPlugins();
        if (empty($publicPlugins)) {
            $this->info('No plugins found in public/js/ to migrate.');
            return;
        }

        $this->info('Found ' . count($publicPlugins) . ' plugin files to migrate.');

        // Step 2: Create backup
        $this->createBackup();

        // Step 3: Move plugin files
        $movedFiles = $this->movePluginFiles($publicPlugins);

        // Step 4: Update app.js with imports
        $this->updateAppJs($movedFiles);

        // Step 5: Update vite.config.js
        $this->updateViteConfig($movedFiles);

        // Step 6: Update config file
        $this->updateConfigFile();

        $this->info('Migration completed successfully!');
        $this->info('Please run "npm install && npm run build" to rebuild your assets.');
    }

    /**
     * Rollback the migration.
     */
    protected function rollback()
    {
        $this->info('Rolling back Laravel GrapesJS plugin migration...');

        $backupPath = storage_path('grapesjs-migration-backup');
        if (!File::exists($backupPath)) {
            $this->error('No backup found. Cannot rollback.');
            return;
        }

        // Restore files from backup
        $this->restoreFromBackup($backupPath);

        // Clean up backup
        File::deleteDirectory($backupPath);

        $this->info('Rollback completed successfully!');
    }

    /**
     * Check if migration has already been done.
     */
    protected function isAlreadyMigrated()
    {
        $appJsPath = resource_path('js/app.js');
        if (!File::exists($appJsPath)) {
            return false;
        }

        $content = File::get($appJsPath);
        return Str::contains($content, '// GrapesJS plugins import');
    }

    /**
     * Find plugin files in public/js/.
     */
    protected function findPublicPlugins()
    {
        $publicJsPath = public_path('js');
        if (!File::exists($publicJsPath)) {
            return [];
        }

        $files = File::allFiles($publicJsPath);
        return collect($files)
            ->filter(function($file) {
                return $file->getExtension() === 'js';
            })
            ->map(function($file) {
                return $file->getPathname();
            })
            ->toArray();
    }

    /**
     * Create backup of current state.
     */
    protected function createBackup()
    {
        $backupPath = storage_path('grapesjs-migration-backup');
        $this->info('Creating backup...');

        // Backup config
        $configPath = config_path('laravel-grapesjs.php');
        if (File::exists($configPath)) {
            File::copy($configPath, $backupPath . '/config.php');
        }

        // Backup app.js
        $appJsPath = resource_path('js/app.js');
        if (File::exists($appJsPath)) {
            File::copy($appJsPath, $backupPath . '/app.js');
        }

        // Backup vite.config.js
        $viteConfigPath = base_path('vite.config.js');
        if (File::exists($viteConfigPath)) {
            File::copy($viteConfigPath, $backupPath . '/vite.config.js');
        }

        // Backup public/js directory
        $publicJsPath = public_path('js');
        if (File::exists($publicJsPath)) {
            File::copyDirectory($publicJsPath, $backupPath . '/public-js');
        }
    }

    /**
     * Move plugin files from public/js/ to resources/js/.
     */
    protected function movePluginFiles($pluginFiles)
    {
        $resourcesJsPath = resource_path('js');
        File::ensureDirectoryExists($resourcesJsPath);

        $movedFiles = [];

        foreach ($pluginFiles as $filePath) {
            $fileName = basename($filePath);
            $newPath = $resourcesJsPath . '/' . $fileName;

            File::move($filePath, $newPath);
            $movedFiles[] = $fileName;

            $this->info("Moved: {$fileName}");
        }

        return $movedFiles;
    }

    /**
     * Update resources/js/app.js with plugin imports.
     */
    protected function updateAppJs($movedFiles)
    {
        $appJsPath = resource_path('js/app.js');

        if (!File::exists($appJsPath)) {
            // Create basic app.js if it doesn't exist
            $content = "import './bootstrap';\n\n";
        } else {
            $content = File::get($appJsPath);
        }

        // Add plugin imports
        $imports = "// GrapesJS plugins import\n";
        foreach ($movedFiles as $file) {
            $importName = Str::before($file, '.js');
            $imports .= "import './{$importName}';\n";
        }
        $imports .= "\n";

        // Insert imports at the beginning
        $content = $imports . $content;

        File::put($appJsPath, $content);
        $this->info('Updated resources/js/app.js with plugin imports');
    }

    /**
     * Update vite.config.js to include plugin files.
     */
    protected function updateViteConfig($movedFiles)
    {
        $viteConfigPath = base_path('vite.config.js');

        if (!File::exists($viteConfigPath)) {
            $this->warn('vite.config.js not found. Please manually add plugin files to your Vite input.');
            return;
        }

        $content = File::get($viteConfigPath);

        // Look for input array
        if (!preg_match('/input:\s*\[([^\]]*)\]/s', $content, $matches)) {
            $this->warn('Could not find input array in vite.config.js. Please manually add plugin files.');
            return;
        }

        $inputArray = $matches[1];

        // Add plugin files to input array
        $pluginInputs = [];
        foreach ($movedFiles as $file) {
            $pluginInputs[] = "        'resources/js/{$file}'";
        }

        // Insert before closing bracket
        $newInputArray = rtrim($inputArray, "\n\r\t ,") . ",\n" . implode(",\n", $pluginInputs) . "\n    ";

        $content = str_replace($inputArray, $newInputArray, $content);

        File::put($viteConfigPath, $content);
        $this->info('Updated vite.config.js with plugin files');
    }

    /**
     * Update config file to remove script-based plugins.
     */
    protected function updateConfigFile()
    {
        $configPath = config_path('laravel-grapesjs.php');

        if (!File::exists($configPath)) {
            return;
        }

        $content = File::get($configPath);

        // Remove custom plugins section or make it empty
        $content = preg_replace(
            "/'custom'\s*=>\s*\[[^\]]*\],/",
            "'custom' => [],",
            $content
        );

        File::put($configPath, $content);
        $this->info('Updated config/laravel-grapesjs.php to remove script-based plugins');
    }

    /**
     * Restore files from backup.
     */
    protected function restoreFromBackup($backupPath)
    {
        // Restore config
        $configBackup = $backupPath . '/config.php';
        if (File::exists($configBackup)) {
            File::copy($configBackup, config_path('laravel-grapesjs.php'));
        }

        // Restore app.js
        $appJsBackup = $backupPath . '/app.js';
        if (File::exists($appJsBackup)) {
            File::copy($appJsBackup, resource_path('js/app.js'));
        }

        // Restore vite.config.js
        $viteConfigBackup = $backupPath . '/vite.config.js';
        if (File::exists($viteConfigBackup)) {
            File::copy($viteConfigBackup, base_path('vite.config.js'));
        }

        // Restore public/js directory
        $publicJsBackup = $backupPath . '/public-js';
        if (File::exists($publicJsBackup)) {
            $publicJsPath = public_path('js');
            if (File::exists($publicJsPath)) {
                File::deleteDirectory($publicJsPath);
            }
            File::copyDirectory($publicJsBackup, $publicJsPath);
        }
    }
}