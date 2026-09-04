<?php
/**
 * Configure Hustle Pro newsletter signup (mirrors old-site "General newsletter").
 *
 * - SendGrid Marketing → "Web signups" list
 * - Embedded opt-in shortcode: [wd_hustle id="N" type="embedded"/]
 *
 * Run: wp eval-file wordpress-migration/setup-hustle-newsletter.php
 *
 * API key: uses Gravity SMTP SendGrid key if present, else AS_SENDGRID_API_KEY / $GLOBALS.
 */

if (!defined('ABSPATH')) {
	exit(1);
}

if (!class_exists('Hustle_Module_Model')) {
	echo "Hustle Pro is not loaded.\n";
	return;
}

$list_id = 'd6507184-f423-4780-917b-38ea8975f7a1';
$list_name = 'Web signups';

$api_key = '';
if (defined('AS_SENDGRID_API_KEY') && AS_SENDGRID_API_KEY) {
	$api_key = (string) AS_SENDGRID_API_KEY;
}
if ($api_key === '' && !empty($GLOBALS['as_sendgrid_api_key'])) {
	$api_key = (string) $GLOBALS['as_sendgrid_api_key'];
}
if ($api_key === '') {
	$gs = json_decode((string) get_option('gravitysmtp_sendgrid', ''), true);
	if (is_array($gs) && !empty($gs['api_key'])) {
		$api_key = (string) $gs['api_key'];
	}
}
if ($api_key === '' || strpos($api_key, 'SG.') !== 0) {
	echo "No SendGrid API key found (Gravity SMTP / AS_SENDGRID_API_KEY).\n";
	return;
}

// Global Hustle → SendGrid connection (Marketing Campaigns / new_campaigns).
$multi_id = 'as_web_signups_' . substr(md5($list_id), 0, 10);
$existing_provider = get_option('hustle_provider_sendgrid_settings', []);
if (!is_array($existing_provider)) {
	$existing_provider = [];
}
$existing_provider[$multi_id] = [
	'api_key'       => $api_key,
	'new_campaigns' => 'new_campaigns',
	'name'          => 'Online signups',
	'lists'         => [
		$list_id => $list_name,
	],
];
update_option('hustle_provider_sendgrid_settings', $existing_provider);
update_option('hustle_provider_sendgrid_version', '1.0');

$activated = get_option('hustle_activated_providers', []);
if (!is_array($activated)) {
	$activated = [];
}
foreach (['local_list', 'sendgrid'] as $slug) {
	if (!in_array($slug, $activated, true)) {
		$activated[] = $slug;
	}
}
update_option('hustle_activated_providers', $activated);
echo "Configured Hustle SendGrid provider → {$list_name}\n";

// Find or create embedded module.
global $wpdb;
$table = $wpdb->prefix . 'hustle_modules';
$module_id = (int) $wpdb->get_var(
	$wpdb->prepare(
		"SELECT module_id FROM {$table} WHERE module_name = %s AND module_type = %s LIMIT 1",
		'General newsletter',
		'embedded'
	)
);

$emails = [
	'after_successful_submission' => 'show_success',
	'success_message'             => '<p>Thank you for subscribing to Autism Sanctuary updates.</p>',
	'auto_close_success_message'  => '0',
	'form_elements'               => [
		'email'  => [
			'label'                   => 'Email',
			'required'                => 'true',
			'css_classes'             => '',
			'type'                    => 'email',
			'name'                    => 'email',
			'required_error_message'  => 'Your email is required.',
			'validation_message'      => 'Please enter a valid email.',
			'placeholder'             => 'you@example.com',
			'validate'                => 'true',
			'can_delete'              => 'false',
		],
		'submit' => [
			'label'                   => 'Subscribe',
			'required'                => 'true',
			'css_classes'             => '',
			'type'                    => 'submit',
			'name'                    => 'submit',
			'required_error_message'  => '',
			'validation_message'      => '',
			'placeholder'             => 'Subscribe',
			'error_message'           => 'Something went wrong, please try again.',
			'can_delete'              => 'false',
		],
	],
];

$design = [
	'style'                         => 'minimal',
	'form_layout'                   => 'one',
	'optin_form_layout'             => 'inline',
	'optin_form_layout_mobile'      => 'stacked',
	'color_palette'                 => 'gray_slate',
	'customize_colors'              => '1',
	'main_bg_color'                 => 'rgba(255,255,255,0)',
	'form_area_bg'                  => 'rgba(255,255,255,0)',
	'title_color'                   => '#1e3d2c',
	'subtitle_color'                => '#4a534c',
	'content_color'                 => '#4a534c',
	'optin_input_static_bg'         => '#ffffff',
	'optin_input_static_bo'         => '#d9d2c4',
	'optin_form_field_text_static_color' => '#1b1b1b',
	'optin_placeholder_color'       => '#4a534c',
	'optin_submit_button_static_bg' => '#2f5d43',
	'optin_submit_button_static_bo' => '#2f5d43',
	'optin_submit_button_static_color' => '#ffffff',
	'optin_submit_button_hover_bg'  => '#1e3d2c',
	'optin_submit_button_hover_bo'  => '#1e3d2c',
	'optin_submit_button_hover_color' => '#ffffff',
	'customize_css'                 => '1',
	'custom_css'                    => '.hustle-layout { background: transparent !important; box-shadow: none !important; }',
];

$content = [
	'module_name'  => 'General newsletter',
	'title'        => '',
	'sub_title'    => '',
	'main_content' => '',
	'show_cta'     => '0',
];

$display = [
	'inline_enabled'    => '0',
	'widget_enabled'    => '0',
	'shortcode_enabled' => '1',
];

$integrations_settings = [
	'allow_subscribed_users'      => '1',
	'disallow_submission_message' => 'This email address is already subscribed.',
	'active_integrations'         => 'local_list,sendgrid',
	'active_integrations_count'   => '2',
];

$integrations = [
	'local_list' => [
		'local_list_name' => 'General newsletter',
	],
	'sendgrid'   => [
		'selected_global_multi_id' => $multi_id,
		'list_id'                  => $list_id,
		'list_name'                => $list_name . ' (' . $list_id . ')',
	],
];

$data = [
	'module_name'            => 'General newsletter',
	'module_type'            => 'embedded',
	'module_mode'            => 'optin',
	'content'                => $content,
	'emails'                 => $emails,
	'design'                 => $design,
	'display'                => $display,
	'integrations_settings'  => $integrations_settings,
	'integrations'           => $integrations,
];

if ($module_id > 0) {
	$module = Hustle_Module_Model::get_module($module_id);
	if (is_wp_error($module) || !$module) {
		echo "Could not load existing module #{$module_id}\n";
		return;
	}
	$module->module_name = 'General newsletter';
	$module->module_type = 'embedded';
	$module->module_mode = 'optin';
	$module->active = 1;
	$module->save();
	$module->update_meta(Hustle_Module_Model::KEY_CONTENT, $content);
	$module->update_meta(Hustle_Module_Model::KEY_EMAILS, $emails);
	$module->update_meta(Hustle_Module_Model::KEY_DESIGN, $design);
	$module->update_meta(Hustle_Module_Model::KEY_DISPLAY_OPTIONS, $display);
	$module->update_meta(Hustle_Module_Model::KEY_INTEGRATIONS_SETTINGS, $integrations_settings);
	$module->set_provider_settings('local_list', $integrations['local_list']);
	$module->set_provider_settings('sendgrid', $integrations['sendgrid']);
	$module->enable_type_track_mode('embedded', true);
	echo "Updated Hustle module #{$module_id}\n";
} else {
	$module = Hustle_Module_Model::new_instance();
	$new_id = $module->create_new($data);
	if (!$new_id) {
		echo "Failed to create Hustle module.\n";
		return;
	}
	$module = Hustle_Module_Model::get_module($new_id);
	$module->active = 1;
	$module->save();
	$module->set_provider_settings('local_list', $integrations['local_list']);
	$module->set_provider_settings('sendgrid', $integrations['sendgrid']);
	$module_id = (int) $new_id;
	echo "Created Hustle module #{$module_id}\n";
}

update_option('as_hustle_newsletter_module_id', $module_id);
echo "Shortcode: [wd_hustle id=\"{$module_id}\" type=\"embedded\"/]\n";
echo "Done.\n";
