<?php
/**
 * Merge tags for donor acknowledgment emails.
 * {donation_txn} → entry transaction_id (Stripe PaymentIntent / charge id)
 */
add_filter('gform_replace_merge_tags', static function ($text, $form, $entry) {
	if (!is_array($entry) || $text === '' || $text === null) {
		return $text;
	}
	if (strpos((string) $text, '{donation_txn}') === false) {
		return $text;
	}
	$txn = isset($entry['transaction_id']) ? (string) $entry['transaction_id'] : '';
	return str_replace('{donation_txn}', esc_html($txn), (string) $text);
}, 10, 3);
