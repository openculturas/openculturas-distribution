/**
 * Runs as the "prebuild" lifecycle script before `npm run build`.
 * Fails fast with a clear message when node_modules doesn't match the
 * current package-lock.json, instead of letting gulp fail on missing or
 * mismatched dependencies.
 */

const crypto = require('crypto');
const fs = require('fs');
const path = require('path');

const lockFilePath = path.resolve(__dirname, '..', 'package-lock.json');
const markerFilePath = path.resolve(
  __dirname,
  '..',
  'node_modules',
  '.install-hash',
);

const currentHash = crypto
  .createHash('sha256')
  .update(fs.readFileSync(lockFilePath))
  .digest('hex');
const installedHash = fs.existsSync(markerFilePath)
  ? fs.readFileSync(markerFilePath, 'utf8').trim()
  : null;

if (currentHash !== installedHash) {
  // eslint-disable-next-line no-console -- this script's only purpose is to report the mismatch before the build fails
  console.error(
    '\npackage-lock.json has changed since the last `npm ci` / `npm install` in this theme.',
  );
  // eslint-disable-next-line no-console -- this script's only purpose is to report the mismatch before the build fails
  console.error(
    'Run `npm ci` in profile/themes/openculturas_base, then try the build again.\n',
  );
  process.exit(1);
}
