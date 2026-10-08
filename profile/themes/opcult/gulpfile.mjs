/**
 * @file
 * OpenCulturas base theme 2.0: gulpfile for compiling SASS.
 * https://css-tricks.com/gulp-for-beginners/
 */

import path from 'node:path';
import gulp from 'gulp';
const {src, dest, watch, series} = gulp;

import * as dartSass from 'sass';
import gulpSass from 'gulp-sass';
const sass = gulpSass(dartSass);

import sourcemaps from 'gulp-sourcemaps';     // Create sass sourcemaps.
import autoprefixer from 'gulp-autoprefixer'; // Adds vendor prefixes to CSS rules.
import { deleteSync } from 'del';             // Delete generated files when needed.
import plumber from 'gulp-plumber';           // Used to catch errors and continue watching.
import svgSprite from "gulp-svg-sprite";      // Build svg-sprite to make referencing SVG icons easier.

// Clean up existing compiled files.
export function cleanCss(done) {
  deleteSync('css/*');
  done();
}

export function cleanSvg(done) {
  deleteSync('sprite/symbol/*');
  done();
}

// Compile sass to css. The keepGoing flag logs Sass errors instead of failing,
// so a typo does not end the watcher. One time builds must fail on errors.
function compileCss(keepGoing) {
  const stream = src('sass/**/*.scss');
  const source = keepGoing
    ? stream.pipe(plumber(function (error) {
      console.log(error.message);
      this.emit('end');
    }))
    : stream;
  return source
    .pipe(sourcemaps.init())
    .pipe(sass.sync({ style: 'expanded' })) //was compressed by default
    .pipe(autoprefixer())
    // Sources outside sass/ come back as the checkout path without its leading
    // slash. Make them relative so the map does not depend on where it is built.
    .pipe(sourcemaps.write('./', {
      mapSources: (sourcePath) => {
        const absolute = path.resolve('/', sourcePath);
        return absolute.startsWith(process.cwd() + path.sep)
          ? path.relative(path.resolve('css'), absolute)
          : sourcePath;
      },
    }))
    .pipe(gulp.dest('css'));
}

export function css() {
  return compileCss(false);
}

function cssKeepGoing() {
  return compileCss(true);
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
  watch('sass/**/*.scss', series(cssKeepGoing));
  watch('templates/**/*.scss', series(cssKeepGoing));
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
