<?php

namespace Dotlogics\Grapesjs\App\Editor;

use Dotlogics\Grapesjs\App\Contracts\Editable;

class Config
{
    //Global styles and scripts
    public array $styles = [];
    public array $scripts = [];

    // General
    public bool $exposeApi = false;
    public string $container = '#editor';
    public bool $fromElement = false;
    public string $height = "100vh";
    public string $width = '100%';
    public bool $forceClass = true;

    //Default Content
    public array $components;
    public array $style;

    // Management
    public Canvas $canvas;
    public PluginManager $pluginManager;
    public StorageManager $storageManager;
    public AssetManager $assetManager;
    public StyleManager $styleManager;

    function __construct(){
        $this->exposeApi = config('laravel-grapesjs.expose_api', false);
        $this->forceClass = config('laravel-grapesjs.force_class', false);
    }

    public function initialize(Editable $editable)
    {
        $pluginManager = app(PluginManager::class, ['templates_url' => $editable->templates_url]);
        $assetManager = app(AssetManager::class);
        $storageManager = app(StorageManager::class, ['save_url' => $editable->store_url]);
        $styleManager = app(StyleManager::class);

        $canvas = app(Canvas::class, ['styles' => $editable->style_sheet_links, 'scripts' => $editable->script_links]);
        
        $this->pluginManager = $pluginManager;
        $this->assetManager = $assetManager;
        $this->canvas = $canvas;
        $this->storageManager = $storageManager;
        $this->styleManager = $styleManager;

        $this->components = $editable->components; 
        $this->style = $editable->styles;

        $this->initStylesAndScripts();
        
        // dd($this->toArray());
        return $this;
    }
    
    protected function initStylesAndScripts()
    {
        collect(['styles', 'scripts'])
            ->each(function($type){
                $items = config("laravel-grapesjs.{$type}", []);
                $items = collect($items)->filter()->values()->toArray();

                $this->{$type} = array_map('url', $items);
            });
    }

    public function getStyles()
    {
        $extraStyles = $this->pluginManager ? $this->pluginManager->getPluginStyles() : [];

        // Convert package asset references to Vite-compatible paths
        $processedStyles = array_map(function($style) {
            // Convert laravel-grapesjs::assets/ paths to Vite source paths
            if (str_starts_with($style, 'laravel-grapesjs::assets/')) {
                $assetPath = str_replace('laravel-grapesjs::assets/', 'src/resources/', $style);
                // Remove .scss extension for CSS assets
                $assetPath = str_replace('.scss', '.css', $assetPath);
                return $assetPath;
            }
            return $style;
        }, $this->styles);

        return [...$extraStyles, ...$processedStyles];
    }

    public function getScripts()
    {
        $extraScripts = $this->pluginManager ? $this->pluginManager->getPluginScripts() : [];

        // Convert package asset references to Vite-compatible paths
        $processedScripts = array_map(function($script) {
            // Convert laravel-grapesjs::assets/ paths to Vite source paths
            if (str_starts_with($script, 'laravel-grapesjs::assets/')) {
                $assetPath = str_replace('laravel-grapesjs::assets/', 'src/resources/', $script);
                return $assetPath;
            }
            return $script;
        }, $this->scripts);

        return [...$extraScripts, ...$processedScripts];
    }

    /**
     * Check if the application is using Vite
     */
    protected function isUsingVite(): bool
    {
        return file_exists(public_path('build/manifest.json')) ||
               file_exists(public_path('vendor/laravel-grapesjs/manifest.json'));
    }

    /**
     * Get Vite-resolved asset path
     */
    protected function resolveViteAsset(string $asset): string
    {
        // For package assets, we need to check if the application has included them in their Vite build
        // If not, fall back to published assets
        $manifestPath = public_path('build/manifest.json');

        if (file_exists($manifestPath)) {
            $manifest = json_decode(file_get_contents($manifestPath), true);
            $viteKey = 'vendor/laravel-grapesjs/' . $asset;

            if (isset($manifest[$viteKey])) {
                return asset('build/' . $manifest[$viteKey]['file']);
            }
        }

        // Fallback to published assets
        return asset('vendor/laravel-grapesjs/assets/' . $asset);
    }

    public function toJson()
    {
        return json_encode($this);
    }
    
    public function __toString()
    {
        return $this->toJson();
    }

    public function toArray()
    {
    	return json_decode($this->toJson(), true);
    }
}
