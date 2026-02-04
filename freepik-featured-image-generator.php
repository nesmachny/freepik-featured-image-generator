<?php
/**
 * Plugin Name: Freepik Featured Image Generator
 * Plugin URI: https://nesmachny.com/freepik-featured-image-generator
 * Description: Generate AI-powered featured images for posts using Freepik API. Supports multiple models, customizable styles, and automatic generation.
 * Version: 1.2.3
 * Author: Sergey Nesmachny
 * Author URI: https://nesmachny.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: freepik-featured-image-generator
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants
define('FPFIG_VERSION', '1.2.3');
define('FPFIG_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('FPFIG_PLUGIN_URL', plugin_dir_url(__FILE__));
define('FPFIG_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main plugin class
 */
class Freepik_Featured_Image_Generator {

    /**
     * Plugin instance
     */
    private static $instance = null;

    /**
     * Plugin options
     */
    private $options;

    /**
     * Get plugin instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->options = get_option('fpfig_settings', $this->get_default_options());

        // Admin hooks
        add_action('admin_menu', [$this, 'add_settings_page']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('add_meta_boxes', [$this, 'add_metabox']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);

        // REST API
        add_action('rest_api_init', [$this, 'register_rest_routes']);

        // Auto-generation hook (if enabled)
        if ($this->get_option('auto_generate')) {
            add_action('wp_insert_post', [$this, 'auto_generate_on_publish'], 10, 3);
        }

        // Plugin action links
        add_filter('plugin_action_links_' . FPFIG_PLUGIN_BASENAME, [$this, 'add_action_links']);
    }

    /**
     * Get default options (static version for activation hook)
     */
    public static function get_default_options_static() {
        return [
            'api_key' => '',
            'model' => 'mystic',
            'sub_model' => 'flexible',
            'aspect_ratio' => 'horizontal_2_1',
            'resolution' => '1k',
            'creative_detailing' => 50,
            'output_format' => 'webp',
            'output_quality' => 85,
            'auto_generate' => false,
            'post_types' => ['post'],
            'system_prompt' => "Create a professional blog header illustration for an article titled '{title}'. " .
                "Style: {style_description}. " .
                "Include visual elements: {elements}. " .
                "Mood: {mood}. " .
                "CRITICAL: Absolutely NO text, NO letters, NO words, NO numbers, NO typography, NO writing, NO captions, NO labels, NO signs anywhere in the image - the image must be completely text-free. " .
                "If currency symbols are used, use Euro (€) symbol, never US Dollar ($). " .
                "Clean simple background, professional corporate style. " .
                "High quality, sharp details, photorealistic rendering.",
            'category_styles' => [
                'default' => [
                    'name' => 'Default',
                    'colors' => 'teal and white tones',
                    'elements' => 'financial charts, business elements',
                    'mood' => 'professional and trustworthy',
                ],
                'taxes' => [
                    'name' => 'Taxes',
                    'colors' => 'warm amber and gold tones',
                    'elements' => 'calendar, documents, calculator, euro coins',
                    'mood' => 'professional and organized',
                ],
                'invoicing' => [
                    'name' => 'Invoicing',
                    'colors' => 'blue and white tones',
                    'elements' => 'invoices, receipts, digital documents, laptop',
                    'mood' => 'modern and efficient',
                ],
                'business' => [
                    'name' => 'Business Tips',
                    'colors' => 'green and teal tones',
                    'elements' => 'growth charts, lightbulb, business icons',
                    'mood' => 'inspiring and helpful',
                ],
                'news' => [
                    'name' => 'News',
                    'colors' => 'purple and violet tones',
                    'elements' => 'newspaper, notifications, megaphone',
                    'mood' => 'dynamic and informative',
                ],
                'technology' => [
                    'name' => 'Technology',
                    'colors' => 'teal and cyan tones',
                    'elements' => 'dashboard, integration icons, connected devices',
                    'mood' => 'tech-forward and seamless',
                ],
            ],
        ];
    }

    /**
     * Get default options
     */
    public function get_default_options() {
        return self::get_default_options_static();
    }

    /**
     * Get default category styles
     */
    public function get_default_category_styles() {
        return [
            'default' => [
                'name' => 'Default',
                'colors' => 'teal and white tones',
                'elements' => 'financial charts, business elements',
                'mood' => 'professional and trustworthy',
            ],
            'taxes' => [
                'name' => 'Taxes',
                'colors' => 'warm amber and gold tones',
                'elements' => 'calendar, documents, calculator, euro coins',
                'mood' => 'professional and organized',
            ],
            'invoicing' => [
                'name' => 'Invoicing',
                'colors' => 'blue and white tones',
                'elements' => 'invoices, receipts, digital documents, laptop',
                'mood' => 'modern and efficient',
            ],
            'business' => [
                'name' => 'Business Tips',
                'colors' => 'green and teal tones',
                'elements' => 'growth charts, lightbulb, business icons',
                'mood' => 'inspiring and helpful',
            ],
            'news' => [
                'name' => 'News',
                'colors' => 'purple and violet tones',
                'elements' => 'newspaper, notifications, megaphone',
                'mood' => 'dynamic and informative',
            ],
            'technology' => [
                'name' => 'Technology',
                'colors' => 'teal and cyan tones',
                'elements' => 'dashboard, integration icons, connected devices',
                'mood' => 'tech-forward and seamless',
            ],
        ];
    }

    /**
     * Get option value
     */
    public function get_option($key, $default = null) {
        if (isset($this->options[$key])) {
            return $this->options[$key];
        }
        $defaults = $this->get_default_options();
        return $default ?? ($defaults[$key] ?? null);
    }

    /**
     * Get API URL based on model
     */
    public function get_api_url() {
        $model = $this->get_option('model');
        $urls = [
            'mystic' => 'https://api.freepik.com/v1/ai/mystic',
            'flux-dev' => 'https://api.freepik.com/v1/ai/text-to-image/flux-dev',
            'flux-pro' => 'https://api.freepik.com/v1/ai/text-to-image/flux-pro-v1-1',
            'flux-2-pro' => 'https://api.freepik.com/v1/ai/text-to-image/flux-2-pro',
            'flux-2-turbo' => 'https://api.freepik.com/v1/ai/text-to-image/flux-2-turbo',
            'hyperflux' => 'https://api.freepik.com/v1/ai/text-to-image/hyperflux',
        ];
        return $urls[$model] ?? $urls['mystic'];
    }

    /**
     * Add settings page to admin menu
     */
    public function add_settings_page() {
        add_options_page(
            __('AI Featured Image Generator', 'freepik-featured-image-generator'),
            __('AI Image Generator', 'freepik-featured-image-generator'),
            'manage_options',
            'freepik-featured-image-generator',
            [$this, 'render_settings_page']
        );
    }

    /**
     * Register plugin settings
     */
    public function register_settings() {
        register_setting('fpfig_settings_group', 'fpfig_settings', [
            'sanitize_callback' => [$this, 'sanitize_settings'],
        ]);

        // API Section
        add_settings_section(
            'fpfig_api_section',
            __('API Configuration', 'freepik-featured-image-generator'),
            [$this, 'render_api_section'],
            'freepik-featured-image-generator'
        );

        add_settings_field(
            'api_key',
            __('Freepik API Key', 'freepik-featured-image-generator'),
            [$this, 'render_api_key_field'],
            'freepik-featured-image-generator',
            'fpfig_api_section'
        );

        // Model Section
        add_settings_section(
            'fpfig_model_section',
            __('Image Generation Settings', 'freepik-featured-image-generator'),
            [$this, 'render_model_section'],
            'freepik-featured-image-generator'
        );

        add_settings_field(
            'model',
            __('AI Model', 'freepik-featured-image-generator'),
            [$this, 'render_model_field'],
            'freepik-featured-image-generator',
            'fpfig_model_section'
        );

        add_settings_field(
            'aspect_ratio',
            __('Aspect Ratio', 'freepik-featured-image-generator'),
            [$this, 'render_aspect_ratio_field'],
            'freepik-featured-image-generator',
            'fpfig_model_section'
        );

        add_settings_field(
            'resolution',
            __('Resolution', 'freepik-featured-image-generator'),
            [$this, 'render_resolution_field'],
            'freepik-featured-image-generator',
            'fpfig_model_section'
        );

        add_settings_field(
            'creative_detailing',
            __('Creative Detailing', 'freepik-featured-image-generator'),
            [$this, 'render_creative_detailing_field'],
            'freepik-featured-image-generator',
            'fpfig_model_section'
        );

        add_settings_field(
            'output_format',
            __('Output Format', 'freepik-featured-image-generator'),
            [$this, 'render_output_format_field'],
            'freepik-featured-image-generator',
            'fpfig_model_section'
        );

        add_settings_field(
            'output_quality',
            __('Output Quality', 'freepik-featured-image-generator'),
            [$this, 'render_output_quality_field'],
            'freepik-featured-image-generator',
            'fpfig_model_section'
        );

        // Prompt Section
        add_settings_section(
            'fpfig_prompt_section',
            __('Prompt Template', 'freepik-featured-image-generator'),
            [$this, 'render_prompt_section'],
            'freepik-featured-image-generator'
        );

        add_settings_field(
            'system_prompt',
            __('System Prompt', 'freepik-featured-image-generator'),
            [$this, 'render_system_prompt_field'],
            'freepik-featured-image-generator',
            'fpfig_prompt_section'
        );

        // Styles Section
        add_settings_section(
            'fpfig_styles_section',
            __('Category Styles', 'freepik-featured-image-generator'),
            [$this, 'render_styles_section'],
            'freepik-featured-image-generator'
        );

        add_settings_field(
            'category_styles',
            __('Style Presets', 'freepik-featured-image-generator'),
            [$this, 'render_category_styles_field'],
            'freepik-featured-image-generator',
            'fpfig_styles_section'
        );

        // Automation Section
        add_settings_section(
            'fpfig_automation_section',
            __('Automation', 'freepik-featured-image-generator'),
            [$this, 'render_automation_section'],
            'freepik-featured-image-generator'
        );

        add_settings_field(
            'auto_generate',
            __('Auto-Generate', 'freepik-featured-image-generator'),
            [$this, 'render_auto_generate_field'],
            'freepik-featured-image-generator',
            'fpfig_automation_section'
        );

        add_settings_field(
            'post_types',
            __('Post Types', 'freepik-featured-image-generator'),
            [$this, 'render_post_types_field'],
            'freepik-featured-image-generator',
            'fpfig_automation_section'
        );
    }

    /**
     * Sanitize settings
     */
    public function sanitize_settings($input) {
        $sanitized = [];

        $sanitized['api_key'] = sanitize_text_field($input['api_key'] ?? '');
        $sanitized['model'] = sanitize_text_field($input['model'] ?? 'mystic');
        $sanitized['sub_model'] = sanitize_text_field($input['sub_model'] ?? 'flexible');
        $sanitized['aspect_ratio'] = sanitize_text_field($input['aspect_ratio'] ?? 'horizontal_2_1');
        $sanitized['resolution'] = sanitize_text_field($input['resolution'] ?? '1k');
        $sanitized['creative_detailing'] = absint($input['creative_detailing'] ?? 50);
        $sanitized['output_format'] = sanitize_text_field($input['output_format'] ?? 'webp');
        $sanitized['output_quality'] = absint($input['output_quality'] ?? 85);
        $sanitized['auto_generate'] = !empty($input['auto_generate']);
        $sanitized['system_prompt'] = wp_kses_post($input['system_prompt'] ?? '');

        // Post types
        $sanitized['post_types'] = [];
        if (!empty($input['post_types']) && is_array($input['post_types'])) {
            foreach ($input['post_types'] as $pt) {
                $sanitized['post_types'][] = sanitize_key($pt);
            }
        }

        // Category styles
        $sanitized['category_styles'] = [];
        if (!empty($input['category_styles']) && is_array($input['category_styles'])) {
            foreach ($input['category_styles'] as $key => $style) {
                $sanitized['category_styles'][sanitize_key($key)] = [
                    'name' => sanitize_text_field($style['name'] ?? ''),
                    'colors' => sanitize_text_field($style['colors'] ?? ''),
                    'elements' => sanitize_text_field($style['elements'] ?? ''),
                    'mood' => sanitize_text_field($style['mood'] ?? ''),
                ];
            }
        }

        return $sanitized;
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

            <?php if (!$this->get_option('api_key')): ?>
                <div class="notice notice-warning">
                    <p>
                        <strong><?php _e('API Key Required', 'freepik-featured-image-generator'); ?></strong><br>
                        <?php _e('Please enter your Freepik API key to enable image generation.', 'freepik-featured-image-generator'); ?>
                        <a href="https://www.freepik.com/api" target="_blank"><?php _e('Get API Key', 'freepik-featured-image-generator'); ?></a>
                    </p>
                </div>
            <?php endif; ?>

            <form method="post" action="options.php">
                <?php
                settings_fields('fpfig_settings_group');
                do_settings_sections('freepik-featured-image-generator');
                submit_button();
                ?>
            </form>

            <div class="aifig-test-section">
                <h2><?php _e('Test API Connection', 'freepik-featured-image-generator'); ?></h2>
                <button type="button" class="button" id="fpfig-test-api">
                    <?php _e('Test Connection', 'freepik-featured-image-generator'); ?>
                </button>
                <span id="fpfig-test-result"></span>
            </div>
        </div>

        <style>
            .aifig-test-section {
                margin-top: 30px;
                padding: 20px;
                background: #fff;
                border: 1px solid #ccd0d4;
                border-radius: 4px;
            }
            .aifig-test-section h2 {
                margin-top: 0;
            }
            #fpfig-test-result {
                margin-left: 15px;
            }
            .aifig-style-row {
                display: flex;
                gap: 10px;
                margin-bottom: 10px;
                padding: 15px;
                background: #f9f9f9;
                border: 1px solid #ddd;
                border-radius: 4px;
            }
            .aifig-style-row input[type="text"] {
                flex: 1;
            }
            .aifig-style-row .aifig-style-key {
                width: 100px;
                font-weight: bold;
            }
            .aifig-style-row .aifig-remove-style {
                color: #a00;
                cursor: pointer;
            }
            .aifig-add-style {
                margin-top: 10px;
            }
            .aifig-prompt-help {
                margin-top: 10px;
                padding: 10px;
                background: #e7f3ff;
                border-radius: 4px;
            }
            .aifig-prompt-help code {
                background: #fff;
                padding: 2px 6px;
                border-radius: 3px;
            }
        </style>

        <script>
        jQuery(document).ready(function($) {
            // Test API connection
            $('#fpfig-test-api').on('click', function() {
                var $btn = $(this);
                var $result = $('#fpfig-test-result');

                $btn.prop('disabled', true).text('<?php _e('Testing...', 'freepik-featured-image-generator'); ?>');
                $result.html('');

                $.post(ajaxurl, {
                    action: 'fpfig_test_api',
                    nonce: '<?php echo wp_create_nonce('fpfig_test_api'); ?>'
                }, function(response) {
                    $btn.prop('disabled', false).text('<?php _e('Test Connection', 'freepik-featured-image-generator'); ?>');
                    if (response.success) {
                        $result.html('<span style="color:green;">✅ ' + response.data.message + '</span>');
                    } else {
                        $result.html('<span style="color:red;">❌ ' + response.data.message + '</span>');
                    }
                }).fail(function() {
                    $btn.prop('disabled', false).text('<?php _e('Test Connection', 'freepik-featured-image-generator'); ?>');
                    $result.html('<span style="color:red;">❌ <?php _e('Network error', 'freepik-featured-image-generator'); ?></span>');
                });
            });

            // Add new style
            $(document).on('click', '.aifig-add-style', function() {
                var key = prompt('<?php _e('Enter style key (e.g., "marketing", "finance"):', 'freepik-featured-image-generator'); ?>');
                if (!key) return;
                key = key.toLowerCase().replace(/[^a-z0-9_-]/g, '');

                var $container = $('#fpfig-styles-container');
                var html = `
                    <div class="aifig-style-row" data-key="${key}">
                        <span class="aifig-style-key">${key}</span>
                        <input type="text" name="fpfig_settings[category_styles][${key}][name]" placeholder="<?php _e('Display Name', 'freepik-featured-image-generator'); ?>" value="">
                        <input type="text" name="fpfig_settings[category_styles][${key}][colors]" placeholder="<?php _e('Colors', 'freepik-featured-image-generator'); ?>" value="">
                        <input type="text" name="fpfig_settings[category_styles][${key}][elements]" placeholder="<?php _e('Elements', 'freepik-featured-image-generator'); ?>" value="">
                        <input type="text" name="fpfig_settings[category_styles][${key}][mood]" placeholder="<?php _e('Mood', 'freepik-featured-image-generator'); ?>" value="">
                        <span class="aifig-remove-style" title="<?php _e('Remove', 'freepik-featured-image-generator'); ?>">×</span>
                    </div>
                `;
                $container.append(html);
            });

            // Remove style
            $(document).on('click', '.aifig-remove-style', function() {
                if (confirm('<?php _e('Remove this style?', 'freepik-featured-image-generator'); ?>')) {
                    $(this).closest('.aifig-style-row').remove();
                }
            });
        });
        </script>
        <?php
    }

    /**
     * Render section descriptions
     */
    public function render_api_section() {
        echo '<p>' . __('Configure your Freepik API credentials.', 'freepik-featured-image-generator') . '</p>';
    }

    public function render_model_section() {
        echo '<p>' . __('Configure image generation parameters.', 'freepik-featured-image-generator') . '</p>';
    }

    public function render_prompt_section() {
        echo '<p>' . __('Customize the prompt template used for image generation.', 'freepik-featured-image-generator') . '</p>';
    }

    public function render_styles_section() {
        echo '<p>' . __('Define visual styles for different content categories. The style will be selected based on the post\'s primary category.', 'freepik-featured-image-generator') . '</p>';
    }

    public function render_automation_section() {
        echo '<p>' . __('Configure automatic image generation behavior.', 'freepik-featured-image-generator') . '</p>';
    }

    /**
     * Render form fields
     */
    public function render_api_key_field() {
        $value = $this->get_option('api_key');
        ?>
        <input type="password" name="fpfig_settings[api_key]" value="<?php echo esc_attr($value); ?>" class="regular-text" autocomplete="off">
        <p class="description">
            <?php _e('Get your API key from', 'freepik-featured-image-generator'); ?>
            <a href="https://www.freepik.com/api" target="_blank">Freepik API</a>
        </p>
        <?php
    }

    public function render_model_field() {
        $value = $this->get_option('model');
        $sub_model = $this->get_option('sub_model');
        ?>
        <select name="fpfig_settings[model]" id="fpfig-model-select">
            <option value="mystic" <?php selected($value, 'mystic'); ?>>Mystic (Best for illustrations)</option>
            <option value="flux-dev" <?php selected($value, 'flux-dev'); ?>>Flux Dev (Fast, good quality)</option>
            <option value="flux-pro" <?php selected($value, 'flux-pro'); ?>>Flux Pro 1.1 (Premium quality)</option>
            <option value="flux-2-pro" <?php selected($value, 'flux-2-pro'); ?>>Flux 2 Pro (Latest premium)</option>
            <option value="flux-2-turbo" <?php selected($value, 'flux-2-turbo'); ?>>Flux 2 Turbo (Fast premium)</option>
            <option value="hyperflux" <?php selected($value, 'hyperflux'); ?>>HyperFlux (Ultra fast)</option>
        </select>

        <div id="fpfig-submodel-container" style="margin-top: 10px; <?php echo $value !== 'mystic' ? 'display:none;' : ''; ?>">
            <label><?php _e('Mystic Sub-Model:', 'freepik-featured-image-generator'); ?></label>
            <select name="fpfig_settings[sub_model]">
                <option value="zen" <?php selected($sub_model, 'zen'); ?>>Zen (Artistic)</option>
                <option value="flexible" <?php selected($sub_model, 'flexible'); ?>>Flexible (Balanced)</option>
                <option value="fluid" <?php selected($sub_model, 'fluid'); ?>>Fluid (Creative)</option>
            </select>
        </div>

        <script>
        jQuery('#fpfig-model-select').on('change', function() {
            jQuery('#fpfig-submodel-container').toggle(this.value === 'mystic');
        });
        </script>
        <?php
    }

    public function render_aspect_ratio_field() {
        $value = $this->get_option('aspect_ratio');
        ?>
        <select name="fpfig_settings[aspect_ratio]">
            <option value="square_1_1" <?php selected($value, 'square_1_1'); ?>>1:1 Square</option>
            <option value="classic_4_3" <?php selected($value, 'classic_4_3'); ?>>4:3 Classic</option>
            <option value="traditional_3_2" <?php selected($value, 'traditional_3_2'); ?>>3:2 Traditional</option>
            <option value="horizontal_2_1" <?php selected($value, 'horizontal_2_1'); ?>>2:1 Horizontal (Recommended for blogs)</option>
            <option value="widescreen_16_9" <?php selected($value, 'widescreen_16_9'); ?>>16:9 Widescreen</option>
            <option value="panoramic_21_9" <?php selected($value, 'panoramic_21_9'); ?>>21:9 Panoramic</option>
            <option value="social_story_9_16" <?php selected($value, 'social_story_9_16'); ?>>9:16 Vertical/Story</option>
        </select>
        <p class="description"><?php _e('Recommended: 2:1 Horizontal for blog featured images', 'freepik-featured-image-generator'); ?></p>
        <?php
    }

    public function render_resolution_field() {
        $value = $this->get_option('resolution');
        ?>
        <select name="fpfig_settings[resolution]">
            <option value="1k" <?php selected($value, '1k'); ?>>1K (1024px - saves credits)</option>
            <option value="2k" <?php selected($value, '2k'); ?>>2K (2048px - higher quality)</option>
        </select>
        <p class="description"><?php _e('1K is usually sufficient for web use', 'freepik-featured-image-generator'); ?></p>
        <?php
    }

    public function render_creative_detailing_field() {
        $value = $this->get_option('creative_detailing');
        ?>
        <input type="range" name="fpfig_settings[creative_detailing]" min="0" max="100" value="<?php echo esc_attr($value); ?>" id="fpfig-creative-slider">
        <span id="fpfig-creative-value"><?php echo esc_html($value); ?></span>%
        <p class="description"><?php _e('Higher values = more creative freedom, lower = more prompt adherence', 'freepik-featured-image-generator'); ?></p>
        <script>
        jQuery('#fpfig-creative-slider').on('input', function() {
            jQuery('#fpfig-creative-value').text(this.value);
        });
        </script>
        <?php
    }

    public function render_output_format_field() {
        $value = $this->get_option('output_format');
        $avif_supported = function_exists('imageavif');
        $webp_supported = function_exists('imagewebp');
        ?>
        <select name="fpfig_settings[output_format]">
            <option value="original" <?php selected($value, 'original'); ?>><?php _e('Original (as received from API)', 'freepik-featured-image-generator'); ?></option>
            <option value="jpg" <?php selected($value, 'jpg'); ?>>JPEG</option>
            <option value="png" <?php selected($value, 'png'); ?>>PNG</option>
            <?php if ($webp_supported): ?>
                <option value="webp" <?php selected($value, 'webp'); ?>>WebP <?php _e('(Recommended)', 'freepik-featured-image-generator'); ?></option>
            <?php else: ?>
                <option value="webp" disabled>WebP (<?php _e('Not supported by server', 'freepik-featured-image-generator'); ?>)</option>
            <?php endif; ?>
            <?php if ($avif_supported): ?>
                <option value="avif" <?php selected($value, 'avif'); ?>>AVIF <?php _e('(Best compression)', 'freepik-featured-image-generator'); ?></option>
            <?php else: ?>
                <option value="avif" disabled>AVIF (<?php _e('Not supported by server', 'freepik-featured-image-generator'); ?>)</option>
            <?php endif; ?>
        </select>
        <p class="description">
            <?php _e('WebP offers ~30% smaller files than JPEG/PNG. AVIF offers even better compression but requires PHP 8.1+.', 'freepik-featured-image-generator'); ?>
            <br>
            <strong><?php _e('Server support:', 'freepik-featured-image-generator'); ?></strong>
            WebP: <?php echo $webp_supported ? '✅' : '❌'; ?>,
            AVIF: <?php echo $avif_supported ? '✅' : '❌'; ?>
        </p>
        <?php
    }

    public function render_output_quality_field() {
        $value = $this->get_option('output_quality');
        ?>
        <input type="range" name="fpfig_settings[output_quality]" min="50" max="100" value="<?php echo esc_attr($value); ?>" id="fpfig-quality-slider">
        <span id="fpfig-quality-value"><?php echo esc_html($value); ?></span>%
        <p class="description"><?php _e('Quality for JPEG/WebP/AVIF. Higher = better quality but larger files. 80-90 is recommended.', 'freepik-featured-image-generator'); ?></p>
        <script>
        jQuery('#fpfig-quality-slider').on('input', function() {
            jQuery('#fpfig-quality-value').text(this.value);
        });
        </script>
        <?php
    }

    public function render_system_prompt_field() {
        $value = $this->get_option('system_prompt');
        ?>
        <textarea name="fpfig_settings[system_prompt]" rows="6" class="large-text code"><?php echo esc_textarea($value); ?></textarea>
        <div class="aifig-prompt-help">
            <strong><?php _e('Available placeholders:', 'freepik-featured-image-generator'); ?></strong><br>
            <code>{title}</code> - <?php _e('Post title', 'freepik-featured-image-generator'); ?><br>
            <code>{style_description}</code> - <?php _e('Style colors (from category style)', 'freepik-featured-image-generator'); ?><br>
            <code>{elements}</code> - <?php _e('Visual elements (from category style)', 'freepik-featured-image-generator'); ?><br>
            <code>{mood}</code> - <?php _e('Image mood (from category style)', 'freepik-featured-image-generator'); ?><br>
            <code>{category}</code> - <?php _e('Category name', 'freepik-featured-image-generator'); ?><br>
            <code>{excerpt}</code> - <?php _e('Post excerpt (first 100 chars)', 'freepik-featured-image-generator'); ?>
        </div>
        <?php
    }

    public function render_category_styles_field() {
        $styles = $this->get_option('category_styles');
        if (empty($styles)) {
            $styles = $this->get_default_category_styles();
        }
        ?>
        <div id="fpfig-styles-container">
            <?php foreach ($styles as $key => $style): ?>
                <div class="aifig-style-row" data-key="<?php echo esc_attr($key); ?>">
                    <span class="aifig-style-key"><?php echo esc_html($key); ?></span>
                    <input type="text" name="fpfig_settings[category_styles][<?php echo esc_attr($key); ?>][name]"
                           placeholder="<?php _e('Display Name', 'freepik-featured-image-generator'); ?>"
                           value="<?php echo esc_attr($style['name']); ?>">
                    <input type="text" name="fpfig_settings[category_styles][<?php echo esc_attr($key); ?>][colors]"
                           placeholder="<?php _e('Colors', 'freepik-featured-image-generator'); ?>"
                           value="<?php echo esc_attr($style['colors']); ?>">
                    <input type="text" name="fpfig_settings[category_styles][<?php echo esc_attr($key); ?>][elements]"
                           placeholder="<?php _e('Elements', 'freepik-featured-image-generator'); ?>"
                           value="<?php echo esc_attr($style['elements']); ?>">
                    <input type="text" name="fpfig_settings[category_styles][<?php echo esc_attr($key); ?>][mood]"
                           placeholder="<?php _e('Mood', 'freepik-featured-image-generator'); ?>"
                           value="<?php echo esc_attr($style['mood']); ?>">
                    <?php if ($key !== 'default'): ?>
                        <span class="aifig-remove-style" title="<?php _e('Remove', 'freepik-featured-image-generator'); ?>">×</span>
                    <?php else: ?>
                        <span style="width: 20px;"></span>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <button type="button" class="button aifig-add-style"><?php _e('+ Add Style', 'freepik-featured-image-generator'); ?></button>
        <p class="description"><?php _e('Styles are matched by category slug. "default" is used when no category matches.', 'freepik-featured-image-generator'); ?></p>
        <?php
    }

    public function render_auto_generate_field() {
        $value = $this->get_option('auto_generate');
        ?>
        <label>
            <input type="checkbox" name="fpfig_settings[auto_generate]" value="1" <?php checked($value); ?>>
            <?php _e('Automatically generate featured image when post is published (if none set)', 'freepik-featured-image-generator'); ?>
        </label>
        <p class="description"><?php _e('You can always manually generate images from the post editor', 'freepik-featured-image-generator'); ?></p>
        <?php
    }

    public function render_post_types_field() {
        $value = $this->get_option('post_types');
        $post_types = get_post_types(['public' => true], 'objects');
        ?>
        <fieldset>
            <?php foreach ($post_types as $pt): ?>
                <?php if ($pt->name === 'attachment') continue; ?>
                <label style="display: block; margin-bottom: 5px;">
                    <input type="checkbox" name="fpfig_settings[post_types][]" value="<?php echo esc_attr($pt->name); ?>"
                           <?php checked(in_array($pt->name, $value)); ?>>
                    <?php echo esc_html($pt->label); ?>
                </label>
            <?php endforeach; ?>
        </fieldset>
        <p class="description"><?php _e('Enable AI image generation for these post types', 'freepik-featured-image-generator'); ?></p>
        <?php
    }

    /**
     * Add action links to plugins page
     */
    public function add_action_links($links) {
        $settings_link = '<a href="' . admin_url('options-general.php?page=freepik-featured-image-generator') . '">' . __('Settings', 'freepik-featured-image-generator') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }

    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        global $post;

        // Only on post edit screens
        if (!in_array($hook, ['post.php', 'post-new.php'])) {
            return;
        }

        // Check if this post type is enabled
        $post_types = $this->get_option('post_types');
        if (!$post || !in_array($post->post_type, $post_types)) {
            return;
        }

        wp_enqueue_script('jquery');
    }

    /**
     * Add metabox to post editor
     */
    public function add_metabox() {
        $post_types = $this->get_option('post_types');
        foreach ($post_types as $pt) {
            add_meta_box(
                'fpfig_generator_metabox',
                __('🎨 AI Image Generator', 'freepik-featured-image-generator'),
                [$this, 'render_metabox'],
                $pt,
                'side',
                'high'
            );
        }
    }

    /**
     * Render metabox content
     */
    public function render_metabox($post) {
        // Check if API key is set
        if (!$this->get_option('api_key')) {
            echo '<p style="color: #a00;">' . __('Please configure your API key in', 'freepik-featured-image-generator') . ' ';
            echo '<a href="' . admin_url('options-general.php?page=freepik-featured-image-generator') . '">' . __('Settings', 'freepik-featured-image-generator') . '</a></p>';
            return;
        }

        $has_thumbnail = has_post_thumbnail($post->ID);
        $generated = get_post_meta($post->ID, '_fpfig_generated', true);
        $used_prompt = get_post_meta($post->ID, '_fpfig_prompt', true);
        $used_category = get_post_meta($post->ID, '_fpfig_category', true);

        // Get current category for style preview
        $categories = wp_get_post_categories($post->ID, ['fields' => 'all']);
        $current_category = !empty($categories) ? $categories[0]->slug : 'default';
        $style = $this->get_category_style($current_category);

        wp_nonce_field('fpfig_generate', 'fpfig_nonce');
        ?>
        <div id="fpfig-generator-box">
            <style>
                #fpfig-generator-box { font-size: 13px; }
                #fpfig-generator-box .status-row {
                    display: flex;
                    align-items: center;
                    gap: 8px;
                    margin-bottom: 12px;
                    padding: 8px;
                    background: #f0f0f1;
                    border-radius: 4px;
                }
                #fpfig-generator-box .status-icon { font-size: 16px; }
                #fpfig-generator-box .style-preview {
                    background: #fff;
                    border: 1px solid #ddd;
                    border-radius: 4px;
                    padding: 10px;
                    margin-bottom: 12px;
                }
                #fpfig-generator-box .style-preview h4 {
                    margin: 0 0 8px 0;
                    font-size: 12px;
                    color: #666;
                }
                #fpfig-generator-box .style-preview p {
                    margin: 4px 0;
                    font-size: 11px;
                    color: #444;
                }
                #fpfig-generator-box .button-row {
                    display: flex;
                    gap: 8px;
                    margin-top: 12px;
                }
                #fpfig-generator-box .button-row .button {
                    flex: 1;
                    text-align: center;
                }
                #fpfig-generator-box .generating {
                    display: none;
                    align-items: center;
                    gap: 8px;
                    padding: 12px;
                    background: #e7f3ff;
                    border-radius: 4px;
                    margin-top: 12px;
                }
                #fpfig-generator-box .generating.active { display: flex; }
                #fpfig-generator-box .spinner-inline {
                    width: 16px;
                    height: 16px;
                    border: 2px solid #0073aa;
                    border-top-color: transparent;
                    border-radius: 50%;
                    animation: aifig-spin 1s linear infinite;
                }
                @keyframes aifig-spin { to { transform: rotate(360deg); } }
                #fpfig-generator-box .error-msg {
                    background: #fef0f0;
                    border: 1px solid #f5c6cb;
                    color: #721c24;
                    padding: 8px;
                    border-radius: 4px;
                    margin-top: 12px;
                    display: none;
                }
                #fpfig-generator-box .success-msg {
                    background: #d4edda;
                    border: 1px solid #c3e6cb;
                    color: #155724;
                    padding: 8px;
                    border-radius: 4px;
                    margin-top: 12px;
                    display: none;
                }
                #fpfig-generator-box .prompt-used {
                    font-size: 11px;
                    color: #666;
                    margin-top: 8px;
                    padding: 8px;
                    background: #f9f9f9;
                    border-radius: 4px;
                    max-height: 100px;
                    overflow-y: auto;
                }
            </style>

            <!-- Status -->
            <div class="status-row">
                <?php if ($has_thumbnail && $generated): ?>
                    <span class="status-icon">✅</span>
                    <span><?php _e('AI-generated image set', 'freepik-featured-image-generator'); ?></span>
                <?php elseif ($has_thumbnail): ?>
                    <span class="status-icon">🖼️</span>
                    <span><?php _e('Manual image set', 'freepik-featured-image-generator'); ?></span>
                <?php else: ?>
                    <span class="status-icon">⚠️</span>
                    <span><?php _e('No featured image', 'freepik-featured-image-generator'); ?></span>
                <?php endif; ?>
            </div>

            <!-- Style Selector -->
            <?php
            $all_styles = $this->get_option('category_styles');
            $used_style = get_post_meta($post->ID, '_fpfig_style_key', true);
            ?>
            <div class="style-selector" style="margin-bottom: 12px;">
                <label for="fpfig-style-select" style="display: block; margin-bottom: 4px; font-weight: 500;">
                    <?php _e('Style:', 'freepik-featured-image-generator'); ?>
                </label>
                <select id="fpfig-style-select" style="width: 100%;">
                    <option value="auto" <?php selected(!$used_style || $used_style === 'auto'); ?>>
                        🔄 <?php _e('Auto (by category)', 'freepik-featured-image-generator'); ?> → <?php echo esc_html($style['name'] ?? ucfirst($current_category)); ?>
                    </option>
                    <?php foreach ($all_styles as $style_key => $style_data): ?>
                        <option value="<?php echo esc_attr($style_key); ?>" <?php selected($used_style, $style_key); ?>>
                            <?php echo esc_html($style_data['name'] ?? ucfirst($style_key)); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Style Preview -->
            <div class="style-preview" id="fpfig-style-preview">
                <h4>📋 <?php _e('Style Preview', 'freepik-featured-image-generator'); ?></h4>
                <p><strong><?php _e('Colors:', 'freepik-featured-image-generator'); ?></strong> <span id="fpfig-preview-colors"><?php echo esc_html($style['colors']); ?></span></p>
                <p><strong><?php _e('Elements:', 'freepik-featured-image-generator'); ?></strong> <span id="fpfig-preview-elements"><?php echo esc_html($style['elements']); ?></span></p>
                <p><strong><?php _e('Mood:', 'freepik-featured-image-generator'); ?></strong> <span id="fpfig-preview-mood"><?php echo esc_html($style['mood']); ?></span></p>
            </div>

            <!-- Buttons -->
            <div class="button-row">
                <?php if (!$has_thumbnail): ?>
                    <button type="button" class="button button-primary" onclick="fpfig_generate(<?php echo $post->ID; ?>, false)">
                        🎨 <?php _e('Generate Image', 'freepik-featured-image-generator'); ?>
                    </button>
                <?php else: ?>
                    <button type="button" class="button" onclick="fpfig_generate(<?php echo $post->ID; ?>, true)">
                        🔄 <?php _e('Regenerate', 'freepik-featured-image-generator'); ?>
                    </button>
                <?php endif; ?>
            </div>

            <!-- Loading State -->
            <div class="generating" id="fpfig-generating">
                <div class="spinner-inline"></div>
                <span><?php _e('Generating image... (10-30 sec)', 'freepik-featured-image-generator'); ?></span>
            </div>

            <!-- Messages -->
            <div class="error-msg" id="fpfig-error"></div>
            <div class="success-msg" id="fpfig-success"></div>

            <!-- Used Prompt -->
            <?php if ($used_prompt): ?>
                <div class="prompt-used">
                    <strong><?php _e('Last prompt:', 'freepik-featured-image-generator'); ?></strong><br>
                    <?php echo esc_html($used_prompt); ?>
                </div>
            <?php endif; ?>
        </div>

        <script>
        // Style data for preview updates
        var fpfigStyles = <?php echo json_encode($all_styles); ?>;
        var fpfigAutoStyle = <?php echo json_encode($style); ?>;

        // Update preview when style changes
        document.getElementById('fpfig-style-select').addEventListener('change', function() {
            var selectedKey = this.value;
            var styleData = (selectedKey === 'auto') ? fpfigAutoStyle : fpfigStyles[selectedKey];

            if (styleData) {
                document.getElementById('fpfig-preview-colors').textContent = styleData.colors || 'professional colors';
                document.getElementById('fpfig-preview-elements').textContent = styleData.elements || 'business elements';
                document.getElementById('fpfig-preview-mood').textContent = styleData.mood || 'professional';
            }
        });

        function fpfig_generate(postId, force) {
            var box = document.getElementById('fpfig-generator-box');
            var generating = document.getElementById('fpfig-generating');
            var errorDiv = document.getElementById('fpfig-error');
            var successDiv = document.getElementById('fpfig-success');
            var styleSelect = document.getElementById('fpfig-style-select');
            var selectedStyle = styleSelect.value;

            // Reset messages
            errorDiv.style.display = 'none';
            successDiv.style.display = 'none';

            // Confirm if regenerating
            if (force && !confirm('<?php _e('Regenerate featured image? This will replace the current image.', 'freepik-featured-image-generator'); ?>')) {
                return;
            }

            // Show loading
            generating.classList.add('active');
            box.querySelectorAll('button').forEach(function(b) { b.disabled = true; });
            styleSelect.disabled = true;

            // Make request with style parameter
            var url = '<?php echo rest_url('fpfig/v1/generate/'); ?>' + postId;
            var params = [];
            if (force) params.push('force=1');
            if (selectedStyle && selectedStyle !== 'auto') params.push('style=' + encodeURIComponent(selectedStyle));
            if (params.length) url += '?' + params.join('&');

            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': '<?php echo wp_create_nonce('wp_rest'); ?>'
                },
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                generating.classList.remove('active');
                box.querySelectorAll('button').forEach(function(b) { b.disabled = false; });
                styleSelect.disabled = false;

                if (data.success) {
                    successDiv.innerHTML = '✅ <?php _e('Image generated successfully!', 'freepik-featured-image-generator'); ?><br><small><?php _e('Refreshing page...', 'freepik-featured-image-generator'); ?></small>';
                    successDiv.style.display = 'block';
                    setTimeout(function() { location.reload(); }, 1500);
                } else {
                    errorDiv.innerHTML = '❌ ' + (data.message || data.error || '<?php _e('Unknown error', 'freepik-featured-image-generator'); ?>');
                    errorDiv.style.display = 'block';
                }
            })
            .catch(function(e) {
                generating.classList.remove('active');
                box.querySelectorAll('button').forEach(function(b) { b.disabled = false; });
                styleSelect.disabled = false;
                errorDiv.innerHTML = '❌ <?php _e('Network error:', 'freepik-featured-image-generator'); ?> ' + e.message;
                errorDiv.style.display = 'block';
            });
        }
        </script>
        <?php
    }

    /**
     * Register REST API routes
     */
    public function register_rest_routes() {
        register_rest_route('fpfig/v1', '/generate/(?P<post_id>\d+)', [
            'methods' => 'POST',
            'callback' => [$this, 'rest_generate_image'],
            'permission_callback' => function() {
                return current_user_can('edit_posts');
            },
            'args' => [
                'post_id' => [
                    'required' => true,
                    'type' => 'integer',
                ],
                'force' => [
                    'required' => false,
                    'type' => 'boolean',
                    'default' => false,
                ],
                'style' => [
                    'required' => false,
                    'type' => 'string',
                    'default' => '',
                ],
            ],
        ]);

        register_rest_route('fpfig/v1', '/custom', [
            'methods' => 'POST',
            'callback' => [$this, 'rest_generate_custom'],
            'permission_callback' => function() {
                return current_user_can('edit_posts');
            },
            'args' => [
                'prompt' => [
                    'required' => true,
                    'type' => 'string',
                ],
                'post_id' => [
                    'required' => false,
                    'type' => 'integer',
                ],
            ],
        ]);
    }

    /**
     * REST endpoint: Generate image for post
     */
    public function rest_generate_image($request) {
        $post_id = $request->get_param('post_id');
        $force = $request->get_param('force');
        $style_key = $request->get_param('style');

        $post = get_post($post_id);
        if (!$post) {
            return new WP_Error('not_found', __('Post not found', 'freepik-featured-image-generator'), ['status' => 404]);
        }

        // Check if already has featured image
        if (!$force && has_post_thumbnail($post_id)) {
            return [
                'success' => false,
                'message' => __('Post already has a featured image. Use force=true to regenerate.', 'freepik-featured-image-generator'),
            ];
        }

        // Build prompt (with optional style override)
        $prompt_data = $this->build_image_prompt($post_id, $style_key);
        if (!$prompt_data) {
            return new WP_Error('prompt_failed', __('Failed to build prompt', 'freepik-featured-image-generator'));
        }

        // Call Freepik API
        $result = $this->call_freepik_api($prompt_data['prompt']);

        if (!$result['success']) {
            return new WP_Error('api_failed', $result['error'], ['status' => 500]);
        }

        // Save image as attachment
        $image_source = $result['image_url'] ?? $result['image_data'] ?? null;
        if (!$image_source) {
            return new WP_Error('no_image', __('No image data in API response', 'freepik-featured-image-generator'), ['status' => 500]);
        }

        $attachment_id = $this->save_image_as_attachment($image_source, $post_id);
        if (is_wp_error($attachment_id)) {
            return $attachment_id;
        }

        // Set as featured image
        set_post_thumbnail($post_id, $attachment_id);

        // Store generation info
        update_post_meta($post_id, '_fpfig_generated', true);
        update_post_meta($post_id, '_fpfig_prompt', $prompt_data['prompt']);
        update_post_meta($post_id, '_fpfig_category', $prompt_data['category']);
        update_post_meta($post_id, '_fpfig_style_key', $prompt_data['style_key'] ?? '');

        return [
            'success' => true,
            'attachment_id' => $attachment_id,
            'attachment_url' => wp_get_attachment_url($attachment_id),
            'prompt' => $prompt_data['prompt'],
            'category' => $prompt_data['category'],
        ];
    }

    /**
     * REST endpoint: Generate custom image
     */
    public function rest_generate_custom($request) {
        $prompt = $request->get_param('prompt');
        $post_id = $request->get_param('post_id');

        $result = $this->call_freepik_api($prompt);

        if (!$result['success']) {
            return new WP_Error('api_failed', $result['error'], ['status' => 500]);
        }

        $response = [
            'success' => true,
            'image_url' => $result['image_url'] ?? null,
            'prompt' => $prompt,
        ];

        // Optionally save to post
        if ($post_id && !empty($result['image_url'])) {
            $attachment_id = $this->save_image_as_attachment($result['image_url'], $post_id);
            if (!is_wp_error($attachment_id)) {
                $response['attachment_id'] = $attachment_id;
                $response['attachment_url'] = wp_get_attachment_url($attachment_id);
            }
        }

        return $response;
    }

    /**
     * Get category style
     */
    public function get_category_style($category_slug) {
        $styles = $this->get_option('category_styles');
        if (empty($styles)) {
            $styles = $this->get_default_category_styles();
        }
        return $styles[$category_slug] ?? $styles['default'] ?? $this->get_default_category_styles()['default'];
    }

    /**
     * Build prompt from post content
     */
    public function build_image_prompt($post_id, $style_key = '') {
        $post = get_post($post_id);
        if (!$post) {
            return null;
        }

        // Get category
        $categories = wp_get_post_categories($post_id, ['fields' => 'all']);
        $category_slug = !empty($categories) ? $categories[0]->slug : 'default';
        $category_name = !empty($categories) ? $categories[0]->name : 'General';

        // Use custom style if provided, otherwise auto-detect from category
        if ($style_key && $style_key !== 'auto') {
            $style = $this->get_category_style($style_key);
            $used_style_key = $style_key;
        } else {
            $style = $this->get_category_style($category_slug);
            $used_style_key = 'auto';
        }

        // Get prompt template
        $template = $this->get_option('system_prompt');

        // Replace placeholders
        $prompt = str_replace(
            ['{title}', '{style_description}', '{elements}', '{mood}', '{category}', '{excerpt}'],
            [
                $post->post_title,
                $style['colors'] ?? 'professional colors',
                $style['elements'] ?? 'business elements',
                $style['mood'] ?? 'professional',
                $category_name,
                wp_trim_words(strip_tags($post->post_excerpt ?: $post->post_content), 20, '...'),
            ],
            $template
        );

        return [
            'prompt' => $prompt,
            'category' => $category_slug,
            'style' => $style,
            'style_key' => $used_style_key,
        ];
    }

    /**
     * Call Freepik API
     */
    public function call_freepik_api($prompt) {
        $api_key = $this->get_option('api_key');
        if (!$api_key) {
            return ['success' => false, 'error' => __('API key not configured', 'freepik-featured-image-generator')];
        }

        $api_url = $this->get_api_url();
        $model = $this->get_option('model');

        $body = [
            'prompt' => $prompt,
            'negative_prompt' => 'text, letters, words, numbers, typography, writing, captions, labels, signs, watermarks, logos, titles, headlines, subtitles, inscriptions, characters, fonts, alphabet',
            'aspect_ratio' => $this->get_option('aspect_ratio'),
            'resolution' => $this->get_option('resolution'),
        ];

        // Add model-specific parameters
        if ($model === 'mystic') {
            $body['model'] = $this->get_option('sub_model');
            $body['creative_detailing'] = $this->get_option('creative_detailing');
        }

        // Create generation task
        $response = wp_remote_post($api_url, [
            'headers' => [
                'x-freepik-api-key' => $api_key,
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode($body),
            'timeout' => 30,
        ]);

        if (is_wp_error($response)) {
            return ['success' => false, 'error' => $response->get_error_message()];
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);

        if ($status_code !== 200 && $status_code !== 201) {
            return [
                'success' => false,
                'error' => $body['message'] ?? __('API request failed', 'freepik-featured-image-generator'),
                'status_code' => $status_code,
            ];
        }

        // Get task_id for polling
        $task_id = $body['data']['task_id'] ?? null;
        if (!$task_id) {
            return ['success' => false, 'error' => __('No task_id in response', 'freepik-featured-image-generator')];
        }

        // Poll for completion
        $max_attempts = 30;
        $poll_interval = 2;

        for ($i = 0; $i < $max_attempts; $i++) {
            sleep($poll_interval);

            $status_response = wp_remote_get($api_url . '/' . $task_id, [
                'headers' => ['x-freepik-api-key' => $api_key],
                'timeout' => 15,
            ]);

            if (is_wp_error($status_response)) {
                continue;
            }

            $status_body = json_decode(wp_remote_retrieve_body($status_response), true);
            $status = $status_body['data']['status'] ?? 'UNKNOWN';

            if ($status === 'COMPLETED') {
                $image_url = $status_body['data']['generated'][0] ?? null;
                if ($image_url) {
                    return [
                        'success' => true,
                        'image_url' => $image_url,
                        'task_id' => $task_id,
                    ];
                }
            } elseif ($status === 'FAILED') {
                return [
                    'success' => false,
                    'error' => __('Image generation failed', 'freepik-featured-image-generator'),
                    'task_id' => $task_id,
                ];
            }
        }

        return [
            'success' => false,
            'error' => sprintf(__('Generation timed out after %d seconds', 'freepik-featured-image-generator'), $max_attempts * $poll_interval),
            'task_id' => $task_id,
        ];
    }

    /**
     * Save image as WordPress attachment
     */
    public function save_image_as_attachment($image_source, $post_id, $filename = null) {
        // Download image
        if (filter_var($image_source, FILTER_VALIDATE_URL)) {
            $response = wp_remote_get($image_source, ['timeout' => 30]);
            if (is_wp_error($response)) {
                return new WP_Error('download_failed', __('Failed to download image', 'freepik-featured-image-generator'));
            }
            $image_data = wp_remote_retrieve_body($response);
        } else {
            $image_data = base64_decode($image_source);
        }

        if (!$image_data || strlen($image_data) < 100) {
            return new WP_Error('invalid_image', __('Invalid image data', 'freepik-featured-image-generator'));
        }

        // Get output format settings
        $output_format = $this->get_option('output_format');
        $output_quality = $this->get_option('output_quality');

        // Detect original image type
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $original_mime = $finfo->buffer($image_data);

        // Determine final format and extension
        if ($output_format === 'original') {
            $extension = 'jpg';
            if (strpos($original_mime, 'png') !== false) {
                $extension = 'png';
            } elseif (strpos($original_mime, 'webp') !== false) {
                $extension = 'webp';
            } elseif (strpos($original_mime, 'avif') !== false) {
                $extension = 'avif';
            }
            $final_image_data = $image_data;
        } else {
            $extension = $output_format;
            $final_image_data = $this->convert_image($image_data, $output_format, $output_quality);
            if (is_wp_error($final_image_data)) {
                // Fallback to original if conversion fails
                $extension = 'jpg';
                if (strpos($original_mime, 'png') !== false) {
                    $extension = 'png';
                }
                $final_image_data = $image_data;
            }
        }

        // Generate filename (use post ID to avoid issues with non-ASCII titles)
        if (!$filename) {
            $filename = 'fpfig-post-' . $post_id . '-' . time() . '.' . $extension;
        }

        // Save file
        $upload_dir = wp_upload_dir();
        $file_path = $upload_dir['path'] . '/' . $filename;

        if (!file_put_contents($file_path, $final_image_data)) {
            return new WP_Error('save_failed', __('Failed to save image file', 'freepik-featured-image-generator'));
        }

        // Create attachment
        $file_type = wp_check_filetype($filename, null);
        $attachment = [
            'post_mime_type' => $file_type['type'],
            'post_title' => sanitize_file_name($filename),
            'post_content' => '',
            'post_status' => 'inherit',
        ];

        $attach_id = wp_insert_attachment($attachment, $file_path, $post_id);
        if (is_wp_error($attach_id)) {
            return $attach_id;
        }

        // Generate metadata
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attach_data = wp_generate_attachment_metadata($attach_id, $file_path);
        wp_update_attachment_metadata($attach_id, $attach_data);

        return $attach_id;
    }

    /**
     * Convert image to specified format
     */
    private function convert_image($image_data, $format, $quality = 85) {
        // Create image resource from data
        $image = @imagecreatefromstring($image_data);
        if (!$image) {
            return new WP_Error('conversion_failed', __('Failed to create image from data', 'freepik-featured-image-generator'));
        }

        // Preserve transparency for PNG/WebP
        imagealphablending($image, true);
        imagesavealpha($image, true);

        // Capture output to buffer
        ob_start();

        switch ($format) {
            case 'jpg':
            case 'jpeg':
                // Convert to RGB for JPEG (no alpha)
                $width = imagesx($image);
                $height = imagesy($image);
                $rgb_image = imagecreatetruecolor($width, $height);
                $white = imagecolorallocate($rgb_image, 255, 255, 255);
                imagefill($rgb_image, 0, 0, $white);
                imagecopy($rgb_image, $image, 0, 0, 0, 0, $width, $height);
                imagejpeg($rgb_image, null, $quality);
                imagedestroy($rgb_image);
                break;

            case 'png':
                // PNG quality is 0-9 (inverted from percentage)
                $png_quality = (int) ((100 - $quality) / 11.111);
                imagepng($image, null, $png_quality);
                break;

            case 'webp':
                if (!function_exists('imagewebp')) {
                    imagedestroy($image);
                    ob_end_clean();
                    return new WP_Error('webp_not_supported', __('WebP is not supported on this server', 'freepik-featured-image-generator'));
                }
                imagewebp($image, null, $quality);
                break;

            case 'avif':
                if (!function_exists('imageavif')) {
                    imagedestroy($image);
                    ob_end_clean();
                    return new WP_Error('avif_not_supported', __('AVIF is not supported on this server', 'freepik-featured-image-generator'));
                }
                imageavif($image, null, $quality);
                break;

            default:
                imagedestroy($image);
                ob_end_clean();
                return new WP_Error('unknown_format', __('Unknown output format', 'freepik-featured-image-generator'));
        }

        $output = ob_get_clean();
        imagedestroy($image);

        if (empty($output)) {
            return new WP_Error('conversion_failed', __('Image conversion produced empty output', 'freepik-featured-image-generator'));
        }

        return $output;
    }

    /**
     * Auto-generate on publish
     */
    public function auto_generate_on_publish($post_id, $post, $update) {
        // Only enabled post types
        $post_types = $this->get_option('post_types');
        if (!in_array($post->post_type, $post_types)) {
            return;
        }

        // Only on first publish
        if ($update || $post->post_status !== 'publish') {
            return;
        }

        // Skip if already has featured image
        if (has_post_thumbnail($post_id)) {
            return;
        }

        // Generate image
        $prompt_data = $this->build_image_prompt($post_id);
        if ($prompt_data) {
            $result = $this->call_freepik_api($prompt_data['prompt']);

            if ($result['success'] && !empty($result['image_url'])) {
                $attachment_id = $this->save_image_as_attachment($result['image_url'], $post_id);
                if (!is_wp_error($attachment_id)) {
                    set_post_thumbnail($post_id, $attachment_id);
                    update_post_meta($post_id, '_fpfig_generated', true);
                    update_post_meta($post_id, '_fpfig_prompt', $prompt_data['prompt']);
                }
            }
        }
    }
}

// Initialize plugin
add_action('plugins_loaded', function() {
    Freepik_Featured_Image_Generator::get_instance();
});

// AJAX handler for API test
add_action('wp_ajax_fpfig_test_api', function() {
    check_ajax_referer('fpfig_test_api', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('Permission denied', 'freepik-featured-image-generator')]);
    }

    $plugin = Freepik_Featured_Image_Generator::get_instance();
    $api_key = $plugin->get_option('api_key');

    if (!$api_key) {
        wp_send_json_error(['message' => __('API key not configured', 'freepik-featured-image-generator')]);
    }

    // Test API with simple request
    $response = wp_remote_get('https://api.freepik.com/v1/resources', [
        'headers' => ['x-freepik-api-key' => $api_key],
        'timeout' => 10,
    ]);

    if (is_wp_error($response)) {
        wp_send_json_error(['message' => $response->get_error_message()]);
    }

    $status_code = wp_remote_retrieve_response_code($response);

    if ($status_code === 200) {
        wp_send_json_success(['message' => __('Connection successful!', 'freepik-featured-image-generator')]);
    } elseif ($status_code === 401) {
        wp_send_json_error(['message' => __('Invalid API key', 'freepik-featured-image-generator')]);
    } else {
        wp_send_json_error(['message' => sprintf(__('API returned status %d', 'freepik-featured-image-generator'), $status_code)]);
    }
});

// Activation hook
register_activation_hook(__FILE__, function() {
    // Set default options if not exists
    if (!get_option('fpfig_settings')) {
        add_option('fpfig_settings', Freepik_Featured_Image_Generator::get_default_options_static());
    }
});

// Deactivation hook
register_deactivation_hook(__FILE__, function() {
    // Clean up if needed
});
