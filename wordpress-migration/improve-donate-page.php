<?php
/**
 * Add mailing address to Donate page + restore old-form address/honor fields.
 *
 * Run: wp eval-file wordpress-migration/improve-donate-page.php
 */

if (!defined('ABSPATH')) {
	exit(1);
}

if (!class_exists('GFAPI')) {
	echo "Gravity Forms not available.\n";
	return;
}

echo "=== Improve donate page ===\n";

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
$existing_ids = [];
foreach ($form['fields'] as $field) {
	$existing_ids[(int) $field->id] = true;
}

$fund_choices = [
	['text' => 'Where needed most', 'value' => 'where-needed'],
	['text' => 'Day program developments and needs', 'value' => 'day-program'],
	['text' => 'Residential initiatives (group home, supported living)', 'value' => 'residential'],
	['text' => 'Agriculture and land management (livestock, high tunnel/produce, equipment)', 'value' => 'agriculture'],
];

foreach ($form['fields'] as $field) {
	if ((int) $field->id === 7) {
		$field->label = 'Please use my donation towards';
		$field->choices = $fund_choices;
		echo "Updated fund labels on field #7\n";
	}
}

function as_donate_gf_field(array $props) {
	return GF_Fields::create($props);
}

function as_donate_address_props($id, $label, $conditional = null, $description = '') {
	$props = [
		'type'        => 'address',
		'id'          => (int) $id,
		'label'       => $label,
		'isRequired'  => true,
		'addressType' => 'us',
		'description' => $description,
		'inputs'      => [
			['id' => $id . '.1', 'label' => 'Street Address'],
			['id' => $id . '.2', 'label' => 'Address Line 2'],
			['id' => $id . '.3', 'label' => 'City'],
			['id' => $id . '.4', 'label' => 'State'],
			['id' => $id . '.5', 'label' => 'ZIP Code'],
			['id' => $id . '.6', 'label' => 'Country', 'isHidden' => true],
		],
	];
	if ($conditional) {
		$props['conditionalLogic'] = $conditional;
	}
	return $props;
}

$honor_show = [
	'actionType' => 'show',
	'logicType'  => 'all',
	'rules'      => [
		['fieldId' => '8', 'operator' => 'is', 'value' => 'yes'],
	],
];

$new_field_defs = [
	14 => as_donate_address_props(
		14,
		'Your mailing address',
		null,
		'Used for our records and paper acknowledgments.'
	),
	15 => [
		'type'             => 'select',
		'id'               => 15,
		'label'            => 'Please send notice of this donation to',
		'isRequired'       => true,
		'choices'          => [
			['text' => 'My email address', 'value' => 'donor_email'],
			['text' => 'My mailing address', 'value' => 'donor_mail'],
			['text' => "Honoree's address", 'value' => 'honoree_mail'],
			['text' => 'Another address', 'value' => 'other_mail'],
		],
		'conditionalLogic' => $honor_show,
	],
	20 => [
		'type'             => 'select',
		'id'               => 20,
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
	],
	16 => as_donate_address_props(16, "Honoree's address", [
		'actionType' => 'show',
		'logicType'  => 'all',
		'rules'      => [
			['fieldId' => '15', 'operator' => 'is', 'value' => 'honoree_mail'],
		],
	]),
	17 => [
		'type'             => 'name',
		'id'               => 17,
		'label'            => 'Recipient name',
		'isRequired'       => true,
		'nameFormat'       => 'extended',
		'inputs'           => [
			['id' => '17.2', 'label' => 'Prefix', 'isHidden' => true, 'inputType' => 'radio', 'choices' => [
				['text' => 'Mr.', 'value' => 'Mr.'],
				['text' => 'Mrs.', 'value' => 'Mrs.'],
				['text' => 'Miss', 'value' => 'Miss'],
				['text' => 'Ms.', 'value' => 'Ms.'],
				['text' => 'Dr.', 'value' => 'Dr.'],
			]],
			['id' => '17.3', 'label' => 'First'],
			['id' => '17.4', 'label' => 'Middle', 'isHidden' => true],
			['id' => '17.6', 'label' => 'Last'],
			['id' => '17.8', 'label' => 'Suffix', 'isHidden' => true],
		],
		'conditionalLogic' => [
			'actionType' => 'show',
			'logicType'  => 'all',
			'rules'      => [
				['fieldId' => '15', 'operator' => 'is', 'value' => 'other_mail'],
			],
		],
	],
	18 => as_donate_address_props(18, 'Recipient address', [
		'actionType' => 'show',
		'logicType'  => 'all',
		'rules'      => [
			['fieldId' => '15', 'operator' => 'is', 'value' => 'other_mail'],
		],
	]),
	19 => [
		'type'    => 'html',
		'id'      => 19,
		'label'   => 'Tax note',
		'content' => '<p class="as-donate-tax-note">Autism Sanctuary is a Virginia 501(c)(3) nonprofit (EIN 84-4794206). Card payments are processed securely by Stripe. You will receive a receipt for tax substantiation.</p>',
	],
];

$created = [];
foreach ($new_field_defs as $id => $props) {
	if (!empty($existing_ids[$id])) {
		echo "Field #{$id} already exists — left in place\n";
		continue;
	}
	$created[$id] = as_donate_gf_field($props);
	echo "Prepared field #{$id} ({$props['type']})\n";
}

if ($created) {
	$reordered = [];
	foreach ($form['fields'] as $field) {
		$id = (int) $field->id;
		$reordered[] = $field;
		if ($id === 9) {
			foreach ([15, 20, 16, 17, 18] as $nid) {
				if (isset($created[$nid])) {
					$reordered[] = $created[$nid];
					unset($created[$nid]);
				}
			}
		}
		if ($id === 11 && isset($created[14])) {
			$reordered[] = $created[14];
			unset($created[14]);
		}
		if ($id === 12 && isset($created[19])) {
			$reordered[] = $created[19];
			unset($created[19]);
		}
	}
	foreach ($created as $leftover) {
		$reordered[] = $leftover;
	}
	$form['fields'] = $reordered;
}

$result = GFAPI::update_form($form);
if (is_wp_error($result)) {
	echo 'Form update failed: ' . $result->get_error_message() . "\n";
	return;
}
echo "Updated Donate form #{$form_id}\n";

$give_by_mail = <<<'HTML'
<div class="as-give-by-mail">
<h2>Give by mail</h2>
<p>Prefer to send a check? Mail it to:</p>
<address>
<strong>Autism Sanctuary</strong><br />
2860 Pea Ridge Road<br />
Charlottesville, VA 22901
</address>
<p><a href="https://maps.google.com/?q=2860+Pea+Ridge+Road,+Charlottesville,+VA+22901" rel="noopener noreferrer" target="_blank">Get directions</a></p>
<p>Please make checks payable to Autism Sanctuary. We are a Virginia 501(c)(3) nonprofit (EIN 84-4794206). Gifts are tax-deductible to the extent allowed by law.</p>
</div>
HTML;

$page = get_page_by_path('donate');
if (!$page) {
	echo "Donate page missing\n";
	return;
}

$backup_dir = WP_CONTENT_DIR . '/as-donate-improve-backup-' . gmdate('Ymd-His');
wp_mkdir_p($backup_dir);
file_put_contents("{$backup_dir}/donate.raw.txt", $page->post_content);
echo "Backup: {$backup_dir}\n";

$content = $page->post_content;
if (strpos($content, 'as-give-by-mail') !== false) {
	echo "Give-by-mail block already present\n";
} else {
	$patched = preg_replace_callback(
		'/("innerContent":\{"desktop":\{"value":")(.*?)("\}\})/s',
		static function ($m) use ($give_by_mail) {
			$raw = $m[2];
			$html = json_decode('"' . str_replace(["\n", "\r"], ['\\n', ''], $raw) . '"');
			if (!is_string($html) || strpos($html, 'Looking ahead') === false) {
				return $m[0];
			}
			if (strpos($html, 'as-give-by-mail') !== false) {
				return $m[0];
			}
			if (strpos($html, '</figure>') !== false) {
				$html = str_replace('</figure>', "</figure>\n" . $give_by_mail, $html);
			} else {
				$html .= "\n" . $give_by_mail;
			}
			$enc = substr(json_encode($html, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 1, -1);
			return $m[1] . $enc . $m[3];
		},
		$content
	);
	if (!$patched || $patched === $content) {
		echo "WARN: could not splice give-by-mail into Divi text; appending shortcode HTML fallback\n";
	} else {
		wp_update_post([
			'ID'           => $page->ID,
			'post_content' => wp_slash($patched),
		]);
		echo "Inserted give-by-mail address on /donate/ (#{$page->ID})\n";
		$content = $patched;
	}
}

if (function_exists('wp_cache_flush')) {
	wp_cache_flush();
}

echo "=== Done ===\n";
