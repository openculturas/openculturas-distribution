import { writeFile } from 'node:fs';
import log from './log';
import compile from './compile';

export default function changeOrAdded(filePath) {
  log(`'${filePath}' is being processed.`);
  // Transform the file.
  compile(filePath, function write(code) {
    const fileName = filePath.slice(0, -7);
    // Write the result to the filesystem.
    writeFile(`${fileName}.js`, code, () => {
      log(`'${filePath}' is finished.`);
    });
  });
}
