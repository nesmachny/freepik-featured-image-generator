# Freepik Featured Image Generator

A WordPress plugin that generates AI-powered featured images for posts using the Freepik API. Supports multiple AI models, customizable styles, and automatic generation.

## Features

- **Multiple AI Models**: Mystic, Flux Dev, Flux Pro 1.1, Flux 2 Pro, Flux 2 Turbo, HyperFlux
- **Manual Style Selector**: Choose style preset manually or auto-detect by category
- **Customizable Styles**: Define visual styles for different content categories
- **Flexible Prompts**: Edit the system prompt template with placeholders
- **Output Formats**: Save images as WebP, AVIF, JPEG, or PNG
- **Auto-Generation**: Optionally generate images automatically when posts are published
- **Multiple Post Types**: Enable for posts, pages, or custom post types
- **Metabox Interface**: Easy-to-use interface in the post editor
- **REST API**: Programmatic access for custom integrations
- **Anti-Text Protection**: Built-in negative prompt to prevent text in images

## Installation

1. Download the plugin zip file
2. Go to WordPress Admin > Plugins > Add New > Upload Plugin
3. Upload the zip file and click "Install Now"
4. Activate the plugin
5. Go to Settings > AI Image Generator to configure

## Configuration

### API Key

1. Get your API key from [Freepik API](https://www.freepik.com/api)
2. Enter the key in Settings > AI Image Generator > API Key

### AI Models

| Model | Best For | Quality | Speed |
|-------|----------|---------|-------|
| Mystic | Illustrations, flat design | Excellent | Medium |
| Flux Dev | General purpose | Good | Fast |
| Flux Pro 1.1 | High quality | Premium | Slow |
| Flux 2 Pro | Latest premium | Premium | Medium |
| Flux 2 Turbo | Fast premium | Premium | Fast |
| HyperFlux | Speed priority | Good | Ultra Fast |

### Output Formats

| Format | Best For | File Size |
|--------|----------|-----------|
| WebP | Modern browsers (recommended) | Small |
| AVIF | Latest browsers | Smallest |
| JPEG | Maximum compatibility | Medium |
| PNG | Transparency support | Large |

### Aspect Ratios

- **1:1 Square** - Social media posts
- **4:3 Classic** - Traditional photos
- **3:2 Traditional** - Standard photographs
- **2:1 Horizontal** - Blog headers (recommended)
- **16:9 Widescreen** - Video thumbnails
- **21:9 Panoramic** - Wide banners
- **9:16 Vertical** - Stories, mobile

### System Prompt

Customize the prompt template using these placeholders:

| Placeholder | Description |
|-------------|-------------|
| `{title}` | Post title |
| `{style_description}` | Style colors from category |
| `{elements}` | Visual elements from category |
| `{mood}` | Image mood from category |
| `{category}` | Category name |
| `{excerpt}` | Post excerpt (first 100 chars) |

### Category Styles

Define visual styles for each content category:

- **Key**: Category slug (e.g., "taxes", "marketing")
- **Name**: Display name
- **Colors**: Color palette description
- **Elements**: Visual elements to include
- **Mood**: Overall mood/feel

## Usage

### Post Editor

1. Create or edit a post
2. Find the "AI Image Generator" metabox in the sidebar
3. Select style manually or use "Auto (by category)"
4. Click "Generate Image" or "Regenerate"
5. Wait 10-30 seconds for generation

### REST API

Generate image for a post:
```bash
POST /wp-json/fpfig/v1/generate/{post_id}
POST /wp-json/fpfig/v1/generate/{post_id}?style=marketing
POST /wp-json/fpfig/v1/generate/{post_id}?force=1
```

Generate with custom prompt:
```bash
POST /wp-json/fpfig/v1/custom
Content-Type: application/json

{
  "prompt": "Your custom prompt here",
  "post_id": 123  // Optional: attach to post
}
```

## Requirements

- WordPress 5.8 or higher
- PHP 7.4 or higher
- GD library (for WebP/AVIF conversion)
- Freepik API key with AI credits

## Changelog

### 1.2.1
- Fixed Flux Pro API endpoint URL
- Added new models: Flux 2 Pro, Flux 2 Turbo, HyperFlux
- Removed non-existent Flux Realism model

### 1.2.0
- Added manual style selector in metabox
- Style preview updates dynamically
- Selected style saved per post

### 1.1.1
- Added negative_prompt to prevent text in images

### 1.1.0
- Added output format selection (WebP, AVIF, JPEG, PNG)
- Image conversion with GD library

### 1.0.0
- Initial release
- Support for Mystic, Flux Dev, Flux Pro models
- Customizable styles and prompts
- Auto-generation option
- REST API endpoints

## License

GPL v2 or later

## Credits

Developed by [Sergey Nesmachny](https://nesmachny.com)

Uses [Freepik AI API](https://www.freepik.com/api) for image generation.
