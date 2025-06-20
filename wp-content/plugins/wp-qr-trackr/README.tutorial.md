# Tutorial: Building a Tic Tac Toe WordPress Plugin with Cursor (Inspired by QR Trackr)

---

## Tell Them What You're Going to Tell Them

Welcome! In this hands-on tutorial, you'll learn how to build a complete Tic Tac Toe WordPress plugin from scratch using Cursor, following the same modular, standards-compliant approach as the QR Trackr plugin.  
We'll walk through forking the QR Trackr repo as a reference, setting up your local environment, and using a series of Cursor prompts to create, test, and activate your new plugin.  
You'll see how to design both the UI and backend, implement logging, and follow best practices for WordPress plugin development—learning by doing, step by step.

---

## Step 1: Fork and Set Up the Example Project

**Prompt:**  
> Fork the `wp-qr-trackr` repository to your own GitHub account.  
> Clone your fork locally:  
> ```sh
> git clone https://github.com/YOUR-USERNAME/wp-qr-trackr.git
> cd wp-qr-trackr
> ```

**Prompt:**  
> Run the reset Docker script to ensure a clean local WordPress environment:  
> ```sh
> ./scripts/reset-docker.sh
> ```

**Prompt:**  
> Open the project in Cursor.

---

## Step 2: Create the Tic Tac Toe Plugin Scaffold

**Prompt:**  
> Create a new plugin directory:  
> ```
> Please create a new plugin in `wp-content/plugins/tic-tac-toe/` with the following files:
> - tic-tac-toe.php (main plugin file)
> - includes/module-admin.php
> - includes/module-ajax.php
> - includes/module-game.php
> - includes/module-utils.php
> - assets/js/admin.js
> - assets/css/admin.css
> 
> The plugin header should be:
> ```
> /*
> Plugin Name: Tic Tac Toe
> Description: Play Tic Tac Toe in the WordPress admin. Built modularly, following QR Trackr and Cursor rules.
> Version: 1.0.0
> Author: Your Name
> */
> ```
> ```

---

## Step 3: Bootstrap the Plugin (Following QR Trackr's Structure)

**Prompt:**  
> In `tic-tac-toe.php`, bootstrap the plugin by requiring all modules from the `includes/` directory, just like QR Trackr.  
> Ensure no business logic is in the main file—only module loading and initialization.

---

## Step 4: Implement the Admin UI

**Prompt:**  
> In `includes/module-admin.php`, create a function to add a new top-level menu item "Tic Tac Toe" in the WordPress admin.  
> The menu should open a page with the title "Tic Tac Toe Game".  
> The page should include a 3x3 grid, a "New Game" button, and a status area for messages (e.g., "Player X's turn", "Player O wins!").

**Prompt:**  
> In `assets/js/admin.js`, implement the front-end logic for the game board, handling user clicks, updating the UI, and sending moves to the backend via AJAX.

**Prompt:**  
> In `assets/css/admin.css`, style the board to be mobile-first, visually clear, and accessible.

---

## Step 5: Implement AJAX and Game Logic

**Prompt:**  
> In `includes/module-ajax.php`, register AJAX actions for:
> - Starting a new game
> - Making a move
> - Returning the current game state
> 
> All AJAX handlers should validate nonces and user permissions, and return JSON responses.

**Prompt:**  
> In `includes/module-game.php`, implement the backend logic for:
> - Initializing a new game (empty board, X starts)
> - Validating moves (no overwriting, correct turn)
> - Checking for win/draw conditions
> - Storing game state in user meta or a custom table

---

## Step 6: Logging and Debug Mode

**Prompt:**  
> Add a debug mode option to the plugin settings (in `module-admin.php`), allowing admins to toggle logging on/off.
> 
> In all modules, use a `tic_tac_toe_debug_log()` function (modeled after QR Trackr) to log key events (game start, moves, errors) when debug mode is enabled.

---

## Step 7: Database and Data Handling

**Prompt:**  
> If using a custom table for game state, add migration logic in an activation module (like QR Trackr's `module-activation.php`).  
> Otherwise, use user meta for per-user games.

**Prompt:**  
> Ensure all data is sanitized and validated before saving or outputting, following Cursor and WordPress security rules.

---

## Step 8: Activate and Test the Plugin

**Prompt:**  
> In the WordPress admin, go to Plugins and activate "Tic Tac Toe".

**Prompt:**  
> Open the "Tic Tac Toe" menu.  
> Play a game, try starting a new game, and test win/draw detection.  
> Open the browser console and PHP error log to verify debug messages if debug mode is enabled.

---

## Step 9: Key Takeaways and Review

**Prompt:**  
> Summarize what you've done:
> - Forked QR Trackr as a reference for modular, standards-compliant plugin development.
> - Used Cursor prompts to scaffold, build, and test a new plugin end-to-end.
> - Followed Cursor rules for modularity, security, and maintainability.
> - Implemented a clean UI, robust backend, AJAX, logging, and (optionally) database migration.
> - Demonstrated learning by doing—building a real plugin, not just reading about it.

---

## Tell Them What You've Told Them

In this tutorial, you:
- Learned how to use Cursor prompts to build a WordPress plugin from scratch, using QR Trackr as a model.
- Stepped through setup, UI/UX, backend logic, AJAX, logging, and database handling.
- Saw how modularity, clear separation of concerns, and adherence to Cursor rules make plugins easier to build, maintain, and extend.
- Practiced prompt engineering: using focused, directive prompts to guide Cursor, always referencing Cursor rules and best practices.
- Covered UI (titles, menu bar, modals, AJAX), backend (game logic, data storage, validation), logging, and more.

**Principles followed:**
- Cursor rules to narrow focus and guide decisions.
- Modular, maintainable code.
- Security and validation at every step.
- Clear, user-friendly UI and robust backend.
- Logging and debug toggles for troubleshooting.
- Learning by doing—watching and building in real time.

---

You now have a working Tic Tac Toe plugin, a deeper understanding of plugin architecture, and a repeatable process for future projects. Happy building! 