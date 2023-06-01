/**
 * @file
 *
 * Provides the build:js command to compile *.es6.js files to ES5.
 *
 * Run build:js with --file to only parse a specific file. Using the --check
 * flag build:js can be run to check if files are compiled correctly.
 * @example <caption>Only process misc/drupal.es6.js and
 *   misc/drupal.init.es6.js</caption yarn run build:js -- --file
 *   misc/drupal.es6.js --file misc/drupal.init.es6.js
 * @example <caption>Check if all files have been compiled correctly</caption
 * yarn run build:js -- --check
 *
 * @internal This file is part of the core javascript build process and is only
 * meant to be used in that context.
 */

import path from 'node:path';
import process from 'node:process';
// eslint-disable-next-line import/no-extraneous-dependencies
import { glob } from 'glob';
// eslint-disable-next-line import/no-extraneous-dependencies
import minimist from 'minimist';
import changeOrAdded from './changeOrAdded';
import check from './check';

// eslint-disable-next-line import/no-extraneous-dependencies
const argv = minimist(process.argv.slice(2));

// Allow the caller to override the root directory.
const dir = argv.dir || './';

// Match only on .es6.js files.
const fileMatch = `${dir}/**/*.es6.js`;
// Ignore everything in node_modules
const globOptions = {
  ignore: `${dir}/**/node_modules/**`,
};
const processFiles = (error, filePaths) => {
  if (error) {
    process.exitCode = 1;
  }
  // Process all the found files.
  let callback = changeOrAdded;
  if (argv.check) {
    callback = check;
  }
  filePaths.map((filePath) => path.resolve(filePath)).forEach(callback);
};

if (argv.file) {
  processFiles(null, [].concat(argv.file));
} else {
  processFiles(null, glob(fileMatch, globOptions));
}
process.exitCode = 0;
