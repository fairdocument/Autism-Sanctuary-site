<?php
/**
 * Wire Gravity Forms Donate form to Stripe (one-time + monthly).
 * Run: wp eval-file wordpress-migration/setup-stripe-donate.php
 */

if (!defined('ABSPATH')) {
	exit(1);
}

if (!class_exists('GFAPI') || !function_exists('gf_stripe')) {
	echo "Gravity Forms Stripe is not available.\n";
	return;
}

echo "=== Stripe donate setup ===\n";

$thanks = get_page_by_path('donate/thanks');
$thanks_id = $thanks ? (int) $thanks->ID : 0;

$form = [
	'title'            => 'Donate',
	'description'      => '',
	'labelPlacement'   => 'top_label',
	'button'           => ['type' => 'text', 'text' => 'Give securely'],
	'enableHoneypot'   => true,
	'fields'           => [
		[
			'type'       => 'text',
			'id'         => 1,
			'label'      => 'Name',
			'isRequired' => true,
		],
		[
			'type'       => 'email',
			'id'         => 2,
			'label'      => 'Email',
			'isRequired' => true,
		],
		[
			'type'        => 'phone',
			'id'          => 3,
			'label'       => 'Phone',
			'isRequired'  => false,
			'phoneFormat' => 'standard',
		],
		[
			'type'          => 'radio',
			'id'            => 4,
			'label'         => 'Frequency',
			'isRequired'    => true,
			'choices'       => [
				['text' => 'One-time', 'value' => 'one-time', 'isSelected' => true],
				['text' => 'Monthly', 'value' => 'monthly'],
			],
		],
		[
			'type'    => 'checkbox',
			'id'      => 13,
			'label'   => 'Amount options',
			'choices' => [
				['text' => 'Enter a custom amount instead', 'value' => 'custom'],
			],
			'inputs'  => [
				['id' => '13.1', 'label' => 'Enter a custom amount instead'],
			],
		],
		[
			'type'            => 'product',
			'id'              => 5,
			'label'           => 'Gift amount',
			'inputType'       => 'radio',
			'isRequired'      => true,
			'enablePrice'     => true,
			'disableQuantity' => true,
			'choices'         => [
				['text' => '$25', 'value' => 'Donation - $25', 'price' => '25', 'isSelected' => true],
				['text' => '$50', 'value' => 'Donation - $50', 'price' => '50'],
				['text' => '$100', 'value' => 'Donation - $100', 'price' => '100'],
				['text' => '$250', 'value' => 'Donation - $250', 'price' => '250'],
			],
			'inputs'          => [
				['id' => '5.1', 'label' => 'Name'],
				['id' => '5.2', 'label' => 'Price'],
				['id' => '5.3', 'label' => 'Quantity'],
			],
			'conditionalLogic'=> [
				'actionType' => 'hide',
				'logicType'  => 'all',
				'rules'      => [
					[
						'fieldId'  => '13',
						'operator' => 'is',
						'value'    => 'custom',
					],
				],
			],
		],
		[
			'type'            => 'product',
			'id'              => 6,
			'label'           => 'Custom amount',
			'inputType'       => 'price',
			'isRequired'      => true,
			'disableQuantity' => true,
			'basePrice'       => '',
			'inputs'          => [
				['id' => '6.1', 'label' => 'Name'],
				['id' => '6.2', 'label' => 'Price'],
				['id' => '6.3', 'label' => 'Quantity'],
			],
			'conditionalLogic'=> [
				'actionType' => 'show',
				'logicType'  => 'all',
				'rules'      => [
					[
						'fieldId'  => '13',
						'operator' => 'is',
						'value'    => 'custom',
					],
				],
			],
		],
		[
			'type'       => 'select',
			'id'         => 7,
			'label'      => 'Apply my gift to',
			'isRequired' => true,
			'choices'    => [
				['text' => 'Where needed most', 'value' => 'where-needed'],
				['text' => 'Day program', 'value' => 'day-program'],
				['text' => 'Residential', 'value' => 'residential'],
				['text' => 'Agriculture and land', 'value' => 'agriculture'],
			],
		],
		[
			'type'    => 'checkbox',
			'id'      => 8,
			'label'   => 'Dedication',
			'choices' => [
				['text' => 'This gift is in honor or memory of someone', 'value' => 'yes'],
			],
			'inputs'  => [
				['id' => '8.1', 'label' => 'This gift is in honor or memory of someone'],
			],
		],
		[
			'type'             => 'text',
			'id'               => 9,
			'label'            => 'In honor / memory of',
			'isRequired'       => true,
			'conditionalLogic' => [
				'actionType' => 'show',
				'logicType'  => 'all',
				'rules'      => [
					[
						'fieldId'  => '8',
						'operator' => 'is',
						'value'    => 'yes',
					],
				],
			],
		],
		[
			'type'       => 'textarea',
			'id'         => 10,
			'label'      => 'Message (optional)',
			'isRequired' => false,
		],
		[
			'type'       => 'stripe_creditcard',
			'id'         => 11,
			'label'      => 'Payment',
			'isRequired' => true,
			'inputs'     => [
				['id' => '11.1', 'label' => 'Card Details'],
				['id' => '11.4', 'label' => 'Card Type'],
				['id' => '11.5', 'label' => 'Cardholder Name'],
			],
		],
		[
			'type'  => 'total',
			'id'    => 12,
			'label' => 'Total',
		],
	],
	'confirmations' => [
		[
			'id'        => wp_generate_uuid4(),
			'name'      => 'Default Confirmation',
			'isDefault' => true,
			'type'      => $thanks_id ? 'page' : 'message',
			'pageId'    => $thanks_id ? (string) $thanks_id : '',
			'message'   => 'Thank you for your gift to Autism Sanctuary.',
		],
	],
	'notifications' => [
		[
			'id'       => wp_generate_uuid4(),
			'name'     => 'Admin Notification',
			'event'    => 'form_submission',
			'to'       => 'info@autismsanctuary.org',
			'toType'   => 'email',
			'subject'  => 'New donation: {Name:1} — {Total:12}',
			'message'  => '{all_fields}',
			'from'     => '{admin_email}',
			'fromName' => 'Autism Sanctuary Website',
			'replyTo'  => '{Email:2}',
			'isActive' => true,
		],
	],
];

// Ensure form is active before write.
$existing_id = 0;
foreach (GFAPI::get_forms(true) as $f) {
	if (in_array($f['title'], ['Donate', 'Donate Inquiry'], true)) {
		$existing_id = (int) $f['id'];
		break;
	}
}

if ($existing_id) {
	$form['id'] = $existing_id;
	$form['is_active'] = '1';
	$result = GFAPI::update_form($form);
	if (is_wp_error($result)) {
		echo 'Form update failed: ' . $result->get_error_message() . "\n";
		return;
	}
	$form_id = $existing_id;
	echo "Updated form #{$form_id}\n";
} else {
	$form_id = GFAPI::add_form($form);
	if (is_wp_error($form_id)) {
		echo 'Form create failed: ' . $form_id->get_error_message() . "\n";
		return;
	}
	$form_id = (int) $form_id;
	echo "Created form #{$form_id}\n";
}

if (class_exists('GFFormsModel')) {
	GFFormsModel::update_form_active($form_id, true);
}

// Remove existing Stripe feeds on this form, then add fresh ones.
$existing_feeds = GFAPI::get_feeds(null, $form_id, 'gravityformsstripe', null);
if (!is_wp_error($existing_feeds) && is_array($existing_feeds)) {
	foreach ($existing_feeds as $feed) {
		GFAPI::delete_feed((int) $feed['id']);
		echo "Deleted old feed #{$feed['id']}\n";
	}
}

$one_time_meta = [
	'feedName'                                => 'One-time donation',
	'transactionType'                         => 'product',
	'paymentAmount'                           => 'form_total',
	'receipt_field'                           => '2',
	'customerInformation_email'               => '2',
	'feed_condition_conditional_logic'        => '1',
	'feed_condition_conditional_logic_object' => [
		'conditionalLogic' => [
			'actionType' => 'show',
			'logicType'  => 'all',
			'rules'      => [
				[
					'fieldId'  => '4',
					'operator' => 'is',
					'value'    => 'one-time',
				],
			],
		],
	],
	'metaData'                                => [
		[
			'key'        => 'gf_custom',
			'custom_key' => 'fund',
			'value'      => '7',
		],
		[
			'key'        => 'gf_custom',
			'custom_key' => 'frequency',
			'value'      => '4',
		],
		[
			'key'        => 'gf_custom',
			'custom_key' => 'honoree',
			'value'      => '9',
		],
	],
];

$monthly_meta = [
	'feedName'                                => 'Monthly donation',
	'transactionType'                         => 'subscription',
	'recurringAmount'                         => 'form_total',
	'billingCycle_length'                     => '1',
	'billingCycle_unit'                       => 'month',
	'subscription_name'                       => 'Autism Sanctuary monthly gift',
	'setupFee_enabled'                        => '0',
	'trial_enabled'                           => '0',
	'receipt_field'                           => '2',
	'customerInformation_email'               => '2',
	'customerInformation_description'         => '1',
	'feed_condition_conditional_logic'        => '1',
	'feed_condition_conditional_logic_object' => [
		'conditionalLogic' => [
			'actionType' => 'show',
			'logicType'  => 'all',
			'rules'      => [
				[
					'fieldId'  => '4',
					'operator' => 'is',
					'value'    => 'monthly',
				],
			],
		],
	],
	'metaData'                                => [
		[
			'key'        => 'gf_custom',
			'custom_key' => 'fund',
			'value'      => '7',
		],
		[
			'key'        => 'gf_custom',
			'custom_key' => 'frequency',
			'value'      => '4',
		],
		[
			'key'        => 'gf_custom',
			'custom_key' => 'honoree',
			'value'      => '9',
		],
	],
];

$feed1 = GFAPI::add_feed($form_id, $one_time_meta, 'gravityformsstripe');
if (is_wp_error($feed1)) {
	echo 'One-time feed error: ' . $feed1->get_error_message() . "\n";
} else {
	echo "One-time Stripe feed #{$feed1}\n";
}

$feed2 = GFAPI::add_feed($form_id, $monthly_meta, 'gravityformsstripe');
if (is_wp_error($feed2)) {
	echo 'Monthly feed error: ' . $feed2->get_error_message() . "\n";
} else {
	echo "Monthly Stripe feed #{$feed2}\n";
}

// Update Donate page copy — remove “Stripe coming soon” note.
$donate = get_page_by_path('donate');
if ($donate) {
	$content = $donate->post_content;
	$intro = '<p>Give securely with Stripe—one-time or monthly—and choose how you would like your gift used.</p>
<div class="as-grid as-grid--3" style="margin:2rem 0">
  <div class="as-feature"><h3>Care farming infrastructure</h3><p>Trails, barns, gardens, and sensory spaces that make outdoor days possible.</p></div>
  <div class="as-feature"><h3>Participant access</h3><p>Support that helps people with significant needs take part fully in farm life.</p></div>
  <div class="as-feature"><h3>Workforce strength</h3><p>Training and retention for DSPs who show up in boots, rain or shine.</p></div>
</div>
[gravityform id="' . $form_id . '" title="false" description="false" ajax="true"]';

	// Replace body inside prose wrapper while keeping banner.
	if (preg_match('/(<section class="as-banner">.*?<\/section>)/s', $content, $m)) {
		$banner = $m[1];
		$new = $banner . "\n<div class=\"as-page as-prose\">" . $intro . '</div>';
		wp_update_post([
			'ID'           => $donate->ID,
			'post_content' => $new,
		]);
		echo "Donate page content updated\n";
	} else {
		wp_update_post([
			'ID'           => $donate->ID,
			'post_content' => $donate->post_content . "\n" . '[gravityform id="' . $form_id . '" title="false" description="false" ajax="true"]',
		]);
		echo "Donate page shortcode appended\n";
	}
}

// Keep Inquiry form (#1) for contact/admissions/careers — untouched.

echo "=== Done. Form ID {$form_id} ===\n";
echo "Test a small gift on /donate/ (use Stripe test mode if the feed is set to test).\n";
