import gulp from 'gulp';
import * as dartSass from 'sass';
import gulpSass from 'gulp-sass';
const sass = gulpSass(dartSass);
import gulpAutoprefixer from 'gulp-autoprefixer';
import sassGlob from 'gulp-sass-glob';
import sourcemaps from "gulp-sourcemaps";
import concat from 'gulp-concat';
import environments from 'gulp-environments';
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
    .pipe(gulp.dest(paths.styles.dest));
});

gulp.task('js', function () {
  return gulp
    .src(paths.scripts.src)
    .pipe(concat(paths.scripts.dest.filename))
    .pipe(gulp.dest(paths.scripts.dest.dir));
});

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
gulp.task('dev', gulp.series('set-env-development', gulp.parallel('sass', 'js')));
gulp.task('build', gulp.series('set-env-production', gulp.parallel('sass', 'js')));
gulp.task('watch', gulp.series(
    gulp.task('set-env-development'),
    gulp.parallel('sass', 'js'),
));
