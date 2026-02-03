# AI Featured Image Generator

A WordPress plugin that generates AI-powered featured images for posts using the Freepik API. Supports multiple AI models, customizable styles, and automatic generation.

## Features

- **Multiple AI Models**: Choose from Mystic, Flux Dev, Flux Pro, or Flux Realism
- **Customizable Styles**: Define visual styles for different content categories
- **Flexible Prompts**: Edit the system prompt template with placeholders
- **Auto-Generation**: Optionally generate images automatically when posts are published
- **Multiple Post Types**: Enable for posts, pages, or custom post types
- **Metabox Interface**: Easy-to-use interface in the post editor
- **REST API**: Programmatic access for custom integrations

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
| Flux Pro | High quality | Premium | Slow |
| Flux Realism | Photorealistic | Excellent | Medium |

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
3. Review the style preview based on the post's category
4. Click "Generate Image" or "Regenerate"
5. Wait 10-30 seconds for generation

### REST API

Generate image for a post:
```bash
POST /wp-json/aifig/v1/generate/{post_id}
```

Generate with custom prompt:
```bash
POST /wp-json/aifig/v1/custom
Content-Type: application/json

{
  "prompt": "Your custom prompt here",
  "post_id": 123  // Optional: attach to post
}
```

## Requirements

- WordPress 5.8 or higher
- PHP 7.4 or higher
- Freepik API key with AI credits

## Changelog

### 1.0.0
- Initial release
- Support for Mystic, Flux Dev, Flux Pro, Flux Realism models
- Customizable styles and prompts
- Auto-generation option
- REST API endpoints

## License

GPL v2 or later

## Credits

Developed by [Sergey Nesmachny](https://easyfin.pt)

Uses [Freepik AI API](https://www.freepik.com/api) for image generation.
