const gulp = require('gulp')

gulp.task('build', () => {
	return gulp
		.src(
			[
				'./README.md',
				'./banner.php',
				'./LICENSE',
				'./assets/**/*',
			],
			{ base: '.' }
		)
		.pipe(gulp.dest('./build/'))
})
