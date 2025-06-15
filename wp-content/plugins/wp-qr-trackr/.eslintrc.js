module.exports = {
	root: true,
	extends: [
		'plugin:@wordpress/eslint-plugin/recommended',
	],
	env: {
		browser: true,
		jquery: true,
	},
	globals: {
		wp: true,
		qrTrackr: true,
	},
	rules: {
		'@wordpress/no-global-active-element': 'off',
		'@wordpress/no-global-get-selection': 'off',
		'@wordpress/no-unsafe-wp-apis': 'off',
	},
}; 