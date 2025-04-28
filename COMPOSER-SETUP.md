# Setting up Composer for Local Business Directory Plugin

This document provides instructions for setting up Composer for the Local Business Directory WordPress plugin.

## Prerequisites

- PHP 7.4 or higher
- Command line access to your server
- Composer ([Get Composer](https://getcomposer.org/download/))

## Installation Steps

### 1. Install Homebrew (macOS only)

```bash
# Install Homebrew if not already installed
/bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"

# Add Homebrew to your PATH (the installer will suggest the correct command)
# It will be something like:
eval "$(/opt/homebrew/bin/brew shellenv)"
```

### 2. Install PHP

#### On macOS (using Homebrew)

```bash
brew install php
```

#### On Linux (Debian/Ubuntu)

```bash
sudo apt update
sudo apt install php php-cli php-common php-xml php-json php-mbstring php-zip
```

### 3. Install Composer

#### Via Homebrew (macOS)

```bash
brew install composer
```

#### Manually (all platforms)

```bash
# Download the installer
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"

# Run the installer
php composer-setup.php

# Move Composer to a global location
sudo mv composer.phar /usr/local/bin/composer

# Remove the installer
php -r "unlink('composer-setup.php');"
```

### 4. Install Dependencies

Navigate to your plugin directory and run:

```bash
composer install
```

This will install all required dependencies according to the `composer.json` file, including:
- WordPress stubs for IDE autocompletion
- WordPress Coding Standards for PHP_CodeSniffer
- PHP Compatibility checking

## Development Tools Usage

The plugin includes several development tools:

### PHP_CodeSniffer

Check your code against WordPress standards:

```bash
composer phpcs
```

Fix some common issues automatically:

```bash
composer phpcbf
```

### IDE Configuration

The plugin includes configuration files for Visual Studio Code. Install the following extensions:
- PHP Intelephense
- phpcs

VS Code will automatically use the WordPress stubs for code completion and error checking.

## Troubleshooting

### Command Not Found

If you see `command not found: composer` after installation:

1. Check if Composer is in your PATH by running: `echo $PATH`
2. If using Homebrew, run: `eval "$(/opt/homebrew/bin/brew shellenv)"`
3. Try using the full path to Composer: `/usr/local/bin/composer`

### Memory Limit Issues

If you encounter memory limit errors, try:

```bash
php -d memory_limit=-1 /usr/local/bin/composer install
``` 