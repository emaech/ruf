# Rename Uploaded Files (RUF) Plugin

![RUF! Rename Uploaded Files](https://github.com/emaech/ruf/raw/main/ruf/ruf.png)

## Description
The **Rename Uploaded Files** plugin for WordPress allows users to easily rename uploaded media files (attachments) and updates database references accordingly. This helps maintain organized media libraries and ensures that any references to renamed files across posts and pages are automatically updated.

## Features
- Allows renaming of media files (attachments) directly from the WordPress admin dashboard.
- Automatically updates references to renamed files in the content of posts and pages.
- Supports renaming of image thumbnails and webp versions, ensuring all related files are correctly updated.
- Updates the `guid` and file path in the WordPress database.

## Installation

1. Download the plugin file.
2. Upload the `ruf` plugin folder to the `/wp-content/plugins/` directory.
3. Activate the plugin through the 'Plugins' menu in WordPress.

## Usage - One at a Time
1. Go to **Media > Rename Files** from the WordPress admin dashboard.
2. Select the file you want to rename from the dropdown.
3. Enter the new name for the file.
4. Click the **Rename File** button to apply the changes.

The file name and all associated references (e.g., thumbnails, webp versions) will be updated automatically.

## Usage - Batch
1. Go to **Media > Rename Files** from the WordPress admin dashboard.
2. Enter the character string you want to **Replace**.
3. Enter the character string you want to replace those characters **With** - you can leave this field blank if you just want to remove the characters.
4. Click the **Batch Rename File** button to apply the changes.

The file find all files with the character string and rename them name as well as all associated references (e.g., thumbnails, webp versions) will be updated automatically.

## Issues
If you encounter any issues or bugs, you're on your own. I wrote this for myself and am not interested in maintaining it beyond my own needs.

## Disclaimer
This software is provided **"as is"**, without warranty of any kind, express or implied, including but not limited to the warranties of merchantability, fitness for a particular purpose, and noninfringement. By using this plugin, you agree to use it **at your own risk**. The author is not responsible for any damages, data loss, or other issues caused by the use of this plugin.


