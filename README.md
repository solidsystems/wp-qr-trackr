# WordPress Plugin Template

This repository serves as a professional starting point for developing secure, elegant, and mobile-first WordPress plugins.

## Features & Standards
- **Professional WordPress Best Practices**: All plugins are built with industry-leading standards for security, maintainability, and performance.
- **Excellent Security**: Follows strict security guidelines to protect your WordPress site and data.
- **Elegant, Maintainable Code**: Code is structured for clarity, extensibility, and ease of maintenance.
- **Debug Logging**: Debug logging is available and can be enabled upon request for troubleshooting and development.
- **Mobile-First UI**: Both generator functions and wp-admin panes are styled for optimal experience on all devices, prioritizing mobile usability.

## Using This Template with Cursor

1. **Clone the Repository**
   ```sh
   git clone <your-repo-url>
   cd <your-repo-directory>
   ```
2. **Open in Cursor**
   - Launch [Cursor](https://cursor.so/) and open this directory.
3. **Customize Your Plugin**
   - Rename the plugin directory and files as needed.
   - Update the plugin headers in the main PHP file.
   - Implement your custom functionality, following the standards in `.cursorrules`.
4. **Development Workflow**
   - Use Cursor's AI features to generate, refactor, and document code.
   - Commit changes regularly.
   - Test your plugin in a local or staging WordPress environment.
5. **Debug Logging**
   - Enable debug logging as needed for troubleshooting (see `$lib/utils/debug.ts` or your plugin's debug utility).

## About the Template Author

This template is maintained by a professional WordPress developer specializing in secure, elegant, and mobile-first plugin development. For questions or contributions, please open an issue or pull request.

## Recommended Workflow: Branching, Documentation, and PRs

To maintain high standards and clear project history, follow this workflow for all changes:

1. **Create a New Branch**
   - For each new feature or fix, create a dedicated branch from `main`:
     ```sh
     git checkout -b feature/your-feature-name
     ```
2. **Update Documentation**
   - Update the `README.md` and other relevant documentation files with every code change or new feature.
   - Ensure documentation accurately reflects the current state of the codebase.
3. **Commit Changes**
   - Write clear, descriptive commit messages.
   - Example:
     ```sh
     git add .
     git commit -m "Add feature X with updated documentation"
     ```
4. **Push to Remote**
   - Push your branch to the remote repository:
     ```sh
     git push origin feature/your-feature-name
     ```
5. **Open or Update a Pull Request (PR)**
   - Open a PR from your branch into `main`.
   - If you make further changes, push them to the same branch; the PR will update automatically.
   - Reviewers should verify that documentation is up to date before merging.

This workflow ensures code and documentation remain synchronized, improving maintainability and onboarding for new contributors.
