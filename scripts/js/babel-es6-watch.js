/**
 * @file
 *
 * Watch changes to *.es6.js files and compile them to ES5 during development.
 */

import { stat, unlink } from 'node:fs';
// eslint-disable-next-line import/no-extraneous-dependencies
import chokidar from 'chokidar';
import changeOrAdded from './changeOrAdded';
import log from './log';

// Match only on .es6.js files.
const fileMatch = './**/*.es6.js';
// Ignore everything in node_modules
const watcher = chokidar.watch(fileMatch, {
  ignoreInitial: true,
  ignored: './node_modules/**',
});

const unlinkHandler = (err) => {
  if (err) {
    log(err);
  }
};

// Watch for filesystem changes.
watcher
  .on('add', changeOrAdded)
  .on('change', changeOrAdded)
  .on('unlink', (filePath) => {
    const fileName = filePath.slice(0, -7);
    stat(`${fileName}.js`, () => {
      unlink(`${fileName}.js`, unlinkHandler);
    });
  })
  .on('ready', () => log(`Watching '${fileMatch}' for changes.`));
