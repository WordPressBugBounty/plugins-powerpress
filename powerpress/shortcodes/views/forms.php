<?php
$error = '';
$success = '';
if (!empty($_POST)) {
    if (!isset($_POST['ppn_nonce']) || !wp_verify_nonce($_POST['ppn_nonce'], 'ppn_application_submit')) {
        $error = 'Your session has expired. Please refresh the page and try again.';
    } else {
        if (!empty($props['tos_url']) && ($_POST['terms'] ?? '') !== 'agree') {
            $error = 'Please agree to the Terms and Conditions';
        }

        if (empty($error)) {
            $feedUrl = urlencode(esc_url_raw($_POST['feedUrl'] ?? ''));

            $post = false;
            $requestUrl = '/2/powerpress/network/' . $props['powerpress_network']['network_id'] . '/applicant/findshow?feedUrl='.$feedUrl ;
            $results = $GLOBALS['ppn_object']->requestAPI($requestUrl, true, $post);
            if (isset($results['program_id'])) {
                $requestUrl = '/2/powerpress/network/' . $props['powerpress_network']['network_id'] . '/applicant/submit';
                $requestUrl .= '?feedUrl=' . $results['program_rssurl']. '&programId=' . $results['program_id'];
                $requestUrl .= '&webName=' . $results['program_keyword'];
                $requestUrl .= '&listId=0';

                // new application fields, truncated to match db column limits
                $trim = function_exists('mb_substr')
                    ? function($s, $len) { return mb_substr($s, 0, $len, 'UTF-8'); }
                    : function($s, $len) { return substr($s, 0, $len); };
                $showName = urlencode($trim(sanitize_text_field($_POST['showName'] ?? ''), 255));
                $podcasterName = urlencode($trim(sanitize_text_field($_POST['podcasterName'] ?? ''), 255));
                $websiteUrl = urlencode($trim(esc_url_raw($_POST['websiteUrl'] ?? ''), 255));
                $listingUrl = urlencode($trim(esc_url_raw($_POST['listingUrl'] ?? ''), 255));
                $applicationNote = urlencode($trim(sanitize_textarea_field($_POST['applicationNote'] ?? ''), 255));
                $requestUrl .= "&showName={$showName}&podcasterName={$podcasterName}&websiteUrl={$websiteUrl}&listingUrl={$listingUrl}&applicationNote={$applicationNote}";

                $submit = $GLOBALS['ppn_object']->requestAPI($requestUrl, true, $post);
                if (isset($submit['danger'])) {
                    $error = 'Application could not be submitted. If you have not already submitted an application, please contact the network administrator.';
                } else {
                    $success = 'Application successfully submitted!';
                }
            } else {
                $error = isset($results['alert']) ?
                    $results['alert'] : "Show could not be found in Blubrry directory. Please double check your URL or contact Blubrry support.";
            }
        }
    }
}
?>
<?php // styles enqueued via powerpress_enqueue_assets in ShortCode.php ?>

<div class="ppn-form-div">
<?php if ($error != '') {
    ?>
    <div class="sub-error">
        <div class="sub-error-icon">
            <div class="sub-error-icon-check"></div>
        </div>
        <div class="sub-alert-text"><?php echo esc_html($error); ?></div>
    </div>
<?php }

if ($success != '') { 
    ?>
    <div class="sub-success">
        <div class="sub-success-icon">
            <div class="sub-success-icon-check"></div>
        </div>
        <div class="sub-alert-text"><?php echo $success; ?></div>
    </div>
    <?php
    $error = '';
} ?>
    <div class="sub-form">
        <form method="POST">
                <?php wp_nonce_field('ppn_application_submit', 'ppn_nonce'); ?>
                <div class="form-group">
                    <label for="feedUrl" class="sr-only">RSS Feed URL:</label>
                    <input id="feedUrl" name="feedUrl" type="url" class="form-control" placeholder="Your RSS feed URL (e.g. https://example.com/feed/podcast)" 
                           value="<?php echo !empty($error) ? esc_attr($_POST['feedUrl'] ?? '') : ''; ?>"  required autofocus>
                    <br/>

                    <label for="showName" class="sr-only">Podcast Name:</label>
                    <input id="showName" name="showName" class="form-control" placeholder="Podcast Name" maxlength="255" 
                           value="<?php echo !empty($error) ? esc_attr($_POST['showName'] ?? '') : ''; ?>" required>
                    <br/>

                    <label for="podcasterName" class="sr-only">Name:</label>
                    <input id="podcasterName" name="podcasterName" class="form-control" placeholder="Name" maxlength="255" 
                           value="<?php echo !empty($error) ? esc_attr($_POST['podcasterName'] ?? '') : ''; ?>" required>
                    <br/>

                    <label for="websiteUrl" class="sr-only">Podcast Website:</label>
                    <input id="websiteUrl" name="websiteUrl" type="url" class="form-control" placeholder="Podcast Website (optional)" 
                           value="<?php echo !empty($error) ? esc_attr($_POST['websiteUrl'] ?? '') : ''; ?>" maxlength="255">
                    <br/>

                    <label for="listingUrl" class="sr-only">Directory Listing URL:</label>
                    <input id="listingUrl" name="listingUrl" type="url" class="form-control" placeholder="Link to your listing on Apple Podcasts, Spotify, or similar (optional)" 
                           value="<?php echo !empty($error) ? esc_attr($_POST['listingUrl'] ?? '') : ''; ?>" maxlength="255">
                    <br/>

                    <label for="applicationNote" class="sr-only">Why You'd Be a Great Fit:</label>
                    <textarea id="applicationNote" name="applicationNote" class="form-control" rows="4" maxlength="255" 
                              value="<?php echo !empty($error) ? esc_textarea($_POST['applicationNote'] ?? '') : ''; ?>" placeholder="Share a bit about your show and what excites you about joining this network. (optional)"></textarea>
                    <br/>

                    <?php
                    $tosUrl = $props['tos_url'] ?? '';
                    if (!empty($tosUrl)):
                    ?>
                    <label for="terms" class="sr-only">Terms Agreement</label>
                    <input id="sub-checkbox" class="sub-checkbox-cl" type="checkbox" name="terms" value="agree" 
                        <?php if (!empty($error) && ($_POST['terms'] ?? '') === 'agree') echo 'checked'; ?> />
                    <label for="sub-checkbox" class="sub-checkbox-cl-label">I agree to network <a target="_blank" href="<?php echo esc_url($tosUrl); ?>">terms and
                        conditions</a>.
                    </label>
                    <br><br>
                    <?php endif; ?>

                    <button class="sub-btn" type="submit">Submit</button>
                </div>
        </form>
    </div>

</div>
