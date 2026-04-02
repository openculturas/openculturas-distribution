/**
 * @file
 * OpenCulturas Sub theme: gulpfile for compiling SASS.
 *
 * Based on opcult base theme's gulpfile.
 * Includes loadPaths to access opcult's SASS abstracts and base partials.
 *
 * @see ../opcult/gulpfile.mjs
 */

import gulp from 'gulp';
const {src, dest, watch, series} = gulp;

import * as dartSass from 'sass';
import gulpSass from 'gulp-sass';
const sass = gulpSass(dartSass);

import sourcemaps from 'gulp-sourcemaps';     // Create sass sourcemaps.
import autoprefixer from 'gulp-autoprefixer'; // Adds vendor prefixes to CSS rules.
import { deleteSync } from 'del';             // Delete generated files when needed.
import plumber from 'gulp-plumber';           // Used to catch errors and continue build.
import svgSprite from "gulp-svg-sprite";      // Build svg-sprite to make referencing SVG icons easier.

/**
 * Path to opcult base theme's SASS directory (relative to this gulpfile).
 */
const opcultSassPath = '../opcult/sass';

// Clean up existing compiled files.
export function cleanCss(done) {
  deleteSync('css/*');
  done();
}

export function cleanSvg(done) {
  deleteSync('sprite/symbol/*');
  done();
}

// Compile sass to css.
export function css() {
  return src('sass/**/*.scss')
    .pipe(plumber(function (error) {
      console.log(error.message);
      this.emit('end');
    }))
    .pipe(sourcemaps.init())
    .pipe(sass.sync({
      style: 'expanded',
      loadPaths: [opcultSassPath],
    }))
    .pipe(autoprefixer())
    .pipe(sourcemaps.write('./'))
    .pipe(gulp.dest('css'));
}

// Build SVG sprite.
import debug from 'gulp-debug';

export function svg() {
  return src('sprite/svg/*.svg')
    .pipe(debug({ title: 'input:' }))
    .pipe(
      svgSprite({
        mode: {
          stack: {
            dest: "symbol",
            sprite: '../oc-sprite.svg',
            inline: false,
            bust: false,
            render: {
              scss: {
                dest: '_sprite.scss',
                template: './sprite/tpl/scss-template.txt',
              }
            }
          }
        }
      })
    )
    .pipe(debug({ title: 'output:' }))
    .pipe(dest('sprite'));
}

// Watch sass files & rebuild on any changes.
export function watchFiles() {
  watch('sass/**/*.scss', series('css'));
}

// One time build process.
export function build(done) {
  series('cleanCss', 'css')(done);
}

// Add new export only for svg
// One time build process.
export function buildSvg(done) {
  series('cleanSvg', 'svg')(done);
}

export { watchFiles as watch };

