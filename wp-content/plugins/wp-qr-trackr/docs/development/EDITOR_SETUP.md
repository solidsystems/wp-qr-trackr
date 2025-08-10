# Editor Setup Guide

This guide provides comprehensive setup instructions for developers using VS Code or Cursor with the WP QR Trackr project, including LLM integrations and best practices.

## VS Code Setup

### Prerequisites
- VS Code installed (latest version recommended)
- Docker Desktop running
- Git installed

### Essential Extensions

#### **PHP Development**
- **PHP Intelephense** - Advanced PHP language support
- **PHP Debug** - Xdebug integration for debugging
- **PHP CS Fixer** - Code formatting and standards
- **PHP DocBlocker** - Automatic docblock generation

#### **WordPress Development**
- **WordPress Snippets** - WordPress code snippets
- **WordPress Hooks** - Hook and filter autocomplete
- **PHP Namespace Resolver** - Namespace management

#### **Docker Integration**
- **Docker** - Docker container management
- **Dev Containers** - Development container support
- **Docker Explorer** - Container and image management

#### **Code Quality**
- **ESLint** - JavaScript linting
- **Prettier** - Code formatting
- **GitLens** - Enhanced Git integration
- **Error Lens** - Inline error display

### LLM Integration Extensions

#### **Open Source LLM Extensions**

##### 1. **Continue (Recommended)**
- **Extension ID**: `Continue.continue`
- **Description**: Open-source VS Code extension for AI pair programming
- **Features**:
  - Local model support (Ollama, LM Studio)
  - Cloud model integration (OpenAI, Anthropic, etc.)
  - Code completion and chat
  - File-aware context
- **Installation**: Search "Continue" in VS Code extensions
- **Configuration**: Supports `.continue` configuration file

##### 2. **Tabnine**
- **Extension ID**: `tabnine.tabnine-vscode`
- **Description**: AI code completion with local model support
- **Features**:
  - Local model deployment
  - Team learning capabilities
  - Privacy-focused
- **Installation**: Search "Tabnine" in VS Code extensions

##### 3. **GitHub Copilot**
- **Extension ID**: `GitHub.copilot`
- **Description**: GitHub's AI pair programming tool
- **Features**:
  - Real-time code suggestions
  - Chat interface
  - Integration with GitHub
- **Installation**: Search "GitHub Copilot" in VS Code extensions

##### 4. **Codeium**
- **Extension ID**: `Exafunction.codeium`
- **Description**: Free AI code completion
- **Features**:
  - Free tier available
  - Multiple language support
  - IDE integration
- **Installation**: Search "Codeium" in VS Code extensions

##### 5. **Ollama Integration**
- **Extension ID**: `gencay.vscode-chatgpt`
- **Description**: ChatGPT-like interface with Ollama support
- **Features**:
  - Local model support via Ollama
  - Chat interface
  - Code generation
- **Installation**: Search "ChatGPT" in VS Code extensions

### VS Code Configuration

#### **Settings.json**
```json
{
  "php.validate.enable": true,
  "php.suggest.basic": false,
  "php.intelephense.files.maxSize": 5000000,
  "php.intelephense.completion.triggerParameterHints": true,
  "php.intelephense.format.enable": true,
  "php.intelephense.diagnostics.enable": true,
  "php.intelephense.trace.server": "messages",
  "php.intelephense.stubs": [
    "apache",
    "bcmath",
    "bz2",
    "calendar",
    "com_dotnet",
    "Core",
    "ctype",
    "curl",
    "date",
    "dba",
    "dom",
    "enchant",
    "exif",
    "FFI",
    "fileinfo",
    "filter",
    "fpm",
    "ftp",
    "gd",
    "gettext",
    "gmp",
    "hash",
    "iconv",
    "imap",
    "intl",
    "json",
    "ldap",
    "libxml",
    "mbstring",
    "meta",
    "mysqli",
    "oci8",
    "odbc",
    "openssl",
    "pcntl",
    "pcre",
    "PDO",
    "pdo_ibm",
    "pdo_mysql",
    "pdo_pgsql",
    "pdo_sqlite",
    "pgsql",
    "Phar",
    "posix",
    "pspell",
    "readline",
    "Reflection",
    "session",
    "shmop",
    "SimpleXML",
    "snmp",
    "soap",
    "sockets",
    "sodium",
    "SPL",
    "sqlite3",
    "standard",
    "superglobals",
    "sysvmsg",
    "sysvsem",
    "sysvshm",
    "tidy",
    "tokenizer",
    "xml",
    "xmlreader",
    "xmlrpc",
    "xmlwriter",
    "xsl",
    "Zend OPcache",
    "zip",
    "zlib",
    "wordpress"
  ],
  "files.associations": {
    "*.php": "php"
  },
  "emmet.includeLanguages": {
    "php": "html"
  },
  "editor.formatOnSave": true,
  "editor.codeActionsOnSave": {
    "source.fixAll.eslint": true
  },
  "docker.showStartPage": false,
  "docker.commands.build": "docker compose -f docker/docker-compose.dev.yml build",
  "docker.commands.up": "docker compose -f docker/docker-compose.dev.yml up -d",
  "docker.commands.down": "docker compose -f docker/docker-compose.dev.yml down"
}
```

#### **Workspace Settings**
Create `.vscode/settings.json` in your project root:
```json
{
  "php.validate.executablePath": "/usr/bin/php",
  "php.suggest.basic": false,
  "php.intelephense.files.maxSize": 5000000,
  "php.intelephense.completion.triggerParameterHints": true,
  "php.intelephense.format.enable": true,
  "php.intelephense.diagnostics.enable": true,
  "php.intelephense.trace.server": "messages",
  "php.intelephense.stubs": [
    "wordpress"
  ],
  "files.associations": {
    "*.php": "php"
  },
  "emmet.includeLanguages": {
    "php": "html"
  },
  "editor.formatOnSave": true,
  "editor.codeActionsOnSave": {
    "source.fixAll.eslint": true
  }
}
```

### VS Code Tasks

Create `.vscode/tasks.json`:
```json
{
  "version": "2.0.0",
  "tasks": [
    {
      "label": "Start Dev Environment",
      "type": "shell",
      "command": "./scripts/setup-wordpress-enhanced.sh",
      "args": ["dev"],
      "group": "build",
      "presentation": {
        "echo": true,
        "reveal": "always",
        "focus": false,
        "panel": "shared"
      }
    },
    {
      "label": "Start Nonprod Environment",
      "type": "shell",
      "command": "./scripts/setup-wordpress-enhanced.sh",
      "args": ["nonprod"],
      "group": "build",
      "presentation": {
        "echo": true,
        "reveal": "always",
        "focus": false,
        "panel": "shared"
      }
    },
    {
      "label": "Health Check Dev",
      "type": "shell",
      "command": "./scripts/manage-containers.sh",
      "args": ["health", "dev"],
      "group": "test",
      "presentation": {
        "echo": true,
        "reveal": "always",
        "focus": false,
        "panel": "shared"
      }
    },
    {
      "label": "Run PHPCS",
      "type": "shell",
      "command": "make",
      "args": ["validate"],
      "group": "test",
      "presentation": {
        "echo": true,
        "reveal": "always",
        "focus": false,
        "panel": "shared"
      }
    }
  ]
}
```

### VS Code Launch Configuration

Create `.vscode/launch.json`:
```json
{
  "version": "0.2.0",
  "configurations": [
    {
      "name": "Listen for Xdebug",
      "type": "php",
      "request": "launch",
      "port": 9003,
      "pathMappings": {
        "/var/www/html/wp-content/plugins/wp-qr-trackr": "${workspaceFolder}"
      }
    }
  ]
}
```

## Cursor Setup

### What is Cursor?

Cursor is a modern code editor built on VS Code that includes AI capabilities out of the box. It's designed for developers who want AI assistance integrated directly into their development workflow.

### Key Features
- **Built-in AI Chat**: Integrated AI assistant for code help
- **AI Code Completion**: Real-time code suggestions
- **File-aware Context**: AI understands your project structure
- **VS Code Compatibility**: Supports most VS Code extensions
- **Git Integration**: AI can help with commit messages and code reviews

### Installation
1. Download Cursor from [cursor.sh](https://cursor.sh)
2. Install following the platform-specific instructions
3. Open your project folder in Cursor

### Cursor Rules

#### **What are Cursor Rules?**

Cursor Rules are configuration files that tell the AI how to behave when working with your codebase. They help ensure consistent code quality, follow project standards, and maintain best practices.

#### **Project Cursor Rules**

The WP QR Trackr project includes comprehensive Cursor Rules in `.cursorrules`:

```markdown
# WordPress Plugin Development Standards
- All plugins are developed with professional WordPress best practices
- Functionality is implemented in an elegant, maintainable, and efficient manner
- Debug logging is available upon request and should be implemented where appropriate
- UI styling must be mobile-first for optimal experience on all devices

# PHPCS Compliance Enforcement
- ALL SQL queries MUST use $wpdb->prepare() for any variables
- Table names MUST use {$wpdb->prefix}table_name format
- All user input MUST use placeholders (%d, %s, %f) in prepare statements
- All inline comments MUST end with proper punctuation (. ! ?)
- All functions MUST have complete docblocks with @param, @return, @throws tags

# Security Standards
- ALL user input MUST be sanitized using WordPress functions
- ALL output MUST be escaped using esc_html(), esc_url(), esc_attr()
- ALL form submissions MUST verify nonces
- ALL AJAX requests MUST verify nonces
- ALL conditionals MUST use Yoda conditions where possible

# WordPress Function Usage
- NEVER use serialize() - use wp_json_encode()
- NEVER use date() - use gmdate() for timezone safety
- NEVER use json_encode() - use wp_json_encode()
- ALL enqueued assets MUST have resource versioning for cache busting

# File Organization
- Class files MUST be prefixed with 'class-'
- File names MUST use lowercase and hyphens, not underscores
- All files MUST have proper file-level docblocks with @package tags
```

#### **Using Cursor Rules**

1. **AI Chat**: Use `Cmd/Ctrl + K` to open AI chat
2. **Code Completion**: AI will suggest code following project standards
3. **Code Review**: Ask AI to review code for compliance
4. **Refactoring**: Request AI to refactor code according to rules

### Cursor Extensions

#### **Essential Extensions**
- **PHP Intelephense** - Advanced PHP language support
- **WordPress Snippets** - WordPress code snippets
- **Docker** - Container management
- **GitLens** - Enhanced Git integration

#### **LLM Extensions for Cursor**

##### 1. **Continue Integration**
- Cursor has built-in support for Continue
- Configure in settings for local model support
- Use `.continue` configuration file

##### 2. **Ollama Integration**
- Install Ollama locally
- Configure Cursor to use local models
- Supports various open-source models

##### 3. **Custom AI Providers**
- Configure API keys for various providers
- Support for OpenAI, Anthropic, and others
- Local model support via Ollama

### Cursor Configuration

#### **Settings.json**
```json
{
  "cursor.general.enableTelemetry": false,
  "cursor.chat.enableAutoComplete": true,
  "cursor.chat.enableInlineChat": true,
  "cursor.chat.enableCodeActions": true,
  "cursor.chat.enableFileContext": true,
  "cursor.chat.enableGitContext": true,
  "cursor.chat.enableWorkspaceContext": true,
  "php.validate.enable": true,
  "php.suggest.basic": false,
  "php.intelephense.files.maxSize": 5000000,
  "php.intelephense.completion.triggerParameterHints": true,
  "php.intelephense.format.enable": true,
  "php.intelephense.diagnostics.enable": true,
  "php.intelephense.stubs": ["wordpress"],
  "files.associations": {
    "*.php": "php"
  },
  "emmet.includeLanguages": {
    "php": "html"
  },
  "editor.formatOnSave": true
}
```

### Cursor Workflow

#### **Daily Development Workflow**

1. **Start Environment**:
   ```bash
   # Use the enhanced setup script
   ./scripts/setup-wordpress-enhanced.sh dev
   ```

2. **Health Check**:
   ```bash
   # Verify environment is healthy
   ./scripts/manage-containers.sh health dev
   ```

3. **AI-Assisted Development**:
   - Use `Cmd/Ctrl + K` for AI chat
   - Ask for code suggestions following project standards
   - Request code reviews and improvements

4. **Code Quality**:
   ```bash
   # Run validation
   make validate
   ```

#### **AI Chat Examples**

**Code Generation**:
```
Generate a WordPress admin function that follows our coding standards for handling AJAX requests
```

**Code Review**:
```
Review this code for WordPress coding standards compliance and suggest improvements
```

**Debugging**:
```
Help me debug this PHP error in the WordPress plugin
```

**Documentation**:
```
Generate docblocks for this function following our project standards
```

### Best Practices

#### **VS Code Best Practices**
1. **Use Workspace Settings**: Configure project-specific settings
2. **Leverage Extensions**: Install recommended extensions for productivity
3. **Use Tasks**: Create custom tasks for common operations
4. **Configure Debugging**: Set up Xdebug for PHP debugging
5. **Use Git Integration**: Leverage GitLens for enhanced Git workflow

#### **Cursor Best Practices**
1. **Understand Cursor Rules**: Familiarize yourself with project rules
2. **Use AI Chat Effectively**: Ask specific, contextual questions
3. **Leverage File Context**: AI understands your project structure
4. **Review AI Suggestions**: Always review and validate AI-generated code
5. **Use Built-in Features**: Take advantage of Cursor's AI capabilities

#### **LLM Integration Best Practices**
1. **Local Models**: Use local models for sensitive code
2. **Context Awareness**: Provide relevant context to AI
3. **Iterative Refinement**: Use AI for initial suggestions, then refine
4. **Code Review**: Always review AI-generated code
5. **Security**: Be cautious with sensitive information in AI chats

### Troubleshooting

#### **Common Issues**

##### **VS Code Issues**
- **PHP IntelliSense not working**: Check PHP executable path in settings
- **Docker integration issues**: Ensure Docker Desktop is running
- **Extension conflicts**: Disable conflicting extensions

##### **Cursor Issues**
- **AI not responding**: Check internet connection and API keys
- **Context not working**: Ensure project is properly opened
- **Rules not applying**: Verify `.cursorrules` file is in project root

##### **LLM Integration Issues**
- **Local models not working**: Check Ollama installation and configuration
- **API rate limits**: Monitor usage and implement rate limiting
- **Context limitations**: Break large requests into smaller chunks

### Resources

#### **VS Code Resources**
- [VS Code Documentation](https://code.visualstudio.com/docs)
- [PHP Extension Pack](https://marketplace.visualstudio.com/items?itemName=DEVSENSE.phptools-vscode)
- [WordPress Development Guide](https://developer.wordpress.org/)

#### **Cursor Resources**
- [Cursor Documentation](https://cursor.sh/docs)
- [Cursor Rules Guide](https://cursor.sh/docs/rules)
- [AI Chat Best Practices](https://cursor.sh/docs/chat)

#### **LLM Resources**
- [Ollama Documentation](https://ollama.ai/docs)
- [Continue Documentation](https://continue.dev/docs)
- [Local Model Setup](https://github.com/ollama/ollama)

### Conclusion

Both VS Code and Cursor provide excellent development environments for the WP QR Trackr project. VS Code offers extensive customization and extension support, while Cursor provides integrated AI capabilities. Choose the editor that best fits your workflow and preferences.

The key to success with either editor is:
1. **Proper Configuration**: Set up the environment correctly
2. **Extension Management**: Install and configure useful extensions
3. **LLM Integration**: Leverage AI assistance effectively
4. **Project Standards**: Follow the established coding standards
5. **Continuous Learning**: Stay updated with new features and best practices

Remember that AI is a tool to enhance your productivity, not replace your expertise. Always review and validate AI-generated code, and use it as a starting point for your development work. 