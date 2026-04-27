<?php // TODO: is this used? user is already authed via powerpress when they reach the network admin ?>
<div class="container">
    <h1 class="pageTitle"><?php esc_html_e('Signin Blubrry Account', 'powerpress');?></h1><br>
     <form method ="POST" action="" id="signinForm"> <!-- Make sure to keep back slash there for WordPress -->
        <button name="signinRequest" type="submit" class="button button-primary" id="signinButton"><?php esc_html_e('Login', 'powerpress');?></button>
		<input type="hidden" name="ppn-action" value="link-account" />
		<?php wp_nonce_field('powerpress', '_ppn_nonce'); ?>
     </form>
</div>
