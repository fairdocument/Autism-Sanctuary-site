<?php
/**
 * Move donor mailing address under card details.
 * Restore archive-form notice-to logic (always asked; 4 options if in honor, 2 if not).
 *
 * Run: wp eval-file wordpress-migration/fix-donate-notice-logic.php
 */

if (!defined('ABSPATH')) {
	exit(1);
}

if (!class_exists('GFAPI')) {
	echo "Gravity Forms not available.\n";
	return;
}

echo "=== Fix donate notice logic + address placement ===\n";

$form_id = 0;
foreach (GFAPI::get_forms(true) as $f) {
	if ($f['title'] === 'Donate') {
		$form_id = (int) $f['id'];
		break;
	}
}
if (!$form_id) {
	echo "Donate form not found.\n";
	return;
}

$form = GFAPI::get_form($form_id);
$by_id = [];
foreach ($form['fields'] as $field) {
	$by_id[(int) $field->id] = $field;
}

if (empty($by_id[14])) {
	echo "Missing mailing address field #14\n";
	return;
}

// Honor-yes notice: 4 destinations (archive field 27).
if (!empty($by_id[15])) {
	$by_id[15]->label = 'Please send notice of this donation to';
	$by_id[15]->isRequired = true;
	$by_id[15]->choices = [
		['text' => 'My email address', 'value' => 'donor_email'],
		['text' => 'My mailing address', 'value' => 'donor_mail'],
		['text' => "Honoree's address", 'value' => 'honoree_mail'],
		['text' => 'Another address', 'value' => 'other_mail'],
	];
	$by_id[15]->conditionalLogic = [
		'actionType' => 'show',
		'logicType'  => 'all',
		'rules'      => [
			['fieldId' => '8', 'operator' => 'is', 'value' => 'yes'],
		],
	];
	echo "Updated notice field #15 (in honor)\n";
}

// Honor-no notice: email or donor mail only (archive field 28).
if (empty($by_id[20])) {
	$by_id[20] = GF_Fields::create([
		'type'             => 'select',
		'id'               => 20,
		'formId'           => $form_id,
		'label'            => 'Please send notice of this donation to',
		'isRequired'       => true,
		'choices'          => [
			['text' => 'My email address', 'value' => 'donor_email'],
			['text' => 'My mailing address', 'value' => 'donor_mail'],
		],
		'conditionalLogic' => [
			'actionType' => 'show',
			'logicType'  => 'all',
			'rules'      => [
				['fieldId' => '8', 'operator' => 'isnot', 'value' => 'yes'],
			],
		],
	]);
	echo "Added notice field #20 (not in honor)\n";
} else {
	$by_id[20]->label = 'Please send notice of this donation to';
	$by_id[20]->isRequired = true;
	$by_id[20]->choices = [
		['text' => 'My email address', 'value' => 'donor_email'],
		['text' => 'My mailing address', 'value' => 'donor_mail'],
	];
	$by_id[20]->conditionalLogic = [
		'actionType' => 'show',
		'logicType'  => 'all',
		'rules'      => [
			['fieldId' => '8', 'operator' => 'isnot', 'value' => 'yes'],
		],
	];
	echo "Updated notice field #20 (not in honor)\n";
}

if (!empty($by_id[16])) {
	$by_id[16]->conditionalLogic = [
		'actionType' => 'show',
		'logicType'  => 'all',
		'rules'      => [
			['fieldId' => '15', 'operator' => 'is', 'value' => 'honoree_mail'],
		],
	];
}
if (!empty($by_id[17])) {
	$by_id[17]->conditionalLogic = [
		'actionType' => 'show',
		'logicType'  => 'all',
		'rules'      => [
			['fieldId' => '15', 'operator' => 'is', 'value' => 'other_mail'],
		],
	];
}
if (!empty($by_id[18])) {
	$by_id[18]->conditionalLogic = [
		'actionType' => 'show',
		'logicType'  => 'all',
		'rules'      => [
			['fieldId' => '15', 'operator' => 'is', 'value' => 'other_mail'],
		],
	];
}

// Donor mailing address always collected (archive field 16), placed under card details.
$by_id[14]->conditionalLogic = '';
$by_id[14]->isRequired = true;
$by_id[14]->description = 'Used for our records and paper acknowledgments.';
echo "Mailing address #14 required, no conditional hide\n";

$desired = [1, 2, 3, 4, 13, 5, 6, 7, 8, 9, 15, 20, 16, 17, 18, 10, 11, 14, 12, 19];
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
echo "Updated Donate form #{$form_id}\n";

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
echo "Order: notice-to (honor/not) → extra honor addresses → card details → your mailing address\n";
