<?php
/***
 * @package PowerPressNetwork
 */



if ( !function_exists('add_action') )
    die("access denied.");

require_once(dirname(__FILE__).'/api-data-transfer-bus.class.php');
require_once(dirname(__FILE__).'/powerpressadmin-auth.class.php');
require_once(dirname(__FILE__).'/views/components/network-page-select.php');
require_once(dirname(__FILE__).'/views/components/network-list-section.php');

class PowerPressNetwork
{
    private $display;
    private $apiBus;
    private $parent_slug;
    private $justCreatedNetwork = false;

    function __construct($parent_slug)
    {
        $this->parent_slug = $parent_slug;
        $this->init();
        $this->apiBus = new PowerpressNetworkDataBus();

        add_action('admin_menu', array($this, 'clearLegacyCron'));
        add_action('wp_ajax_add_program_to_network', array($this, 'addProgramToNetwork'));
        add_action('wp_ajax_ppn_page_action', array($this, 'ajaxPageAction'));
        add_action('add_meta_boxes', array($this, 'addShortcodeLegendBox'));
    }

    /** whether the editor legend is wanted, on until the Manage tab turns it off */
    static function shortcodeLegendEnabled(): bool {
        $settings = get_option('powerpress_network', []);

        return !isset($settings['shortcode_legend']) || !empty($settings['shortcode_legend']);
    }

    /** the shortcode legend, on the editor screens a network page is written on */
    function addShortcodeLegendBox(): void {
        if (!function_exists('add_meta_box') || !self::shortcodeLegendEnabled()) {
            return;
        }

        foreach (['page', 'post'] as $screen) {
            add_meta_box(
                'ppn-shortcode-legend',
                __('PowerPress Network Shortcodes', 'powerpress'),
                [$this, 'renderShortcodeLegendBox'],
                $screen,
                'normal',
                'low'
            );
        }
    }

    /** render the legend metabox */
    function renderShortcodeLegendBox(): void {
        include(dirname(__FILE__) . '/network/shortcode-legend.php');
    }

    function init()
    {
        $ppnFrontAjax = wp_doing_ajax() && isset($_REQUEST['action']) && in_array($_REQUEST['action'], ['ppn_episodes', 'ppn_episode_panel'], true);

        if (!is_admin() || $ppnFrontAjax) {
            require_once(dirname(__FILE__) . '/shortcodes/ShortCode.php');
            require_once(dirname(__FILE__) . '/shortcodes/Application.php');
            require_once(dirname(__FILE__) . '/shortcodes/List.php');
            require_once(dirname(__FILE__) . '/shortcodes/Program.php');
            require_once(dirname(__FILE__) . '/shortcodes/Grid.php');
            require_once(dirname(__FILE__) . '/shortcodes/Carousel.php');
        }

        if (is_admin() && isset($_GET['page']) && $_GET['page'] == 'powerpress') {
            $key = $_GET['key'] ?? '';

            switch ($key) {
                case 'programs':
                    include(dirname(__FILE__) . '/network/programs.php');
                    break;
                case 'lists':
                    include(dirname(__FILE__) . '/network/lists.php');
                    break;
                case 'base':
                    include(dirname(__FILE__) . '/network/base.php');
                    break;
                case 'applications':
                    include(dirname(__FILE__) . '/network/applications.php');
                    break;
                default:
                    break;
            }
        }
    }

	/** the blubrry access token powerpress holds, or false when there is none */
	function getAccessToken()
	{
		if( !function_exists('powerpress_getAccessToken') )
			return false;

		return powerpress_getAccessToken();
	}

    function requestAPI($requestUrl, $auth = false, $post = false)
    {
		$accessToken = '';	
		if( $auth ) {
			$accessToken = $this->getAccessToken();
			if( empty($accessToken) )
				return false;
		}
		
		// Equivelant command line argument to run command
		//mail('c', 'dd', "curl ". (is_array($post) ? '-d "'.implode("&", $post) .'" ' : '') ."-H \"Authorization: Bearer $accessToken\" \"$requestUrl\"");
		$auth = new PowerPressAuth();
		$response = $auth->api($accessToken, $requestUrl, $post);
		
		if( $response === false ) {
            powerpress_page_message_add_error( __('Error: ' . $auth->getLastError(), 'powerpress') );
        }
		return $response;
    }

    static function getHTML($filename, $props, $networkInfo, $shows_html = '', $groups_html = '', $requests_html = '', $secondary_props = array(), $manage_html = '')
    {
        if (is_file(dirname(__FILE__) . '/shortcodes/views/' . $filename)) {
            ob_start();
            include(dirname(__FILE__) . '/shortcodes/views/' . $filename);
            $html = ob_get_contents();
            ob_end_clean();
            return $html;
        } else if (is_file(dirname(__FILE__) . '/network/' . $filename)) {
            ob_start();
            include(dirname(__FILE__) . '/network/' . $filename);
            $html = ob_get_contents();
            ob_end_clean();
            return $html;
        } else {
            return "<div><strong>View for $filename unavailable.</strong></div>";
        }
    }

    static function powerpress_network_plugin_url()
    {
        $local_path = __FILE__;
        if (DIRECTORY_SEPARATOR == '\\') { // Win32 fix
            $local_path = basename(dirname(__FILE__)) . '/' . basename(__FILE__);

            if (strpos(__FILE__, 'C:\\') === 0 && strstr($local_path, 'mu-plugins')) {
                $local_path = __FILE__;
                $local_path = substr($local_path, 2);
                $local_path = str_replace('\\', '/', $local_path);
            }

            if (strstr(__FILE__, 'mu-plugins')) {
                // mu-plugins URL!
                return content_url() . '/mu-plugins/' . dirname($local_path) . '/';
            }
        }

        $plugin_url = plugins_url('', $local_path);
        return $plugin_url . '/';
    }

    public function setDisplay()
    {
        $this->display = $this->action_admin_init();
    }

    static function createPage()
    {
        $originalMap = get_option('powerpress_network_map');
        $map = $originalMap ?: array();
        $pageCreated = false;
        $target = null;
        $postID = null;
        if ($_POST['target'] == "Program"){
            $target = "p-".$_POST['targetId'];
        } else if ($_POST['target'] == "List") {
            $target = "l-".$_POST['targetId'];
        } else if ($_POST['target'] == "Application") {
            $target = "Application";
        } else if ($_POST['target'] == "Homepage") {
            $target = "Homepage";
        }
        // homepage + application reuse existing page if published;
        // programs and lists always create fresh so users can replace pages (dupe page name, unique slug)
        $isSingleton = in_array($_POST['target'], ['Homepage', 'Application'], true);
        if ($isSingleton && isset($map[$target])) {
            $postID = $map[$target];
            if (get_post_status($postID) == 'publish') {
                $pageCreated = true;
            }
        }

        if (!$pageCreated){
            global $user_ID;
            $page['post_type'] = 'page';
            $page['post_content'] = $_POST['content'];
            $page['post_parent'] = 0;
            $page['post_author'] = $user_ID;
            $page['post_status'] = 'publish';
            $page['post_title'] = __(htmlspecialchars($_POST['pageTitle']), 'powerpress');
            $postID = wp_insert_post($page);
            if ($postID != 0){
                $map[$target] = $postID;
                if($originalMap === null) {
                    add_option('powerpress_network_map', $map);
                } else{
                    update_option('powerpress_network_map', $map);
                }
            }
        }
        return $postID;
    }

    /** put the right ppn shortcode on a linked page, swapping a stale id or appending when absent */
    private static function ensureShortcodeOnPage($postID, $target, $targetId) {
        $targetId = preg_replace('/[^A-Za-z0-9_-]/', '', (string) $targetId);
        if (empty($postID) || empty($target) || empty($targetId)) return;

        $post = get_post($postID);
        if (!$post) return;

        $content = $post->post_content;

        if ($target === 'List') {
            $changed = false;

            // LEGACY: also matches ppn-gridview
            $gridShortcode = "[ppn-show-grid id=\"{$targetId}\" cols=\"4\"]";
            $grid = self::findShortcode($content, ['ppn-show-grid', 'ppn-gridview']);
            $gridId = $grid ? ($grid['atts']['id'] ?? null) : null;

            if ($gridId !== null && $gridId !== 'all' && strpos($content, $gridShortcode) === false) {
                $content = str_replace($grid['text'], $gridShortcode, $content);
                $changed = true;
            }

            // LEGACY: also matches ppn-list
            $listShortcode = "[ppn-show-list id=\"{$targetId}\" style=\"detailed\"]";
            $list = self::findShortcode($content, ['ppn-show-list', 'ppn-list']);

            if ($list && ($list['atts']['id'] ?? null) !== 'all') {
                $atts = $list['atts'];
                $atts['id'] = $targetId;
                $scoped = self::buildShortcode($list['tag'], $atts);

                if ($scoped !== $list['text']) {
                    $content = str_replace($list['text'], $scoped, $content);
                    $changed = true;
                }
            }

            // if no ppn shortcode found at all, append the group grid
            if (!$changed && strpos($content, $gridShortcode) === false) {
                $content = trim($content) . "\n\n" . $gridShortcode;
            }

            if (!$list) {
                $content = trim($content) . "\n\n" . $listShortcode;
            }
        } elseif ($target === 'Program') {
            $shortcode = "[ppn-show id=\"{$targetId}\"]";
            if (strpos($content, $shortcode) !== false) return;

            // LEGACY: also matches ppn-program
            $show = self::findShortcode($content, ['ppn-show', 'ppn-program']);

            if ($show && isset($show['atts']['id'])) {
                $content = str_replace($show['text'], $shortcode, $content);
            } else {
                $content = trim($content) . "\n\n" . $shortcode;
            }
        } else {
            return;
        }

        wp_update_post([
            'ID'           => $postID,
            'post_content' => $content,
        ]);
    }

    /** first of these shortcode tags in content, w/ its attributes parsed, null when absent */
    private static function findShortcode(string $content, array $tags) {
        if (!preg_match('/' . get_shortcode_regex($tags) . '/', $content, $match)) {
            return null;
        }

        return [
            'text' => $match[0],
            'tag' => $match[2],
            'atts' => shortcode_parse_atts($match[3]),
        ];
    }

    /** renders a shortcode tag back out, attribute order as parsed */
    private static function buildShortcode(string $tag, array $atts): string {
        $parts = [$tag];

        foreach ($atts as $name => $value) {
            $parts[] = is_int($name) ? $value : "{$name}=\"{$value}\"";
        }

        return '[' . implode(' ', $parts) . ']';
    }

    private function handlePageAction($createUrl)
    {
        $option = get_option('powerpress_network_map');
        $postID = 0;
        if (isset($_POST['pageAction']) && $_POST['pageAction'] == 'unlink') {
            if ($_POST['target'] == 'List') {
                unset($option['l-' . $_POST['targetId']]);
                $this->removeOption('link_page_list');
            } else if ($_POST['target'] == 'Program') {
                unset($option['p-' . $_POST['targetId']]);
            }
            update_option('powerpress_network_map', $option);
            powerpress_page_message_add_notice(__('Page unlinked.', 'powerpress'));
        } else if (isset($_POST['pageAction']) && $_POST['pageAction'] == 'clearSiteCache') {
            $network_id = get_option('powerpress_network_id');
            if ($network_id) {
                $cacheNameBase = "ppn-cache n-{$network_id}";

                // base, applications, programs, lists
                foreach (['', '-a', '-p', '-l'] as $suffix) {
                    $this->apiBus->clearCache("{$cacheNameBase}{$suffix}");
                }

                powerpress_page_message_add_notice(__('Cache cleared.', 'powerpress'));
            }
        } else {
            if (empty($_POST['pageID'])) {
                $postID = $this->createPage();
            } else {
                $postID = (int) $_POST['pageID'];
                if (!$postID || !current_user_can('edit_post', $postID)) {
                    powerpress_page_message_add_error(__('You do not have permission to link that page.', 'powerpress'));
                    return;
                }

                if ($_POST['target'] == 'List') {
                    $option['l-' . $_POST['targetId']] = $postID;
                } else if ($_POST['target'] == 'Program') {
                    $option['p-' . $_POST['targetId']] = $postID;
                }
                update_option('powerpress_network_map', $option);

                // update shortcode on page if user opted in
                if (!empty($_POST['updateShortcode'])) {
                    self::ensureShortcodeOnPage($postID, $_POST['target'], $_POST['targetId']);
                }
            }
            if ($postID != 0) {
                if ($_POST['target'] == 'List') {
                    $option['l-' . $_POST['targetId']] = $postID;
                    $this->insertOption('link_page_list', get_permalink($postID));
                } else if ($_POST['target'] == 'Program') {
                    $option['p-' . $_POST['targetId']] = $postID;
                }
                powerpress_page_message_add_notice(__('Page linked successfully.', 'powerpress'));
            }
        }
        if ($postID != 0 && !(isset($_POST['redirectUrl']) && $_POST['redirectUrl'] == 'false')) {
            header("location: {$createUrl}wp-admin/post.php?post={$postID}&action=edit");
            exit;
        }
    }

    private function checkSignin()
    {
		$accessToken = $this->getAccessToken();
		if( !empty($accessToken) )
			return true;
			
		return false;
    }

	public function action_admin_init()
    {
		// Only do anything if we are in the network page..unlink a
		if(empty($_GET['page']) || $_GET['page'] != 'network-plugin' )
			return;

        if (!current_user_can('manage_options'))
            return;

        // Move wp-admin code here that processes things before any HTML is sent back by the server.
        $apiArray = powerpress_get_api_array();
        $apiUrl = $apiArray[0];
        $createUrl = get_home_url() . '/';
        $creds = array();

        // verify nonce on all POST requests
        if (!empty($_POST) && !wp_verify_nonce($_POST['_ppn_nonce'] ?? '', 'powerpress')) {
            powerpress_page_message_add_error(__('Please refresh and try again.', 'powerpress'));
            return;
        }

        if (isset ($_POST['target']) || isset($_POST['clearSiteCache'])) {
            $this->handlePageAction($createUrl);
        }
        if (isset($_POST['unlinkAccount'])){
            // flush network cache
            PowerpressNetworkDataBus::clearCacheByLike("ppn-cache %");

            delete_option('powerpress_network_id');
            delete_option('powerpress_network_title');
            powerpress_page_message_add_notice(__('Network unlinked.', 'powerpress'));
        }
		
		if( !empty($_POST['ppn-action']) ) {
			switch( $_POST['ppn-action']) {
                case 'create-network': {
                    $requestUrl = '/2/powerpress/network/create/';
                    $props = $this->requestAPI($requestUrl, true, $_POST);
                    
                    // save network to wpdb
                    if(!empty($props['network_id'])){ // new network ID
                        // hard clear stale data from old network (THIS WILL NEED UPDATE IF WE GO MULTI NETWORK)
                        PowerpressNetworkDataBus::clearCacheByLike('ppn-cache %');
                        delete_option('powerpress_network_map');
                        delete_option('powerpress_network');
                        delete_option('powerpress_network_id');
                        delete_option('powerpress_network_title');

                        // setup new network
                        update_option('powerpress_network_id', $props['network_id']);
                        if (!empty($props['network_title'])) {
                            update_option('powerpress_network_title', $props['network_title']);
                        }
                        if (isset($props['network_description'])) {
                            $ppnSettings = get_option('powerpress_network', []);
                            $ppnSettings['network_description'] = $props['network_description'];
                            update_option('powerpress_network', $ppnSettings);
                        }
                        $this->justCreatedNetwork = true;

                        powerpress_page_message_add_notice(__('Network successfully created.', 'powerpress-network'));
                    } else if (is_array($props) && !empty($props['error'])) {
                        powerpress_page_message_add_error($props['error']);
                    } else if ($props !== false) {
                        powerpress_page_message_add_error(__('Could not create network. Please check the title and description and try again.', 'powerpress-network'));
                    }
                } break;

				case 'set-network-id': {
					$networkId = $_POST['networkId'];
					$requestUrl = '/2/powerpress/network/' . $networkId;
					$props = $this->requestAPI($requestUrl, true);


					//$props = PowerpressNetworkDataBus::getCacheOrCallAPI($creds, $cacheName, $requestUrl, $needDirectAPI);
					if( !empty($props['network_id']) ) {
						update_option('powerpress_network_id', $networkId);
						if( !empty($props['network_title']) )
							update_option('powerpress_network_title', $props['network_title']);
						// store network description for homepage display
						if( isset($props['network_description']) ) {
							$ppnSettings = get_option('powerpress_network', []);
							$ppnSettings['network_description'] = $props['network_description'];
							update_option('powerpress_network', $ppnSettings);
						}
						powerpress_page_message_add_notice(__('Network linked successfully.', 'powerpress'));
                    }
                    
                    // NETWORK NOT ON BLUBRRY DB
                    elseif (!empty($props['error']) && $props['error'] == 'Your account does not have the network with specified id') {
                        // API confirms network is gone, scrub from local list cache
                        $this->scrubStaleNetwork($networkId);
                        powerpress_page_message_add_error(__('That network is not available on the linked Blubrry account. It has been removed from your list.', 'powerpress'));
                    }

                    
                    else {
						powerpress_page_message_add_error(__('Could not find that network. Please check the ID and try again.', 'powerpress'));
					}

				}; break;

                case 'save-tos-url': {
                    $tosRaw = trim($_POST['ppn_tos_url'] ?? '');

                    if ($tosRaw && stripos($tosRaw, 'http') !== 0) {
                        $tosRaw = 'https://' . $tosRaw;
                    }

                    if ($tosRaw && !filter_var($tosRaw, FILTER_VALIDATE_URL)) {
                        http_response_code(400);
                        break;
                    }
                    $tosUrl = esc_url_raw($tosRaw);
					$ppnSettings = get_option('powerpress_network', []);
					$ppnSettings['tos_url'] = $tosUrl;
					update_option('powerpress_network', $ppnSettings);
					powerpress_page_message_add_notice(__('Terms of service URL saved.', 'powerpress'));
				}; break;

                case 'unset-network-id': {
                    // flush network cache
                    $oldNetworkId = get_option('powerpress_network_id');
                    if ($oldNetworkId) {
                        delete_option("ppn-cache n-{$oldNetworkId}");
                        PowerpressNetworkDataBus::clearCacheByLike("ppn-cache n-{$oldNetworkId}-%");
                        delete_option("ppn-cache n");
                    }
					delete_option('powerpress_network_id');
					delete_option('powerpress_network_title');
					$networkId = '';
					powerpress_page_message_add_notice(__('Network unlinked.', 'powerpress'));
				}; break;
			}
		}
        $passSignIn = $this->checkSignin();

        $status = null;
        $props = array();
        $program_props = array();
        $list_props = array();
        $application_props = array();
        $needDirectAPI = false;


        $networkInfo = get_option('powerpress_network', array());
		$networkId  = get_option('powerpress_network_id');
        $networkTitle = get_option('powerpress_network_title');
        $networkInfo['network_id'] = $networkId;
        $networkInfo['network_title'] = $networkTitle;
		//echo "PowerPress Network ID: $networkId <br />";


        // pull the network record on admin load w/ 1 hour throttle
        if (!empty($networkId) && !get_transient('ppn_network_info_synced')) {
            set_transient('ppn_network_info_synced', 1, HOUR_IN_SECONDS);
            self::syncNetworkInfo($this->apiBus->getSpecificNetworkInAccount($apiUrl, $creds, $networkInfo, true), $networkInfo);
            $networkTitle = $networkInfo['network_title'];
        }

        if ($passSignIn) { //If the user pass the signin section
            if ( empty($networkId) ){
                $status = 'List Networks';
            } else {
				if (isset($_GET['status']) && ($_GET['status'] != 'List Networks' ) )  {
					$status = htmlspecialchars($_GET['status']);
				} else {
					$status = 'Select Choice';
				}
            }
			//echo "-    - -  - - - - - - - - - - - - $status <br />";
            if (isset($_POST['listId'])) {
                $this->insertOption('list_id', $_POST['listId']);
                $networkInfo = get_option('powerpress_network');
				$networkInfo['network_id'] = $networkId;
                $networkInfo['network_title'] = $networkTitle;
            }
            if (isset($_POST['programId'])){
                $this->insertOption('program_id', $_POST['programId']);
                $networkInfo = get_option('powerpress_network');
				$networkInfo['network_id'] = $networkId;
                $networkInfo['network_title'] = $networkTitle;
            }
            if (isset($_POST['linkPageList'])){
                $this->insertOption('link_page_list', $_POST['linkPageList']);
                $networkInfo = get_option('powerpress_network');
				$networkInfo['network_id'] = $networkId;
                $networkInfo['network_title'] = $networkTitle;
            }
            if (isset($_POST['shortcodeLegend'])) {
                $this->insertOption('shortcode_legend', $_POST['shortcodeLegend'] === '1' ? 1 : 0);
            }

            if (isset($_POST['playerStyle'])) { //Player choice, local only, never leaves the site
                $playerStyle = PowerPressNetwork::resolvePlayer(sanitize_text_field(wp_unslash($_POST['playerStyle'])));

                $this->insertOption('player_style', $playerStyle);
                $networkInfo['player_style'] = $playerStyle;
            }

            if (isset($_POST['editNetworkTitle'])) { //Edit Network record
                $update = [
                    'editNetworkTitle' => mb_substr(wp_unslash($_POST['editNetworkTitle']), 0, 255),
                    'editNetworkDescription' => mb_substr(wp_unslash($_POST['editNetworkDescription'] ?? ''), 0, 255),
                ];
                $stored = get_option('powerpress_network', []);
                $edited = $update['editNetworkTitle'] !== (string) $networkTitle
                    || $update['editNetworkDescription'] !== (string) ($stored['network_description'] ?? '');

                if ($edited) {
                    $props = $this->apiBus->updateNetwork($apiUrl, $creds, $networkInfo, $update);
                    $needDirectAPI = true;

                    if ($props !== false && empty($props['danger'])) {
                        // set local data for shortcodes
                        update_option('powerpress_network_title', $props['network_title']);
                        $this->insertOption('network_description', $props['network_description']);
                        delete_option('ppn-cache n');
                        delete_option('ppn-cache n-'.$networkInfo['network_id']);
                        $networkInfo['network_title'] = $props['network_title'];
                        $networkTitle = $props['network_title'];

                        // set transient to prevent load sync + send success to user
                        set_transient('ppn_network_info_synced', 1, HOUR_IN_SECONDS);
                        powerpress_page_message_add_notice(__('Network details saved.', 'powerpress'), 'inline ppn-notice--transient');
                    } else {
                        $failure = (is_array($props) && !empty($props['alert'])) ? $props['alert'] : __('Could not save network details. Please try again.', 'powerpress');
                        powerpress_page_message_add_error($failure);
                    }
                } else {
                    powerpress_page_message_add_notice(__('Settings saved.', 'powerpress'), 'inline ppn-notice--transient');
                }
            }

            if (isset ($_POST['changeOrCreate']) && $_POST['changeOrCreate'] == true) {
                if (isset($_POST['newListTitle'])) { //Create New List
                    $create = array(
                            'newListTitle' => mb_substr(wp_unslash($_POST['newListTitle']), 0, 255),
                            'newListDescription' => mb_substr(wp_unslash($_POST['newListDescription']), 0, 255),
                    );
                    $props = $this->apiBus->createNewList($apiUrl, $creds, $networkInfo, $create);
                    $needDirectAPI = true;
                    delete_option('ppn-cache n-'.$networkInfo['network_id'].'-l');
                    if ($props !== false && empty($props['danger'])) {
                        powerpress_page_message_add_notice(__('Group created successfully.', 'powerpress'));
                        // save list_id so manage list view can load new group
                        if (!empty($props['list_id'])) {
                            $this->insertOption('list_id', $props['list_id']);
                            $networkInfo['list_id'] = $props['list_id'];
                        }
                    }
                }

                if (isset($_POST['editListTitle'])) { //Edit List
                    $update = array(
                            'editListTitle'  => mb_substr(wp_unslash($_POST['editListTitle']), 0, 255),
                            'editListDescription'=> mb_substr(wp_unslash($_POST['editListDescription']), 0, 255)
                    );
                    $props = $this->apiBus->updateList($apiUrl, $creds, $networkInfo, $update);
                    $needDirectAPI = true;
                    delete_option("ppn-cache n-{$networkInfo['network_id']}-l");
                }

                if (isset($_POST['requestAction'])) { //Change List
                    $needDirectAPI = true;
                    if ($_POST['requestAction'] == 'delete') {
                        if (isset($_POST['listId'])) {
                            $this->insertOption('list_id', $_POST['listId']);
                            $networkInfo = get_option('powerpress_network');
							$networkInfo['network_id'] = $networkId;
                            $networkInfo['network_title'] = $networkTitle;
                            $props = $this->apiBus->deleteSpecificList($apiUrl, $creds, $networkInfo);
                            
                            if ($props !== false && empty($props['danger'])) {
                                powerpress_page_message_add_notice(__('Group deleted.', 'powerpress'));
                            }
                        }
                        
                        else if (isset($_POST['target']) && $_POST['target'] == 'program') {
                            $networkInfo = get_option('powerpress_network');
                            $networkInfo['network_title'] = get_option('powerpress_network_title');
                            $networkInfo['network_id'] = get_option('powerpress_network_id');
                            $networkInfo['program_id'] = $_POST['targetId'];
                            $props = $this->apiBus->removeSpecificProgramInNetwork($apiUrl, $creds, $networkInfo, true);

                            if ($props !== false && empty($props['danger'])) {
                                powerpress_page_message_add_notice(__('Show removed from network.', 'powerpress'));
                            }
                        }
                    } 
                    
                    else if ($_POST['requestAction'] == 'save') {
                        $props = $this->apiBus->updateProgramsInSpecificList($apiUrl, $creds, $networkInfo, $_POST['program'] ?? []);
                        if ($props !== false && empty($props['danger'])) {
                            powerpress_page_message_add_notice(__('Group saved successfully.', 'powerpress'));
                        }
                    } 
                    
                    else if ($_POST['requestAction'] == 'add' && $_POST['list_id'] ) {
                        $networkInfo['list_id'] = $_POST['list_id'];
                        $props = $this->apiBus->addProgramToList($apiUrl, $creds, $networkInfo, $_POST['program']);
                        if ($props !== false && empty($props['danger'])) {
                            powerpress_page_message_add_notice(__('Show added to group.', 'powerpress'));
                        }
                    }
                }


                if (isset($_POST['appAction'])) {
                    $needDirectAPI = true;
                    $action = array(
                            'appAction' => htmlspecialchars($_POST['appAction']),
                            'applicantId' => intval($_POST['applicantId'])
                    );
                    $props = $this->apiBus->changeApplicationStatus($apiUrl, $creds, $networkInfo, $action);
                    // clear programs cache so newly approved programs can be viewed
                    $networkId = get_option('powerpress_network_id');
                    $this->apiBus->clearCache('ppn-cache n-'.$networkId.'-p');
                }
            }

            // display api error
            if (is_array($props)) {
                if (!empty($props['danger']) && !empty($props['alert'])) {
                    powerpress_page_message_add_error($props['alert']);
                } else if (!empty($props['error'])) {
                    powerpress_page_message_add_error($props['error']);
                }
            }

            switch ($status) {
                case 'List Networks':
                    $props = $this->apiBus->getNetworksInAccount($creds);
                    if (isset($_POST['linkNetwork']) && $_POST['linkNetwork'] == 'unlink') {
                        delete_option('powerpress_network');
                    }
                    break;

                case 'Select Choice':
                    if ($this->justCreatedNetwork) {
                        $probeState = 'confirmed';
                    } else {
                        $probe = $this->requestAPI('/2/powerpress/network/' . absint($networkId), true);
                        $probeState = $this->networkProbeState($probe);
                    }

                    if ($probeState === 'confirmed') {
                        $program_props = $this->apiBus->getProgramsInNetwork($apiUrl, $creds, $networkInfo, $needDirectAPI );
                        $list_props = $this->apiBus->getListsInNetwork($apiUrl, $creds, $networkInfo, $needDirectAPI );
                        $application_props = $this->apiBus->getApplicantsInNetwork($apiUrl, $creds, $networkInfo, true );
                        $props = $this->apiBus->getSpecificNetworkInAccount($apiUrl, $creds, $networkInfo, $needDirectAPI );
                        $networkInfo = get_option('powerpress_network', array());
                        $networkInfo['network_id'] = $networkId;
                        $networkInfo['network_title'] = $networkTitle;
                        break;
                    }

                    if ($probeState === 'not_found') {
                        $this->scrubStaleNetwork($networkId);
                        delete_option('powerpress_network_id');
                        delete_option('powerpress_network_title');

                        powerpress_page_message_add_error(__('That network is not available on the linked Blubrry account. It has been removed from your list.', 'powerpress'));

                        $networkId = '';
                        $networkTitle = '';
                        $networkInfo['network_id'] = '';
                        $networkInfo['network_title'] = '';
                        $status = 'List Networks';
                        $props = $this->apiBus->getNetworksInAccount($creds);
                        break;
                    }

                    powerpress_page_message_add_error(__('Could not reach Blubrry to load your network. Please try again in a moment.', 'powerpress'));
                    $status = 'List Networks';
                    $props = $this->cachedNetworks();
                    break;

                case 'Manage List':
                    if (!empty($props['list_id'])) {
                        $networkInfo['list_id'] = $props['list_id'];
                    }
                    $props = $this->apiBus->getSpecificListInNetwork($apiUrl, $creds, $networkInfo, $needDirectAPI );
                    $networkInfo = get_option ('powerpress_network');
					$networkInfo['network_id'] = $networkId;
                    $networkInfo['network_title'] = $networkTitle;
                    // clean up bad page link if page was deleted
                    if (!empty($networkInfo['link_page_list'])) {
                        $map = get_option('powerpress_network_map', []);
                        $mapKey = 'l-' . $networkInfo['list_id'];
                        $pageId = $map[$mapKey] ?? 0;
                        if (!$pageId || get_post_status($pageId) !== 'publish') {
                            $this->removeOption('link_page_list');
                            $networkInfo['link_page_list'] = '';
                            if ($pageId) {
                                unset($map[$mapKey]);
                                update_option('powerpress_network_map', $map);
                            }
                        }
                    }
                    break;

            }

        }
        $return = array();
        $return['status'] = $status;
        $return['props'] = $props;
        $return['program_props'] = $program_props;
        $return['list_props'] = $list_props;
        $return['application_props'] = $application_props;
        $return['network_info'] = $networkInfo;
        return $return;

    }

    static function removeOption ($key)
    {
        $result = get_option ('powerpress_network');
        unset ($result[$key]);
        update_option('powerpress_network', $result);
    }

    /** the last known network list, for rendering when the origin cannot be reached */
    private function cachedNetworks(): array {
        $cache = get_option('ppn-cache n', []);

        if (!is_array($cache) || !isset($cache['data']) || !is_array($cache['data'])) {
            return [];
        }

        return $cache['data'];
    }

    /** a program's coverart url, or the local placeholder when the api gave us no filename */
    static function programArtworkUrl(array $show, int $size = 90): string {
        $image = trim((string) ($show['program_itunes_image'] ?? ''));

        if ($image === '') {
            return powerpress_get_root_url() . 'images/pts_cover.jpg';
        }

        return "https://assets.blubrry.com/coverart/{$size}/{$image}";
    }

    /** the two players a network may pick from, keyed by the value powerpress_general stores */
    static function playerChoices(): array {
        return [
            'blubrrymodern' => 'Blubrry Player',
            'mediaelement-audio' => 'Default Media Player',
        ];
    }

    /** the player a network gets when it has never chosen one */
    static function defaultPlayer(): string {
        return 'blubrrymodern';
    }

    /** site wide player values that predate the network's two choices, mapped onto their match */
    static function playerAliases(): array {
        return [
            'blubrryaudio' => 'blubrrymodern',
            'html5audio' => 'mediaelement-audio',
        ];
    }

    /** resolve a stored player value to a network choice, the one path admin and render share */
    static function resolvePlayer($stored): string {
        $stored = (string) $stored;
        $stored = self::playerAliases()[$stored] ?? $stored;

        return isset(self::playerChoices()[$stored]) ? $stored : self::defaultPlayer();
    }

    /** every shortcode's attributes, accepted values and defaults, read from the classes themselves */
    static function shortcodeLegend(): array {
        $legend = [];

        foreach (self::shortcodeClasses() as $tag => $class) {
            $defaults = $class::attributeDefaults();
            $accepted = $class::attributeLegend();
            $inert = $class::inertAttributes();
            $rows = [];

            foreach ($defaults as $name => $default) {
                if (in_array($name, $inert, true)) {
                    continue;
                }

                $rows[$name] = [
                    'accepts' => $accepted[$name] ?? '',
                    'enumerated' => is_array($accepted[$name] ?? ''),
                    'default' => ($default === null || $default === '') ? '-' : (string) $default,
                ];
            }

            $legend[$tag] = [
                'description' => $class::shortcodeDescription(),
                'example' => $class::shortcodeExample(),
                'attributes' => $rows,
            ];
        }

        return $legend;
    }

    /** shortcode tag to the class that owns its attribute vocabulary */
    static function shortcodeClasses(): array {
        return [
            'ppn-show' => 'PowerPressNetworkProgram',
            'ppn-show-list' => 'PowerPressNetworkList',
            'ppn-show-grid' => 'PowerPressNetworkGrid',
            'ppn-show-carousel' => 'PowerPressNetworkCarousel',
            'ppn-application' => 'PowerPressNetworkApplication',
        ];
    }

    /** refresh the cached network title and description from api response */
    static function syncNetworkInfo($fetched, array &$networkInfo): void {
        if (!is_array($fetched) || !empty($fetched['error'])) {
            return;
        }

        foreach (['network_title', 'network_description'] as $key) {
            if (!array_key_exists($key, $fetched)) {
                continue;
            }

            $value = (string) $fetched[$key];
            if ($value === (string) ($networkInfo[$key] ?? '')) {
                continue;
            }

            $networkInfo[$key] = $value;
            if ($key === 'network_title') {
                update_option('powerpress_network_title', $value);
                continue;
            }

            self::insertOption($key, $value);
        }
    }

    static function insertOption ($key, $value)
    {
        $result = get_option ('powerpress_network');
        $result[$key] = $value;
        update_option('powerpress_network', $result);
    }

    function display_plugin()
    {
        echo '<div id="ppn-notices">';
        if (function_exists('powerpress_page_message_print') && ($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            powerpress_page_message_print();
        }
        echo '</div>';

        $status = $this->display['status'];
        $props = $this->display['props'];
        $program_props = $this->display['program_props'];
        $list_props = $this->display['list_props'];
        $application_props = $this->display['application_props'];
        $networkInfo = $this->display['network_info'];
        ?>

        <div class="ppn-admin">
        <?php
        $ppn_nonce = wp_create_nonce('powerpress');
        $ppn_nonce_json = json_encode($ppn_nonce);
        echo "<script>var ppnNonce = {$ppn_nonce_json};</script>";
        switch ($status) {
            case 'List Networks':
                echo $this->getHTML('networks.php', $props, $networkInfo);
                break;
            case 'Select Choice':
                $shows_html = $this->getHTML('programs.php', $program_props, $networkInfo, '', '', '', $list_props);
                $groups_html = $this->getHTML('lists.php', $list_props, $networkInfo);
                $requests_html = $this->getHTML('applications.php', $application_props, $networkInfo);
                $manage_html = $this->getHTML('managenetwork.php', $props, $networkInfo);
                echo $this->getHTML('base.php', $props, $networkInfo, $shows_html, $groups_html, $requests_html, [], $manage_html);
                break;
            case 'Create List':
            case 'Manage List':
                echo $this->getHTML('managelist.php', $props, $networkInfo);
                break;
            default:
                echo $this->getHTML('signin.php', $props, $networkInfo);
                break;
        }
        ?>
    </div>
<?php
    }

    /** clears the orphaned hourly updateProgram event */
    function clearLegacyCron()
    {
        if (wp_next_scheduled('updateProgram')) {
            wp_clear_scheduled_hook('updateProgram');
        }
    }

    /** ajax: add one show to the network, refusing a show it already holds */
    function addProgramToNetwork() {
        // 1) AUTHORIZE
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'powerpress')) {
            wp_send_json_error('error');
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error('error');
        }

        // 2) READ POST
        $networkID = htmlspecialchars($_POST['network_id'] ?? '');

        if (empty($networkID)) {
            wp_send_json_error('error');
        }

        // 3) POSTED NETWORK MUST BE THE LINKED ONE
        if ((string) get_option('powerpress_network_id') !== $networkID) {
            wp_send_json_error('error');
        }

        // 4) REJECT A SHOW ALREADY IN THE NETWORK
        $requestUrl = "/2/powerpress/network/{$networkID}/exists-program/";
        $result = $this->requestAPI($requestUrl, true, $_POST);

        if (!$result || isset($result['error'])) {
            wp_send_json_error('error');
        }

        if ($result['exists']) {
            wp_send_json_error('error');
        }

        // 5) ADD
        $requestUrl = "/2/powerpress/network/{$networkID}/programs/add/";
        $result = $this->requestAPI($requestUrl, true, $_POST);

        if (empty($result) || isset($result['error'])) {
            wp_send_json_error('error');
        }

        // 6) CONFIRM
        $programTitle = sanitize_text_field(wp_unslash($_POST['program_title'] ?? ''));
        $networkTitle = wp_unslash(get_option('powerpress_network_title', ''));
        $msg = sprintf(__('%s added to %s', 'powerpress'), $programTitle, $networkTitle);

        wp_send_json_success(['message' => $msg]);
    }

    /** ajax: link a network target to an existing page, or create the page for it */
    function ajaxPageAction() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'powerpress')) {
            wp_send_json_error('invalid nonce');
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error('insufficient permissions');
        }

        $target = sanitize_text_field($_POST['target'] ?? '');
        $targetId = sanitize_text_field($_POST['targetId'] ?? '');
        $mode = sanitize_text_field($_POST['mode'] ?? '');

        if (empty($target)) {
            wp_send_json_error('missing target');
        }

        $option = get_option('powerpress_network_map') ?: [];

        if ($mode === 'link') {
            // LINK EXISTING PAGE
            $pageId = intval($_POST['pageID'] ?? 0);
            if (!$pageId || !current_user_can('edit_post', $pageId)) {
                wp_send_json_error('invalid page');
            }

            if ($target === 'List') {
                $option["l-{$targetId}"] = $pageId;
            } elseif ($target === 'Program') {
                $option["p-{$targetId}"] = $pageId;
            }
            update_option('powerpress_network_map', $option);

            if (!empty($_POST['updateShortcode'])) {
                self::ensureShortcodeOnPage($pageId, $target, $targetId);
            }
            $postId = $pageId;
        } else {
            // CREATE NEW PAGE
            $postId = self::createPage();
        }

        if (!$postId) {
            wp_send_json_error('page creation failed');
        }

        if ($target === 'List') {
            $this->insertOption('link_page_list', get_permalink($postId));
        }

        wp_send_json_success([
            'post_id' => $postId,
            'permalink' => get_permalink($postId),
            'edit_url' => admin_url("post.php?post={$postId}&action=edit"),
        ]);
    }

    /** drop local caches for a network the server no longer has, a one way sync */
    private function scrubStaleNetwork($networkId) {
        $networkId = absint($networkId);
        if (!$networkId) {
            return;
        }

        $cache = get_option('ppn-cache n', []);
        if (!empty($cache['data']) && is_array($cache['data'])) {
            $cache['data'] = array_values(array_filter($cache['data'], function($n) use ($networkId) {
                return empty($n['network_id']) || (int) $n['network_id'] !== $networkId;
            }));
            update_option('ppn-cache n', $cache);
        }

        delete_option("ppn-cache n-{$networkId}");
        PowerpressNetworkDataBus::clearCacheByLike("ppn-cache n-{$networkId}-%");
    }

    /** what a network probe told us: it is there, it is gone, or the api could not say */
    private function networkProbeState($probe) {
        if (is_array($probe) && !empty($probe['network_id'])) {
            return 'confirmed';
        }

        if (is_array($probe) && !empty($probe['error']) && $probe['error'] == 'Your account does not have the network with specified id') {
            return 'not_found';
        }

        return 'unavailable';
    }

}
