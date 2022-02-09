/**
 * Gulp file
 *
 * @author Takuto Yanagida
 * @version 2022-02-09
 */

/* eslint-disable no-undef */
'use strict';

const SRC_JS_RAW  = ['src/**/*.js', '!src/**/*.min.js'];
const SRC_JS_MIN  = ['src/**/*.min.js'];
const SRC_SASS    = ['src/**/*.scss'];
const SRC_CSS_RAW = ['src/**/*.css', '!src/**/*.min.css'];
const SRC_CSS_MIN = ['src/**/*.min.css'];
const SRC_PHP     = ['src/**/*.php'];
const SRC_IMG     = ['src/**/*.png'];
const SRC_LOCALE  = ['src/languages/**/*.po'];
const DIST        = './dist';

const SASS_OUTPUT_STYLE = 'compressed';  // 'expanded' or 'compressed'

const gulp = require('gulp');
const $ = require('gulp-load-plugins')({ pattern: ['gulp-*'] });


// -----------------------------------------------------------------------------


gulp.task('js-raw', () => {
	if (SRC_JS_RAW.length === 0) return done();
	return gulp.src(SRC_JS_RAW, { base: 'src' })
		.pipe($.plumber())
		.pipe($.babel())
		.pipe($.terser())
		.pipe($.changed(DIST, { hasChanged: $.changed.compareContents, extension: '.min.js' }))
		.pipe($.rename({ extname: '.min.js' }))
		.pipe(gulp.dest(DIST));
});

gulp.task('js-min', () => {
	if (SRC_JS_MIN.length === 0) return done();
	return gulp.src(SRC_JS_MIN)
		.pipe($.plumber())
		.pipe($.changed(DIST, { hasChanged: $.changed.compareContents }))
		.pipe(gulp.dest(DIST));
});

gulp.task('js', gulp.parallel('js-raw', 'js-min'));


// -----------------------------------------------------------------------------


gulp.task('sass', () => {
	if (SRC_SASS.length === 0) return done();
	return gulp.src(SRC_SASS)
		.pipe($.plumber({
			errorHandler: function (err) {
				console.log(err.messageFormatted);
				this.emit('end');
			}
		}))
		.pipe($.sourcemaps.init())
		.pipe($.dartSass({ outputStyle: SASS_OUTPUT_STYLE }))
		.pipe($.autoprefixer({ remove: false }))
		.pipe($.changed(DIST, { hasChanged: $.changed.compareContents, extension: '.min.js' }))
		.pipe($.rename({ extname: '.min.css' }))
		.pipe($.sourcemaps.write('.'))
		.pipe(gulp.dest(DIST));
});

gulp.task('css-raw', () => {
	if (SRC_CSS_RAW.length === 0) return done();
	return gulp.src(SRC_CSS_RAW, { base: 'src' })
		.pipe($.plumber())
		.pipe($.sourcemaps.init())
		.pipe($.cleanCss())
		.pipe($.changed(DIST, { hasChanged: $.changed.compareContents, extension: '.min.js' }))
		.pipe($.rename({ extname: '.min.css' }))
		.pipe($.sourcemaps.write('.'))
		.pipe(gulp.dest(DIST));
});

gulp.task('css-min', () => {
	if (SRC_CSS_MIN.length === 0) return done();
	return gulp.src(SRC_CSS_MIN)
		.pipe($.plumber())
		.pipe($.changed(DIST, { hasChanged: $.changed.compareContents }))
		.pipe(gulp.dest(DIST));
});

gulp.task('css', gulp.parallel('sass', 'css-raw', 'css-min'));


// -----------------------------------------------------------------------------


gulp.task('php', () => {
	if (SRC_PHP.length === 0) return done();
	return gulp.src(SRC_PHP)
		.pipe($.plumber())
		.pipe($.changed(DIST, { hasChanged: $.changed.compareContents }))
		.pipe(gulp.dest(DIST));
});

gulp.task('img', () => {
	if (SRC_IMG.length === 0) return done();
	return gulp.src(SRC_IMG)
		.pipe($.plumber())
		.pipe($.changed(DIST, { hasChanged: $.changed.compareContents }))
		.pipe(gulp.dest(DIST));
});


// -----------------------------------------------------------------------------


gulp.task('locale', function () {
	if (SRC_LOCALE.length === 0) return done();
	return gulp.src(SRC_LOCALE, { base: 'src' })
		.pipe($.plumber())
		.pipe($.gettext())
		.pipe($.changed(DIST, { hasChanged: $.changed.compareContents, extension: '.mo' }))
		.pipe(gulp.dest(DIST));
});


// -----------------------------------------------------------------------------


gulp.task('watch', () => {
	gulp.watch(SRC_JS_RAW, gulp.series('js-raw'));
	gulp.watch(SRC_JS_MIN, gulp.series('js-min'));
	gulp.watch(SRC_SASS, gulp.series('sass'));
	gulp.watch(SRC_CSS_RAW, gulp.series('css-raw'));
	gulp.watch(SRC_CSS_MIN, gulp.series('css-min'));
	gulp.watch(SRC_PHP, gulp.series('php'));
	gulp.watch(SRC_IMG, gulp.series('img'));
	gulp.watch(SRC_LOCALE, gulp.series('locale'));
});

gulp.task('build', gulp.parallel('js', 'css', 'php', 'img', 'locale'));

gulp.task('default', gulp.series('build', 'watch'));
