<?php

namespace Dotlogics\Grapesjs\App\Traits;

use Dotlogics\Grapesjs\App\Editor\Config;
use Illuminate\Http\Request;

trait EditorTrait{

	protected function show_gjs_editor(Request $request, $model): \Illuminate\View\View
		$editorConfig = app(Config::class)->initialize($model);
		
		return view('laravel-grapesjs::editor', compact('editorConfig', 'model'));
	}

	protected function store_gjs_data(Request $request, $model): \Illuminate\Http\Response
	{
		$validated = $request->validate([
			'laravel-grapesjs-components' => 'nullable|string',
			'laravel-grapesjs-styles' => 'nullable|string',
			'laravel-grapesjs-css' => 'nullable|string',
			'laravel-grapesjs-html' => 'nullable|string',
		]);

		$model->gjs_data = [
	        'components' => $validated['laravel-grapesjs-components'],
	        'styles' => $validated['laravel-grapesjs-styles'],
	        'css' => $validated['laravel-grapesjs-css'],
	        'html' => $validated['laravel-grapesjs-html'],
	    ];

	    $model->save();

	    return response()->noContent(200);
	}
}
