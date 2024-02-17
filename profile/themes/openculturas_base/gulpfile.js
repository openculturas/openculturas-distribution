import gulp from 'gulp';
import * as dartSass from 'sass';
import gulpSass from 'gulp-sass';
const sass = gulpSass(dartSass);
import fs from 'fs';
import gulpAutoprefixer from 'gulp-autoprefixer';
import rename from 'gulp-rename';
import sassGlob from 'gulp-sass-glob';
import sourcemaps from "gulp-sourcemaps";
import concat from 'gulp-concat';
import environments from 'gulp-environments';
import browserSync from 'browser-sync';
import { createRequire } from 'module';
const require = createRequire(import.meta.url);
const development = environments.development,
production = environments.production;

let browserSyncConfig;

const paths = {
  styles: {
    src: [
      './scss_config/style.scss',
      './scss_config/wysiwyg.scss',
      './scss_config/cke5-wysiwyg.scss',
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
    .pipe(production(gulpAutoprefixer()))
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

gulp.task('js-watch', gulp.series('js', function (done) {
  browserSync.reload();
  done();
}));

function copyConfig(done) {
  try {
    fs.accessSync('./config.cjs', fs.constants.R_OK);
    console.log('config.cjs found :)');
    done();
  } catch (e) {
    console.log('config.cjs not found. Will create it four you...');
    console.log(
      'Please check the hostname in config.cjs and change it to your local host, if needed.',
    );
    return gulp
      .src('./config.cjs.example')
      .pipe(rename('config.cjs'))
      .pipe(gulp.dest('.'));
  }
}

function readConfig(done) {
  browserSyncConfig = require("./config.cjs").default;
  done();
}

function developmentServer() {
  browserSync.init({
    proxy: browserSyncConfig.browserSync.hostname,
    online: browserSyncConfig.browserSync.online,
    open: browserSyncConfig.browserSync.open,
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
gulp.task('initConfig', gulp.series(copyConfig, readConfig))
gulp.task('dev', gulp.series('set-env-development', gulp.parallel('sass', 'js')));
gulp.task('build', gulp.series('set-env-production', gulp.parallel('sass', 'js')));
gulp.task('watch', gulp.series(
    gulp.parallel('initConfig', 'set-env-development'),
    gulp.parallel('sass', 'js', developmentServer),
));
