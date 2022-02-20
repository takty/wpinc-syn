/**
 * Gulp file
 *
 * @author Takuto Yanagida
 * @version 2022-02-20
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
const SRC_PO      = ['src/languages/**/*.po', '!src/languages/**/wpinc-*.po'];
const SRC_JSON    = ['src/languages/**/*.json'];
const DIST        = './dist';

const SASS_OUTPUT_STYLE = 'compressed';  // 'expanded' or 'compressed'

const gulp      = require('gulp');
const gulp_sass = require('gulp-sass')(require('sass'));
const $         = require('gulp-load-plugins')({ pattern: ['gulp-*', '!gulp-sass'] });


// -----------------------------------------------------------------------------


const js_raw = () => {
	if (SRC_JS_RAW.length === 0) return done();
	return gulp.src(SRC_JS_RAW, { base: 'src' })
		.pipe($.plumber())
		.pipe($.babel())
		.pipe($.terser())
		.pipe($.rename({ extname: '.min.js' }))
		.pipe($.changed(DIST, { hasChanged: $.changed.compareContents }))
		.pipe(gulp.dest(DIST, { sourcemaps: '.' }));
};

const js_min = () => {
	if (SRC_JS_MIN.length === 0) return done();
	return gulp.src(SRC_JS_MIN)
		.pipe($.plumber())
		.pipe($.changed(DIST, { hasChanged: $.changed.compareContents }))
		.pipe(gulp.dest(DIST));
};

const js = gulp.parallel(js_raw, js_min);


// -----------------------------------------------------------------------------


const sass = () => {
	if (SRC_SASS.length === 0) return done();
	return gulp.src(SRC_SASS)
		.pipe($.plumber({
			errorHandler: function (err) {
				console.log(err.messageFormatted);
				this.emit('end');
			}
		}))
		.pipe(gulp_sass({ outputStyle: SASS_OUTPUT_STYLE }))
		.pipe($.autoprefixer({ remove: false }))
		.pipe($.rename({ extname: '.min.css' }))
		.pipe($.changed(DIST, { hasChanged: $.changed.compareContents }))
		.pipe(gulp.dest(DIST, { sourcemaps: '.' }));
};

const css_raw = () => {
	if (SRC_CSS_RAW.length === 0) return done();
	return gulp.src(SRC_CSS_RAW, { base: 'src' })
		.pipe($.plumber())
		.pipe($.cleanCss())
		.pipe($.rename({ extname: '.min.css' }))
		.pipe($.changed(DIST, { hasChanged: $.changed.compareContents }))
		.pipe(gulp.dest(DIST, { sourcemaps: '.' }));
};

const css_min = () => {
	if (SRC_CSS_MIN.length === 0) return done();
	return gulp.src(SRC_CSS_MIN)
		.pipe($.plumber())
		.pipe($.changed(DIST, { hasChanged: $.changed.compareContents }))
		.pipe(gulp.dest(DIST));
};

const css = gulp.parallel(sass, css_raw, css_min);


// -----------------------------------------------------------------------------


const php = () => {
	if (SRC_PHP.length === 0) return done();
	return gulp.src(SRC_PHP)
		.pipe($.plumber())
		.pipe($.changed(DIST, { hasChanged: $.changed.compareContents }))
		.pipe(gulp.dest(DIST));
};

const img = () => {
	if (SRC_IMG.length === 0) return done();
	return gulp.src(SRC_IMG)
		.pipe($.plumber())
		.pipe($.changed(DIST, { hasChanged: $.changed.compareContents }))
		.pipe(gulp.dest(DIST));
};


// -----------------------------------------------------------------------------


const po = () => {
	if (SRC_PO.length === 0) return done();
	return gulp.src(SRC_PO, { base: 'src' })
		.pipe($.plumber())
		.pipe($.gettext())
		.pipe($.changed(DIST, { hasChanged: $.changed.compareContents }))
		.pipe(gulp.dest(DIST));
};

const json = () => {
	if (SRC_JSON.length === 0) return done();
	return gulp.src(SRC_JSON, { base: 'src' })
		.pipe($.plumber())
		.pipe($.changed(DIST, { hasChanged: $.changed.compareContents }))
		.pipe(gulp.dest(DIST));
};

const locale = gulp.parallel(po, json);


// -----------------------------------------------------------------------------


const watch = () => {
	gulp.watch(SRC_JS_RAW, js_raw);
	gulp.watch(SRC_JS_MIN, js_min);
	gulp.watch(SRC_SASS, sass);
	gulp.watch(SRC_CSS_RAW, css_raw);
	gulp.watch(SRC_CSS_MIN, css_min);
	gulp.watch(SRC_PHP, php);
	gulp.watch(SRC_IMG, img);
	gulp.watch(SRC_PO, locale);
	gulp.watch(SRC_JSON, locale);
};

exports.build = gulp.parallel(js, css, php, img, locale);

exports.default = gulp.series(exports.build, watch);
