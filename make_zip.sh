#!/bin/bash

# WordPress Plugin ZIP Creator
# Creates a clean ZIP file for WordPress plugin distribution

# Plugin name (change this to match your plugin folder name)
PLUGIN_NAME="vernissaria-qr"

# Get the current directory
CURRENT_DIR=$(pwd)

rm "$CURRENT_DIR/$PLUGIN_NAME.zip"



# zip -r "$PLUGIN_NAME.zip" "$PLUGIN_NAME" -x ‘**/.*’ -x ‘**/__MACOS’


zip -r "$CURRENT_DIR/$PLUGIN_NAME.zip" "$PLUGIN_NAME"


echo "Plugin ZIP created: $PLUGIN_NAME.zip"
# echo "File size: $(du -h "$CURRENT_DIR/$PLUGIN_NAME.zip" | cut -f1)"
