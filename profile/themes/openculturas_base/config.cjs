/**
 * Browsersync config.
 * If you are using another local host for development, change the line below in config.js to your host.
 *
 * @type {{browserSync: {hostname: string}}}
 */
exports.default = {
  browserSync: {
    hostname: 'http://openculturas.ddev.site/',
    // Disable for offline development.
    online: true,
    // Open ab new browser window at start
    open: false,
  }
};