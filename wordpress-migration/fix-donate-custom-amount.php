<?php
/**
 * Fix Donate custom-amount product field.
 *
 * Bug: product field #6 was created with multi-input structure (6.1/6.2/6.3)
 * while inputType=price expects a single amount input. GF then rendered
 * value="Array", so totals stayed $0 and Stripe did not charge.
 *
 * Run: wp eval-file wordpress-migration/fix-donate-custom-amount.php
 */

if (!defined('ABSPATH')) {
	exit(1);
}

if (!class_exists('GFAPI')) {
	echo "Gravity Forms not available.\n";
	return;
}

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
if (empty($form['currency'])) {
	$form['currency'] = 'USD';
	echo "Set form currency to USD\n";
}

$fixed = false;
foreach ($form['fields'] as &$field) {
	if ((int) $field->id !== 6) {
		continue;
	}

	echo 'Before: inputType=' . $field->inputType . ' inputs=' . json_encode($field->inputs) . "\n";

	// User-defined price: one amount input (input_6), not 6.1/6.2/6.3.
	$field->type            = 'product';
	$field->inputType       = 'price';
	$field->inputs          = null;
	$field->basePrice       = '';
	$field->defaultValue    = '';
	$field->disableQuantity = true;
	$field->enablePrice     = false;
	$field->isRequired      = true;
	$field->label           = 'Custom amount';
	$field->conditionalLogic = [
		'actionType' => 'show',
		'logicType'  => 'all',
		'rules'      => [
			[
				'fieldId'  => '13',
				'operator' => 'is',
				'value'    => 'custom',
			],
		],
	];

	echo 'After: inputType=' . $field->inputType . ' inputs=' . json_encode($field->inputs) . "\n";
	$fixed = true;
}
unset($field);

if (!$fixed) {
	echo "Field #6 not found on form #{$form_id}\n";
	return;
}

$result = GFAPI::update_form($form);
if (is_wp_error($result)) {
	echo 'Update failed: ' . $result->get_error_message() . "\n";
	return;
}

$form = GFAPI::get_form($form_id);
foreach ($form['fields'] as $field) {
	if ((int) $field->id !== 6) {
		continue;
	}
	$html = $field->get_field_input($form, '');
	$has_array = strpos($html, "value='Array'") !== false || strpos($html, 'value="Array"') !== false;
	echo $has_array ? "FAIL: still rendering value=Array\n" : "OK: custom amount no longer renders Array\n";
	if (preg_match('/<input[^>]*class="[^"]*ginput_amount[^"]*"[^>]*>/', $html, $m)) {
		echo 'input: ' . $m[0] . "\n";
	}
}

echo "=== Done. Form #{$form_id} ===\n";
