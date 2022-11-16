const gulp = require('gulp')

gulp.task('build', () => {
	return gulp
		.src(
			[
				'./README.md',
				'./load.php',
				'./LICENSE',
				'./assets/**/*',
			],
			{ base: '.' }
		)
		.pipe(gulp.dest('./build/'))
})
