/**
 * ESLint configuration for QR Trackr plugin.
 *
 * @package QR_Trackr
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