module.exports = {
	root: true,
	extends: [
		'plugin:@wordpress/eslint-plugin/recommended',
		'plugin:@wordpress/eslint-plugin/jsdoc',
	],
	env: {
		browser: true,
		jquery: true,
		es6: true,
	},
	globals: {
		wp: true,
		qrTrackr: true,
	},
	rules: {
		'@wordpress/no-global-active-element': 'off',
		'@wordpress/no-global-get-selection': 'off',
		'@wordpress/no-unsafe-wp-apis': 'off',
		'@wordpress/no-null': 'off',
		'@wordpress/no-unsafe-return': 'off',
	},
	parserOptions: {
		requireConfigFile: false,
		ecmaVersion: 2022,
		sourceType: 'module',
	},
}; 