<?php
/**
 * Plugin Name: Kashiwazaki SEO Dynamic Date
 * Plugin URI: https://www.tsuyoshikashiwazaki.jp
 * Description: 投稿や固定ページに動的な日付を表示するショートコードを提供し、コンテンツを常に最新の状態に保つことでSEO効果を向上させます
 * Version: 1.0.0
 * Author: 柏崎剛 (Tsuyoshi Kashiwazaki)
 * Author URI: https://www.tsuyoshikashiwazaki.jp
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: kashiwazaki-seo-dynamic-date
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('KSDATE_VERSION', '1.0.0');
define('KSDATE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('KSDATE_PLUGIN_URL', plugin_dir_url(__FILE__));
define('KSDATE_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Initialize the plugin
 */
function ksdate_init() {
    // Load text domain for internationalization
    load_plugin_textdomain(
        'kashiwazaki-seo-dynamic-date',
        false,
        dirname(KSDATE_PLUGIN_BASENAME) . '/languages'
    );
}
add_action('init', 'ksdate_init');

/**
 * Get default date format from settings
 *
 * @return string Default date format
 */
function ksdate_get_default_format() {
    $options = get_option('ksdate_settings');
    return isset($options['default_format']) && !empty($options['default_format'])
        ? $options['default_format']
        : 'Y年m月d日';
}

/**
 * Main shortcode callback function
 *
 * @param array $atts Shortcode attributes
 * @return string Formatted date string or year difference
 */
function ksdate_shortcode($atts) {
    // Parse shortcode attributes
    $atts = shortcode_atts(
        array(
            'format' => ksdate_get_default_format(),
            'offset' => '',
            'diff' => '',
        ),
        $atts,
        'ksdate'
    );

    // Sanitize parameters
    $format = sanitize_text_field($atts['format']);
    $offset = sanitize_text_field($atts['offset']);
    $diff = sanitize_text_field($atts['diff']);

    try {
        // If diff attribute is specified, calculate date difference
        if (!empty($diff)) {
            $result = ksdate_calculate_diff($diff, $format);
            return esc_html($result);
        }

        // Calculate date with offset
        $date = ksdate_calculate_date($format, $offset);

        // Return escaped HTML
        return esc_html($date);
    } catch (Exception $e) {
        // On error, return empty string (no error message shown to user)
        return '';
    }
}
add_shortcode('ksdate', 'ksdate_shortcode');

/**
 * Calculate date difference from specified date to current date (or vice versa for future dates)
 *
 * @param string $from_date Target date (e.g., "1999", "1999-01", "1999-01-15", "2030", "2030-12-31")
 * @param string $format Output format to determine unit
 * @return string Formatted difference with or without unit
 * @throws Exception If date format is invalid
 */
function ksdate_calculate_diff($from_date, $format = '') {
    $timezone = wp_timezone();
    $current_datetime = new DateTime('now', $timezone);

    // Parse the from_date and determine the unit
    $target_datetime = null;
    $default_unit = 'years'; // Default unit

    // Match YYYY-MM-DD
    if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $from_date, $matches)) {
        $target_datetime = new DateTime($from_date, $timezone);
        $default_unit = 'days';
    }
    // Match YYYY-MM
    elseif (preg_match('/^(\d{4})-(\d{2})$/', $from_date, $matches)) {
        $target_datetime = new DateTime($from_date . '-01', $timezone);
        $default_unit = 'months';
    }
    // Match YYYY
    elseif (preg_match('/^\d{4}$/', $from_date)) {
        $target_datetime = new DateTime($from_date . '-01-01', $timezone);
        $default_unit = 'years';
    }
    else {
        throw new Exception('Invalid date format');
    }

    // Determine unit from format parameter
    $unit = $default_unit;
    $unit_text = '';

    if (!empty($format)) {
        if (strpos($format, '年') !== false) {
            $unit = 'years';
            $unit_text = '年';
        } elseif (strpos($format, 'ヶ月') !== false || strpos($format, '月') !== false) {
            $unit = 'months';
            $unit_text = 'ヶ月';
        } elseif (strpos($format, '日') !== false) {
            $unit = 'days';
            $unit_text = '日';
        }
    }

    // Determine if target is in the past or future
    $is_future = $target_datetime > $current_datetime;

    // Calculate difference based on unit
    $diff = 0;
    $interval = $current_datetime->diff($target_datetime);

    switch ($unit) {
        case 'years':
            $diff = $interval->y;
            break;

        case 'months':
            $diff = ($interval->y * 12) + $interval->m;
            break;

        case 'days':
            $diff = $interval->days;
            break;
    }

    // Return with or without unit text
    if (!empty($unit_text)) {
        return $diff . $unit_text;
    }

    return (string)$diff;
}

/**
 * Parse offset string and convert to strtotime format
 *
 * @param string $offset Offset string like "-20y", "+3d", etc.
 * @return string|null strtotime compatible string or null if invalid
 */
function ksdate_parse_offset($offset) {
    if (empty($offset)) {
        return null;
    }

    // Regular expression to match offset pattern
    if (!preg_match('/^([+-]?\d+)([ymwd])$/i', $offset, $matches)) {
        return null;
    }

    $value = $matches[1];
    $unit = strtolower($matches[2]);

    // Convert to strtotime format
    switch ($unit) {
        case 'y':
            return $value . ' years';
        case 'm':
            return $value . ' months';
        case 'w':
            return $value . ' weeks';
        case 'd':
            return $value . ' days';
        default:
            return null;
    }
}

/**
 * Calculate date with optional offset
 *
 * @param string $format Date format string
 * @param string $offset Offset string
 * @return string Formatted date
 * @throws Exception If date calculation fails
 */
function ksdate_calculate_date($format, $offset = '') {
    // Get WordPress timezone
    $timezone = wp_timezone();

    // Create DateTime object with current time in WordPress timezone
    $datetime = new DateTime('now', $timezone);

    // Apply offset if provided
    if (!empty($offset)) {
        $offset_string = ksdate_parse_offset($offset);

        if ($offset_string !== null) {
            try {
                $datetime->modify($offset_string);
            } catch (Exception $e) {
                // If modify fails, continue with current date
            }
        }
    }

    // Use wp_date for proper localization
    return wp_date($format, $datetime->getTimestamp());
}

/**
 * Add settings link to plugin action links
 *
 * @param array $links Plugin action links
 * @return array Modified plugin action links
 */
function ksdate_add_settings_link($links) {
    $settings_link = '<a href="' . admin_url('admin.php?page=kashiwazaki-seo-dynamic-date') . '">' . __('Settings', 'kashiwazaki-seo-dynamic-date') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}
add_filter('plugin_action_links_' . KSDATE_PLUGIN_BASENAME, 'ksdate_add_settings_link');

/**
 * Add admin menu page
 */
function ksdate_add_admin_menu() {
    add_menu_page(
        __('Kashiwazaki SEO Dynamic Date', 'kashiwazaki-seo-dynamic-date'),
        __('Kashiwazaki SEO Dynamic Date', 'kashiwazaki-seo-dynamic-date'),
        'manage_options',
        'kashiwazaki-seo-dynamic-date',
        'ksdate_admin_page',
        'dashicons-calendar-alt',
        81
    );
}
add_action('admin_menu', 'ksdate_add_admin_menu');

/**
 * Register plugin settings
 */
function ksdate_register_settings() {
    register_setting('ksdate_settings_group', 'ksdate_settings', 'ksdate_sanitize_settings');
}
add_action('admin_init', 'ksdate_register_settings');

/**
 * Sanitize settings
 *
 * @param array $input Raw settings input
 * @return array Sanitized settings
 */
function ksdate_sanitize_settings($input) {
    $sanitized = array();

    if (isset($input['default_format'])) {
        $sanitized['default_format'] = sanitize_text_field($input['default_format']);
    }

    return $sanitized;
}

/**
 * Enqueue admin scripts and styles
 */
function ksdate_enqueue_admin_assets($hook) {
    if ($hook !== 'toplevel_page_kashiwazaki-seo-dynamic-date') {
        return;
    }

    wp_enqueue_style('ksdate-admin-style', KSDATE_PLUGIN_URL . 'assets/admin-style.css', array(), KSDATE_VERSION);
    wp_enqueue_script('ksdate-admin-script', KSDATE_PLUGIN_URL . 'assets/admin-script.js', array('jquery'), KSDATE_VERSION, true);

    wp_localize_script('ksdate-admin-script', 'ksdateAdmin', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('ksdate_preview_nonce'),
    ));
}
add_action('admin_enqueue_scripts', 'ksdate_enqueue_admin_assets');

/**
 * AJAX handler for date preview
 */
function ksdate_ajax_preview() {
    check_ajax_referer('ksdate_preview_nonce', 'nonce');

    $format = isset($_POST['format']) ? sanitize_text_field($_POST['format']) : 'Y年m月d日';
    $offset = isset($_POST['offset']) ? sanitize_text_field($_POST['offset']) : '';

    try {
        $result = ksdate_calculate_date($format, $offset);
        wp_send_json_success(array('result' => $result));
    } catch (Exception $e) {
        wp_send_json_error(array('message' => __('Invalid format or offset', 'kashiwazaki-seo-dynamic-date')));
    }
}
add_action('wp_ajax_ksdate_preview', 'ksdate_ajax_preview');

/**
 * Admin page content
 */
function ksdate_admin_page() {
    // Get current options
    $options = get_option('ksdate_settings', array());
    $default_format = isset($options['default_format']) ? $options['default_format'] : 'Y年m月d日';

    // Get current tab
    $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'settings';

    // Handle settings save
    if (isset($_POST['ksdate_save_settings']) && check_admin_referer('ksdate_settings_nonce')) {
        // Settings are handled by WordPress Settings API
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Settings saved successfully!', 'kashiwazaki-seo-dynamic-date') . '</p></div>';
    }
    ?>
    <div class="wrap ksdate-admin-wrap">
        <h1>
            <span class="dashicons dashicons-calendar-alt" style="font-size: 32px; margin-right: 10px;"></span>
            <?php echo esc_html__('Kashiwazaki SEO Dynamic Date', 'kashiwazaki-seo-dynamic-date'); ?>
        </h1>

        <nav class="nav-tab-wrapper">
            <a href="?page=kashiwazaki-seo-dynamic-date&tab=settings" class="nav-tab <?php echo $active_tab === 'settings' ? 'nav-tab-active' : ''; ?>">
                <?php echo esc_html__('Settings', 'kashiwazaki-seo-dynamic-date'); ?>
            </a>
            <a href="?page=kashiwazaki-seo-dynamic-date&tab=preview" class="nav-tab <?php echo $active_tab === 'preview' ? 'nav-tab-active' : ''; ?>">
                <?php echo esc_html__('Preview', 'kashiwazaki-seo-dynamic-date'); ?>
            </a>
            <a href="?page=kashiwazaki-seo-dynamic-date&tab=usage" class="nav-tab <?php echo $active_tab === 'usage' ? 'nav-tab-active' : ''; ?>">
                <?php echo esc_html__('How to Use', 'kashiwazaki-seo-dynamic-date'); ?>
            </a>
            <a href="?page=kashiwazaki-seo-dynamic-date&tab=examples" class="nav-tab <?php echo $active_tab === 'examples' ? 'nav-tab-active' : ''; ?>">
                <?php echo esc_html__('Examples', 'kashiwazaki-seo-dynamic-date'); ?>
            </a>
        </nav>

        <?php if ($active_tab === 'settings'): ?>
            <div class="ksdate-tab-content">
                <form method="post" action="options.php">
                    <?php
                    settings_fields('ksdate_settings_group');
                    wp_nonce_field('ksdate_settings_nonce');
                    ?>

                    <div class="ksdate-settings-section">
                        <h2><?php echo esc_html__('General Settings', 'kashiwazaki-seo-dynamic-date'); ?></h2>

                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="default_format"><?php echo esc_html__('Default Date Format', 'kashiwazaki-seo-dynamic-date'); ?></label>
                                </th>
                                <td>
                                    <input type="text" id="default_format" name="ksdate_settings[default_format]"
                                           value="<?php echo esc_attr($default_format); ?>"
                                           class="regular-text" />
                                    <p class="description">
                                        <?php echo esc_html__('The default format used when no format attribute is specified in the shortcode.', 'kashiwazaki-seo-dynamic-date'); ?>
                                        <br>
                                        <?php echo esc_html__('Current output:', 'kashiwazaki-seo-dynamic-date'); ?>
                                        <strong><?php echo esc_html(ksdate_calculate_date($default_format, '')); ?></strong>
                                    </p>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <?php submit_button(__('Save Settings', 'kashiwazaki-seo-dynamic-date')); ?>
                </form>
            </div>

        <?php elseif ($active_tab === 'preview'): ?>
            <div class="ksdate-tab-content">
                <div class="ksdate-preview-section">
                    <h2><?php echo esc_html__('Live Preview', 'kashiwazaki-seo-dynamic-date'); ?></h2>
                    <p><?php echo esc_html__('Test your date formats and offsets in real-time.', 'kashiwazaki-seo-dynamic-date'); ?></p>

                    <div class="ksdate-preview-form">
                        <div class="ksdate-form-group">
                            <label for="preview_format"><?php echo esc_html__('Format:', 'kashiwazaki-seo-dynamic-date'); ?></label>
                            <input type="text" id="preview_format" class="regular-text" value="Y年m月d日" />
                            <p class="description"><?php echo esc_html__('PHP date format string', 'kashiwazaki-seo-dynamic-date'); ?></p>
                        </div>

                        <div class="ksdate-form-group">
                            <label for="preview_offset"><?php echo esc_html__('Offset:', 'kashiwazaki-seo-dynamic-date'); ?></label>
                            <input type="text" id="preview_offset" class="regular-text" placeholder="-1y, +3d, -6m, etc." />
                            <p class="description"><?php echo esc_html__('Optional: Relative date offset', 'kashiwazaki-seo-dynamic-date'); ?></p>
                        </div>

                        <button type="button" id="ksdate-preview-button" class="button button-primary">
                            <?php echo esc_html__('Preview', 'kashiwazaki-seo-dynamic-date'); ?>
                        </button>

                        <div id="ksdate-preview-result" style="display: none;">
                            <h3><?php echo esc_html__('Result:', 'kashiwazaki-seo-dynamic-date'); ?></h3>
                            <div class="ksdate-preview-output"></div>
                        </div>

                        <div id="ksdate-preview-shortcode" style="display: none;">
                            <h3><?php echo esc_html__('Shortcode:', 'kashiwazaki-seo-dynamic-date'); ?></h3>
                            <input type="text" class="regular-text ksdate-shortcode-output" readonly />
                            <button type="button" class="button ksdate-copy-shortcode"><?php echo esc_html__('Copy', 'kashiwazaki-seo-dynamic-date'); ?></button>
                        </div>
                    </div>

                    <div class="ksdate-quick-formats">
                        <h3><?php echo esc_html__('Quick Formats', 'kashiwazaki-seo-dynamic-date'); ?></h3>
                        <div class="ksdate-format-buttons">
                            <button type="button" class="button ksdate-quick-format" data-format="Y年m月d日">Y年m月d日</button>
                            <button type="button" class="button ksdate-quick-format" data-format="Y/m/d">Y/m/d</button>
                            <button type="button" class="button ksdate-quick-format" data-format="Y-m-d">Y-m-d</button>
                            <button type="button" class="button ksdate-quick-format" data-format="Y年n月j日">Y年n月j日</button>
                            <button type="button" class="button ksdate-quick-format" data-format="Y">Y (年のみ)</button>
                            <button type="button" class="button ksdate-quick-format" data-format="Y年m月">Y年m月</button>
                            <button type="button" class="button ksdate-quick-format" data-format="m月d日">m月d日</button>
                            <button type="button" class="button ksdate-quick-format" data-format="Y-m-d H:i:s">Y-m-d H:i:s</button>
                        </div>
                    </div>
                </div>
            </div>

        <?php elseif ($active_tab === 'usage'): ?>
            <div class="ksdate-tab-content">
                <div class="ksdate-usage-section">
                    <h2><?php echo esc_html__('How to Use', 'kashiwazaki-seo-dynamic-date'); ?></h2>
                    <p><?php echo esc_html__('Use the [ksdate] shortcode in your posts and pages to display dynamic dates.', 'kashiwazaki-seo-dynamic-date'); ?></p>

                    <div class="card">
                        <h3><?php echo esc_html__('Basic Usage', 'kashiwazaki-seo-dynamic-date'); ?></h3>
                        <pre>[ksdate]</pre>
                        <p><?php echo esc_html__('Displays the current date in the default format.', 'kashiwazaki-seo-dynamic-date'); ?></p>
                    </div>

                    <div class="card">
                        <h3><?php echo esc_html__('Custom Format', 'kashiwazaki-seo-dynamic-date'); ?></h3>
                        <pre>[ksdate format="Y/m/d"]</pre>
                        <pre>[ksdate format="Y-m-d H:i:s"]</pre>
                        <p><?php echo esc_html__('Specify a custom date format using PHP date format characters.', 'kashiwazaki-seo-dynamic-date'); ?></p>
                    </div>

                    <div class="card">
                        <h3><?php echo esc_html__('With Offset', 'kashiwazaki-seo-dynamic-date'); ?></h3>
                        <pre>[ksdate offset="-1y"]    <?php echo esc_html__('// 1 year ago', 'kashiwazaki-seo-dynamic-date'); ?></pre>
                        <pre>[ksdate offset="-6m"]    <?php echo esc_html__('// 6 months ago', 'kashiwazaki-seo-dynamic-date'); ?></pre>
                        <pre>[ksdate offset="+7d"]    <?php echo esc_html__('// 7 days later', 'kashiwazaki-seo-dynamic-date'); ?></pre>
                        <pre>[ksdate offset="-2w"]    <?php echo esc_html__('// 2 weeks ago', 'kashiwazaki-seo-dynamic-date'); ?></pre>
                    </div>

                    <div class="card">
                        <h3><?php echo esc_html__('Date Difference (Past & Future)', 'kashiwazaki-seo-dynamic-date'); ?></h3>

                        <h4><?php echo esc_html__('Past Dates (How long since...)', 'kashiwazaki-seo-dynamic-date'); ?></h4>
                        <pre>[ksdate diff="1999"]                    <?php echo esc_html__('// Years since 1999', 'kashiwazaki-seo-dynamic-date'); ?></pre>
                        <pre>[ksdate diff="1999" format="年"]        <?php echo esc_html__('// Years with unit', 'kashiwazaki-seo-dynamic-date'); ?></pre>
                        <pre>[ksdate diff="1999-01"]                 <?php echo esc_html__('// Months since Jan 1999', 'kashiwazaki-seo-dynamic-date'); ?></pre>
                        <pre>[ksdate diff="1999-01" format="ヶ月"]   <?php echo esc_html__('// Months with unit', 'kashiwazaki-seo-dynamic-date'); ?></pre>
                        <pre>[ksdate diff="1999-01-01"]              <?php echo esc_html__('// Days since Jan 1, 1999', 'kashiwazaki-seo-dynamic-date'); ?></pre>
                        <pre>[ksdate diff="1999-01-01" format="日"]  <?php echo esc_html__('// Days with unit', 'kashiwazaki-seo-dynamic-date'); ?></pre>

                        <h4><?php echo esc_html__('Future Dates (How long until...)', 'kashiwazaki-seo-dynamic-date'); ?></h4>
                        <pre>[ksdate diff="2030"]                    <?php echo esc_html__('// Years until 2030', 'kashiwazaki-seo-dynamic-date'); ?></pre>
                        <pre>[ksdate diff="2030" format="年"]        <?php echo esc_html__('// Years until with unit', 'kashiwazaki-seo-dynamic-date'); ?></pre>
                        <pre>[ksdate diff="2025-12"]                 <?php echo esc_html__('// Months until Dec 2025', 'kashiwazaki-seo-dynamic-date'); ?></pre>
                        <pre>[ksdate diff="2025-12" format="ヶ月"]   <?php echo esc_html__('// Months until with unit', 'kashiwazaki-seo-dynamic-date'); ?></pre>
                        <pre>[ksdate diff="2025-12-31"]              <?php echo esc_html__('// Days until Dec 31, 2025', 'kashiwazaki-seo-dynamic-date'); ?></pre>
                        <pre>[ksdate diff="2025-12-31" format="日"]  <?php echo esc_html__('// Days until with unit', 'kashiwazaki-seo-dynamic-date'); ?></pre>

                        <p class="description">
                            <strong><?php echo esc_html__('How it works:', 'kashiwazaki-seo-dynamic-date'); ?></strong><br>
                            <?php echo esc_html__('• Calculate the difference between a specified date and now', 'kashiwazaki-seo-dynamic-date'); ?><br>
                            <?php echo esc_html__('• Works for both past dates (elapsed time) and future dates (countdown)', 'kashiwazaki-seo-dynamic-date'); ?><br>
                            <?php echo esc_html__('• Always returns a positive number (absolute difference)', 'kashiwazaki-seo-dynamic-date'); ?><br>
                            <br>
                            <strong><?php echo esc_html__('Date format determines the unit:', 'kashiwazaki-seo-dynamic-date'); ?></strong><br>
                            <?php echo esc_html__('• YYYY (e.g., "1999", "2030") → calculates years by default', 'kashiwazaki-seo-dynamic-date'); ?><br>
                            <?php echo esc_html__('• YYYY-MM (e.g., "1999-01", "2025-12") → calculates months by default', 'kashiwazaki-seo-dynamic-date'); ?><br>
                            <?php echo esc_html__('• YYYY-MM-DD (e.g., "1999-01-01", "2025-12-31") → calculates days by default', 'kashiwazaki-seo-dynamic-date'); ?><br>
                            <br>
                            <strong><?php echo esc_html__('Using format attribute:', 'kashiwazaki-seo-dynamic-date'); ?></strong><br>
                            <?php echo esc_html__('• format="年" → Override to years and add Japanese unit (e.g., "26年")', 'kashiwazaki-seo-dynamic-date'); ?><br>
                            <?php echo esc_html__('• format="ヶ月" → Override to months and add Japanese unit (e.g., "313ヶ月")', 'kashiwazaki-seo-dynamic-date'); ?><br>
                            <?php echo esc_html__('• format="日" → Override to days and add Japanese unit (e.g., "9794日")', 'kashiwazaki-seo-dynamic-date'); ?><br>
                            <?php echo esc_html__('• Without format → Returns just the number (e.g., "26")', 'kashiwazaki-seo-dynamic-date'); ?><br>
                            <br>
                            <strong><?php echo esc_html__('Important notes:', 'kashiwazaki-seo-dynamic-date'); ?></strong><br>
                            <?php echo esc_html__('• The shortcode only outputs the number. You need to add context words like "から" (since), "まであと" (until), etc.', 'kashiwazaki-seo-dynamic-date'); ?><br>
                            <?php echo esc_html__('• For past dates: "1999年から[ksdate diff="1999" format="年"]が経過"', 'kashiwazaki-seo-dynamic-date'); ?><br>
                            <?php echo esc_html__('• For future dates: "2030年まであと[ksdate diff="2030" format="年"]"', 'kashiwazaki-seo-dynamic-date'); ?>
                        </p>
                    </div>

                    <div class="card">
                        <h3><?php echo esc_html__('Supported Offset Units', 'kashiwazaki-seo-dynamic-date'); ?></h3>
                        <table class="widefat">
                            <thead>
                                <tr>
                                    <th><?php echo esc_html__('Unit', 'kashiwazaki-seo-dynamic-date'); ?></th>
                                    <th><?php echo esc_html__('Description', 'kashiwazaki-seo-dynamic-date'); ?></th>
                                    <th><?php echo esc_html__('Example', 'kashiwazaki-seo-dynamic-date'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><code>y</code></td>
                                    <td><?php echo esc_html__('Years', 'kashiwazaki-seo-dynamic-date'); ?></td>
                                    <td><code>-20y</code> (20年前)</td>
                                </tr>
                                <tr>
                                    <td><code>m</code></td>
                                    <td><?php echo esc_html__('Months', 'kashiwazaki-seo-dynamic-date'); ?></td>
                                    <td><code>-6m</code> (6ヶ月前)</td>
                                </tr>
                                <tr>
                                    <td><code>w</code></td>
                                    <td><?php echo esc_html__('Weeks', 'kashiwazaki-seo-dynamic-date'); ?></td>
                                    <td><code>-2w</code> (2週間前)</td>
                                </tr>
                                <tr>
                                    <td><code>d</code></td>
                                    <td><?php echo esc_html__('Days', 'kashiwazaki-seo-dynamic-date'); ?></td>
                                    <td><code>+7d</code> (7日後)</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="card">
                        <h3><?php echo esc_html__('Common Date Format Characters', 'kashiwazaki-seo-dynamic-date'); ?></h3>
                        <table class="widefat">
                            <thead>
                                <tr>
                                    <th><?php echo esc_html__('Character', 'kashiwazaki-seo-dynamic-date'); ?></th>
                                    <th><?php echo esc_html__('Description', 'kashiwazaki-seo-dynamic-date'); ?></th>
                                    <th><?php echo esc_html__('Example', 'kashiwazaki-seo-dynamic-date'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr><td><code>Y</code></td><td>4桁の年</td><td>2025</td></tr>
                                <tr><td><code>y</code></td><td>2桁の年</td><td>25</td></tr>
                                <tr><td><code>m</code></td><td>ゼロ埋め月 (01-12)</td><td>10</td></tr>
                                <tr><td><code>n</code></td><td>月 (1-12)</td><td>10</td></tr>
                                <tr><td><code>d</code></td><td>ゼロ埋め日 (01-31)</td><td>24</td></tr>
                                <tr><td><code>j</code></td><td>日 (1-31)</td><td>24</td></tr>
                                <tr><td><code>H</code></td><td>24時間形式の時</td><td>16</td></tr>
                                <tr><td><code>i</code></td><td>分</td><td>30</td></tr>
                                <tr><td><code>s</code></td><td>秒</td><td>45</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        <?php elseif ($active_tab === 'examples'): ?>
            <div class="ksdate-tab-content">
                <div class="ksdate-examples-section">
                    <h2><?php echo esc_html__('Practical Examples', 'kashiwazaki-seo-dynamic-date'); ?></h2>

                    <div class="card">
                        <h3><?php echo esc_html__('SEO: Last Updated Date', 'kashiwazaki-seo-dynamic-date'); ?></h3>
                        <pre>&lt;p&gt;最終更新: [ksdate format="Y年m月d日"]&lt;/p&gt;</pre>
                        <p class="ksdate-example-output"><?php echo esc_html__('Output:', 'kashiwazaki-seo-dynamic-date'); ?> 最終更新: <?php echo esc_html(ksdate_calculate_date('Y年m月d日', '')); ?></p>
                    </div>

                    <div class="card">
                        <h3><?php echo esc_html__('Copyright Notice', 'kashiwazaki-seo-dynamic-date'); ?></h3>
                        <pre>&lt;p&gt;© [ksdate format="Y"] Your Company. All Rights Reserved.&lt;/p&gt;</pre>
                        <p class="ksdate-example-output"><?php echo esc_html__('Output:', 'kashiwazaki-seo-dynamic-date'); ?> © <?php echo esc_html(ksdate_calculate_date('Y', '')); ?> Your Company. All Rights Reserved.</p>
                    </div>

                    <div class="card">
                        <h3><?php echo esc_html__('Statistical Data (Previous Year)', 'kashiwazaki-seo-dynamic-date'); ?></h3>
                        <pre>&lt;h2&gt;[ksdate format="Y年" offset="-1y"]の売上実績&lt;/h2&gt;</pre>
                        <p class="ksdate-example-output"><?php echo esc_html__('Output:', 'kashiwazaki-seo-dynamic-date'); ?> <?php echo esc_html(ksdate_calculate_date('Y年', '-1y')); ?>の売上実績</p>
                    </div>

                    <div class="card">
                        <h3><?php echo esc_html__('Event Announcement (Future Date)', 'kashiwazaki-seo-dynamic-date'); ?></h3>
                        <pre>&lt;p&gt;次回イベント: [ksdate format="Y年n月j日" offset="+14d"]開催予定&lt;/p&gt;</pre>
                        <p class="ksdate-example-output"><?php echo esc_html__('Output:', 'kashiwazaki-seo-dynamic-date'); ?> 次回イベント: <?php echo esc_html(ksdate_calculate_date('Y年n月j日', '+14d')); ?>開催予定</p>
                    </div>

                    <div class="card">
                        <h3><?php echo esc_html__('Article Freshness Indicator', 'kashiwazaki-seo-dynamic-date'); ?></h3>
                        <pre>&lt;p&gt;この情報は[ksdate format="Y年m月"]時点のものです。&lt;/p&gt;</pre>
                        <p class="ksdate-example-output"><?php echo esc_html__('Output:', 'kashiwazaki-seo-dynamic-date'); ?> この情報は<?php echo esc_html(ksdate_calculate_date('Y年m月', '')); ?>時点のものです。</p>
                    </div>

                    <div class="card">
                        <h3><?php echo esc_html__('Multiple Dates in Comparison', 'kashiwazaki-seo-dynamic-date'); ?></h3>
                        <pre>&lt;p&gt;[ksdate format="Y年" offset="-2y"]から[ksdate format="Y年"]までの成長&lt;/p&gt;</pre>
                        <p class="ksdate-example-output"><?php echo esc_html__('Output:', 'kashiwazaki-seo-dynamic-date'); ?> <?php echo esc_html(ksdate_calculate_date('Y年', '-2y')); ?>から<?php echo esc_html(ksdate_calculate_date('Y年', '')); ?>までの成長</p>
                    </div>

                    <div class="card">
                        <h3><?php echo esc_html__('Years Since Foundation', 'kashiwazaki-seo-dynamic-date'); ?></h3>
                        <pre>&lt;p&gt;1999年から[ksdate diff="1999" format="年"]が経過しました&lt;/p&gt;</pre>
                        <p class="ksdate-example-output"><?php echo esc_html__('Output:', 'kashiwazaki-seo-dynamic-date'); ?> 1999年から<?php echo esc_html(ksdate_calculate_diff('1999', '年')); ?>が経過しました</p>
                    </div>

                    <div class="card">
                        <h3><?php echo esc_html__('Months Since Launch', 'kashiwazaki-seo-dynamic-date'); ?></h3>
                        <pre>&lt;p&gt;サービス開始から[ksdate diff="2020-04" format="ヶ月"]&lt;/p&gt;</pre>
                        <p class="ksdate-example-output"><?php echo esc_html__('Output:', 'kashiwazaki-seo-dynamic-date'); ?> サービス開始から<?php echo esc_html(ksdate_calculate_diff('2020-04', 'ヶ月')); ?></p>
                    </div>

                    <div class="card">
                        <h3><?php echo esc_html__('Days Since Opening', 'kashiwazaki-seo-dynamic-date'); ?></h3>
                        <pre>&lt;p&gt;開店から[ksdate diff="2024-01-01" format="日"]目&lt;/p&gt;</pre>
                        <p class="ksdate-example-output"><?php echo esc_html__('Output:', 'kashiwazaki-seo-dynamic-date'); ?> 開店から<?php echo esc_html(ksdate_calculate_diff('2024-01-01', '日')); ?>目</p>
                    </div>

                    <div class="card">
                        <h3><?php echo esc_html__('Years Until Target', 'kashiwazaki-seo-dynamic-date'); ?></h3>
                        <pre>&lt;p&gt;2030年まであと[ksdate diff="2030" format="年"]&lt;/p&gt;</pre>
                        <p class="ksdate-example-output"><?php echo esc_html__('Output:', 'kashiwazaki-seo-dynamic-date'); ?> 2030年まであと<?php echo esc_html(ksdate_calculate_diff('2030', '年')); ?></p>
                    </div>

                    <div class="card">
                        <h3><?php echo esc_html__('Days Until Event', 'kashiwazaki-seo-dynamic-date'); ?></h3>
                        <pre>&lt;p&gt;イベントまであと[ksdate diff="2025-12-31" format="日"]！&lt;/p&gt;</pre>
                        <p class="ksdate-example-output"><?php echo esc_html__('Output:', 'kashiwazaki-seo-dynamic-date'); ?> イベントまであと<?php echo esc_html(ksdate_calculate_diff('2025-12-31', '日')); ?>！</p>
                    </div>

                    <div class="card">
                        <h3><?php echo esc_html__('Countdown to Anniversary', 'kashiwazaki-seo-dynamic-date'); ?></h3>
                        <pre>&lt;p&gt;創立記念日まであと[ksdate diff="2026-04" format="ヶ月"]&lt;/p&gt;</pre>
                        <p class="ksdate-example-output"><?php echo esc_html__('Output:', 'kashiwazaki-seo-dynamic-date'); ?> 創立記念日まであと<?php echo esc_html(ksdate_calculate_diff('2026-04', 'ヶ月')); ?></p>
                    </div>

                    <div class="card">
                        <h3><?php echo esc_html__('SEO Best Practices', 'kashiwazaki-seo-dynamic-date'); ?></h3>
                        <ul>
                            <li><?php echo esc_html__('Use for "last updated" dates to show content freshness', 'kashiwazaki-seo-dynamic-date'); ?></li>
                            <li><?php echo esc_html__('Display current year in copyright notices', 'kashiwazaki-seo-dynamic-date'); ?></li>
                            <li><?php echo esc_html__('Show relative dates for statistics and data', 'kashiwazaki-seo-dynamic-date'); ?></li>
                            <li><?php echo esc_html__('Create evergreen content with automatically updating dates', 'kashiwazaki-seo-dynamic-date'); ?></li>
                            <li><?php echo esc_html__('Improve CTR by showing recent dates in meta descriptions', 'kashiwazaki-seo-dynamic-date'); ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * Plugin activation hook
 */
function ksdate_activate() {
    // Flush rewrite rules
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'ksdate_activate');

/**
 * Plugin deactivation hook
 */
function ksdate_deactivate() {
    // Flush rewrite rules
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'ksdate_deactivate');