<?php
/**
 * Add "How did you hear about us" to the Inquiry (contact) form.
 *
 * Run: wp eval-file wordpress-migration/add-contact-hear-about.php
 */

if (!defined('ABSPATH')) {
	exit(1);
}

if (!class_exists('GFAPI')) {
	echo "Gravity Forms not available.\n";
	return;
}

echo "=== Add hear-about-us to Inquiry form ===\n";

$form_id = 0;
foreach (GFAPI::get_forms(true) as $f) {
	if ($f['title'] === 'Inquiry') {
		$form_id = (int) $f['id'];
		break;
	}
}
if (!$form_id) {
	echo "Inquiry form not found.\n";
	return;
}

$form = GFAPI::get_form($form_id);
$by_id = [];
foreach ($form['fields'] as $field) {
	$by_id[(int) $field->id] = $field;
}

$hear_choices = [
	['text' => 'Friend or family', 'value' => 'friend-family'],
	['text' => 'Another family or participant', 'value' => 'another-family'],
	['text' => 'Support coordinator or CSB', 'value' => 'support-coordinator'],
	['text' => 'Healthcare or service provider', 'value' => 'provider'],
	['text' => 'Social media', 'value' => 'social-media'],
	['text' => 'Search engine', 'value' => 'search'],
	['text' => 'Event or farmers market', 'value' => 'event'],
	['text' => 'News or media', 'value' => 'news'],
	['text' => 'Other', 'value' => 'other'],
];

if (empty($by_id[6])) {
	$by_id[6] = GF_Fields::create([
		'type'        => 'select',
		'id'          => 6,
		'formId'      => $form_id,
		'label'       => 'How did you hear about us',
		'isRequired'  => false,
		'placeholder' => 'Select one',
		'choices'     => $hear_choices,
	]);
	echo "Added field #6 How did you hear about us\n";
} else {
	$by_id[6]->label = 'How did you hear about us';
	$by_id[6]->choices = $hear_choices;
	$by_id[6]->placeholder = 'Select one';
	$by_id[6]->isRequired = false;
	echo "Updated field #6\n";
}

if (empty($by_id[7])) {
	$by_id[7] = GF_Fields::create([
		'type'             => 'text',
		'id'               => 7,
		'formId'           => $form_id,
		'label'            => 'Please tell us more',
		'isRequired'       => false,
		'conditionalLogic' => [
			'actionType' => 'show',
			'logicType'  => 'all',
			'rules'      => [
				['fieldId' => '6', 'operator' => 'is', 'value' => 'other'],
			],
		],
	]);
	echo "Added field #7 Please tell us more (shows when Other)\n";
} else {
	$by_id[7]->label = 'Please tell us more';
	$by_id[7]->conditionalLogic = [
		'actionType' => 'show',
		'logicType'  => 'all',
		'rules'      => [
			['fieldId' => '6', 'operator' => 'is', 'value' => 'other'],
		],
	];
	echo "Updated field #7\n";
}

$desired = [1, 2, 3, 4, 6, 7, 5];
$new = [];
$seen = [];
foreach ($desired as $id) {
	if (empty($by_id[$id])) {
		continue;
	}
	$new[] = $by_id[$id];
	$seen[$id] = true;
}
foreach ($form['fields'] as $field) {
	$id = (int) $field->id;
	if (empty($seen[$id])) {
		$new[] = $field;
	}
}
$form['fields'] = $new;

$result = GFAPI::update_form($form);
if (is_wp_error($result)) {
	echo 'Form update failed: ' . $result->get_error_message() . "\n";
	return;
}
echo "Updated Inquiry form #{$form_id}\n";

if (function_exists('wp_cache_flush')) {
	wp_cache_flush();
}
if (class_exists('\\Hummingbird\\Core\\Utils')) {
	$mod = \Hummingbird\Core\Utils::get_module('page_cache');
	if ($mod && method_exists($mod, 'clear_cache')) {
		$mod->clear_cache();
		echo "Cleared Hummingbird page cache\n";
	}
}

echo "=== Done ===\n";
