import { readFile } from 'node:fs';
import process from 'node:process';
// eslint-disable-next-line import/no-extraneous-dependencies
import chalk from 'chalk';

import log from './log';
import compile from './compile';

export default function check(filePath) {
  log(`'${filePath}' is being checked.`);
  // Transform the file.
  compile(filePath, (code) => {
    const fileName = filePath.slice(0, -7);
    readFile(`${fileName}.js`, function read(err, data) {
      if (err) {
        log(chalk.red(err));
        process.exitCode = 1;
        return;
      }
      if (code !== data.toString()) {
        log(chalk.red(`'${filePath}' is not updated.`));
        process.exitCode = 1;
      }
    });
  });
}
