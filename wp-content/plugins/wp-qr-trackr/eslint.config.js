export default [
  {
    ignores: [
      'vendor/',
      'node_modules/',
      'coverage/',
    ],
    files: ["**/*.js"],
    languageOptions: {
      ecmaVersion: "latest",
      sourceType: "module",
    },
    rules: {
      // Add custom rules as needed
    },
  },
]; 