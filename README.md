# Laravel Grapesjs Editor

This package provides an easy way to integrate [GrapesJS](https://grapesjs.com/) into your Laravel project.

## Requirements

- Laravel 11.x or 12.x
- PHP 8.2.0 or higher
- curl 7.34.0 or higher
- Node.js 16+ and NPM

## Vite Integration (Laravel 11+)

If your application uses Vite (default for Laravel 11+), you need to include the package assets in your Vite build:

### 1. Update your `vite.config.js`

```javascript
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
  plugins: [
    laravel({
      input: [
        'resources/css/app.css',
        'resources/js/app.js',
        // Add package assets
        'vendor/laravel-grapesjs/src/resources/scss/gjs.scss',
        'vendor/laravel-grapesjs/src/resources/js/index.js',
      ],
    }),
  ],
});
```

### 2. Update your Blade template

Instead of using the package's default template, create your own and include:

```blade
@vite([
    'vendor/laravel-grapesjs/src/resources/scss/gjs.scss',
    'vendor/laravel-grapesjs/src/resources/js/index.js'
])
```

## Migrating from Non-Experimental Versions

If you're upgrading from a non-experimental version to the Vite-integrated version, you'll need to migrate your custom plugins. You can do this manually or use the automated migration command.

### Automated Migration (Recommended)

Run the migration command to automatically convert your plugin setup:

```bash
php artisan grapesjs:migrate-plugins
```

This command will:

- Move custom plugin files from `public/js/` to `resources/js/`
- Update your `resources/js/app.js` with proper imports
- Modify your `vite.config.js` to include plugin files
- Remove plugin scripts from `config/laravel-grapesjs.php`

### Manual Migration

If you prefer to migrate manually:

1. **Move custom plugin files**

   ```bash
   # Move from public/js/ to resources/js/
   mv public/js/custom-blocks.js resources/js/custom-blocks.js
   ```

2. **Import plugins in your main app file**

   ```javascript
   // resources/js/app.js
   import './custom-blocks';
   ```

3. **Update vite.config.js**

   ```javascript
   input: [
     'resources/css/app.css',
     'resources/js/app.js',
     'resources/js/custom-blocks.js', // Add this
     // ... other inputs
   ];
   ```

4. **Remove plugin scripts from config**

   ```php
   // config/laravel-grapesjs.php
   'plugins' => [
       'custom' => [
           // Remove script-based plugin definitions
           // 'my-plugin' => 'https://example.com/plugin.js',
       ],
   ],
   ```

5. **Update plugin registration**
   Instead of defining plugins in config, register them in your JavaScript:

   ```javascript
   // resources/js/custom-blocks.js
   import grapesjs from 'grapesjs';

   // Register your plugin
   grapesjs.plugins.add('my-plugin', function (editor, options) {
     // Plugin implementation
   });
   ```

### Troubleshooting

- **Plugins not loading**: Ensure all custom plugins are imported in `resources/js/app.js`
- **Vite build errors**: Check that all plugin files are included in `vite.config.js` input array
- **Mixed loading**: The package supports both script-based (legacy) and module-based loading

## Installation

> `composer require jd-dotlogics/laravel-grapesjs`

## Publish files & migrate

> `php artisan vendor:publish --tag="laravel-grapesjs"`

> `php artisan migrate`

## Getting started

1. Add 'gjs_data' column to the model's database table (e.g Page), for which you are going to use the editor.

2. Implement Editable Interface and use the EditableTrait trait for the Model class

```php
use Illuminate\Database\Eloquent\Model;
use Dotlogics\Grapesjs\App\Traits\EditableTrait;
use Dotlogics\Grapesjs\App\Contracts\Editable;

class Page extends Model implements Editable
{
    use EditableTrait;

    ...
}
```

3. Next Create a Route for editor

```php
Route::get('pages/{page}/editor', 'PageController@editor');

```

4. In your controller, use the EditorTrait and add the editor method

```php
<?php

namespace App\Http\Controllers;

use App\Models\Page;
use Illuminate\Http\Request;
use Dotlogics\Grapesjs\App\Traits\EditorTrait;

class PageController extends Controller
{
    use EditorTrait;

    ...


    public function editor(Request $request, Page $page)
    {
        return $this->show_gjs_editor($request, $page);
    }

    ...
}


```

5. Open this route /pages/:page_id/editor (where the :page_id is the id of your model)

## Placeholders

Placeholders are like short-code in wordpress. The syntax of placeholder is

> `[[This-Is-Placeholder]]`

Create a file named "this-is-placeholder.blade.php" in "/resources/views/vendor/laravel-grapesjs/placeholders" directory.

The placeholder will be replaced by the content of the relative blade file "this-is-placeholder.blade.php"

## Templates

You can create global templates (or blocks) in the "/resources/views/vendor/laravel-grapesjs/templates" directory. And the templates/blocks will be available in the block section of editor. You can also create model specific templates/blocks by defining getTemplatesPath/getGjsBlocksPath in model

```php
public function getTemplatesPath(){ return 'pages_templates'; }
```

This will look for templates under "laravel-grapesj::pages_templates" directory.

You can also return null from these methods to hide templates/blocks for any model.

## Display output

The "Editable" model (e.g. Page) will have two public properties, css and html. In your blade file you can use these properties to display the content.

```blade
<style type="text/css">
 {!! $page->css !!}
</style>

{!! $page->html !!}

```

Thank you for using.
