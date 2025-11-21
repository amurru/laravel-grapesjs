export default (editor, opts = {}) => {
  const options = {
    fonts: [],
    ...opts,
  };

  let fonts = options.fonts;

  editor.on('load', () => {
    try {
      if (!fonts || !Array.isArray(fonts)) {
        fonts = [];
      }

      fonts.push({
        value: '',
        name: 'Unset',
        prepend: true,
      });

      let fontProperty = editor.StyleManager.getProperty('typography', 'font-family');
      if (!fontProperty) return;

      let options = fontProperty.getOptions();

      fonts.forEach((font) => {
        if (typeof font === 'string' || font instanceof String) {
          font = {
            name: font,
            value: font,
          };
        }

        if (typeof font.value === 'undefined') {
          // Invalid font configuration, skip it
          return;
        }

        options[font.prepend ? 'unshift' : 'push']({
          id: font.value,
          label: font.name || font.value,
        });
      });

      fontProperty.setOptions(options);
      editor.StyleManager.render();
    } catch (e) {
      console.error('Error loading custom fonts:', e);
    }
  });
};
