# Lunio for WordPress

This plugin allows you to embed a Canadian tax calculator on your WordPress site using the Lunio Developer API.

## Features

- Clean, responsive tax calculator shortcode
- Supports all Canadian provinces and territories
- Calculates GST, HST, PST, and QST as applicable
- Secure API key management
- Debug mode for troubleshooting

## Installation

1. Download the plugin zip file.
2. Go to WordPress Admin > Plugins > Add New > Upload Plugin.
3. Upload the zip file and activate the plugin.

## Setup

1. Obtain a Lunio API key from [Lunio Developer Portal](https://lunio.ca/developers).
2. Go to Settings > Lunio in your WordPress admin.
3. Enter your API key and save settings.
4. Click "Test Connection" to verify the API key works.

## Usage

Add the shortcode `[lunio_tax_calculator]` to any page or post to display the tax calculator.

## API Plan

This plugin requires a Lunio API plan. Visit [Lunio Pricing](https://lunio.ca/pricing) for details.

## Troubleshooting

- Ensure your API key is correct and active.
- Check debug mode in settings for error logs.
- Verify your server can make outbound HTTPS requests.

## Requirements

- WordPress 5.0+
- PHP 7.0+
- Active Lunio API subscription