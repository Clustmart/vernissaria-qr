#!/bin/bash

# WordPress Plugin ZIP Creator
# Creates a clean ZIP file for WordPress plugin distribution

# Plugin name (change this to match your plugin folder name)
PLUGIN_NAME="vernissaria-qr"

# Get the current directory
CURRENT_DIR=$(pwd)

rm "$CURRENT_DIR/$PLUGIN_NAME.zip"

# Create a temporary directory
TEMP_DIR=$(mktemp -d)

# Copy plugin files to temp directory, excluding hidden files and unnecessary items
rsync -av --exclude=".*" \
          --exclude=".git*" \
          --exclude=".DS_Store" \
          --exclude="Thumbs.db" \
          "./" "$TEMP_DIR/$PLUGIN_NAME/"

# Create ZIP file
cd "$TEMP_DIR"

zip -r "$CURRENT_DIR/$PLUGIN_NAME.zip" "$PLUGIN_NAME"

# Cleanup
# rm -rf "$TEMP_DIR"

echo "Plugin ZIP created: $PLUGIN_NAME.zip"
# echo "File size: $(du -h "$CURRENT_DIR/$PLUGIN_NAME.zip" | cut -f1)"

# List contents of the ZIP file
# echo -e "\nContents of $PLUGIN_NAME.zip:"
# unzip -l "$CURRENT_DIR/$PLUGIN_NAME.zip"