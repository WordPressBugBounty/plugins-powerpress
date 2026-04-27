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

    function __construct($parent_slug)
    {
        $this->parent_slug = $parent_slug;
        $this->init();
        $this->apiBus = new PowerpressNetworkDataBus();

        add_action('admin_menu', array($this, 'checkUpdateProgram'));
        add_action('wp_ajax_add_program_to_network', array($this, 'addProgramToNetwork'));
        add_action('wp_ajax_ppn_page_action', array($this, 'ajaxPageAction'));
    }

    function init()
    {

        if (!is_admin()) {
            require_once(dirname(__FILE__) . '/shortcodes/ShortCode.php');
            require_once(dirname(__FILE__) . '/shortcodes/Application.php');
            require_once(dirname(__FILE__) . '/shortcodes/ListPreview.php');
            require_once(dirname(__FILE__) . '/shortcodes/List.php');
            require_once(dirname(__FILE__) . '/shortcodes/Program.php');
            require_once(dirname(__FILE__) . '/shortcodes/Grid.php');

        }

        if (is_admin() && isset($_GET['page']) && $_GET['page'] == 'powerpress') {
            $key = $_GET['key'] ?? '';

            switch ($key) {
                case 'programs':
                    include(dirname(__FILE__) . '/admin/programs.php');
                    break;
                case 'lists':
                    include(dirname(__FILE__) . '/admin/lists.php');
                    break;
                //case 'link':
                    //include(dirname(__FILE__) . '/admin/link.php');
                    //break;
                //case 'index':
                    //include(dirname(__FILE__) . '/admin/index.php');
                    //break;
                case 'base':
                    include(dirname(__FILE__) . '/admin/base.php');
                    break;
                case 'applications':
                    include(dirname(__FILE__) . '/admin/applications.php');
                    break;
                default:
                    break;
            }

        }

    }
	
	function getAccessToken()
	{
		// Look at the creds and use the latest access token, if its not the latest refresh it...
		$creds = get_option('powerpress_network_creds');
		if( !empty($creds['access_token']) && !empty($creds['access_expires']) && $creds['access_expires'] > time() ) { // If access token did not expire
			return $creds['access_token'];
		}
		
		if( !empty($creds['refresh_token']) && !empty($creds['client_id']) && !empty($creds['client_secret']) ) {
			
			// Create new access token with refresh token here...
			$auth = new PowerPressAuth();
			$resultTokens = $auth->getAccessTokenFromRefreshToken($creds['refresh_token'], $creds['client_id'], $creds['client_secret']);
			
			if( !empty($resultTokens['access_token']) && !empty($resultTokens['expires_in']) ) {
				powerpress_save_settings( array('access_token'=>$resultTokens['access_token'], 'access_expires'=>( time() + $resultTokens['expires_in'] - 10 ) ), 'powerpress_network_creds');
				
				return $resultTokens['access_token'];
			}
		}
		
		// If we failed to get credentials, return false
		return false;
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

    static function getHTML($filename, $props, $networkInfo, $accountInfo, $shows_html = '', $groups_html = '', $requests_html = '', $secondary_props = array())
    {
        if (is_file(dirname(__FILE__) . '/shortcodes/views/' . $filename)) {
            ob_start();
            include(dirname(__FILE__) . '/shortcodes/views/' . $filename);
            $html = ob_get_contents();
            ob_end_clean();
            return $html;
        } else if (is_file(dirname(__FILE__) . '/admin/' . $filename)) {
            ob_start();
            include(dirname(__FILE__) . '/admin/' . $filename);
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

    /**
     * ensure page content has the correct ppn shortcode after linking
     *
     * if a stale shortcode of the same type exists (wrong id), replace it.
     * if no matching shortcode exists, append one.
     * if the correct shortcode is already present, do nothing.
     */
    private static function ensureShortcodeOnPage($postID, $target, $targetId) {
        if (empty($postID) || empty($target) || empty($targetId)) return;

        $post = get_post($postID);
        if (!$post) return;

        $content = $post->post_content;

        if ($target === 'List') {
            $changed = false;

            // fix stale ppn-gridview (skip id="all")
            $gridShortcode = "[ppn-gridview id=\"{$targetId}\" rows=\"100\" cols=\"3\"]";
            $gridPattern = '/\[ppn-gridview\s+id="(?!all)[^"]*"[^\]]*\]/';
            if (strpos($content, $gridShortcode) === false && preg_match($gridPattern, $content)) {
                $content = preg_replace($gridPattern, $gridShortcode, $content, 1);
                $changed = true;
            }

            // fix stale ppn-list (skip id="all")
            $listPattern = '/\[ppn-list\s+id="(?!all)[^"]*"[^\]]*\]/';
            if (preg_match($listPattern, $content, $m)) {
                // preserve existing attrs (style, etc) but swap the id
                $fixed = preg_replace('/id="[^"]*"/', "id=\"{$targetId}\"", $m[0], 1);
                $content = str_replace($m[0], $fixed, $content);
                $changed = true;
            }

            // if no ppn shortcode found at all, append default gridview
            if (!$changed && strpos($content, $gridShortcode) === false) {
                $content = trim($content) . "\n\n" . $gridShortcode;
            }
        } elseif ($target === 'Program') {
            $shortcode = "[ppn-program id=\"{$targetId}\"]";
            if (strpos($content, $shortcode) !== false) return;
            // replace stale ppn-program w/ different id
            $pattern = '/\[ppn-program\s+id\s*=\s*"?[^"\]]*"?\]/';
            if (preg_match($pattern, $content)) {
                $content = preg_replace($pattern, $shortcode, $content, 1);
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

    private function handlePageAction($createUrl)
    {
        $option = get_option('powerpress_network_map');
        $postID = 0;
        if (isset($_POST['pageAction']) && $_POST['pageAction'] == 'unlink'){
            if ($_POST['target'] == 'List') {
                unset($option['l-' . $_POST['targetId']]);
                $this->removeOption('link_page_list');
            } else if ($_POST['target'] == 'Program') {
                unset($option['p-' . $_POST['targetId']]);
                $this->removeOption('link_page_program');
            }
            update_option ('powerpress_network_map', $option);
            powerpress_page_message_add_notice(__('Page unlinked.', 'powerpress'));
        } else if (isset($_POST['pageAction']) && $_POST['pageAction'] == 'clearSiteCache') {
            $network_id = get_option('powerpress_network_id');
            if ($network_id) {
                $cacheNameBase = 'ppn-cache n-' . $network_id;
                // base network data
                $this->apiBus->clearCache($cacheNameBase);
                // applications
                $this->apiBus->clearCache($cacheNameBase . '-a');
                // programs
                $this->apiBus->clearCache($cacheNameBase . '-p');
                // lists
                $this->apiBus->clearCache($cacheNameBase . '-l');
                powerpress_page_message_add_notice(__('Cache cleared.', 'powerpress'));
            }
        } else {
            if (empty($_POST['pageID'])) {
                $postID = $this->createPage();
            } else{
                $postID = $_POST['pageID'];
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
                    $this->insertOption('link_page_program', get_permalink($postID));
                }
                powerpress_page_message_add_notice(__('Page linked successfully.', 'powerpress'));
            }
        }
        if ($postID!= 0 && !(isset($_POST['redirectUrl']) && $_POST['redirectUrl'] == 'false')) {
            header('location: ' . $createUrl . 'wp-admin/post.php?post=' . $postID . '&action=edit');
            exit;
        }
    }

    private function handleCodeReturn()
    {
		if( empty($_GET['state']) || empty($_GET['code']) ) {
			powerpress_page_message_add_error( __('An error occurred linking your account. Missing parameters.', 'powerpress') );
			return false;
		}
		
		$tempClient = get_option('powerpress_network_temp_client');
		if( $_GET['state'] != $tempClient['state'] ) {
			powerpress_page_message_add_error( __('An error occurred linking your account. State does not match.', 'powerpress') );
			return false;
		} 
		$redirectUri = admin_url('admin.php?page=network-plugin');
		$auth = new PowerPressAuth();
		
		// Get the client ID for this installation
		$resultClient = $auth->issueClient($_GET['code'], $tempClient['temp_client_id'], $tempClient['temp_client_secret'], $redirectUri);
		if( $resultClient === false || empty($resultClient['client_id']) || empty($resultClient['client_secret']) ) {
			if( !empty($resultClient['error_description']) )
				powerpress_page_message_add_error( $resultClient['error_description'] );
			else if( !empty($resultClient['error']) )
					powerpress_page_message_add_error( $resultClient['error'] );
			else
				powerpress_page_message_add_error( __('Error issuing client:','powerpress') .' '.$auth->GetLastError() . $auth->getDebugInfo() );
			return false;
		}
		
		// Get the access and refresh token for this client
		$resultTokens = $auth->getAccessTokenFromCode( $_GET['code'], $resultClient['client_id'], $resultClient['client_secret'], $redirectUri);
		if( $resultTokens === false || empty($resultTokens['access_token']) || empty($resultTokens['refresh_token']) ) {
			if( !empty($resultTokens['error_description']) )
				powerpress_page_message_add_error( $resultTokens['error_description'] );
			else if( !empty($resultTokens['error']) )
					powerpress_page_message_add_error( $resultTokens['error'] );
			else
				powerpress_page_message_add_error( __('Error retrieving access token:','powerpress') .' '.$auth->GetLastError() );
			return false;
		}
		
		$props = array();
		$props['code'] = $_GET['code'];
		$props['client_id'] = $resultClient['client_id'];
		$props['client_secret'] = $resultClient['client_secret'];
		$props['access_token'] = $resultTokens['access_token'];
		$props['access_expires'] = ( time() + $resultTokens['expires_in'] - 10 );
		$props['refresh_token'] = $resultTokens['refresh_token'];
		////update_option('network_general', $props);
		powerpress_save_settings( $props, 'powerpress_network_creds');
		
		powerpress_page_message_add_notice( __('Account linked successfully.', 'powerpress') );
		return;
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
        if (isset($_GET['code'])) {
            $this->handleCodeReturn();
        }

        if (isset($_POST['unlinkAccount'])){
            // flush network cache
            PowerpressNetworkDataBus::clearCacheByLike("ppn-cache %");

            delete_option('powerpress_network_creds');
            delete_option('powerpress_network_id');
            delete_option('powerpress_network_title');
            delete_option('powerpress_network_temp_client');
            powerpress_page_message_add_notice(__('Account unlinked.', 'powerpress'));
        }
		
		if( !empty($_POST['ppn-action']) ) {
			switch( $_POST['ppn-action']) {
				case 'link-account': {
					// Link account action requested
					if (isset($_POST['signinRequest'])) {
					
						$auth = new PowerPressAuth();
						$result = $auth->getTemporaryCredentials();
					   
						// Okay we got it!
						if( $result !== false && !empty($result['temp_client_id']) && !empty($result['temp_client_secret']) ) {
							$state = md5( rand(0, 999999) . time() );
							update_option('powerpress_network_temp_client', array('temp_client_id' => $result['temp_client_id'], 'temp_client_secret' =>$result['temp_client_secret'], 'state'=>$state ));
							header('location:' . $auth->getApiUrl() . 'oauth2/authorize?response_type=code&client_id=' . $result['temp_client_id'] . '&redirect_uri=' . $createUrl . 'wp-admin/admin.php?page=network-plugin&state='.$state );
							exit;
						}
						
						// Handle error here
						if( !empty($result['error_description']) )
							powerpress_page_message_add_error( $result['error_description'] );
						else if( !empty($result['error']) )
							powerpress_page_message_add_error( $result['error'] );
						else
							powerpress_page_message_add_error( __('Error creating temporary client:','powerpress') .' '.$auth->GetLastError() );
					}
				}; break;

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
                        powerpress_page_message_add_notice(__('Network successfully created.', 'powerpress-network'));
                    } else {
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
                        powerpress_page_message_add_error(__('That network no longer exists. It has been removed from your list.', 'powerpress'));
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

        $accountInfo = $this->apiBus->getNetworkOwnerInformation($apiUrl, $creds, $networkInfo, $needDirectAPI);

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
            if (isset($_POST['linkPageProgram'])){
                $this->insertOption('link_page_program', $_POST['linkPageProgram']);
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
            if (isset($_POST['needDirectAPI']) && $_POST['needDirectAPI'] == true){
                // flush all ppn-cache options
                global $wpdb;
                $wpdb->query(
                    $wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", 'ppn-cache %')
                );
            }

            if (isset ($_POST['changeOrCreate']) && $_POST['changeOrCreate'] == true) {
                if (isset($_POST['newListTitle'])) { //Create New List
                    $create = array(
                            'newListTitle' => wp_unslash($_POST['newListTitle']),
                            'newListDescription' => mb_substr(wp_unslash($_POST['newListDescription']), 0, 500),
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
                            'editListTitle'  => wp_unslash($_POST['editListTitle']),
                            'editListDescription'=> mb_substr(wp_unslash($_POST['editListDescription']), 0, 500)
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

            $requestUrl = null;
//            $status = 'Manage Program';
//            $_POST['linkPage'] = 'Nothing here';
            switch ($status) {
                case 'List Networks':
                    $props = $this->apiBus->getNetworksInAccount($creds);
                    if (isset($_POST['linkNetwork']) && $_POST['linkNetwork'] == 'unlink') {
                        delete_option('powerpress_network');
                    }
                    break;

                case 'Select Choice':
                    // pre-flight probe
                    $probe = $this->requestAPI('/2/powerpress/network/' . absint($networkId), true);
                    if (is_array($probe) && !empty($probe['error']) && $probe['error'] == 'Your account does not have the network with specified id') {
                        $this->scrubStaleNetwork($networkId);
                        delete_option('powerpress_network_id');
                        delete_option('powerpress_network_title');

                        powerpress_page_message_add_error(__('That network no longer exists. It has been removed from your list.', 'powerpress'));

                        $networkId = '';
                        $networkTitle = '';
                        $networkInfo['network_id'] = '';
                        $networkInfo['network_title'] = '';
                        $status = 'List Networks';
                        $props = $this->apiBus->getNetworksInAccount($creds);
                        break;
                    } 

                    $program_props = $this->apiBus->getProgramsInNetwork($apiUrl, $creds, $networkInfo, $needDirectAPI );
                    $list_props = $this->apiBus->getListsInNetwork($apiUrl, $creds, $networkInfo, $needDirectAPI );
                    $application_props = $this->apiBus->getApplicantsInNetwork($apiUrl, $creds, $networkInfo, true );
                    $props = $this->apiBus->getSpecificNetworkInAccount($apiUrl, $creds, $networkInfo, $needDirectAPI );
                    $networkInfo = get_option('powerpress_network', array());
					$networkInfo['network_id'] = $networkId;
                    $networkInfo['network_title'] = $networkTitle;
                    break;

                case 'List Programs':
                    $props = $this->apiBus->getProgramsInNetwork($apiUrl, $creds, $networkInfo, $needDirectAPI );
                    break;

                case 'List Lists':
                    $props = $this->apiBus->getListsInNetwork($apiUrl, $creds, $networkInfo, $needDirectAPI );
                    $networkInfo = get_option('powerpress_network');
					$networkInfo['network_id'] = $networkId;
                    $networkInfo['network_title'] = $networkTitle;
                    break;

                case 'List Applicants':
                    $props = $this->apiBus->getApplicantsInNetwork($apiUrl, $creds, $networkInfo, true );
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

                case 'Manage Program':
                    $props = $this->apiBus->getSpecificProgramInNetwork($apiUrl, $creds, $networkInfo, $needDirectAPI);
                    $networkInfo = get_option ('powerpress_network');
					$networkInfo['network_id'] = $networkId;
                    $networkInfo['network_title'] = $networkTitle;
                    // clean up stale page link if page was deleted
                    if (!empty($networkInfo['link_page_program'])) {
                        $map = get_option('powerpress_network_map', []);
                        $mapKey = 'p-' . ($networkInfo['program_id'] ?? '');
                        $pageId = $map[$mapKey] ?? 0;
                        if (!$pageId || get_post_status($pageId) !== 'publish') {
                            $this->removeOption('link_page_program');
                            $networkInfo['link_page_program'] = '';
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
        $return['accountInfo'] = $accountInfo;
        $return['network_info'] = $networkInfo;
        return $return;

    }

    static function removeOption ($key)
    {
        $result = get_option ('powerpress_network');
        unset ($result[$key]);
        update_option('powerpress_network', $result);
    }

    static function insertOption ($key, $value)
    {
        $result = get_option ('powerpress_network');
        $result[$key] = $value;
        update_option('powerpress_network', $result);
    }

    function display_plugin()
    {
		if( function_exists('powerpress_page_message_print') )
            powerpress_page_message_print();

        $status = $this->display['status'];
        $props = $this->display['props'];
        $program_props = $this->display['program_props'];
        $list_props = $this->display['list_props'];
        $application_props = $this->display['application_props'];
        $accountInfo = $this->display['accountInfo'];
        $networkInfo = $this->display['network_info'];
        ?>

        <div class="ppn-admin">
        <?php
        $ppn_nonce = wp_create_nonce('powerpress');
        echo '<script>var ppnNonce = ' . json_encode($ppn_nonce) . ';</script>';
        switch ($status) {
            case 'List Networks':
                echo $this->getHTML('networks.php', $props, $networkInfo, $accountInfo);
                break;
            case 'Select Choice':
                $shows_html = $this->getHTML('programs.php', $program_props, $networkInfo, $accountInfo, '', '', '', $list_props);
                $groups_html = $this->getHTML('lists.php', $list_props, $networkInfo, $accountInfo);
                $requests_html = $this->getHTML('applications.php', $application_props, $networkInfo, $accountInfo);
                echo $this->getHTML('base.php', $props, $networkInfo, $accountInfo, $shows_html, $groups_html, $requests_html);
                break;
            case 'List Programs':
                echo $this->getHTML('programs.php', $props, $networkInfo, $accountInfo);
                break;
            case 'List Lists':
                echo $this->getHTML('lists.php', $props, $networkInfo, $accountInfo);
                break;
            case 'List Applicants':
                echo $this->getHTML('applications.php', $props, $networkInfo, $accountInfo);
                break;
            case 'Create List':
            case 'Manage List':
                echo $this->getHTML('managelist.php', $props, $networkInfo, $accountInfo);
                break;
            case 'Manage Program':
                echo $this->getHTML('manageprogram.php', $props, $networkInfo, $accountInfo);
                break;
            default:
                echo $this->getHTML('signin.php', $props, $networkInfo, $accountInfo);
                break;
        }
        ?>
    </div>
<?php
    }

    static function updateMeta ($meta_key)
    {
        global $wpdb;
        $update = serialize(['last_update' => time(), 'need_update' => true]);
        $wpdb->update(
            "{$wpdb->prefix}postmeta",
            ['meta_value' => $update],
            ['meta_key' => $meta_key]
        );
    }

    function checkUpdateProgram()
    {
        $option = get_option ('powerpress_network_creds');
        if (empty($option)){
            return;
        }
		
        if (!wp_next_scheduled ( 'updateProgram' )) {
            wp_schedule_event(time(), 'hourly', 'updateProgram');
        }

        $timeExecute = wp_next_scheduled('updateProgram');
        if (time() >= $timeExecute){
            $networkId = get_option('powerpress_network_id');
			if( empty($networkId) )
				return;
            $apiArray = powerpress_get_api_array();
            $apiUrl = $apiArray[0];
            // $post = false; // array('grant_type'=>'client_credentials', 'access_token'=>$accessToken );
            $requestUrl = $apiUrl.'2/powerpress/network/'.$networkId.'/update?since='.($timeExecute - 24*60*60);
            $programUpdate = $this->requestAPI($requestUrl);
            foreach ($programUpdate ?: [] as $program) {
                PowerPressNetwork::updateMeta($program);
            }
        }
    }

    function addProgramToNetwork() {
        // verify nonce
        if(!wp_verify_nonce($_POST['nonce'] ?? '', 'powerpress')){
            wp_send_json_error('error');
        }

        // verify options management
        if(!current_user_can('manage_options')){
            wp_send_json_error('error');
        }

        // collect POST values
        $networkID = htmlspecialchars($_POST['network_id'] ?? '');
        $programID = htmlspecialchars($_POST['program_id'] ?? ''); // currently don't need

        if(empty($networkID)) {
            wp_send_json_error('error');
        }

        // compare networkID from POST to networkID from PP
        if((string)get_option('powerpress_network_id') !== $networkID){
            wp_send_json_error('error');
        }

        // verify programID is not ALREADY within the network
        $requestUrl = '/2/powerpress/network/' . $networkID . '/exists-program/';
        $result = $this->requestAPI($requestUrl, true, $_POST);

        if(!$result || isset($result['error'])){
            wp_send_json_error('error');
        }

        if($result['exists']){
            wp_send_json_error('error');
        }

        // program does not exist within network, add it
        $requestUrl = '/2/powerpress/network/' . $networkID . '/programs/add/';
        $result = $this->requestAPI($requestUrl, true, $_POST);

        if(empty($result) || isset($result['error'])){
            wp_send_json_error('error');
        }

        $programTitle = sanitize_text_field(wp_unslash($_POST['program_title'] ?? ''));
        $networkTitle = wp_unslash(get_option('powerpress_network_title', ''));
        $msg = sprintf(__('%s added to %s', 'powerpress'), $programTitle, $networkTitle);
        wp_send_json_success(['message' => $msg]);
    }

    function ajaxPageAction() {
        // init checks
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
            if (!$pageId) {
                wp_send_json_error('missing page ID');
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

        // store link in network info
        if ($target === 'List') {
            $this->insertOption('link_page_list', get_permalink($postId));
        } elseif ($target === 'Program') {
            $this->insertOption('link_page_program', get_permalink($postId));
        }

        wp_send_json_success([
            'post_id' => $postId,
            'permalink' => get_permalink($postId),
            'edit_url' => admin_url("post.php?post={$postId}&action=edit"),
        ]);
    }

    /** 
     * clear local state for a network 
     * acts as a sync when network removed from blubrry server but still exists on local wp db
     * scrubs network etnry from list cache and deletes network caches
     */
    private function scrubStaleNetwork($networkId) {
        $networkId = absint($networkId);
        if (!$networkId) {
            return;
        }

        // clean cache
        $cache = get_option('ppn-cache n', []);
        if (!empty($cache['data']) && is_array($cache['data'])) {
            $cache['data'] = array_values(
                    array_filter($cache['data'], function($n) use ($networkId) {
                    return empty($n['network_id']) || (int)$n['network_id'] !== $networkId;
                })
            );
            update_option('ppn-cache n', $cache);
        }

        delete_option("ppn-cache n-{$networkId}");
        PowerpressNetworkDataBus::clearCacheByLike("ppn-cache n-{$networkId}-%");
    }

}
