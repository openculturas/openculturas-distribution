'use-strict';

const { src, dest, watch, series, parallel} = require("gulp");
const gulp = require('gulp');

const dartSass = require("sass");
const gulpSass = require("gulp-sass");
const sass = gulpSass(dartSass);

const fs = require("fs");
const rename = require("gulp-rename");
const sassGlob = require("gulp-sass-glob");
const autoprefixer = require("gulp-autoprefixer");
const sourcemaps = require("gulp-sourcemaps");
const concat = require("gulp-concat");
const clean = require("gulp-clean-css");
const minify = require("gulp-minify");
const browserSync = require("browser-sync").create();
var config;

function buildSass() {
  return gulp.src(['./scss_config/base.scss'])
    .pipe(sassGlob())
    .pipe(sass().on('error', sass.logError))
    .pipe(autoprefixer())
    .pipe(concat('style.css'))
    .pipe(clean())
    .pipe(gulp.dest('./css'))
    .pipe(browserSync.stream());
}

function buildDevSass() {
  return gulp.src(['./scss_config/base.scss'])
    .pipe(sassGlob())
    .pipe(sourcemaps.init())
    .pipe(sass().on('error', sass.logError))
    .pipe(autoprefixer())
    .pipe(concat('style.css'))
    .pipe(sourcemaps.write())
    .pipe(gulp.dest('./css'))
    .pipe(browserSync.stream());
}

function buildWysiwygSass() {
  return gulp.src(['./scss_config/wysiwyg.scss'])
    .pipe(sassGlob())
    .pipe(sass().on('error', sass.logError))
    .pipe(autoprefixer())
    .pipe(concat('wysiwyg.css'))
    .pipe(clean())
    .pipe(gulp.dest('./css'));
}

function collectJs() {
  return gulp.src(['./templates/**/**.js'])
    .pipe(concat('openculturas-base.js'))
    .pipe(minify({
      ext: {
        min: '.js'
      },
      noSource: true
    }))
    .pipe(gulp.dest('./js'));
}

function copyConfig (done){
  try {
    fs.accessSync('./config.js');
    console.log('config.js found :)')
    done();
  }
  catch(e){
    console.log('config.js no found :(');
    console.log('No problem, I will create it four you :)');
    return gulp.src('./example.config.js')
      .pipe(rename('config.js'))
      .pipe(gulp.dest('./css'));
  }
}

function readConfig(done) {
  config = require("./config.js");
  done();
}

function browsersync() {
  browserSync.init({
    proxy: config.browserSync.hostname,
    reloadDelay: 300
  });
  gulp.watch(['./scss_config/*', './templates/**/*.scss'], gulp.series('dev'));
  gulp.watch(["./templates/**/*.js"], gulp.series('js', browserSync.reload));
  gulp.watch("./templates/**/*.html.twig", browserSync.reload);
}

gulp.task('sass', series(buildSass, buildWysiwygSass));
gulp.task('js', series(collectJs));
gulp.task('dev', series(buildDevSass, buildWysiwygSass, collectJs));
gulp.task('build', series(buildSass, buildWysiwygSass, collectJs));
gulp.task('initConfig', series(copyConfig, readConfig));
gulp.task('watch', series('initConfig', 'dev', browsersync));

