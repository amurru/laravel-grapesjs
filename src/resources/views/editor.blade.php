<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="csrf-token" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit {{ $model->editor_page_title }}</title>

    {{-- Load Vite assets if any are detected --}}
    @php
        $viteAssets = [];
        $regularAssets = ['styles' => [], 'scripts' => []];

        foreach ($editorConfig->getStyles() as $asset) {
            if (str_starts_with($asset, 'src/resources/')) {
                $viteAssets[] = $asset;
            } else {
                $regularAssets['styles'][] = $asset;
            }
        }

        foreach ($editorConfig->getScripts() as $asset) {
            if (str_starts_with($asset, 'src/resources/')) {
                $viteAssets[] = $asset;
            } else {
                $regularAssets['scripts'][] = $asset;
            }
        }
    @endphp

    @if(!empty($viteAssets))
        @vite($viteAssets)
    @endif

    @foreach ($regularAssets['styles'] as $style)
        <link rel="stylesheet" href="{{ asset($style) }}">
    @endforeach

    <style>
        * {
            margin: 0;
            padding: 0;
        }
    </style>
    <script>
        window.editorConfig = @json($editorConfig ?? []);

        Object.defineProperty(window, 'grapesjs', {
            value: {
                plugins: {
                    plugins: [],

                    /**
                     * Add new plugin. Plugins could not be overwritten
                     * @param {string} id Plugin ID
                     * @param {Function} plugin Function which contains all plugin logic
                     * @return {Function} The plugin function
                     * @example
                     * PluginManager.add('some-plugin', function(editor){
                     *   editor.Commands.add('new-command', {
                     *     run:  function(editor, senderBtn){
                     *       console.log('Executed new-command');
                     *     }
                     *   })
                     * });
                     */
                    add(id, plugin) {
                        if (this.plugins[id]) {
                            return this.plugins[id];
                        }

                        this.plugins[id] = plugin;

                        return plugin;
                    },

                    /**
                     * Returns plugin by ID
                     * @param  {string} id Plugin ID
                     * @return {Function|undefined} Plugin
                     * @example
                     * var plugin = PluginManager.get('some-plugin');
                     * plugin(editor);
                     */
                    get(id) {
                        return this.plugins[id];
                    },

                    /**
                     * Returns object with all plugins
                     * @return {Object}
                     */
                    getAll() {
                        return this.plugins;
                    },
                }
            }
        })
    </script>
</head>

<body>
    <div id="{{ str_replace('#', '', $editorConfig->container ?? 'editor') }}"></div>
    
    @foreach ($regularAssets['scripts'] as $script)
        <script src="{{ asset($script) }}"></script>
    @endforeach
</body>
</html>