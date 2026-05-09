# Lunio for WordPress

This plugin allows you to embed a Canadian tax calculator on your WordPress site using the Lunio Developer API.

## Features

- Clean, responsive tax calculator shortcode
- Supports all Canadian provinces and territories
- Calculates GST, HST, PST, and QST as applicable
- Customizable shortcode attributes for layout and behavior
- Gutenberg block for visual editor integration
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

### Gutenberg Block

For visual editing, use the "Lunio Tax Calculator" block in the Gutenberg editor (under Widgets category). The block provides sidebar controls for all calculator options and renders the same shortcode on the frontend.

### Shortcode Attributes

You can customize the calculator using the following attributes:

- `province`: Pre-select a Canadian province (e.g., `province="ON"` for Ontario). Valid codes: AB, BC, MB, NB, NL, NT, NS, NU, ON, PE, QC, SK, YT.
- `show_breakdown`: Show/hide individual tax breakdown rows. Default: `true`. Use `show_breakdown="false"` to hide GST/HST/PST/QST rows.
- `powered_by`: Show/hide the "Powered by Lunio" link. Default: `true`. Use `powered_by="false"` to hide.
- `layout`: Choose layout style. Default: `full`. Use `layout="compact"` for a smaller, tighter design.

#### Examples

- Basic: `[lunio_tax_calculator]`
- Pre-selected province: `[lunio_tax_calculator province="QC"]`
- Compact layout without breakdown: `[lunio_tax_calculator layout="compact" show_breakdown="false"]`
- Custom setup: `[lunio_tax_calculator province="ON" powered_by="false" layout="compact"]`

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