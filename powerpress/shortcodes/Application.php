<?php

class PowerPressNetworkApplication extends PowerPressNetworkShortCode
{
    function __construct()
    {
        parent::__construct('ppn-application');
    }

    /** accepted attributes and their defaults, also the source for the editor legend */
    static function attributeDefaults(): array {
        return [
            'terms-url' => null,
        ];
    }

    /** one line on what this renders, shown above its attributes in the editor legend */
    static function shortcodeDescription(): string {
        return __('The form a podcaster fills in to ask to join the network.', 'powerpress');
    }

    /** a realistic use of this shortcode, shown under its attributes */
    static function shortcodeExample(): string {
        return '[ppn-application]';
    }

    /** what each attribute accepts, keyed the same as attributeDefaults() */
    static function attributeLegend(): array {
        return [
            'terms-url' => 'a url, overrides the network terms link',
        ];
    }

    /** attributes declared but wired to nothing, kept out of the legend */
    static function inertAttributes(): array {
        return [];
    }

    function ppn_shortcode($attr, $contents)
    {
        $ctx = $this->getNetworkContext();
        if (is_string($ctx)) return $ctx;

        $attr = shortcode_atts(self::attributeDefaults(), $attr, $this->tag);

        // shortcode attribute overrides the tos url held in the network option
        $tosFromDb = $ctx['networkInfo']['tos_url'] ?? '';

        $props = [
            'tos_url' => !empty($attr['terms-url']) ? (string) $attr['terms-url'] : $tosFromDb,
            'powerpress_network' => $ctx['networkInfo'],
            'post' => false,
        ];

        return PowerPressNetwork::getHTML('forms.php', $props, null);
    }
}

$GLOBALS['ppn_application'] = new PowerPressNetworkApplication();

