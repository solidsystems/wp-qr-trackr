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

## Local Development with Docker

You can run this WordPress plugin template locally using Docker. This will spin up both a WordPress instance and a MySQL database, making it easy to test your plugin in a real environment.

### Prerequisites
- [Docker](https://www.digitalocean.com/community/tutorial_series/docker-explained) must be installed on your system. If you need help, see this guide: [How To Install and Use Docker](https://www.digitalocean.com/community/tutorials/how-to-install-and-use-docker-on-mac-os-x).

### Steps
1. **Copy the example environment file:**
   ```sh
   cp .env.example .env
   # Edit .env to set your local secrets if needed
   ```
2. **Initialize the Docker environment:**
   ```sh
   ./scripts/init-docker.sh
   ```
   This script will check if port 8080 is free, stop any Docker container using it, and start the environment.

3. **Access WordPress:**
   - Open your browser and go to [http://localhost:8080](http://localhost:8080)
   - Complete the WordPress setup wizard if prompted.

4. **Develop Your Plugin:**
   - Place your plugin code in `wp-content/plugins` (mounted automatically).
   - Activate and test your plugin from the WordPress admin panel.

### Stopping the Environment
To stop the containers, press `Ctrl+C` in the terminal running Docker, or run:
```sh
docker-compose down
```

## QR Code Shapes (Extensible)

QR Trackr now supports a `shape` parameter for QR code generation. By default, only the standard shape is available, but the Pro plugin (or other plugins) can register new shapes and custom QR code renderers.

### Usage
- The `qr_trackr_generate_qr_image` and `qr_trackr_generate_qr_image_for_link` functions accept a `$shape` parameter.
- Use the `qr_trackr_get_available_shapes()` function to get all registered shapes.

### Extending with Pro
- Pro or other plugins can add new shapes via the `qr_trackr_qr_shapes` filter:

```php
add_filter('qr_trackr_qr_shapes', function($shapes) {
    $shapes['animal_head'] = __('Animal Head', 'qr-trackr-pro');
    return $shapes;
});
```

- To generate custom QR code images for new shapes, use the `qr_trackr_generate_custom_shape` filter:

```php
add_filter('qr_trackr_generate_custom_shape', function($custom, $url, $size, $shape, $filepath) {
    if ($shape === 'animal_head') {
        // Generate and save custom QR code to $filepath
        // ...
        return true; // Indicate handled
    }
    return $custom;
}, 10, 5);
```

- If the filter returns `null`, the core plugin will fall back to the standard QR code.

---

## Pro Version

The Pro add-on can:
- Register new shapes and custom QR code renderers.
- Provide advanced QR code styles, overlays, and more.
- Integrate with the shape system via the documented filters.

See the Pro plugin documentation for more details.
