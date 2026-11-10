<?php
/*
PowerPress uploads, paths inside the wp uploads folder, chapter image sources
*/

if (!defined('ABSPATH')) exit;

class PowerPress_Uploader {

    const MAX_IMAGE_BYTES = 10485760; // 10 Mb

    /** real path of an existing file inside uploads for a url under the uploads url, false otherwise */
    public static function localPath($url) {
        if (!is_string($url) || $url === '') {
            return false;
        }

        $uploads = wp_upload_dir();
        if (!empty($uploads['error'])) {
            return false;
        }

        // 1) URL STARTS W/ THE UPLOADS URL, HTTP OR HTTPS
        $scheme = '#^https?://#i';
        $prefix = preg_replace($scheme, '', trailingslashit($uploads['baseurl']));
        $bare = preg_replace($scheme, '', $url);
        if ($bare === $url || strpos($bare, $prefix) !== 0) {
            return false;
        }

        // 2) DECODED LIKE THE WEB SERVER DOES, NO NULL BYTES
        $relative = rawurldecode(substr($bare, strlen($prefix)));
        if (strpos($relative, "\0") !== false) {
            return false;
        }

        // 3) EXISTING FILE INSIDE UPLOADS
        $base = realpath($uploads['basedir']);
        $path = realpath("{$uploads['basedir']}/{$relative}");
        if ($base === false || $path === false || !is_file($path)) {
            return false;
        }

        $inside = rtrim($base, '/\\') . DIRECTORY_SEPARATOR;

        return (strpos($path, $inside) === 0) ? $path : false;
    }

    /** jpeg or png bytes and a file name for a chapter image, from a picked attachment or a url, false when refused */
    public static function chapterImage($attachment_id, $url) {
        $attachment_id = intval($attachment_id);
        $source = is_string($url) ? $url : '';
        $bytes = false;
        $name = '';

        // 1) PICKED ATTACHMENT: AN IMAGE THE USER CAN READ AND UPLOAD
        if ($attachment_id > 0) {
            if (!current_user_can('upload_files') || !current_user_can('read_post', $attachment_id) || !wp_attachment_is_image($attachment_id)) {
                return false;
            }

            $file = get_attached_file($attachment_id);
            if ($file && is_file($file)) {
                if (filesize($file) > self::MAX_IMAGE_BYTES) {
                    return false;
                }

                $bytes = file_get_contents($file);
                $name = basename($file);
                $source = '';
            } else {
                $source = (string) wp_get_attachment_url($attachment_id);
            }
        }

        // 2) URL: PULLED W/ EVERY REDIRECT HOP CHECKED, ONE BYTE PAST THE CAP MEANS TOO BIG
        if ($source !== '') {
            $args = [
                'timeout' => 15,
                'limit_response_size' => self::MAX_IMAGE_BYTES + 1,
            ];
            $response = powerpress_safe_remote($source, $args, 'GET');
            if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
                return false;
            }

            $bytes = wp_remote_retrieve_body($response);
            if (strlen($bytes) > self::MAX_IMAGE_BYTES) {
                return false;
            }

            $path = (string) parse_url($source, PHP_URL_PATH);
            $name = sanitize_file_name(rawurldecode(basename($path)));
        }

        // 3) JPEG OR PNG BYTES UNDER A JPG OR PNG NAME
        $info = (is_string($bytes) && $bytes !== '') ? @getimagesizefromstring($bytes) : false;
        $is_image = $info && in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG], true);
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (!$is_image || !in_array($ext, ['jpg', 'jpeg', 'png'], true)) {
            return false;
        }

        return [
            'name' => $name,
            'bytes' => $bytes,
        ];
    }
}
