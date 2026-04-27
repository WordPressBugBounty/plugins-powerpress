<?php
// powerpressadmin-ping-sites.php
wp_enqueue_script('powerpress-admin', powerpress_get_root_url() . 'js/admin.js', array(), POWERPRESS_VERSION);

// TODO why isn't this getting included?
//wp_register_style('powerpress_settings_style',  powerpress_get_root_url() . 'css/settings.css', array(), POWERPRESS_VERSION);

function powerpressadmin_diagnostics_process(){
    global $powerpress_diags;
    $powerpress_diags = array();

    // First, see if the user has cURL and/or allow_url_fopen enabled...
    $powerpress_diags['detecting_media'] = array();
    $powerpress_diags['detecting_media']['success'] = true;
    $powerpress_diags['detecting_media']['warning'] = false;
    $powerpress_diags['detecting_media']['allow_url_fopen'] = (ini_get( 'allow_url_fopen' ) != false); // fopen
    $powerpress_diags['detecting_media']['curl'] = function_exists( 'curl_init' ); // cURL
    $powerpress_diags['detecting_media']['message2'] = ''; // if ( !ini_get('open_basedir') )
    $powerpress_diags['detecting_media']['message3'] = ''; // ssl checks

    // Testing:
    //$powerpress_diags['detecting_media']['allow_url_fopen'] = false;
    //$powerpress_diags['detecting_media']['curl'] = false;

    if($powerpress_diags['detecting_media']['curl']) {
        $powerpress_diags['detecting_media']['message'] = __('Your web server supports the PHP cURL library.', 'powerpress');
        if($powerpress_diags['detecting_media']['allow_url_fopen']){
            $powerpress_diags['detecting_media']['message'] .= ' '. __('Your web server is also configured with the php.ini setting \'allow_url_fopen\' enabled, but the cURL library takes precedence.', 'powerpress');
        }

        if(ini_get('open_basedir')){
            $powerpress_diags['detecting_media']['warning'] = true;
            $powerpress_diags['detecting_media']['message2'] = __('Warning: The php.ini setting \'open_basedir\' will prevent the cURL library from following redirects in URLs.', 'powerpress');
        }
    }
    elseif($powerpress_diags['detecting_media']['allow_url_fopen']){
        $powerpress_diags['detecting_media']['message'] = __('Your web server is configured with the php.ini setting \'allow_url_fopen\' enabled.', 'powerpress');
    } else {
        $powerpress_diags['detecting_media']['success'] = false;
        $powerpress_diags['detecting_media']['message'] = __('Your server must either have the php.ini setting \'allow_url_fopen\' enabled or have the PHP cURL library installed in order to detect media information.', 'powerpress');
    }

    // OpenSSL or curl SSL is required
    $powerpress_diags['detecting_media']['openssl'] = extension_loaded('openssl');
    $powerpress_diags['detecting_media']['curl_ssl'] = false;
    if(function_exists('curl_version')){
        $curl_info = curl_version();
        $powerpress_diags['detecting_media']['curl_ssl'] = ($curl_info['features'] & CURL_VERSION_SSL );
    }

    if($powerpress_diags['detecting_media']['openssl'] == false && $powerpress_diags['detecting_media']['curl_ssl'] == false){
        $powerpress_diags['detecting_media']['warning'] = true;
        $powerpress_diags['detecting_media']['message3'] = __('WARNING: Your server should support SSL either openssl or curl_ssl.', 'powerpress');
    }

    // testing:
    //$powerpress_diags['pinging_itunes']['openssl'] = false;
    //$powerpress_diags['pinging_itunes']['curl_ssl'] = false;

    // Third, see if the uploads/powerpress folder is writable
    $UploadArray = wp_upload_dir();
    $powerpress_diags['uploading_artwork'] = array();
    $powerpress_diags['uploading_artwork']['success'] = false;
    $powerpress_diags['uploading_artwork']['file_uploads'] = ini_get( 'file_uploads' );
    $powerpress_diags['uploading_artwork']['writable'] = false;
    $powerpress_diags['uploading_artwork']['upload_path'] = '';
    $powerpress_diags['uploading_artwork']['message'] = '';

    // Testing:
    //$UploadArray['error'] = 'WordPres broke';
    //$powerpress_diags['uploading_artwork']['file_uploads'] = false;
    //$UploadArray['error'] = true;

    if($powerpress_diags['uploading_artwork']['file_uploads'] == false){
        $powerpress_diags['uploading_artwork']['message'] = __('Your server requires the php.ini setting \'file_uploads\' enabled in order to upload podcast artwork.', 'powerpress');
    }
    elseif($UploadArray['error'] === false){
        $powerpress_diags['uploading_artwork']['upload_path'] = $UploadArray['basedir'] . '/powerpress/';

        if(!is_dir($powerpress_diags['uploading_artwork']['upload_path']) && ! wp_mkdir_p( rtrim($powerpress_diags['uploading_artwork']['upload_path'], '/'))){
            $powerpress_diags['uploading_artwork']['message'] = sprintf( __( 'Unable to create directory %s. Is its parent directory writable by the server?', 'powerpress'), rtrim($powerpress_diags['uploading_artwork']['upload_path'], '/') );
        } else {
            $powerpress_diags['uploading_artwork']['writable'] = powerpressadmin_diagnostics_is_writable($powerpress_diags['uploading_artwork']['upload_path']);
            if($powerpress_diags['uploading_artwork']['writable'] == false){
                $powerpress_diags['uploading_artwork']['message'] = sprintf(__('PowerPress is unable to write to the %s directory.', 'powerpress'), $powerpress_diags['uploading_artwork']['upload_path']);
            } else {
                $powerpress_diags['uploading_artwork']['success'] = true;
                $powerpress_diags['uploading_artwork']['message'] = __('You are able to upload and save artwork images for your podcasts.', 'powerpress');
            }
        }
    } else {
        if(strlen($UploadArray['error']) > 2){
            $powerpress_diags['uploading_artwork']['message'] = $UploadArray['error'];
        } else {
            $powerpress_diags['uploading_artwork']['message'] = __('An error occurred obtaining the uploads directory from WordPress.', 'powerpress');
        }
    }

    // Fourth, see if we have enough memory and we're running an appropriate version of PHP
    $powerpress_diags['system_info'] = array();
    $powerpress_diags['system_info']['warning'] = false;
    $powerpress_diags['system_info']['success'] = true;
    $powerpress_diags['system_info']['php_version'] = phpversion();
    $powerpress_diags['system_info']['php_cgi'] = (function_exists('php_sapi_name') && preg_match('/cgi/i', php_sapi_name())? true : false );
    $powerpress_diags['system_info']['memory_limit'] = (int) ini_get('memory_limit');
    $powerpress_diags['system_info']['temp_directory'] = get_temp_dir(); // Function available since WP2.5+

    // testing:
    //$powerpress_diags['system_info']['memory_limit'] = -1;
    //$powerpress_diags['system_info']['memory_limit'] = 0;
    //$powerpress_diags['system_info']['memory_limit'] = 16;

    if($powerpress_diags['system_info']['memory_limit'] == 0){
        if(version_compare($powerpress_diags['system_info']['php_version'], '5.2') > 0){
            $powerpress_diags['system_info']['memory_limit'] = 128;
        } elseif(version_compare($powerpress_diags['system_info']['php_version'], '5.2') == 0){
            $powerpress_diags['system_info']['memory_limit'] = 16;
        } else {
            $powerpress_diags['system_info']['memory_limit'] = 8;
        }
    }

    $powerpress_diags['system_info']['memory_used'] = 0;

    if(version_compare($powerpress_diags['system_info']['php_version'], '7.0') > -1){
        $powerpress_diags['system_info']['message'] = sprintf( __('Your version of PHP (%s) is OK!', 'powerpress'), $powerpress_diags['system_info']['php_version']);
    } elseif(version_compare($powerpress_diags['system_info']['php_version'], '5.4') > -1){
        $powerpress_diags['system_info']['message'] = sprintf( __('Your version of PHP (%s) is OK, though PHP 7.0 or newer is recommended.', 'powerpress'), $powerpress_diags['system_info']['php_version'] );
    } else {
        $powerpress_diags['system_info']['message'] = sprintf( __('Your version of PHP (%s) will work, but PHP 7.0 or newer is recommended.', 'powerpress'), $powerpress_diags['system_info']['php_version'] );
    }

    $used = 0;
    $total = $powerpress_diags['system_info']['memory_limit'];

    if($total == -1){
        $powerpress_diags['system_info']['message2'] = __('Your scripts have no limit to the amount of memory they can use.', 'powerpress');
        $used = (function_exists('memory_get_peak_usage')? memory_get_peak_usage() : ( function_exists('memory_get_usage') ? memory_get_usage() : 0 ) );
        if($used){
            $powerpress_diags['system_info']['memory_used'] = round($used / 1024 / 1024, 2);
        }
    } elseif(function_exists('memory_get_peak_usage')){
        $used = round(memory_get_peak_usage() / 1024 / 1024, 2);
        $powerpress_diags['system_info']['memory_used'] = $used;
        $percent = ($used/$total)*100;
        $powerpress_diags['system_info']['message2'] = sprintf(__('You are using %d%% (%.01fM of %.01dM) of available memory.', 'powerpress'), $percent, $used, $total);
    } elseif(function_exists('memory_get_usage')){
        $used = round(memory_get_usage() / 1024 / 1024, 2);
        $powerpress_diags['system_info']['memory_used'] = $used;
        $percent = ($used/$total)*100;
        $powerpress_diags['system_info']['message2'] = sprintf(__('You are using %d%% (%.01fM of %dM) of available memory. Versions of PHP 5.2 or newer will give you a more accurate total of memory usage.', 'powerpress'), $percent, $used, $total);
    } else {
        $powerpress_diags['system_info']['message2'] = sprintf(__('Your scripts have a total of %dM.', 'powerpress'), $total );
    }

    if($total > 0 && ($used + 4) > $total){
        $powerpress_diags['system_info']['warning'] = true;
        $powerpress_diags['system_info']['message2'] = __('Warning:', 'powerpress') .' '. $powerpress_diags['system_info']['message2'];
        $powerpress_diags['system_info']['message2'] .= ' ';
        $powerpress_diags['system_info']['message2'] .= sprintf(__('We recommend that you have at least %dM (4M more that what is currently used) or more memory to accomodate all of your installed plugins.', 'powerpress'), ceil($used)+4 );
    }

    if(empty($powerpress_diags['system_info']['temp_directory'])){
        $powerpress_diags['system_info']['success'] = false;
        $powerpress_diags['system_info']['message3'] =  __('Error:', 'powerpress') .' '. __('No temporary directory available.', 'powerpress');
    } elseif(is_dir($powerpress_diags['system_info']['temp_directory']) && is_writable($powerpress_diags['system_info']['temp_directory'])){
        $powerpress_diags['system_info']['message3'] = sprintf(__('Temporary directory %s is writable.', 'powerpress'), $powerpress_diags['system_info']['temp_directory']);
    } else {
        $powerpress_diags['system_info']['success'] = false;
        $powerpress_diags['system_info']['message3'] = __('Error:', 'powerpress') .' '. sprintf(__('Temporary directory %s is not writable.', 'powerpress'), $powerpress_diags['system_info']['temp_directory']);
    }

    if(empty($powerpress_diags['system_info']['php_cgi'])){
        $powerpress_diags['system_info']['message4'] = '';
    } else {
        $powerpress_diags['system_info']['message4'] = __('Warning:', 'powerpress') .' '. __('PHP running in CGI mode.', 'powerpress');
    }

    $user_info = wp_get_current_user();
    if(!empty($user_info->user_email) && isset($_GET['Submit'])){
        $emailResults = powerpressadmin_diagnostics_email($user_info->user_email);

        if(isset($emailResults['additional_email_error'])){
            powerpress_page_message_add_notice($emailResults['additional_email_error'], 'inline error');
        }

        if($emailResults['email_success']){
            powerpress_page_message_add_notice(sprintf(__('Diagnostic results sent to %s.', 'powerpress'), $user_info->user_email));

        } else {
            powerpress_page_message_add_notice(sprintf(__('Diagnostics processing failed. Results sent to %s.', 'powerpress'), $user_info->user_email), 'inline error');
        }
    }
}

function get_php_info(){
    $return = array();

    // PHP extensions
    $return['extensions'] = get_loaded_extensions();

    return $return;
}

function get_apache_info(){
    $return = array();

    // Apache
    if(function_exists('apache_get_version')){
        $return['version'] = apache_get_version();
    }

    if(function_exists('apache_get_modules')){
        $return['modules'] = apache_get_modules();
    }

    if(function_exists('apache_getenv')){
        $return['env_server_name'] = apache_getenv('SERVER_NAME');
    }

    if(function_exists('apache_request_headers')){
        $return['request_headers'] = apache_request_headers();
    }

    if(function_exists('apache_response_headers')){
        $return['response_headers'] = apache_response_headers();
    }

    return $return;
}

function get_os(){
    // OPERATING SYSTEM
    $os = PHP_OS_FAMILY;

    if ($os === 'Darwin'){
        // macOS server
        $os = 'MacOS';
    }

    return $os;
}

function get_plugins_info(){
    $return = array();

    $activePlugins = get_option('active_plugins');
    $allPlugins = get_plugins();

    if($activePlugins){
        $return['active'] = $activePlugins;
    }

    if($allPlugins){
        $return['all'] = $allPlugins;
    }

    return $return;
}

function powerpressadmin_diagnostics_email($email, $returnRawData = false){
    global $powerpress_diags, $wpmu_version, $wp_version, $powerpress_diag_message;
    $SettingsGeneral = get_option('powerpress_general');
    $additionalEmailInvalid = false;

    $phpInfo = get_php_info();

    // First we need some basic information about the blog...
    $message = __('Blog Title:', 'powerpress') .' '. get_bloginfo('name') . "<br />\n";
    $message .= __('Blog URL:', 'powerpress') .' '. get_bloginfo('url') . "<br />\n";
    $message .= __('WordPress Version:', 'powerpress') .' '. $wp_version . "<br />\n";
    if(!empty($wpmu_version)){
        $message .= __('WordPress MU Version:', 'powerpress') .' '. $wpmu_version . "<br />\n";
    }
    $message .= __('System:', 'powerpress') .' '. $_SERVER['SERVER_SOFTWARE'] . "<br />\n";
    $message .= __('Open basedir:', 'powerpress') .' '. ini_get('open_basedir') ."<br />\n";

    // Crucial PowerPress Settings
    $message .= "<br />\n";
    $message .= '<strong>'. __('Important PowerPress Settings', 'powerpress') ."</strong><br />\n";
    $message .= " &nbsp; \t &nbsp; ". __('PowerPress version:', 'powerpress') .' '. POWERPRESS_VERSION ."<br />\n";
    $message .= " &nbsp; \t &nbsp; ". __('episode box file size/duration fields:', 'powerpress') .' '. ( empty($SettingsGeneral['episode_box_mode']) ?__('yes', 'powerpress'): ($SettingsGeneral['episode_box_mode']==1?__('no', 'powerpress'):__('yes', 'powerpress')) ) ."<br />\n";
    $message .= " &nbsp; \t &nbsp; ". __('Category Podcasting:', 'powerpress') .' '. ( empty($SettingsGeneral['cat_casting']) ?__('Disabled (default)', 'powerpress'): __('Enabled', 'powerpress')) ."<br />\n";
    $message .= " &nbsp; \t &nbsp; ". __('Podcast Channels:', 'powerpress') .' '. ( empty($SettingsGeneral['channels']) ?__('Disabled (default)', 'powerpress'): __('Enabled', 'powerpress')) ."<br />\n";
    $message .= " &nbsp; \t &nbsp; ". __('Additional Player Options:', 'powerpress') .' '. ( empty($SettingsGeneral['player_options'])?__('Disabled (default)', 'powerpress'): __('Enabled', 'powerpress')) ."<br />\n";

    // Detecting Media Information
    $message .= "<br />\n";
    $message .= '<strong>'.__('Detecting Media Information', 'powerpress') ."</strong><br />\n";
    $message .= " &nbsp; \t &nbsp; ". __('success:', 'powerpress') .' '. ($powerpress_diags['detecting_media']['success']?'true':'false') ."<br />\n";
    $message .= " &nbsp; \t &nbsp; ". __('warning:', 'powerpress') .' '. ($powerpress_diags['detecting_media']['warning']?'true':'false') ."<br />\n";
    $message .= " &nbsp; \t &nbsp; ". __('allow_url_fopen:', 'powerpress') .' '. ($powerpress_diags['detecting_media']['allow_url_fopen']?'true':'false') ."<br />\n";
    $message .= " &nbsp; \t &nbsp; ". __('curl:', 'powerpress') .' '. ($powerpress_diags['detecting_media']['curl']?'true':'false') ."<br />\n";
    $message .= " &nbsp; \t &nbsp; ". __('curl_ssl:', 'powerpress') .' '. ($powerpress_diags['detecting_media']['curl_ssl']?'true':'false') ."<br />\n";
    $message .= " &nbsp; \t &nbsp; ". __('openssl:', 'powerpress') .' '. ($powerpress_diags['detecting_media']['openssl']?'true':'false') ."<br />\n";
    $message .= " &nbsp; \t &nbsp; ". __('message:', 'powerpress') .' '. $powerpress_diags['detecting_media']['message'] ."<br />\n";
    $message .= " &nbsp; \t &nbsp; ". __('message 2:', 'powerpress') .' '. $powerpress_diags['detecting_media']['message2'] ."<br />\n";
    $message .= " &nbsp; \t &nbsp; ". __('message 3:', 'powerpress') .' '. $powerpress_diags['detecting_media']['message3'] ."<br />\n";

    // Uploading Artwork
    $message .= "<br />\n";
    $message .= '<strong>'.__('Uploading Artwork', 'powerpress') ."</strong><br />\n";
    $message .= " &nbsp; \t &nbsp; ". __('success:', 'powerpress') .' '. ($powerpress_diags['uploading_artwork']['success']?'true':'false') ."<br />\n";
    $message .= " &nbsp; \t &nbsp; ". __('file_uploads:', 'powerpress') .' '. ($powerpress_diags['uploading_artwork']['file_uploads']?'true':'false') ."<br />\n";
    $message .= " &nbsp; \t &nbsp; ". __('writable:', 'powerpress') .' '. ($powerpress_diags['uploading_artwork']['writable']?'true':'false') ."<br />\n";
    $message .= " &nbsp; \t &nbsp; ". __('upload_path:', 'powerpress') .' '. $powerpress_diags['uploading_artwork']['upload_path'] ."<br />\n";
    $message .= " &nbsp; \t &nbsp; ". __('message:', 'powerpress') .' '. $powerpress_diags['uploading_artwork']['message'] ."<br />\n";

    // System Information
    $message .= "<br />\n";
    $message .= '<strong>'.__('System Information', 'powerpress') ."</strong><br />\n";
    $os = get_os();
    if($os){
        $message .= " &nbsp; \t &nbsp; Operating System: " . $os . "<br />\n";
    }

    $message .= " &nbsp; \t &nbsp; ". __('success:', 'powerpress') .' '. ($powerpress_diags['system_info']['success']?'true':'false') ."<br />\n";
    $message .= " &nbsp; \t &nbsp; ". __('warning:', 'powerpress') .' '. ($powerpress_diags['system_info']['warning']?'yes':'no') ."<br />\n";
    $message .= " &nbsp; \t &nbsp; ". __('php_version:', 'powerpress') .' '. $powerpress_diags['system_info']['php_version'] ."<br />\n";
    $message .= " &nbsp; \t &nbsp; ". __('memory_limit:', 'powerpress') .' '. $powerpress_diags['system_info']['memory_limit'] ."M\n";
    $message .= " &nbsp; \t &nbsp; ". __('memory_used:', 'powerpress') .' '. sprintf('%.01fM',$powerpress_diags['system_info']['memory_used']) ."<br />\n";
    $message .= " &nbsp; \t &nbsp; ". __('temp directory:', 'powerpress') .' '. $powerpress_diags['system_info']['temp_directory'] ."<br />\n";
    $message .= " &nbsp; \t &nbsp; ". __('message:', 'powerpress') .' '. $powerpress_diags['system_info']['message'] ."<br />\n";
    $message .= " &nbsp; \t &nbsp; ". __('message 2:', 'powerpress') .' '. $powerpress_diags['system_info']['message2'] ."<br />\n";
    $message .= " &nbsp; \t &nbsp; ". __('message 3:', 'powerpress') .' '. $powerpress_diags['system_info']['message3'] ."<br />\n";
    if(!empty($powerpress_diags['system_info']['message4'])){
        $message .= " &nbsp; \t &nbsp; ". __('message 4:', 'powerpress') .' '. $powerpress_diags['system_info']['message4'] ."<br />\n";
    }

    // We are always sending the active plugins list
    if(true){
        $current_plugins = get_option('active_plugins');
        $message .= "<br />\n";
        $message .= '<strong>'.__('Active Plugins', 'powerpress') ."</strong><br />\n";
        foreach($current_plugins as $null=> $plugin_path){
            $plugin_data = get_plugin_data( rtrim(WP_PLUGIN_DIR, '/\\'). '/'. rtrim($plugin_path, '\\/'), false, false ); //Do not apply markup/translate as it'll be cached.
            $message .= " &nbsp; \t &nbsp; " . __('Title:', 'powerpress') .' '. $plugin_data['Title']. "<br />\n";
            $message .= " &nbsp; \t &nbsp; " . __('Relative Path:', 'powerpress') .' '. $plugin_path. "<br />\n";
            $message .= " &nbsp; \t &nbsp; " . __('Version:', 'powerpress') .' '. $plugin_data['Version']. "<br />\n";
            $message .= " &nbsp; \t &nbsp; " . __('Web Site:', 'powerpress') .' '. $plugin_data['PluginURI']. "<br />\n";
        }
    }

    // PHP Extensions
    $phpExtensions = $phpInfo['extensions'];
    $message .= "<br />\n";
    $message .= '<strong>'.__('PHP Extensions', 'powerpress') ."</strong><br />\n";
    foreach($phpExtensions as $null => $phpExtension){
        $message .= " &nbsp; \t &nbsp; " . $phpExtension . "<br />\n";
    }

    // Apache Info
    $apacheInfo = get_apache_info();
    if(!empty($apacheInfo)){
        $apacheVersion = ($apacheInfo['version'] ?? false);
        $apacheServerName = ($apacheInfo['env_server_name'] ?? false);
        $apacheModules = ($apacheInfo['modules'] ?? false);
        $apacheRequestHeaders = ($apacheInfo['request_headers'] ?? false);
        $apacheResponseHeaders = ($apacheInfo['response_headers'] ?? false);

        $message .= "<br />\n";
        $message .= '<strong>'.__('Apache Information', 'powerpress') ."</strong><br />\n";

        if($apacheVersion){
            $message .= " &nbsp; \t &nbsp; " . __('Version:', 'powerpress') .' '. $apacheVersion . "<br />\n";
        }

        if($apacheServerName){
            $message .= " &nbsp; \t &nbsp; " . __('Server Name:', 'powerpress') .' '. $apacheServerName . "<br />\n";
        }

        $message .= "<br />\n";

        if($apacheModules){
            $message .= '<strong>'.__('Modules', 'powerpress') ."</strong><br />\n";
            foreach($apacheModules as $module){
                $message .= " &nbsp; \t &nbsp; " . $module . "<br />\n";
            }
        }

        $message .= "<br />\n";

        if($apacheRequestHeaders){
            $message .= '<strong>'.__('Request Headers', 'powerpress') ."</strong><br />\n";
            foreach($apacheRequestHeaders as $index => $header){
                $message .= " &nbsp; \t &nbsp; " . $index . ": " . $header . "<br />\n";
            }
        }

        $message .= "<br />\n";

        if($apacheResponseHeaders){
            $message .= '<strong>'.__('Request Headers', 'powerpress') ."</strong><br />\n";
            foreach($apacheResponseHeaders as $index => $header){
                $message .= " &nbsp; \t &nbsp; " . $index . ": " . $header . "<br />\n";
            }
        }
    }

    $message .= "<br />\n";

    // Themes
    $themes = wp_get_themes();
    if(!empty($themes)){
        $message .= '<strong>'.__('Themes', 'powerpress') ."</strong><br />\n";
        foreach($themes as $theme){
            $version = $theme->get('Version');
            $themeText = $theme;
            if($version){
                $themeText .= ' - (' . $version . ')';
            }

            $message .= " &nbsp; \t &nbsp; " . $themeText . "<br />\n";
        }
    }

    if($returnRawData){
        return $message;
    }

    // Now lets loop through each section of diagnostics
    $user_info = wp_get_current_user();
    $from_email = $user_info->user_email;
    $from_name = $user_info->user_nicename;
    $headers = 'From: "'.$from_name.'" <'.$from_email.'>'."\n"
        .'Reply-To: "'.$from_name.'" <'.$from_email.'>'."\n"
        .'Return-Path: "'.$from_name.'" <'.$from_email.'>'."\n";

    if(!empty($_GET['support'])){
        $from_name = 'Blubrry Support';
        $from_email = 'support@blubrry.com';
        $headers .= 'CC: "'.$from_name.'" <'.$from_email.'>'."\n";
    }

    if(!empty($_GET['additional']) && !empty($_GET['additional_email'])){
        $from_email = htmlspecialchars($_GET['additional_email']);

        // validate additional email
        if(!filter_var($from_email, FILTER_SANITIZE_EMAIL)){
            $additionalEmailInvalid = "The additional email you provided is invalid. Please check the address and try again.";
        } else {
            $headers .= 'CC: <'.$from_email.'>'."\n";
        }
    }

    $headers .= "Content-Type: text/html\n";

    $return = array();
    if(@wp_mail($email, sprintf(__('Blubrry PowerPress diagnostic results for %s', 'powerpress'), get_bloginfo('name')), $message, $headers)){
        $return['email_success'] = true;
    } else {
        $return['email_success'] = false;
    }

    if(!empty($additionalEmailInvalid)){
        $return['additional_email_error'] = $additionalEmailInvalid;
    }

    $powerpress_diag_message = $message;

    return $return;
}
	
function powerpressadmin_diagnostics_is_writable($dir){
    // Make sure we can create a file in the specified directory...
    if(is_dir($dir)){
        return is_writable($dir);
    }
    return false;
}
	
function powerpressadmin_diagnostics_status($success = true, $warning = false, $diagnosticCategory = '', $noStatus = false){
    $color = '#0A8822';
    $status = __('Success', 'powerpress');
    $rowBackground = '#cee7d3';
    $headerText = ($diagnosticCategory ? $diagnosticCategory . ' - ' : '');

    if($success == false){ // Failed takes precedence over warning
        $color = '#CC0000';
        $status = __('Failed', 'powerpress');
        $rowBackground = '#f5cccc';
    } elseif($warning){
        $color = '#D98500';
        $status = __('Warning', 'powerpress');
        $rowBackground = '#f7e7cc';
    } elseif($noStatus){
        $status = '';
        $rowBackground = '#c8c8c8';
        $headerText = $diagnosticCategory;
    }
    ?>

    <div class="row diagnostics-status-row" style="background-color: <?php echo $rowBackground; ?>">
        <h2 style="margin: 0;">
            <strong style="color:<?php echo $color; ?>;">
                <span style="color: black;"><?php echo $headerText; ?></span>
                <?php echo $status; ?>

            </strong>
        </h2>
    </div>
<?php }

function get_feed(){
    $FeedAttribs = array('type'=>'general', 'feed_slug'=>'', 'category_id'=>0, 'term_taxonomy_id'=>0, 'term_id'=>0, 'taxonomy_type'=>'', 'post_type'=>'');

    switch($FeedAttribs['type']){
        case 'category': {
            if(!empty($General['cat_casting_podcast_feeds'])){
                $feed_link = get_category_feed_link($FeedAttribs['category_id'], 'podcast');
            } else { // Use the old link
                $feed_link = get_category_feed_link($FeedAttribs['category_id']);
            }
        } break;

        case 'ttid': {
            $feed_link = get_term_feed_link($FeedAttribs['term_taxonomy_id'], $FeedAttribs['taxonomy_type'], 'rss2');
        } break;

        case 'post_type': {
            $feed_link = get_post_type_archive_feed_link($FeedAttribs['post_type'], $FeedAttribs['feed_slug']);
        } break;

        case 'channel': {
            $feed_link = get_feed_link($FeedAttribs['feed_slug']);
        } break;

        default: {
            $feed_link = get_feed_link('podcast');
        } break;
    }

    return $feed_link;
}
	
function powerpressadmin_diagnostics(){
    global $powerpress_diags, $powerpress_diag_message, $additionalEmailInvalid;
    $GeneralSettings = get_option('powerpress_general');

    if(empty($powerpress_diags)){
        powerpressadmin_diagnostics_process();
        powerpress_page_message_print();
    }
    ?>

    <div class="pp-row">
        <a class="pp-page-back-link" href="admin.php?page=powerpress/powerpressadmin_tools.php"><span>&#8592; PowerPress Tools</span></a>
    </div>

    <div class="pp-card-body">
        <div class="pp-row pp-tools-row">
            <h2 class="pp-page-sub-header">PowerPress Diagnostics</h2>
        </div>

        <div class="pp-row pp-tools-row" style="margin-bottom: 25px;">
            <p class="pp-tools-text">
                The Diagnostics page checks to see if your server is configured to support all the available features in Blubrry PowerPress.
            </p>
        </div>

        <?php // TABS ?>
        <div class="row pp-tab" style="padding-left: 35px;">
            <button id="report-tab" class="tablinks diagnostics-tab active" onclick="powerpress_openTab(event, 'diagnostics-report')">
                Diagnostics Report
            </button>
            <button id="raw-tab" class="tablinks diagnostics-tab" onclick="powerpress_openTab(event, 'diagnostics-raw')">
                Raw Data
            </button>
            <button id="email-tab" class="tablinks diagnostics-tab" onclick="powerpress_openTab(event, 'diagnostics-email')">
                Email Results
            </button>
        </div>

        <?php // REPORT ?>
        <div id="diagnostics-report" class="pp-tabcontent active">
            <?php // IMPORTANT PP SETTINGS ?>
            <div class="diagnostics-message">
                <?php powerpressadmin_diagnostics_status(
                    (!empty($GeneralSettings)),
                    (empty($GeneralSettings)),
                    'PowerPress Settings'
                ); ?>

                <div class="pp-row">
                    <p class="pp-tools-text" style="font-weight: bold;">Important PowerPress settings.</p>
                </div>

                <div class="pp-row">
                    <p class="pp-tools-text">PowerPress Version: <?php echo POWERPRESS_VERSION; ?></p>
                </div>

                <div class="pp-row">
                    <p class="pp-tools-text">Episode box file size/Duration fields: <?php echo (empty($GeneralSettings['episode_box_mode']) ? 'Yes': ($GeneralSettings['episode_box_mode'] == 1 ? 'No' : 'Yes')); ?></p>
                </div>

                <div class="pp-row">
                    <p class="pp-tools-text">Category Podcasting: <?php echo (empty($GeneralSettings['cat_casting']) ? 'Disabled (default)' : 'Enabled'); ?></p>
                </div>

                <div class="pp-row">
                    <p class="pp-tools-text">Podcast Channels: <?php echo (empty($GeneralSettings['channels']) ? 'Disabled (default)' : 'Enabled'); ?></p>
                </div>

                <div class="pp-row">
                    <p class="pp-tools-text">Additional Player Options: <?php echo (empty($GeneralSettings['player_options']) ? 'Disabled (default)' : 'Enabled'); ?></p>
                </div>
            </div>

            <hr class="diagnostic-report-divider">

            <?php // DETECTING MEDIA INFORMATION ?>
            <div class="diagnostics-message">
                <?php powerpressadmin_diagnostics_status(
                        $powerpress_diags['detecting_media']['success'],
                        $powerpress_diags['detecting_media']['warning'],
                        'Detecting Media Information'
                ); ?>

                <div class="pp-row">
                    <p class="pp-tools-text" style="font-weight: bold;">
                        This test checks to see if your web server can make connections with other web servers to obtain file size and media duration information.
                        The test checks to see if either the PHP cURL library is installed or the php.ini setting ‘allow_url_fopen’ enabled.
                    </p>
                </div>

                <div class="pp-row">
                    <p class="pp-tools-text" style="margin-bottom: 5px;"><?php echo htmlspecialchars($powerpress_diags['detecting_media']['message']); ?></p>
                </div>
                <?php if($powerpress_diags['detecting_media']['message2']){ ?>
                    <div class="pp-row">
                        <p class="pp-tools-text" style="margin-bottom: 5px;"><?php echo htmlspecialchars($powerpress_diags['detecting_media']['message2']); ?></p>
                    </div>
                <?php } ?>

                <?php if($powerpress_diags['detecting_media']['message3']){ ?>
                    <div class="pp-row">
                        <p class="pp-tools-text" style="margin-bottom: 5px;"><?php echo htmlspecialchars($powerpress_diags['detecting_media']['message3']); ?></p>
                    </div>
                <?php } ?>

                <?php if($powerpress_diags['detecting_media']['success']){ ?>
                    <div class="pp-row">
                        <p class="pp-tools-text" style="margin-bottom: 5px;">If you are still having problems detecting media information, check with your web hosting provider if there is a firewall blocking your server.</p>
                    </div>
                <?php } else { ?>
                    <div class="pp-row">
                        <p class="pp-tools-text" style="margin-bottom: 5px;">Contact your web hosting provider with the information above.</p>
                    </div>
                <?php } ?>

                <div class="pp-row">
                    <ul>
                        <li>
                            <ul>
                                <li style="font-size: 100%;"><p>allow_url_fopen: <?php echo ($powerpress_diags['detecting_media']['allow_url_fopen'] ? 'true' : 'false'); ?></p></li>
                                <li style="font-size: 100%;"><p>curl: <?php echo ($powerpress_diags['detecting_media']['curl'] ? 'true' : 'false'); ?></p></li>
                                <li style="font-size: 100%;"><p>curl_ssl: <?php echo ($powerpress_diags['detecting_media']['curl_ssl'] ? 'true' : 'false'); ?></p></li>
                                <li style="font-size: 100%;"><p>openssl: <?php echo ($powerpress_diags['detecting_media']['openssl'] ? 'true' : 'false'); ?></p></li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </div>

            <hr class="diagnostic-report-divider">

            <?php // UPLOADING ARTWORK ?>
            <div class="diagnostics-message">
                <?php powerpressadmin_diagnostics_status(
                    $powerpress_diags['uploading_artwork']['success'],
                    false,
                    'Uploading Artwork'
                ); ?>

                <div class="pp-row">
                    <p class="pp-tools-text" style="font-weight: bold;">This test checks if you are able to upload and save artwork.</p>
                </div>

                <div class="pp-row">
                    <p class="pp-tools-text"><?php echo htmlspecialchars($powerpress_diags['uploading_artwork']['message']); ?></p>
                </div>
            </div>

            <hr class="diagnostic-report-divider">

            <?php // SYSTEM INFORMATION ?>
            <div class="diagnostics-message">
                <?php powerpressadmin_diagnostics_status(
                    $powerpress_diags['system_info']['success'],
                    ($powerpress_diags['system_info']['warning'] || $powerpress_diags['system_info']['php_cgi']),
                    'System Information'
                ); ?>

                <?php
                $os = get_os();
                ?>

                <div class="pp-row">
                    <p class="pp-tools-text" style="font-weight: bold;">This test checks your version of PHP, memory usage and temporary directory access.</p>
                </div>

                <div class="pp-row">
                    <p class="pp-tools-text"><?php echo htmlspecialchars(sprintf(__('WordPress Version: %s'), $GLOBALS['wp_version'])); ?></p>
                </div>

                <?php if($os){ ?>
                    <div class="pp-row">
                        <p class="pp-tools-text">Operating System: <?php echo $os; ?></p>
                    </div>
                <?php } ?>

                <div class="pp-row">
                    <p class="pp-tools-text"><?php echo htmlspecialchars($powerpress_diags['system_info']['message']); ?></p>
                </div>

                <div class="pp-row">
                    <p class="pp-tools-text"><?php echo htmlspecialchars($powerpress_diags['system_info']['message2']); ?></p>
                </div>

                <div class="pp-row">
                    <p class="pp-tools-text"><?php echo htmlspecialchars($powerpress_diags['system_info']['message3']); ?></p>
                </div>

                <?php if(!empty($powerpress_diags['system_info']['php_cgi']) ) { ?>
                    <div class="pp-row">
                        <p class="pp-tools-text"><?php echo __('Warning:', 'powerpress') .' '. __('PHP running in CGI mode.', 'powerpress'); ?></p>
                    </div>
                <?php } ?>

                <?php if(!empty($powerpress_diags['system_info']['warning']) ) { ?>
                    <div class="pp-row">
                        <p class="pp-tools-text"><?php echo __('Contact your web hosting provider to inquire how to increase the PHP memory limit on your web server.', 'powerpress'); ?></p>
                    </div>
                <?php } ?>
            </div>

            <hr class="diagnostic-report-divider">

            <div class="row" style="padding-left: 30px;">
                <h2 style="font-weight: bold; text-decoration: underline;">
                    Additional Site Information:
                </h2>
            </div>

            <?php // FEED VALIDATION ?>
            <div class="diagnostics-message">
                <?php powerpressadmin_diagnostics_status(
                    true,
                    false,
                    'Feed Validation',
                    true
                ); ?>

                <div class="pp-row">
                    <p class="pp-tools-text" style="font-weight: bold;">
                        Use this link to validate your podcast feed.
                    </p>
                </div>

                <?php
                $feedURL = get_feed();
                $validationLink = 'https://www.castfeedvalidator.com/validate.php?url=' . $feedURL;
                ?>
                <div class="pp-row">
                    <p class="pp-tools-text" style="font-weight: bold;">
                        <a href="<?php echo $validationLink; ?>" target="_blank"><?php echo $validationLink; ?></a>
                    </p>
                </div>
            </div>

            <?php // PLUGINS ?>
            <div class="diagnostics-message">
                <?php powerpressadmin_diagnostics_status(
                    true,
                    false,
                    'Plugins',
                    true
                ); ?>

                <?php
                $pluginsInfo = get_plugins_info();
                $activePlugins = $allPlugins = false;
                if(!empty($pluginsInfo)){
                    if(isset($pluginsInfo['active'])){
                        $activePlugins = $pluginsInfo['active'];
                    }
                    if(isset($pluginsInfo['all'])){
                        $allPlugins = $pluginsInfo['all'];
                    }
                }
                ?>

                <?php if(!empty($activePlugins)){ ?>
                    <div class="pp-row">
                        <p class="pp-tools-text" style="font-weight: bold;">
                            List of active plugins:
                        </p>
                    </div>

                    <?php foreach($activePlugins as $plugin){
                        $version = false;
                        if($allPlugins){
                            foreach($activePlugins as $plugin_path){
                                if(isset($allPlugins[$plugin_path])){
                                    $version = $allPlugins[$plugin_path]['Version'];
                                }
                            }
                        }
                        ?>
                        <div class="pp-row">
                            <p class="pp-tools-text">
                                <?php echo $plugin; ?>
                                <?php if($version){
                                    echo ' - (Version ' . $version . ')';
                                } ?>
                            </p>
                        </div>
                    <?php }
                } ?>

                <?php if(!empty($allPlugins)){ ?>
                    <div class="pp-row">
                        <p class="pp-tools-text" style="font-weight: bold;">
                            All plugins:
                        </p>
                    </div>

                    <?php foreach($allPlugins as $plugin){
                        $version = $plugin['Version'];
                        ?>
                        <div class="pp-row">
                            <p class="pp-tools-text">
                                <?php echo $plugin['Name']; ?>
                                <?php if($version){
                                    echo ' - (Version ' . $version . ')';
                                } ?>
                            </p>
                        </div>
                    <?php }
                } ?>
            </div>

            <hr class="diagnostic-report-divider">

            <?php // THEMES ?>
            <div class="diagnostics-message">
                <?php powerpressadmin_diagnostics_status(
                    true,
                    false,
                    'Themes',
                    true
                ); ?>

                <div class="pp-row">
                    <p class="pp-tools-text" style="font-weight: bold;">
                        List of themes:
                    </p>
                </div>

                <?php
                $themes = wp_get_themes();

                if(!empty($themes)){
                    foreach($themes as $index => $theme){
                        $version = $theme->get('Version');
                        ?>
                        <div class="pp-row">
                            <p class="pp-tools-text">
                                <?php echo $index; ?>
                                <?php if($version){
                                    echo ' - (Version ' . $version . ')';
                                } ?>
                            </p>
                        </div>
                    <?php }
                } ?>
            </div>

            <hr class="diagnostic-report-divider">

            <?php // APACHE INFO ?>
            <div class="diagnostics-message">
                <?php powerpressadmin_diagnostics_status(
                    true,
                    false,
                    'Apache Information',
                    true
                ); ?>

                <?php
                $apacheInfo = get_apache_info();
                $apacheVersion = ($apacheInfo['version'] ?? false);
                $apacheServerName = ($apacheInfo['env_server_name'] ?? false);
                $apacheModules = ($apacheInfo['modules'] ?? false);
                $apacheRequestHeaders = ($apacheInfo['request_headers'] ?? false);
                $apacheResponseHeaders = ($apacheInfo['response_headers'] ?? false);
                ?>

                <?php if($apacheVersion){ ?>
                    <div class="pp-row">
                        <p class="pp-tools-text">
                            Version: <?php echo $apacheVersion; ?>
                        </p>
                    </div>
                <?php } ?>

                <?php if($apacheServerName){ ?>
                    <div class="pp-row">
                        <p class="pp-tools-text">
                            Server Name: <?php echo $apacheServerName; ?>
                        </p>
                    </div>
                <?php } ?>

                <?php if($apacheModules){ ?>
                    <div class="pp-row">
                        <p class="pp-tools-text" style="font-weight: bold; margin-bottom: 0;">
                            Modules:
                        </p>
                    </div>
                    <?php foreach($apacheModules as $module){ ?>
                        <div class="pp-row">
                            <p class="pp-tools-text" style="margin-bottom: 0;">
                                <?php echo $module; ?>
                            </p>
                        </div>
                    <?php }
                } ?>

                <?php if($apacheRequestHeaders){ ?>
                    <div class="pp-row" style="margin-top: 10px;">
                        <p class="pp-tools-text" style="font-weight: bold; margin-bottom: 0;">
                            Request Headers:
                        </p>
                    </div>
                    <?php foreach($apacheRequestHeaders as $index => $header){ ?>
                        <div class="pp-row">
                            <p class="pp-tools-text" style="margin-bottom: 0;">
                                <?php echo $index; ?>: <?php echo $header; ?>
                            </p>
                        </div>
                    <?php }
                } ?>

                <?php if($apacheResponseHeaders){ ?>
                    <div class="pp-row" style="margin-top: 10px;">
                        <p class="pp-tools-text" style="font-weight: bold; margin-bottom: 0;">
                            Response Headers:
                        </p>
                    </div>
                    <?php foreach($apacheResponseHeaders as $index => $header){ ?>
                        <div class="pp-row">
                            <p class="pp-tools-text" style="margin-bottom: 0;">
                                <?php echo $index; ?>: <?php echo $header; ?>
                            </p>
                        </div>
                    <?php }
                } ?>
            </div>

            <hr class="diagnostic-report-divider">

            <?php // PHP EXTENSIONS ?>
            <div class="diagnostics-message">
                <?php powerpressadmin_diagnostics_status(
                    true,
                    false,
                    'PHP Extensions',
                    true
                ); ?>

                <div class="pp-row">
                    <p class="pp-tools-text" style="font-weight: bold;">
                        This is a list of all the active PHP extensions present on your site.
                    </p>
                </div>

                <?php
                $phpInfo = get_php_info();
                $extensions = $phpInfo['extensions'];

                if(!empty($extensions)){
                    foreach($extensions as $extension){ ?>
                        <div class="pp-row">
                            <p class="pp-tools-text" style="margin-bottom: 0;">
                                <?php echo $extension; ?>
                            </p>
                        </div>
                    <?php }
                } ?>
            </div>
        </div>

        <?php // RAW DATA ?>
        <div id="diagnostics-raw" class="pp-tabcontent">
            <?php
            $rawData = powerpressadmin_diagnostics_email(false, true);
            ?>
            <script>
                function selectAndCopyParagraphById(id) {
                    const p = document.getElementById(id);
                    const range = document.createRange();
                    range.selectNodeContents(p);
                    const selection = window.getSelection();
                    selection.removeAllRanges();
                    selection.addRange(range);
                    document.execCommand("copy");
                }
            </script>

            <div class="diagnostics-message">
                <div class="row pl-3" style="margin-top: 15px; margin-bottom: 15px;">
                    <button onclick="selectAndCopyParagraphById('raw-data-text');" style="padding: 10px 20px; cursor: pointer;">COPY RAW DATA</button>
                </div>

                <p id="raw-data-text"><?php echo $rawData; ?></p>

                <div class="row pl-3" style="margin-top: 15px; margin-bottom: 15px;">
                    <button onclick="selectAndCopyParagraphById('raw-data-text');" style="padding: 10px 20px; cursor: pointer;">COPY RAW DATA</button>
                </div>
            </div>
        </div>

        <?php // EMAILING ?>
        <div id="diagnostics-email" class="pp-tabcontent">
            <div class="email-results">
                <div class="pp-row pp-tools-row" style="margin-top: 15px;">
                    <h3>Email Results</h3>
                </div>

                <div class="pp-row pp-tools-row">
                    <p class="pp-tools-text">Send the results to the selected email addresses.</p>
                </div>

                <form enctype="multipart/form-data" method="get" action="<?php echo admin_url('admin.php'); ?>">
                    <input type="hidden" name="action" value="powerpress-diagnostics" />
                    <input type="hidden" name="page" value="powerpress/powerpressadmin_tools.php" />

                    <?php // Print nonce
                        wp_nonce_field('powerpress-diagnostics');
                    ?>

                    <div class="pp-row pp-tools-row" style="margin-bottom: 15px;">
                        <input style="margin-top: 3px; margin-right: 15px;" type="checkbox" name="CC" value="1" checked disabled/>
                        <p style="margin: 0;">Send to <?php $user_info = wp_get_current_user(); echo $user_info->user_email; ?></p>
                    </div>

                    <div class="pp-row pp-tools-row" style="margin-bottom: 15px;">
                        <input style="margin-top: 3px; margin-right: 15px;" type="checkbox" name="support" value="1" checked/>
                        <p style="margin: 0;">CC: support@blubrry.com</p>
                    </div>

                    <div class="pp-row pp-tools-row" style="margin-bottom: 5px;">
                        <input style="margin-top: 3px; margin-right: 15px;" type="checkbox" name="additional" value="1"/>
                        <p style="margin: 0;">CC: additional email address</p>
                    </div>

                    <div class="pp-row pp-tools-row">
                       <input type="text" name="additional_email" value="" style="width: 30%; padding: 0 10px 0 10px; margin-left: 30px;"/>
                    </div>

                    <div class="pp-row pp-tools-row" style="display: none;">
                        <input style="margin-top: 3px; margin-right: 15px;" type="checkbox" name="ap" value="1" checked/>
                        <p style="margin: 0;"><?php echo __('Include list of active plugins in diagnostics results.', 'powerpress') ?></p>
                    </div>

                    <div class="pp-row pp-tools-row" style="margin-top: 30px;">
                        <input style="margin: 0 0 30px 0;" class="powerpress_save_button_other pp-tools-button" type="submit" name="Submit" id="powerpress_save_button" value="Send Results">
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php } ?>