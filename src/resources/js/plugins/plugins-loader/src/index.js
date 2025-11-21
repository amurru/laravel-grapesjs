export default (editor, plugins = []) => {
  if (!plugins || !Array.isArray(plugins) || !plugins.length) return;

  plugins.forEach((plugin) => {
    try {
      let callback = null;

      // First try to get from the grapesjs plugin registry (for ES6 modules)
      if (window.grapesjs && window.grapesjs.plugins) {
        callback = window.grapesjs.plugins.get(plugin.name);
      }

      // Fallback to global window object (for script-loaded plugins)
      if (!callback) {
        callback = (window[plugin.name] || {}).default || window[plugin.name];
      }

      // Additional fallback: check if plugin is registered as a global function
      if (!callback && typeof window[plugin.name] === 'function') {
        callback = window[plugin.name];
      }

      if (!callback) {
        console.warn(
          `Plugin '${plugin.name}' not found. Make sure it's properly imported or loaded.`
        );
        return;
      }

      // Initialize the plugin
      if (typeof callback === 'function') {
        callback(editor, plugin.options || {});
      } else {
        console.error(`Plugin '${plugin.name}' is not a function.`);
      }
    } catch (e) {
      console.error(`Error loading plugin '${plugin.name}':`, e);
    }
  });
};
