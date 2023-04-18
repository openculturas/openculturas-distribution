const
  { series, parallel } = require('gulp'),
  gulp = require('gulp'),
  dartSass = require('sass'),
  gulpSass = require('gulp-sass'),
  sass = gulpSass(dartSass),
  fs = require('fs'),
  rename = require('gulp-rename'),
  sassGlob = require('gulp-sass-glob'),
  autoprefixer = require('gulp-autoprefixer'),
  sourcemaps = require('gulp-sourcemaps'),
  concat = require('gulp-concat'),
  environments = require('gulp-environments'),
  browserSync = require('browser-sync').create(),
  development = environments.development,
  production = environments.production;
let config;

const paths = {
  styles: {
    src: [
      './scss_config/style.scss',
      './scss_config/wysiwyg.scss',
    ],
    dest: 'css/',
    watch: [
      './scss_config/**/*.scss',
      './templates/**/*.scss',
    ],
  },
  scripts: {
    src: './templates/**/*.js',
    dest: {
      dir: './js',
      filename: 'openculturas-base.js',
    },
    watch: ['./templates/**/*.js'],
  },
  twig: {
    watch: './templates/**/*.html.twig',
  },
};

gulp.task('sass', function () {
  return gulp
    .src(paths.styles.src)
    .pipe(sassGlob())
    .pipe(development(sourcemaps.init()))
    .pipe(sass().on('error', sass.logError))
    .pipe(production(autoprefixer()))
    .pipe(development(sourcemaps.write()))
    .pipe(gulp.dest(paths.styles.dest))
    .pipe(browserSync.stream());
});

gulp.task('js', function () {
  return gulp
    .src(paths.scripts.src)
    .pipe(concat(paths.scripts.dest.filename))
    .pipe(gulp.dest(paths.scripts.dest.dir));
});

gulp.task('js-watch', series('js', function (done) {
  browserSync.reload();
  done();
}));

function copyConfig(done) {
  try {
    fs.accessSync('./config.js');
    console.log('config.js found :)');
    done();
  } catch (e) {
    console.log('config.js not found. Will create it four you...');
    console.log(
      'Please check the hostname in config.js and change it to your local host, if needed.',
    );
    return gulp
      .src('./config.js.example')
      .pipe(rename('config.js'))
      .pipe(gulp.dest('.'));
  }
}

function readConfig(done) {
  config = require('./config.js');
  done();
}

function developmentServer() {
  browserSync.init({
    proxy: config.browserSync.hostname,
    online: config.browserSync.online,
    open: config.browserSync.open,
    reloadDelay: 300,
  });
  gulp.watch(paths.styles.watch, gulp.series('sass'));
  gulp.watch(paths.scripts.watch, gulp.series('js-watch'));
  gulp.watch(paths.twig.watch).on('change', browserSync.reload);
}

gulp.task('set-env-development', function(cb) {
  environments.current(development);
  cb();
});

gulp.task('set-env-production', function(cb) {
  environments.current(production);
  cb();
});

// Legacy.
// Prefer
//   `npm run build` for production build
//   `npm run serve` for development
//
gulp.task('initConfig', series(copyConfig, readConfig))
gulp.task('dev', series('set-env-development', parallel('sass', 'js')));
gulp.task('build', series('set-env-production', parallel('sass', 'js')));
gulp.task('watch', series(
    parallel('initConfig', 'set-env-development'),
    parallel('sass', 'js', developmentServer),
));
