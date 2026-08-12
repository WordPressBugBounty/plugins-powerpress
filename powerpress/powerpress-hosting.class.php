<?php
/*
Blubrry PowerPress media hosting pipeline
*/

if( !defined('ABSPATH') ) exit;

// =======
// HELPERS
// =======

function powerpress_fetch_with_retry(string $url, ?string $auth, array $post = [], int $timeout = 30) {
	$data = powerpress_remote_fopen($url, $auth, $post, $timeout);

	// retry with curl if primary api failed
	if (!$data && strpos($url, 'api.blubrry.com') !== false) {
		$data = powerpress_remote_fopen($url, $auth, $post, $timeout, false, true);
	}

	return $data;
}

function powerpress_extract_filename($url_or_file) {
	$parsed = parse_url($url_or_file);

	// if has host, extract filename from path (parse_url already strips query string from path)
	if (!empty($parsed['host'])) {
		$path = $parsed['path'] ?? '';
		$parts = explode('/', $path);
		$filename = end($parts);
	} else {
		// no host - strip query string first, then extract filename
		$qs_parts = explode('?', $url_or_file);
		$parts = explode('/', $qs_parts[0]);
		$filename = end($parts);
	}

	return sanitize_text_field($filename);
}

function powerpress_is_blubrry_hosted($url) {
	// parse for host
	$parsed = parse_url($url);

	// no host means just a filename, cant determine if blubrry hosted
	if (empty($parsed['host'])) return false;

	$host = strtolower($parsed['host']);

	// build list of blubrry domains based on trusted domains
	$blubrry_prefixes = ['content.', 'content3.', 'ins.', 'mc.', 'media.', 'protected.'];
	$blubrry_domains = [];
	$domains_to_check = defined('POWERPRESS_TRUSTED_DOMAINS') ? POWERPRESS_TRUSTED_DOMAINS : ['blubrry.com'];
	foreach( $domains_to_check as $domain ) {
		foreach( $blubrry_prefixes as $prefix ) {
			$blubrry_domains[] = $prefix . $domain;
		}
	}

	return in_array($host, $blubrry_domains);
}

// ================
// HOSTING PIPELINE
// ================

class PowerPress_Hosting
{
	private $settings;
	private $creds;
	private $auth;
	private $api_urls;

	public function __construct($settings = null, $creds = null, $auth = null, $api_urls = null)
	{
		$this->settings = ($settings === null ? get_option('powerpress_general') : $settings);
		$this->creds    = ($creds === null ? get_option('powerpress_creds') : $creds);
		$this->api_urls = ($api_urls === null ? powerpress_get_api_array() : $api_urls);

		if ($auth === null) {
			require_once(POWERPRESS_ABSPATH .'/powerpressadmin-auth.class.php');
			$auth = new PowerPressAuth();
		}

		$this->auth = $auth;
	}

	public function process($post_id, $post_title)
	{
		$this->drainReportQueue();

		$error = false;

		// ==================
		// BUILD CUSTOM FEEDS
		// ==================

		$custom_feeds = [];
		if (!empty($this->settings['custom_feeds']) && is_array($this->settings['custom_feeds'])) {
			$custom_feeds = $this->settings['custom_feeds'];
		}
		if (!isset($custom_feeds['podcast'])) {
			$custom_feeds['podcast'] = 'podcast';
		}

		// add post type podcasting feeds if enabled
		if (!empty($this->settings['posttype_podcasting'])) {
			$feed_slug_post_types_array = get_option('powerpress_posttype-podcasting');
			if (is_array($feed_slug_post_types_array)) {
				// option stores feed_slug => [post_type => title], so iterate keys
				foreach (array_keys($feed_slug_post_types_array) as $feed_slug) {
					if (empty($custom_feeds[$feed_slug])) {
						$custom_feeds[$feed_slug] = $feed_slug;
					}
				}
			}
		}

		// =================
		// PROCESS EACH FEED
		// =================


		foreach ($custom_feeds as $feed_slug => $feed_title) {
			$field = 'enclosure';
			if ($feed_slug != 'podcast') {
			$field = "_{$feed_slug}:enclosure";
			}

			$enclosure_data = get_post_meta($post_id, $field, true);
			$post_guid = get_the_guid($post_id);
			$post_time = get_post_time('U', false, $post_id);

			if (!$enclosure_data) continue;

			// ====================
			// PARSE ENCLOSURE DATA
			// ====================

			$meta_parts = explode("\n", $enclosure_data, 4);

			$enclosure_url = (count($meta_parts) > 0) ? trim($meta_parts[0]) : '';
			$enclosure_size = (count($meta_parts) > 1) ? trim($meta_parts[1]) : '';
			$enclosure_type = (count($meta_parts) > 2) ? trim($meta_parts[2]) : '';
			// allowed_classes => false prevents php object injection via crafted serialized data
			$episode_data = (count($meta_parts) > 3) ? unserialize($meta_parts[3], ['allowed_classes' => false]) : false;

			if ($enclosure_type == '') {
				$error = __('Blubrry Hosting Error (publish)', 'powerpress') . ': ' . __('Error occurred obtaining enclosure content type.', 'powerpress');
				powerpress_page_message_add_error($error);
			}
			$episode_art = ($episode_data) ? ($episode_data['image'] ?? '') : '';

			$program_keyword = (!empty($episode_data['program_keyword']) ? $episode_data['program_keyword'] : $this->settings['blubrry_program_keyword']);
			$podcast_id = (!empty($episode_data['podcast_id'])) ? $episode_data['podcast_id'] : false;

			// ====================
			// SET PROCESSING FLAGS
			// ====================

			$publish_media = $episode_data && !empty($episode_data['hosting']);
			$process_transcripts = !empty($_POST['Powerpress'][$feed_slug]['transcript']['edit']);
			$process_chapters = !empty($_POST['Powerpress'][$feed_slug]['chapters']['edit']);
			$process_alt_enclosures = !empty($_POST['Powerpress'][$feed_slug]['alternate_enclosure']);

			if (!$publish_media && !$process_transcripts && !$process_chapters && !$process_alt_enclosures) {
				// bare filename with no podcast id means it uploaded but never published
				if (strpos($enclosure_url, '://') === false && empty($podcast_id))
					$this->reportFailure($post_id, $feed_slug, $program_keyword, 'no_hosting_flag', $enclosure_url, $enclosure_size);

				continue;
			}

			$error = false;

			// =======================
			// PROCESS MAIN MEDIA FILE
			// =======================

			if ($publish_media) {
				$is_mp3 = ($enclosure_type == 'audio/mpg' || $enclosure_type == 'audio/mpeg');
				$skip_publish = false;
				$enclosure_url = powerpress_extract_filename($enclosure_url);

				// check if file was already published (user set online manually via publisher)
				$already_published = $this->checkMediaPublished($enclosure_url, $program_keyword);
				if ($already_published && !empty($already_published['url'])) {
					$enclosure_url = $already_published['url'];
					unset($episode_data['hosting']);
					$skip_publish = true;

					if (!empty($already_published['length'])) {
						$enclosure_size = $already_published['length'];
					}

					// save podcast_id from already published media
					if (!empty($already_published['podcast_id'])) {
						$episode_data['podcast_id'] = $already_published['podcast_id'];
						$podcast_id = $already_published['podcast_id'];
					}

					// save updated enclosure data
					$enclosure_data = $enclosure_url . "\n" . $enclosure_size . "\n" . $enclosure_type . "\n" . serialize($episode_data);
					update_post_meta($post_id, $field, $enclosure_data);
				}

				// get media info (and write tags for mp3) if not already published
				if (!$skip_publish) {
					// mp3 files: write id3 tags and get info
					$results = ($is_mp3 && !empty($this->settings['write_tags']))
						? $this->writeTags($enclosure_url, $post_title, $program_keyword)
						// non-mp3 files or mp3 w/o write_tags: get media info
						: $this->mediaInfo($enclosure_url, $program_keyword);

					// process media results
					if (is_array($results) && !isset($results['error'])) {
						if (isset($results['duration']) && $results['duration']) {
							$episode_data['duration'] = $results['duration'];
						}
						if (isset($results['content-type']) && $results['content-type']) {
							$enclosure_type = $results['content-type'];
						}
						if (isset($results['length']) && $results['length']) {
							$enclosure_size = $results['length'];
						}
					} else if (isset($results['error'])) {
						$error = __('Blubrry Hosting Error (media info)', 'powerpress') . ': ' . $results['error'];
						powerpress_page_message_add_error($error, 'inline', ['feed_slug' => $feed_slug, 'media_file' => $enclosure_url]);
					} else {
						$error = __('Blubrry Hosting Error (media info)', 'powerpress') . ': ' . __('Unknown error occurred.', 'powerpress');
						powerpress_page_message_add_error($error, 'inline', ['feed_slug' => $feed_slug, 'media_file' => $enclosure_url]);
					}
				}

				// =============================
				// PUBLISH MEDIA FILE TO BLUBRRY
				// =============================

				if (!$skip_publish && $error == false) {
				$post_vars = [
					'episode_art' => $episode_art,
					'podcast_post_date' => $post_time,
					'podcast_title' => $post_title,
					'podcast_subtitle' => $episode_data['subtitle'] ?? ''
				];

					// process alternate enclosures
					if (!empty($episode_data['alternate_enclosure'])) {
						$post_vars['alternate_enclosures'] = $this->buildAltEnclosurePostVars(
							$episode_data['alternate_enclosure'],
							$post_title,
							$program_keyword,
							$this->settings,
							$feed_slug
						);
					}

					// extend execution time for publish request
					@set_time_limit(60 * 20); // 20 minutes

					// api request
					$results = $this->apiRequest(
						'/2/media/%s/%s?publish=true',
						[urlencode($program_keyword), urlencode($enclosure_url)],
						$post_vars
					);

					// process publish results
					if (is_array($results) && !isset($results['error'])) {
						$enclosure_url = $results['media_url'];

						// validate published url
						$host = parse_url($results['media_url'], PHP_URL_HOST);
						if (empty($host)) {
							$error = __('Blubrry Hosting Error (publish): Please re-upload media file and re-publish post', 'powerpress');
							powerpress_page_message_add_error($error);
						}

						unset($episode_data['hosting']);

						// save podcast id 
						if (!empty($results['podcast_id'])) {
							$episode_data['podcast_id'] = $results['podcast_id'];
						}

						// update alternate enclosures with published urls
						if (!empty($results['alternate_enclosures'])) {
							foreach ($episode_data['alternate_enclosure'] as $idx => $alternate_enclosure) {
								// SSRF check on path
								if (!SSRFCheck($alternate_enclosure['url'], $feed_slug, false, 'alternate enclosure url basename')) continue;

								$alt_filename = powerpress_extract_filename($alternate_enclosure['url']);
								if (array_key_exists($alt_filename, $results['alternate_enclosures'])) {
									$new_alt_url = $results['alternate_enclosures'][$alt_filename];
									$this->updateAltEnclosureUrl($episode_data, $idx, $new_alt_url, $feed_slug, $program_keyword);
								}
							}
						}

						// update uris in alternate enclosures with published urls
						if (!empty($results['alternate_enclosure_uris'])) {
							foreach ($episode_data['alternate_enclosure'] as $alt_idx => $alternate_enclosure) {
								if (!empty($alternate_enclosure['uris']) && is_array($alternate_enclosure['uris'])) {
									foreach ($alternate_enclosure['uris'] as $uri_idx => $uri_data) {
										$uri_value = is_array($uri_data) && !empty($uri_data['uri']) ? $uri_data['uri'] : (is_string($uri_data) ? $uri_data : '');

										if (!empty($uri_value)) {
											$uri_filename = powerpress_extract_filename($uri_value);

											if (array_key_exists($uri_filename, $results['alternate_enclosure_uris'])) {
												$new_uri_url = $results['alternate_enclosure_uris'][$uri_filename];

												// SSRF check on new uri url
												if (SSRFCheck($new_uri_url, $feed_slug, false, 'alternate enclosure uri')) {
													// update uri with published url and remove hosting flag
													$episode_data['alternate_enclosure'][$alt_idx]['uris'][$uri_idx] = [
														'uri' => $new_uri_url,
														'hosting' => ''
													];
												}
											}
										}
									}
								}
							}
						}

						// save updated enclosure data
						$enclosure_data = $enclosure_url . "\n" . $enclosure_size . "\n" . $enclosure_type . "\n" . serialize($episode_data);
						update_post_meta($post_id, $field, $enclosure_data);

					} else if (isset($results['error'])) {
						$error = __('Blubrry Hosting Error (publish)', 'powerpress') . ': ' . $results['error'];
						powerpress_page_message_add_error($error, 'inline', ['feed_slug' => $feed_slug, 'media_file' => $enclosure_url]);
						$this->reportFailure($post_id, $feed_slug, $program_keyword, 'api_error', $enclosure_url, $enclosure_size, $results['error']);
					} else {
						$error = __('Blubrry Hosting Error (publish)', 'powerpress') . ': ' . __('Unknown error occurred.', 'powerpress');
						powerpress_page_message_add_error($error, 'inline', ['feed_slug' => $feed_slug, 'media_file' => $enclosure_url]);
						$this->reportFailure($post_id, $feed_slug, $program_keyword, 'api_error', $enclosure_url, $enclosure_size, 'no response from publish endpoint');
					}
				}

			} else if (isset($episode_data['alternate_enclosure']) && !empty($episode_data['alternate_enclosure'])) {

				// =================================
				// PUBLISH ALTERNATE ENCLOSURES ONLY
				// =================================

				$post_vars = []; // init before use
				$post_vars['publish_alt_enclosures'] = 1;
				$post_vars['alternate_enclosures'] = $this->buildAltEnclosurePostVars(
					$episode_data['alternate_enclosure'],
					$post_title,
					$program_keyword,
					$this->settings,
					$feed_slug
				);

				if ($error == false && !empty($post_vars['alternate_enclosures'])) {
					// get filename from main enclosure url
					$filename = powerpress_extract_filename($enclosure_url);

					// extend execution time
					@set_time_limit(60 * 20); // 20 minutes

					// api request
					$results = $this->apiRequest(
						'/2/media/%s/%s?altEnclosureOnly=1&publish=true',
						array(urlencode($program_keyword), urlencode($filename)),
						$post_vars
					);

					// process publish results
					if (is_array($results) && !isset($results['error'])) {
						unset($episode_data['hosting']);

						// save podcast id
						if (!empty($results['podcast_id'])) {
							$episode_data['podcast_id'] = $results['podcast_id'];
						}

						// update alternate enclosures with published urls
						if (!empty($results['alternate_enclosures'])) {
							foreach ($episode_data['alternate_enclosure'] as $idx => $alternate_enclosure) {
								// ssrf check on path (consistent with main publish path)
								if (!SSRFCheck($alternate_enclosure['url'], $feed_slug, false, 'alternate enclosure url basename')) continue;

								$alt_filename = powerpress_extract_filename($alternate_enclosure['url']);
								if (array_key_exists($alt_filename, $results['alternate_enclosures'])) {
									$new_alt_url = $results['alternate_enclosures'][$alt_filename];
									$this->updateAltEnclosureUrl($episode_data, $idx, $new_alt_url, $feed_slug, $program_keyword);
								}
							}
						}

						// update uris in alternate enclosures with published urls
						if (!empty($results['alternate_enclosure_uris'])) {
							foreach ($episode_data['alternate_enclosure'] as $alt_idx => $alternate_enclosure) {
								if (!empty($alternate_enclosure['uris']) && is_array($alternate_enclosure['uris'])) {
									foreach ($alternate_enclosure['uris'] as $uri_idx => $uri_data) {
										$uri_value = is_array($uri_data) && !empty($uri_data['uri']) ? $uri_data['uri'] : (is_string($uri_data) ? $uri_data : '');

										if (!empty($uri_value)) {
											$uri_filename = powerpress_extract_filename($uri_value);

											if (array_key_exists($uri_filename, $results['alternate_enclosure_uris'])) {
												$new_uri_url = $results['alternate_enclosure_uris'][$uri_filename];

												// SSRF check on new uri url
												if (SSRFCheck($new_uri_url, $feed_slug, false, 'alternate enclosure uri')) {
													// update uri with published url and remove hosting flag
													$episode_data['alternate_enclosure'][$alt_idx]['uris'][$uri_idx] = [
														'uri' => $new_uri_url,
														'hosting' => ''
													];
												}
											}
										}
									}
								}
							}
						}

						// save updated enclosure data
						$enclosure_data = $enclosure_url . "\n" . $enclosure_size . "\n" . $enclosure_type . "\n" . serialize($episode_data);
						update_post_meta($post_id, $field, $enclosure_data);

					} else if (isset($results['error'])) {
						$error = __('Blubrry Hosting Error (alternate enclosure)', 'powerpress') . ': ' . $results['error'];
						powerpress_page_message_add_error($error, 'inline', ['feed_slug' => $feed_slug, 'media_file' => $enclosure_url]);
					} else {
						$error = __('Blubrry Hosting Error (alternate enclosure)', 'powerpress') . ': ' . __('Unknown error occurred.', 'powerpress');
						powerpress_page_message_add_error($error, 'inline', ['feed_slug' => $feed_slug, 'media_file' => $enclosure_url]);
					}
				}
			}

			// =========================================
			// GET PODCAST ID FOR TRANSCRIPTS / CHAPTERS
			// =========================================

			// update podcast_id from publish results if available (takes precedence)
			// $results may not be set if neither publish path was taken
			if (isset($results) && !empty($results['podcast_id'])) {
				$episode_data['podcast_id'] = $results['podcast_id'];
				$podcast_id = $results['podcast_id'];
			} else if (empty($podcast_id)) {
				// fallback: try to get from saved postmeta if not already set from episode_data
				// enclosure format: url\nsize\ntype\nserialized_data
				$postmeta_raw = get_post_meta($post_id, $field, true);
				if (!empty($postmeta_raw) && is_string($postmeta_raw)) {
					$postmeta_parts = explode("\n", $postmeta_raw);
					if (count($postmeta_parts) > 3) {
						// allowed_classes => false prevents php object injection via crafted serialized data
						$postmeta_data = @unserialize($postmeta_parts[3], ['allowed_classes' => false]);
						if (!empty($postmeta_data['podcast_id'])) {
							$podcast_id = $postmeta_data['podcast_id'];
						}
					}
				}
			}

			// build query for api calls
			$podcast_search_and = $podcast_id ? "&podcast_id=" . intval($podcast_id) : "&media_url=" . urlencode($enclosure_url);

	        // ==========================================
	        // SETUP FOR TRANSCRIPT/CHAPTERS/PLAYER CACHE
			// ==========================================

			$blubrry_hosted_media = powerpress_is_blubrry_hosted($enclosure_url);
			if (!empty($this->settings['blubrry_hosting']) && $blubrry_hosted_media) {
				$enclosure_filename = powerpress_extract_filename($enclosure_url);

				// build player cache purge url
				if (!empty($podcast_id)) {
					// with podcast_id, player looks up permalink/artwork server-side
					$purge_url = "https://player.blubrry.com/?podcast_id=" . intval($podcast_id);
					$purge_url .= "&media_url=" . urlencode($enclosure_url);
					if (!empty($this->settings['player']) && $this->settings['player'] == 'blubrrymodern') {
						$purge_url .= '&modern=1';
					}
				} else {
					// w/o podcast_id, permalink/artwork passed in url so must be in purge url
					$purge_url = "https://player.blubrry.com/?media_url=" . urlencode($enclosure_url);
					if (!empty($this->settings['player']) && $this->settings['player'] == 'blubrrymodern') {
						$purge_url .= '&modern=1';
					}

					// add permalink if available
					$permalink = get_permalink($post_id);
					if (!empty($permalink)) {
						$purge_url .= '&podcast_link=' . urlencode($permalink);
					}

					// add episode artwork if enabled
					if (!empty($episode_art) && isset($this->settings['bp_episode_image']) && $this->settings['bp_episode_image'] != false) {
						$purge_url .= '&artwork_url=' . urlencode($episode_art);
					}
				}
			}

			$episode_data_modified = false;
			$transcript_results = [];
			$chapters_results = [];

			// ===================
			// PROCESS TRANSCRIPTS
			// ===================

			if ($process_transcripts) {
				if (!empty($this->settings['blubrry_hosting']) && $blubrry_hosted_media) {
					$should_process_transcript = !empty($_POST['Powerpress'][$feed_slug]['transcript']['generate']) ||
						(!empty($_POST['Powerpress'][$feed_slug]['transcript']['upload']) &&
						!empty($_POST['Powerpress'][$feed_slug]['pci_transcript_url']));

					if ($should_process_transcript) {
						// build transcript-specific query params
						$transcript_query = $podcast_search_and;

						// add transcript url to query string
						if (!empty($_POST['Powerpress'][$feed_slug]['pci_transcript_url']) && !empty($_POST['Powerpress'][$feed_slug]['transcript']['upload'])) {
							$transcript_query .= '&transcript_url=' . urlencode($_POST['Powerpress'][$feed_slug]['pci_transcript_url']);
						}

						// add language parameter
						$transcript_language = powerpress_valid_language($_POST['Powerpress'][$feed_slug]['pci_transcript_language'] ?? '');
						if ($transcript_language !== '') {
							$transcript_query .= '&language=' . $transcript_language;
						}

						// api request
						$enc_keyword = urlencode($program_keyword);
						$enc_filename = urlencode($enclosure_filename);
						$transcript_results = $this->apiRequest(
							"/2/media/{$enc_keyword}/{$enc_filename}?transcript=true{$transcript_query}&purge_transcript=1",
							[],
							[]
						);

						// save temporary transcription file url to feed
						if (!empty($transcript_results['temp_transcription_file'])) {
							$episode_data["pci_transcript_url"] = $transcript_results['temp_transcription_file'];
							$episode_data["pci_transcript"] = 1;
							$episode_data_modified = true;
						}

						// check for insufficient storage error
						if (!empty($transcript_results['insufficient_transcription_storage'])) {
							$error = 'Your episode was published without a transcript because you have reached your transcription limit. Limits are calculated based on transcripts generated for your total media published/replaced for the month.';
							$error = __($error, 'powerpress');
							powerpress_page_message_add_error($error);
						}

						// check for errors
						if (isset($transcript_results['error'])) {
							powerpress_page_message_add_error(__('Error generating transcript: ', 'powerpress') . $transcript_results['error']);
						} else if (empty($transcript_results) || !is_array($transcript_results) || empty($transcript_results['temp_transcription_file'])) {
							powerpress_page_message_add_error(__('Error generating transcript', 'powerpress'));
						}

						// display api messages
						if (!empty($transcript_results['message'])) {
							powerpress_page_message_add_error($transcript_results['message']);
						}
					}
				}
			}

			// ================
			// PROCESS CHAPTERS
			// ================

			if ($process_chapters) {
				if (!empty($this->settings['blubrry_hosting']) && $blubrry_hosted_media && !empty($episode_data["pci_chapters_url"])) {
					// build chapters query params
					$enc_chapters_url = urlencode($episode_data['pci_chapters_url']);
					$chapters_query = "{$podcast_search_and}&chapters_url={$enc_chapters_url}";

	                // ID3 CHAP/CTOC embed params
	                $chapters_body = [];
	                if (!empty($episode_data['write_chapters_to_id3'])) {
	                    $response = wp_remote_get($episode_data['pci_chapters_url'], ['timeout' => 5]);
	                    if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
	                        $chapters_json = wp_remote_retrieve_body($response);
	                        if ($chapters_json !== '') {
	                            $chapters_body = [
	                                'chapters_id3' => 1,
	                                'chapters_json' => $chapters_json,
	                            ];
	                        }
	                    }
	                }

					// api request
					$enc_keyword = urlencode($program_keyword);
					$enc_filename = urlencode($enclosure_filename);
					$chapters_results = $this->apiRequest(
						"/2/media/{$enc_keyword}/{$enc_filename}?chapters=true{$chapters_query}&purge_chapters=1",
						[],
						$chapters_body
					);

					// save new chapters url to feed
					if (!empty($chapters_results['chapters_url'])) {
						$episode_data["pci_chapters_url"] = $chapters_results['chapters_url'];

						// try to detect podcast_id if not already set
						if (empty($episode_data['podcast_id']) && !empty($chapters_results['podcast_id'])) {
							$episode_data['podcast_id'] = $chapters_results['podcast_id'];
						}

						$episode_data_modified = true;
					}

					// check id3 result + error
					if (isset($chapters_results['id3']) && empty($chapters_results['id3']['success'])) {
						$id3_error = $chapters_results['id3']['error'] ?? __('unknown error', 'powerpress');
						powerpress_page_message_add_error(sprintf(
							__('Chapter embed into media file failed: %s', 'powerpress'),
							esc_html($id3_error)
						));
					}

					// display api messages/errors
					if (!empty($chapters_results['message'])) {
						powerpress_page_message_add_error($chapters_results['message']);
					}
					if (!empty($chapters_results['error'])) {
						powerpress_page_message_add_error($chapters_results['error']);
					}
				}
			}

			// =================
			// SAVE EPISODE DATA
			// =================

			if ($episode_data_modified) {
				$enclosure_data = "{$enclosure_url}\n{$enclosure_size}\n{$enclosure_type}\n" . serialize($episode_data);
				update_post_meta($post_id, $field, $enclosure_data);
			}

			// ===============================
			// UPDATE EPISODE TITLE IN BLUBRRY
			// ===============================

			$should_update_title = $episode_data_modified || !empty($podcast_id);

			if ($should_update_title) {
				$post_array = [
					'title' => $post_title,
					'media_url' => $enclosure_url,
					'podcast_post_date' => $post_time,
					'episode_art' => $episode_art
				];

				if (!empty($podcast_id)) {
					$post_array['podcast_id'] = $podcast_id;
				}

				// build endpoint w/ purge_url if available
				$enc_keyword = urlencode($program_keyword);
				$update_title_endpoint = "/2/episode/{$enc_keyword}/update-title/";
				if (!empty($purge_url)) {
					$update_title_endpoint .= "?purge_url=" . urlencode($purge_url);
				}

				$title_results = $this->apiRequest(
					$update_title_endpoint,
					[],
					$post_array
				);

				// check for title update errors
				if (!is_array($title_results)) {
					$error = __('Blubrry Hosting Error (update title)', 'powerpress') . ': ' . __('Failed to update episode title.', 'powerpress');
					powerpress_page_message_add_error($error, 'inline', ['feed_slug' => $feed_slug, 'media_file' => $enclosure_url]);
				} else if (isset($title_results['error'])) {
					$error = __('Blubrry Hosting Error (update title)', 'powerpress') . ': ' . $title_results['error'];
					powerpress_page_message_add_error($error, 'inline', ['feed_slug' => $feed_slug, 'media_file' => $enclosure_url]);
				}
			}
		}
	}

	public function apiRequest(string $endpoint_path, array $url_params, array $post_data, int $timeout = 1800)
	{

		// 1) BUILD REQUEST URL
		if (strpos($endpoint_path, '?') !== false) {
			// separate query string to avoid '%' being handled as format specifier in vsprintf
			list($path_template, $query_string) = explode('?', $endpoint_path, 2);
			$req_url = vsprintf($path_template, $url_params) . '?' . $query_string;
		} else {
			$req_url = vsprintf($endpoint_path, $url_params);
		}
		$req_url .= (strpos($req_url, '?') !== false ? '&' : '?') . 'format=json&cache=' . md5(rand(0, 999) . time());
		$req_url .= (defined('POWERPRESS_BLUBRRY_API_QSA') ? '&' . POWERPRESS_BLUBRRY_API_QSA : '');
		$req_url .= (defined('POWERPRESS_PUBLISH_PROTECTED') ? '&protected=true' : '');

		// 2) OAUTH PATH: use auth object directly
		if ($this->creds) {
			$access_token = powerpress_getAccessToken();
			// pass false for empty post_data to avoid triggering POST request
			return $this->auth->api($access_token, $req_url, $post_data ?: false, false, $timeout, true, true);
		}

		// 3) NON-OAUTH PATH: try each api url with retry
		if (strpos($req_url, '/2/') === 0) {
			$req_url = substr($req_url, 2);
		}

		foreach ($this->api_urls as $api_url) {
			$full_url = rtrim($api_url, '/') . $req_url;
			$json_data = powerpress_fetch_with_retry($full_url, $this->settings['blubrry_auth'] ?? null, $post_data, $timeout);
			if ($json_data) {
				return powerpress_json_decode($json_data);
			}
		}

		return false;
	}

	public function mediaInfo($file, $program_Keyword = false)
	{

		if( empty($program_Keyword) && !empty($this->settings['blubrry_program_keyword']) ) {
			$program_Keyword = $this->settings['blubrry_program_keyword'];
		}

		// api expects filename only, extract from url if needed
		$file = powerpress_extract_filename($file);

		$content = false;
	    $Results = array();
	    
	    if ($this->creds) {
	        $accessToken = powerpress_getAccessToken();
	        $req_url = sprintf('/2/media/%s/%s?format=json&info=true', urlencode($program_Keyword), urlencode($file));
	        $req_url .= (defined('POWERPRESS_BLUBRRY_API_QSA')?'?'. POWERPRESS_BLUBRRY_API_QSA:'');
	        $Results = $this->auth->api($accessToken, $req_url, false);
	    } else {
	        foreach ($this->api_urls as $index => $api_url) {
	            $req_url = sprintf('%s/media/%s/%s?format=json&info=true', rtrim($api_url, '/'), urlencode($program_Keyword), urlencode($file));
	            $req_url .= (defined('POWERPRESS_BLUBRRY_API_QSA') ? '&' . POWERPRESS_BLUBRRY_API_QSA : '');
	            $content = powerpress_remote_fopen($req_url, $this->settings['blubrry_auth'] ?? false);
	            if (!$content && $api_url == 'https://api.blubrry.com/') { // Lets force cURL and see if that helps...
	                $content = powerpress_remote_fopen($req_url, $this->settings['blubrry_auth'] ?? false, array(), 15, false, true);
	            }

	            if ($content != false)
	                break;
	        }

	        if ($content) {
	            $Results = powerpress_json_decode($content);
	        }
	    }

	    if ($Results && is_array($Results) && empty($Results['error']))
	        return $Results;

	    $api_error = !empty($Results['error']) ? sanitize_text_field($Results['error']) : '';
	    if ($api_error !== '')
	        return ['error' => $api_error];

		return array('error'=>__('Error occurred obtaining media information.', 'powerpress') );
	}

	private function writeTags($file, $post_title, $program_keyword = false)
	{

		// Use the Blubrry API to write ID3 tags. to the media...
		
		if( empty($program_keyword) && !empty($this->settings['blubrry_program_keyword']) ) {
			$program_keyword = $this->settings['blubrry_program_keyword'];
		}

		$PostArgs = array();
		$Fields = array('title','artist','album','genre','year','track','composer','copyright','url');
		foreach( $Fields as $null => $field )
		{
			if( !empty($this->settings[ 'tag_'.$field ]) )
			{
				if( $field == 'track' )
				{
					$TrackNumber = get_option('powerpress_track_number');
					if( empty($TrackNumber) )
						$TrackNumber = 1;
					$PostArgs[ $field ] = $TrackNumber;
					update_option('powerpress_track_number', ($TrackNumber+1) );
				}
				else
				{
					$PostArgs[ $field ] = $this->settings[ 'tag_'.$field ];
				}
			}
			else
			{
				switch($field)
				{
					case 'title': {
						$PostArgs['title'] = $post_title;
					}; break;
					case 'album': {
						$PostArgs['album'] = get_bloginfo('name');
					}; break;
					case 'genre': {
						$PostArgs['genre'] = 'Podcast';
					}; break;
					case 'year': {
						$PostArgs['year'] = date('Y');
					}; break;
					case 'artist':
					case 'composer': {
						if( !empty($this->settings['itunes_talent_name']) )
							$PostArgs[ $field ] = $this->settings['itunes_talent_name'];
					}; break;
					case 'copyright': {
						if( !empty($this->settings['itunes_talent_name']) )
							$PostArgs['copyright'] = '(c) '.$this->settings['itunes_talent_name'];
					}; break;
					case 'url': {
						$PostArgs['url'] = get_bloginfo('url');
					}; break;
				}
			}
		}
								
		// Get meta info via API
	    $Results = false;
		$content = false;
	    if ($this->creds) {
	        $accessToken = powerpress_getAccessToken();
	        $req_url = sprintf('/2/media/%s/%s?format=json&id3=true&cache=' . md5( rand(0, 999) . time() ) , urlencode($program_keyword), urlencode($file));
	        $req_url .= (defined('POWERPRESS_BLUBRRY_API_QSA')?'?'. POWERPRESS_BLUBRRY_API_QSA:'');
	        $Results = $this->auth->api($accessToken, $req_url, $PostArgs);
	        //$Results['error'] = print_r($Results, true);
	    } else {
	        foreach ($this->api_urls as $index => $api_url) {
	            $req_url = sprintf('%s/media/%s/%s?format=json&id3=true&cache=' . md5( rand(0, 999) . time() ), rtrim($api_url, '/'), urlencode($program_keyword), urlencode($file));
	            $req_url .= (defined('POWERPRESS_BLUBRRY_API_QSA') ? '&' . POWERPRESS_BLUBRRY_API_QSA : '');
	            $content = powerpress_remote_fopen($req_url, $this->settings['blubrry_auth'] ?? false, $PostArgs);
	            if (!$content && $api_url == 'https://api.blubrry.com/') { // Lets force cURL and see if that helps...
	                $content = powerpress_remote_fopen($req_url, $this->settings['blubrry_auth'] ?? false, $PostArgs, 15, false, true);
	            }
	            if ($content != false)
	                break;
	        }

	        if ($content) {
	            $Results = powerpress_json_decode($content);
	        }
	    }
	    if ($Results && is_array($Results))
	        return $Results;
		
		return array('error'=>__('Error occurred writing MP3 ID3 Tags.', 'powerpress') );
	}

	public function checkMediaPublished(string $filename, string $program_keyword)
	{

		// 1) BUILD API REQUEST URL
		$req_url = sprintf('/2/media/%s/index.json?published=true&cache=%s',
			urlencode($program_keyword),
			md5(rand(0, 999) . time())
		);
		$req_url .= (defined('POWERPRESS_BLUBRRY_API_QSA') ? '&' . POWERPRESS_BLUBRRY_API_QSA : '');

		// 2) FETCH PUBLISHED MEDIA LIST
		if ($this->creds) {
			$access_token = powerpress_getAccessToken();
			$results = $this->auth->api($access_token, $req_url);
		} else {
			$results = false;
			foreach (powerpress_get_api_array() as $api_url) {
				$full_url = rtrim($api_url, '/') . $req_url;
				$json_data = powerpress_fetch_with_retry($full_url, $this->settings['blubrry_auth'] ?? null);
				if ($json_data) {
					$results = powerpress_json_decode($json_data);
					break;
				}
			}
		}

		if (!is_array($results)) return false;


		// 3) FIND MATCHING FILENAME IN RESULTS
		foreach ($results as $media_item) {
			if (!is_array($media_item)) continue;

			if (!empty($media_item['published']) &&
				!empty($media_item['url']) &&
				!empty($media_item['name']) &&
				$media_item['name'] === $filename) {

				$result = [
					'url' => $media_item['url'],
					'length' => $media_item['length'] ?? 0,
					'published' => true
				];
				
				// ensure podcast_id is saved to post meta
				if (!empty($media_item['podcast_id'])) {
					$result['podcast_id'] = $media_item['podcast_id'];
				}
				return $result;
			}
		}

		return false;
	}

	private function buildAltEnclosurePostVars($alternate_enclosures, $post_title, $program_keyword, $feed_slug)
	{

		$api_alt_enclosures = [];

		foreach ($alternate_enclosures as $alternate_enclosure) {
			$process_main_enclosure = !empty($alternate_enclosure['hosting']) && $alternate_enclosure['hosting'] == '1';
			$has_uris_to_process = false;

			// uri processing
			$uris_to_publish = [];
			if (!empty($alternate_enclosure['uris']) && is_array($alternate_enclosure['uris'])) {
				foreach ($alternate_enclosure['uris'] as $uri_data) {
					if (is_array($uri_data)) {
						$uri_value = !empty($uri_data['uri']) ? $uri_data['uri'] : '';
						$uri_hosting = !empty($uri_data['hosting']) ? $uri_data['hosting'] : '';
					} 

					// process if hosting flag is set
					if (!empty($uri_hosting) && $uri_hosting == '1' && !empty($uri_value)) {
						// SSRF check
						if (!SSRFCheck($uri_value, $feed_slug, false, 'alternate enclosure URI')) continue;

						// Extract filename from URI
						$uri_filename = powerpress_extract_filename($uri_value);

						$uris_to_publish[] = [ 'filename' => $uri_filename ];
						$has_uris_to_process = true;

						// write id3 tags for mp3 files
						$is_mp3 = ($alternate_enclosure['type'] == 'audio/mpg' || $alternate_enclosure['type'] == 'audio/mpeg');
						if ($is_mp3 && !empty($this->settings['write_tags'])) {
							$results = $this->writeTags($uri_value, $post_title, $program_keyword);
							if (isset($results['error'])) {
								$error = __('Blubrry Hosting Error (ID3 tags for URI)', 'powerpress') . ': ' . $results['error'];
								powerpress_page_message_add_error($error);
							}
						}
					}
				}
			}

			if ($process_main_enclosure || $has_uris_to_process) {
				// api expects 'size' not 'length'
				$api_alt_enclosure = $alternate_enclosure;
				$api_alt_enclosure['size'] = $alternate_enclosure['length'];

				if ($process_main_enclosure) {
					// SSRF check
					if (!SSRFCheck($alternate_enclosure['url'], $feed_slug, false, 'alternate enclosure url')) continue;

					// api expects filename not full url
					$api_alt_enclosure['url'] = powerpress_extract_filename($alternate_enclosure['url']);

					// write id3 tags for mp3 alternate enclosures
					$this->processAltEnclosureTags($alternate_enclosure, $post_title, $program_keyword);
				}

				// add qualified uris array
				$api_alt_enclosure['uris'] = $uris_to_publish;

				$api_alt_enclosures[] = $api_alt_enclosure;
			}
		}

		return $api_alt_enclosures;
	}

	private function processAltEnclosureTags($alternate_enclosure, $post_title, $program_keyword)
	{

		$is_mp3 = ($alternate_enclosure['type'] == 'audio/mpg' || $alternate_enclosure['type'] == 'audio/mpeg');

		if ($is_mp3 && !empty($this->settings['write_tags'])) {
			$results = $this->writeTags($alternate_enclosure['url'], $post_title, $program_keyword);
			if (isset($results['error'])) {
				$error = __('Blubrry Hosting Error (alternate enclosure)', 'powerpress') . ': ' . $results['error'];
				powerpress_page_message_add_error($error);
			}
		}
	}

	// one report per post
    // nothing sent when media goes online
	private function reportFailure($post_id, $feed_slug, $program_keyword, $reason, $enclosure_url, $enclosure_size, $message = '') {
		static $reported = [];

		$key = "{$post_id}:{$feed_slug}";

		if (isset($reported[$key]) || empty($program_keyword))
			return;

		$reported[$key] = true;

		$context = [];

		if (is_admin()) $context[] = 'is_admin';
		if (defined('DOING_CRON') && DOING_CRON) $context[] = 'doing_cron';
		if (defined('ALTERNATE_WP_CRON') && ALTERNATE_WP_CRON) $context[] = 'alternate_wp_cron';
		if (defined('REST_REQUEST') && REST_REQUEST) $context[] = 'rest_request';
		if (defined('WP_CLI') && WP_CLI) $context[] = 'wp_cli';
		if (defined('XMLRPC_REQUEST') && XMLRPC_REQUEST) $context[] = 'xmlrpc';

		$post_vars = [
			'reason' => $reason,
			'post_id' => $post_id,
			'feed_slug' => $feed_slug,
			'url_form' => (strpos($enclosure_url, '://') === false ? 'bare_filename' : 'full_url'),
			'has_size' => (!empty($enclosure_size) ? 1 : 0),
			'powerpress_version' => POWERPRESS_VERSION,
			'wp_version' => get_bloginfo('version'),
			'php_version' => PHP_VERSION,
			'request_context' => implode(',', $context),
			'message' => substr($message, 0, 500),
		];

		if (!$this->sendReport($program_keyword, $post_vars))
			$this->queueReport($program_keyword, $post_vars);
	}

	private function sendReport($program_keyword, $post_vars) {
		$results = $this->apiRequest('/2/media/%s/diagnostic.json', [urlencode($program_keyword)], $post_vars, 15);

		return (is_array($results) && !empty($results['recorded']));
	}

	// a report about an unreachable api cant reach the api
    // hold it for the next publish
	private function queueReport($program_keyword, $post_vars) {
		$queue = get_option('powerpress_diagnostic_queue');

		if (!is_array($queue))
			$queue = [];

		if (count($queue) >= 20)
			return;

		$queue[] = ['program_keyword' => $program_keyword, 'report' => $post_vars];
		update_option('powerpress_diagnostic_queue', $queue);
	}

	private function drainReportQueue() {
		$queue = get_option('powerpress_diagnostic_queue');

		if (!is_array($queue) || !$queue)
			return;

		$remaining = [];

		foreach ($queue as $entry) {
			if (empty($entry['program_keyword']) || empty($entry['report']))
				continue;

			if (!$this->sendReport($entry['program_keyword'], $entry['report']))
				$remaining[] = $entry;
		}

		if ($remaining)
			update_option('powerpress_diagnostic_queue', $remaining);
		else
			delete_option('powerpress_diagnostic_queue');
	}

	private function updateAltEnclosureUrl(&$episode_data, $idx, $new_alt_url, $feed_slug, $program_keyword)
	{

		// SSRF check on new url
		if (!SSRFCheck($new_alt_url, $feed_slug, false, 'alternate enclosure url basename')) return;

		$episode_data['alternate_enclosure'][$idx]['url'] = $new_alt_url;
		
		// get media info for published file
		$alt_media_info = $this->mediaInfo($new_alt_url, $program_keyword);
		if (!empty($alt_media_info['length'])) {
			$episode_data['alternate_enclosure'][$idx]['length'] = $alt_media_info['length'];
		}
		
		// remove hosting flag since alternate enclosure is now published
		unset($episode_data['alternate_enclosure'][$idx]['hosting']);
	}
}

// ========================
// BACK COMPAT ENTRY POINTS
// ========================

function powerpress_process_hosting($post_id, $post_title)
{
	$hosting = new PowerPress_Hosting();

	return $hosting->process($post_id, $post_title);
}

function powerpress_api_request($endpoint_path, $url_params, $post_data, $settings, $creds, $auth, $api_url_array, $timeout = 1800)
{
	$hosting = new PowerPress_Hosting($settings, $creds, $auth, $api_url_array);

	return $hosting->apiRequest($endpoint_path, $url_params, $post_data, $timeout);
}

function powerpress_get_media_info($file, $program_Keyword = false)
{
	$hosting = new PowerPress_Hosting();

	return $hosting->mediaInfo($file, $program_Keyword);
}

function powerpress_check_media_published($filename, $program_keyword, $settings, $creds, $auth)
{
	$hosting = new PowerPress_Hosting($settings, $creds, $auth);

	return $hosting->checkMediaPublished($filename, $program_keyword);
}
