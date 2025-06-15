/**
 * ESLint configuration for QR Trackr plugin.
 */

export default [
	{
		files: ['**/*.js'],
		rules: {
			'no-console': 'warn',
			'no-unused-vars': 'warn',
			'prefer-const': 'warn',
		},
	},
	{
		files: ['**/*.test.js'],
		rules: {
			'no-console': 'off',
		},
	},
]; 