const {spawn} = require('child_process');

console.log('Starting build...');

const buildJS = spawn('npm', ['run', 'start'], {
	stdio: 'inherit',
	shell: true,
});

buildJS.on('exit', () => {
	console.log('Build finished.');
});

buildJS.on('spawn', () => {
	const releaseProcess = spawn('npm', ['run', 'release'], {
		stdio: 'inherit',
		shell: true
	});

	releaseProcess.on('exit', (code) => {
		buildJS.kill();
	});
});


