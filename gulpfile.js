"use strict";

const {src, dest}   = require("gulp");
const gulp          = require('gulp'),
  sass              = require('gulp-sass'),
  autoprefixer      = require('gulp-autoprefixer'),
  cleanCSS          = require('gulp-clean-css'),
  notify            = require('gulp-notify');

sass.compiler = require('node-sass');

const path = {
  build: {
    css: "static/css/",
    fonts: "build/fonts"
  },
  src: {
    css: "static/scss/*.scss",
    fonts: "src/fonts/**/*.{ttf,woff,woff2,eot}"
  },
  watch: {
    css: "static/scss/*.scss",
  }
};

/* Tasks
=========================*/

function scss() {
  return src(path.src.css)
    .pipe(sass({
      outputStyle: 'expanded'
    }))
    .on('error', notify.onError())
    .pipe(autoprefixer({
      cascade: true
    }))
    .pipe(cleanCSS())
    .pipe(dest(path.build.css));
}

function watchFiles() {
  gulp.watch([path.watch.css], scss);
}

const build = gulp.series(scss);
const watch = gulp.series(watchFiles);

// export tasks
exports.build = build;
exports.watch = watch;

exports.scss = scss;