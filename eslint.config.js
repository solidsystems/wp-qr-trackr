module.exports = [
  {
    ignores: [
      "vendor/",
      "node_modules/",
      "wp-content/plugins/wp-qr-trackr/vendor/",
      "build/",
      "dist/",
      "coverage/",
      "wp-content/plugins/wp-qr-trackr/coverage/",
    ],
  },
  {
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