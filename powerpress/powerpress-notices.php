<?php
/*
Blubrry PowerPress admin notices
*/

if( !defined('ABSPATH') ) exit;

// =====
// STORE
// =====

/** wp_kses allowlist for notice html */
function powerpress_message_allowed_html(): array {
    $attrs = [
        'href' => [],
        'target' => [],
        'rel' => [],
        'class' => [],
        'style' => [],
        'id' => [],
    ];

    return [
        'a' => $attrs,
        'p' => $attrs,
        'div' => $attrs,
        'span' => $attrs,
        'strong' => $attrs,
        'em' => $attrs,
        'br' => [],
        'details' => $attrs,
        'summary' => $attrs,
        'pre' => $attrs,
    ];
}

/** add notice html to powerpress_errors, skips exact dupes */
function powerpress_page_message_store($html, $prepend = false): void {
    $messages = get_option('powerpress_errors');
    if (!is_array($messages)) {
        $messages = [];
    }

    if (in_array($html, $messages, true)) {
        return;
    }

    // jquery re-orders with first as last, so notices go on the front
    if ($prepend) {
        array_unshift($messages, $html);
    } else {
        $messages[] = $html;
    }

    // wp pulls every autoload row on every request, frontend + feeds included, so autoload false
    update_option('powerpress_errors', $messages, false);
}

/** queue an error notice w/ optional debug details */
function powerpress_page_message_add_error($msg, $classes = 'inline', $debug = []): void {
    $msg = wp_kses($msg, powerpress_message_allowed_html());
    $msg .= powerpress_message_details($debug);
    $classes = esc_attr($classes);

    powerpress_page_message_store("<div class='error powerpress-error {$classes}'>{$msg}</div>");
}

/** queue a success notice at the front of the store */
function powerpress_page_message_add_notice($msg, $classes = 'inline'): void {
    $msg = wp_kses($msg, powerpress_message_allowed_html());
    $classes = esc_attr($classes);

    powerpress_page_message_store("<div class='updated fade powerpress-notice {$classes}'>{$msg}</div>", true);
}

/** collapsible debug block w/ feed, file + last remote error, empty when none */
function powerpress_message_details($debug): string {
    if (empty($debug) || !is_array($debug)) {
        return '';
    }

    $details = [];

    if (!empty($debug['feed_slug'])) {
        $feed_slug = esc_html($debug['feed_slug']);
        $details[] = "Feed: {$feed_slug}";
    }

    if (!empty($debug['media_file'])) {
        $media_file = esc_html($debug['media_file']);
        $details[] = "File: {$media_file}";
    }

    if (!empty($GLOBALS['g_powerpress_remote_error'])) {
        $remote_error = esc_html($GLOBALS['g_powerpress_remote_error']);
        $details[] = "Response: {$remote_error}";
    }

    if (empty($details)) {
        return '';
    }

    $details_text = implode("\n", $details);
    $link_text = esc_html(__('Show Details', 'powerpress'));

    return "<details class='powerpress-error-details'><summary>{$link_text}</summary><pre>{$details_text}</pre></details>";
}

// =======
// CLASSIC
// =======

/** print + clear stored notices inline */
function powerpress_page_message_print(): void {
    $messages = get_option('powerpress_errors');
    if (empty($messages) || !is_array($messages)) {
        return;
    }

    delete_option('powerpress_errors');

    foreach ($messages as $message) {
        echo $message;
    }
}

/** print + clear stored notices, skips block editor screens, purges sg cache */
function powerpress_admin_notices(): void {
    $current_screen = function_exists('get_current_screen') ? get_current_screen() : null;
    if (!empty($current_screen) && method_exists($current_screen, 'is_block_editor') && $current_screen->is_block_editor()) {
        return;
    }

    $errors = get_option('powerpress_errors');
    if (empty($errors) || !is_array($errors)) {
        return;
    }

    if (!delete_option('powerpress_errors')) {
        update_option('powerpress_errors', '', false);
    }

    if (function_exists('sg_cachepress_purge_cache')) {
        sg_cachepress_purge_cache();
    }

    foreach ($errors as $error) {
        echo $error;
    }
}

if (is_admin()) {
    add_action('admin_notices', 'powerpress_admin_notices');
}

// ============
// BLOCK EDITOR
// ============

/** register the route the block editor pulls powerpress notices from after a save */
function powerpress_register_editor_notices_route(): void {
    register_rest_route('powerpress/v1', '/editor-notices', [
        'methods' => 'POST',
        'callback' => 'powerpress_take_editor_notices',
        'permission_callback' => function (): bool {
            return current_user_can('edit_posts');
        },
    ]);
}

add_action('rest_api_init', 'powerpress_register_editor_notices_route');

/** hand the stored powerpress messages to the block editor once, as core/notices entries */
function powerpress_take_editor_notices(): array {
    $messages = get_option('powerpress_errors');
    if (empty($messages) || !is_array($messages)) {
        return [];
    }

    delete_option('powerpress_errors');

    $notices = [];
    foreach ($messages as $html) {
        if (!is_string($html)) {
            continue;
        }

        $hash = md5($html);
        $inner = preg_replace('#^<div[^>]*>(.*)</div>$#s', '$1', $html) ?? $html;

        $notices[] = [
            'id' => "powerpress-{$hash}",
            'status' => strpos($html, 'powerpress-error') !== false ? 'error' : 'success',
            'html' => wp_kses($inner, powerpress_message_allowed_html()),
        ];
    }

    return $notices;
}

/** load the block editor script that shows powerpress save notices */
function powerpress_enqueue_editor_notices(): void {
    $screen = get_current_screen();
    if (empty($screen) || $screen->base !== 'post') {
        return;
    }

    powerpress_enqueue_assets([
        'powerpress-editor-notices' => [
            'type' => 'script',
            'path' => 'js/editor-notices',
            'deps' => ['wp-api-fetch', 'wp-data', 'wp-dom-ready', 'wp-edit-post', 'wp-editor', 'wp-notices'],
        ],
    ]);
}

if (is_admin()) {
    add_action('enqueue_block_editor_assets', 'powerpress_enqueue_editor_notices');
}

// ============
// COOKIE SWEEP
// ============

/** pp_event_{feed}_{post}_{success|add_notice} names in a cookie jar */
function powerpress_event_cookie_names(array $cookies): array {
    $names = [];
    foreach (array_keys($cookies) as $name) {
        if (preg_match('/^pp_event_[a-z0-9_-]+_\d+_(success|add_notice)$/', (string) $name)) {
            $names[] = $name;
        }
    }

    return $names;
}

/** expire any pp_event_* cookies the browser sends */
function powerpress_clear_event_cookies(): void {
    if (headers_sent()) {
        return;
    }

    foreach (powerpress_event_cookie_names($_COOKIE) as $name) {
        setcookie($name, '', time() - HOUR_IN_SECONDS, '/');
    }
}

if (is_admin()) {
    add_action('admin_init', 'powerpress_clear_event_cookies');
}
