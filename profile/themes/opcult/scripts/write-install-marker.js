'use strict';

/**
 * Runs as the "postinstall" lifecycle script after `npm ci`/`npm install`.
 * Stamps node_modules with a hash of package-lock.json so `check-install-marker.js`
 * can later tell whether an install is still current.
 */

const crypto = require('crypto');
const fs = require('fs');
const path = require('path');

const lockFilePath = path.resolve(__dirname, '..', 'package-lock.json');
const markerFilePath = path.resolve(__dirname, '..', 'node_modules', '.install-hash');

const hash = crypto.createHash('sha256').update(fs.readFileSync(lockFilePath)).digest('hex');
fs.writeFileSync(markerFilePath, hash);
