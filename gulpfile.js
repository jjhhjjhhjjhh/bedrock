var gulp        = require('gulp'),
    browserSync = require('browser-sync'),
    // autoImports = require('gulp-auto-imports'),
    autoprefixer = require('gulp-autoprefixer'),
    concat      = require('gulp-concat'),
    notifier    = require('node-notifier'),
    map         = require('map-stream'),
    plumber     = require('gulp-plumber'),
    rename      = require('gulp-rename'),
    sass        = require('gulp-sass')(require('sass')),
    sassGlob    = require('gulp-sass-glob'),
    sourcemaps  = require('gulp-sourcemaps'),
    uglify      = require('gulp-uglify'),
    replace = require("gulp-replace"),
    fs = require("fs");

var THEMES_PATH  = "web/app/themes/";
var THEME_PATH   = THEMES_PATH + 'client-theme/';
var COMPILE_PATH = THEME_PATH + 'compile/';


browserSync.emitter.on('init', function () {
    notifier.notify({
        title: 'BrowserSync',
        message: 'Now running.',
        sound: 'Pop'
    });
});

// Not exposed to CLI
const startServer = done => {
  browserSync();
  done();
}

const compileScript = doneCompiling => {
    gulp.src(
        [
            COMPILE_PATH + 'source/js/**/*.js',
            '!' + COMPILE_PATH + 'source/js/vendor/**/*.js'
        ])
        .pipe(plumber({
            errorHandler: function (error) {
                console.log(error.message);
                this.emit('end');
            }
        }))
        .pipe(concat('main.js'))
        // .pipe(gulp.dest(COMPILE_PATH + 'assets/js'))
        .pipe(rename({ suffix: '.min' }))
        .pipe(sourcemaps.init())
        .pipe(uglify())
        .pipe(sourcemaps.write('../maps'))
        .pipe(gulp.dest(COMPILE_PATH + 'assets/js'))
        .pipe(browserSync.reload({ stream: true }));


    gulp.src(
        [
            COMPILE_PATH + 'source/js/vendor/**/*.js'
        ])
        .pipe(plumber({
            errorHandler: function (error) {
                console.log(error.message, error);
                this.emit('end');
            }
        }))
        .pipe(gulp.dest(COMPILE_PATH + 'assets/js/vendor'));


    gulp.src(
        [
            COMPILE_PATH + 'source/js/vendor/compress/**/*.js'
        ])
        .pipe(plumber({
            errorHandler: function (error) {
                console.log(error.message, error);
                this.emit('end');
            }
        }))
        .pipe(concat('compressed.js'))
        .pipe(gulp.dest(COMPILE_PATH + 'assets/js/vendor'))
        .pipe(rename({ suffix: '.min' }))
        .pipe(sourcemaps.init())
        .pipe(uglify())
        .pipe(sourcemaps.write('../maps'))
        .pipe(gulp.dest(COMPILE_PATH + 'assets/js'));
    doneCompiling();
}
const compileStyle = doneCompiling => {
    gulp.src(
        [
            COMPILE_PATH + 'source/scss/main.scss',
        ])
        .pipe(plumber({
            errorHandler: function (error) {
                console.log(error.message);
                this.emit('end');
            }
        }))
        .pipe(rename({ suffix: '.min' }))
        .pipe(sourcemaps.init())
        .pipe(sassGlob())
        .pipe(sass({ outputStyle: 'compressed' }))
        .pipe(autoprefixer({
            grid: true
        }))
        .pipe(sourcemaps.write('../maps'))
        .pipe(gulp.dest(COMPILE_PATH + 'assets/css'))
        .pipe(browserSync.stream({ match: '**/*.css' }));

    bumpVersion();
    doneCompiling();
}
const compileAdminStyle = doneCompiling => {
    gulp.src(
        [
            COMPILE_PATH + 'source/scss/admin.scss',
        ])
        .pipe(plumber({
            errorHandler: function (error) {
                console.log(error.message);
                this.emit('end');
            }
        }))
        .pipe(rename({ suffix: '.min' }))
        .pipe(sourcemaps.init())
        .pipe(sassGlob())
        .pipe(sass({ outputStyle: 'compressed' }))
        .pipe(autoprefixer({
            grid: true
        }))
        .pipe(sourcemaps.write('../maps'))
        .pipe(gulp.dest(COMPILE_PATH + 'assets/css'))
        .pipe(browserSync.stream({ match: '**/*.css' }));

    doneCompiling();
}

const reloadBrowser = doneReloading => {
  browserSync.reload();
  doneReloading();
}

const watchMarkup = doneWatching => {
  gulp.watch([
        '**/*.php',
        '**/*.html'
    ], gulp.series(reloadBrowser)
  );
  doneWatching();
}
const watchScript = doneWatching => {
  gulp.watch([
        COMPILE_PATH + 'source/js/**/*.js'
    ], gulp.series(compileScript)
  );
  doneWatching();
}
const watchStyle = doneWatching => {
  gulp.watch([
        COMPILE_PATH + 'source/scss/**/*.scss'
    ], gulp.parallel(compileStyle, compileAdminStyle)
  );
  doneWatching();
}

const bumpVersion = done => {
  //docString is the file from which you will get your constant string
  var docString = fs.readFileSync(THEME_PATH + "style.css", "utf8");

  //The code below gets your semantic v# from docString
  var versionNumPattern = /Version: (.*)/; //This is just a regEx with a capture group for version number
  var vNumRexEx = new RegExp(versionNumPattern);
  var oldVersionNumber = vNumRexEx.exec(docString)[1]; //This gets the captured group

  //...Split the version number string into elements so you can bump the one you want
  var versionParts = oldVersionNumber.split(".");
  var vArray = {
    vMajor: versionParts[0],
    vMinor: versionParts[1],
    vPatch: versionParts[2]
  };

  vArray.vPatch = parseFloat(vArray.vPatch) + 1;
  if(vArray.vPatch > 9999){
    vArray.vMinor = parseFloat(vArray.vMinor) + 1;
    vArray.vPatch = 1;
  }
  var periodString = ".";

  var newVersionNumber =
    vArray.vMajor + periodString + vArray.vMinor + periodString + vArray.vPatch;

  gulp
    .src([THEME_PATH + "style.css"])
    .pipe(replace(/Version: (.*)/, "Version: " + newVersionNumber))
    .pipe(gulp.dest(THEME_PATH));

  console.log(
    "Version bumped from " + oldVersionNumber + " to " + newVersionNumber
  );

  // done();
};

const compile = gulp.parallel( /*compileMarkup,*/ compileScript, compileStyle, compileAdminStyle)
compile.description = 'compile all sources'


const serveStuff = gulp.series(compile, startServer)
serveStuff.description = 'serve compiled source on local server at port 3000'

const watchStuff = gulp.parallel( watchMarkup, watchScript, watchStyle)
watchStuff.description = 'watch for changes to all source'

const defaultTasks = gulp.parallel(serveStuff, watchStuff)

module.exports = {
  bumpVersion,
  compile,
  // compileMarkup,
  compileScript,
  compileStyle,
  compileAdminStyle,
  startServer,
  serveStuff,
  reloadBrowser,
  watchStuff,
  watchMarkup,
  watchScript,
  watchStyle,
  default: defaultTasks
}
