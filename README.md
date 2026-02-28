# Freepik Featured Image Generator

A WordPress plugin that generates AI-powered featured images for posts using Freepik or OpenRouter API. Supports multiple providers, AI models, customizable styles, and automatic generation.

## Features

- **Multiple API Providers**: Freepik and OpenRouter with easy switching
- **AI-Powered Prompts**: OpenRouter uses a text LLM to analyze post content and generate optimal image prompts
- **Multiple AI Models**:
  - Freepik: Mystic, Flux Dev, Flux Pro 1.1, Flux 2 Pro, Flux 2 Turbo, HyperFlux
  - OpenRouter: Nano Banana 2 (Gemini 3.1 Flash), Nano Banana (Gemini 2.5 Flash), Nano Banana Pro (Gemini 3 Pro), GPT-5 Image
- **Manual Style Selector**: Choose style preset manually or auto-detect by category
- **Customizable Styles**: Define visual styles for different content categories
- **Flexible Prompts**: Edit prompt templates with placeholders (Freepik) or LLM system instructions (OpenRouter)
- **Output Formats**: Save images as WebP, AVIF, JPEG, or PNG
- **Auto-Generation**: Optionally generate images automatically when posts are published
- **Multiple Post Types**: Enable for posts, pages, or custom post types
- **Metabox Interface**: Easy-to-use interface in the post editor
- **REST API**: Programmatic access for custom integrations
- **Anti-Text Protection**: Built-in negative prompt to prevent text in images

## Installation

1. [Download the latest release](https://github.com/nesmachny/freepik-featured-image-generator/releases/latest/download/freepik-featured-image-generator.zip)
2. Go to WordPress Admin > Plugins > Add New > Upload Plugin
3. Upload the zip file and click "Install Now"
4. Activate the plugin
5. Go to Settings > AI Image Generator to configure

## Configuration

### API Provider

Choose between **Freepik** and **OpenRouter** in Settings > AI Image Generator > API Provider. The settings page dynamically shows configuration fields for the selected provider.

### Freepik Setup

1. Get your API key from [Freepik API](https://www.freepik.com/api)
2. Enter the key in Freepik API Key field

### OpenRouter Setup

1. Get your API key from [OpenRouter](https://openrouter.ai/keys)
2. Enter the key in OpenRouter API Key field
3. Select an Image Model for generation
4. Select a Prompt Model (text LLM that analyzes your content)
5. Customize the Prompt System Instructions if needed

### How OpenRouter Works

OpenRouter uses a two-step process:

1. **Step 1 — Prompt Generation**: A text LLM (e.g., Gemini 2.5 Flash) reads the full post content, title, category, and style preferences, then generates an optimized image generation prompt
2. **Step 2 — Image Generation**: The generated prompt is sent to an image model (e.g., Gemini 3.1 Flash) to create the featured image

This produces more contextually relevant images compared to template-based prompts.

### AI Models

#### Freepik Models

| Model | Best For | Quality | Speed |
|-------|----------|---------|-------|
| Mystic | Illustrations, flat design | Excellent | Medium |
| Flux Dev | General purpose | Good | Fast |
| Flux Pro 1.1 | High quality | Premium | Slow |
| Flux 2 Pro | Latest premium | Premium | Medium |
| Flux 2 Turbo | Fast premium | Premium | Fast |
| HyperFlux | Speed priority | Good | Ultra Fast |

#### OpenRouter Image Models

| Model | Description |
|-------|-------------|
| Nano Banana 2 (Gemini 3.1 Flash) | Latest, recommended |
| Nano Banana (Gemini 2.5 Flash) | Stable, fast |
| Nano Banana Pro (Gemini 3 Pro) | Higher quality |
| GPT-5 Image | OpenAI image generation |

#### OpenRouter Prompt Models

| Model | Best For |
|-------|----------|
| Gemini 2.5 Flash | Fast, cheap, recommended |
| Gemini 2.5 Pro | Higher quality prompts |
| Claude Sonnet 4 | Creative prompts |
| GPT-4.1 Mini | Fast alternative |

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

### System Prompt (Freepik)

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
- Freepik API key or OpenRouter API key

## Changelog

### 1.3.0
- Added OpenRouter as second API provider
- AI-powered prompt generation: text LLM reads post content and creates optimized image prompts
- New image models: Gemini 3.1 Flash, Gemini 2.5 Flash, Gemini 3 Pro, GPT-5 Image
- New prompt models: Gemini 2.5 Flash, Gemini 2.5 Pro, Claude Sonnet 4, GPT-4.1 Mini
- Configurable prompt system instructions
- Dynamic settings page: shows/hides fields based on selected provider
- Provider-aware API test, metabox, and notices

### 1.2.3
- Added output quality slider for JPEG/WebP/AVIF
- Minor UI improvements

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

Uses [Freepik AI API](https://www.freepik.com/api) and [OpenRouter API](https://openrouter.ai) for image generation.
