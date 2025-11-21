<?php

namespace Dotlogics\Grapesjs\Tests\Unit;

use Dotlogics\Grapesjs\App\Editor\PluginManager;
use Tests\TestCase;
use Illuminate\Support\Facades\Config;

class PluginManagerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Reset config for each test
        Config::set('laravel-grapesjs.plugins', [
            'default' => [
                'basic_blocks' => true,
                'code_editor' => true,
                'custom_fonts' => [],
            ],
            'custom' => [
                'test-plugin' => 'https://example.com/plugin.js',
                [
                    'name' => 'advanced-plugin',
                    'scripts' => ['https://example.com/advanced.js'],
                    'options' => ['setting' => 'value'],
                    'enabled' => true,
                ],
                [
                    'name' => 'disabled-plugin',
                    'enabled' => false,
                ],
            ],
        ]);
    }

    public function test_get_plugins_loader_options_returns_correct_structure()
    {
        $pluginManager = new PluginManager();

        $options = $pluginManager->getPluginsLoaderOptions();

        $this->assertIsArray($options);
        $this->assertCount(2, $options); // test-plugin and advanced-plugin (disabled one filtered out)

        // Check test-plugin
        $testPlugin = collect($options)->firstWhere('name', 'test-plugin');
        $this->assertNotNull($testPlugin);
        $this->assertEquals('test-plugin', $testPlugin['name']);
        $this->assertEquals(['https://example.com/plugin.js'], $testPlugin['scripts']);
        $this->assertTrue($testPlugin['enabled']);

        // Check advanced-plugin
        $advancedPlugin = collect($options)->firstWhere('name', 'advanced-plugin');
        $this->assertNotNull($advancedPlugin);
        $this->assertEquals('advanced-plugin', $advancedPlugin['name']);
        $this->assertEquals(['https://example.com/advanced.js'], $advancedPlugin['scripts']);
        $this->assertEquals(['setting' => 'value'], $advancedPlugin['options']);
    }

    public function test_is_using_vite_detects_vite_manifest()
    {
        $pluginManager = new PluginManager();

        // Test without manifest
        $this->assertFalse($pluginManager->isUsingVite());

        // Create fake manifest file
        $manifestPath = public_path('build/manifest.json');
        $manifestDir = dirname($manifestPath);

        if (!is_dir($manifestDir)) {
            mkdir($manifestDir, 0755, true);
        }

        file_put_contents($manifestPath, '{}');

        try {
            $this->assertTrue($pluginManager->isUsingVite());
        } finally {
            // Clean up
            if (file_exists($manifestPath)) {
                unlink($manifestPath);
                rmdir($manifestDir);
            }
        }
    }

    public function test_get_module_plugins_returns_plugins_for_vite()
    {
        // Create fake manifest to simulate Vite usage
        $manifestPath = public_path('build/manifest.json');
        $manifestDir = dirname($manifestPath);

        if (!is_dir($manifestDir)) {
            mkdir($manifestDir, 0755, true);
        }

        file_put_contents($manifestPath, '{}');

        try {
            $pluginManager = new PluginManager();
            $modulePlugins = $pluginManager->getModulePlugins();

            $this->assertIsArray($modulePlugins);
            $this->assertCount(2, $modulePlugins); // Same as script plugins when using Vite
        } finally {
            // Clean up
            if (file_exists($manifestPath)) {
                unlink($manifestPath);
                rmdir($manifestDir);
            }
        }
    }

    public function test_get_script_plugins_returns_plugins_for_non_vite()
    {
        $pluginManager = new PluginManager();
        $scriptPlugins = $pluginManager->getScriptPlugins();

        $this->assertIsArray($scriptPlugins);
        $this->assertCount(2, $scriptPlugins); // Same plugins returned for non-Vite
    }

    public function test_plugin_manager_initializes_with_correct_defaults()
    {
        $pluginManager = new PluginManager();

        $this->assertTrue($pluginManager->basicBlocks);
        $this->assertTrue($pluginManager->codeEditor);
        $this->assertIsArray($pluginManager->customFonts);
        $this->assertEmpty($pluginManager->customFonts);
    }

    public function test_plugin_manager_handles_disabled_plugins()
    {
        Config::set('laravel-grapesjs.plugins.custom', [
            [
                'name' => 'enabled-plugin',
                'enabled' => true,
            ],
            [
                'name' => 'disabled-plugin',
                'enabled' => false,
            ],
        ]);

        $pluginManager = new PluginManager();
        $options = $pluginManager->getPluginsLoaderOptions();

        $this->assertCount(1, $options);
        $this->assertEquals('enabled-plugin', $options[0]['name']);
    }
}