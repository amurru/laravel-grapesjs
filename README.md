# Laravel Grapesjs Editor

This package provide an easy way to integrate [GrapesJS](https://grapesjs.com/) into your Laravel project.

## Requirements

- Laravel 11.x or 12.x
- PHP 8.2.0 or higher
- curl 7.34.0 or higher
- Node.js 16+ and NPM

## Vite Integration (Laravel 11+)

If your application uses Vite (default for Laravel 11+), you need to include the package assets in your Vite build:

### 1. Update your `vite.config.js`

```javascript
import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";

export default defineConfig({
  plugins: [
    laravel({
      input: [
        "resources/css/app.css",
        "resources/js/app.js",
        // Add package assets
        "vendor/laravel-grapesjs/src/resources/scss/gjs.scss",
        "vendor/laravel-grapesjs/src/resources/js/index.js",
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

Placeholders are like short-code in wordpress. The synax of placeholder is

> `[[This-Is-Placeholder]]`

Create a file named "this-is-placeholder.blade.php" in "/resources/views/vendor/laravel-grapesjs/placeholders" directory.

The the placeholder will be replaced by the content of the relative blade file "this-is-placeholder.blade.php"

## Templates

You can create global templates (or blocks) in the "/resources/views/vendor/laravel-grapesjs/templates" directory. And the templates/blocks will be availabe in the block section of edittor. You can also create model specific templates/blocks by defining getTemplatesPath/getGjsBlocksPath in model

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
