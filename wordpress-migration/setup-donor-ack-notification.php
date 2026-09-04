<?php
/**
 * Add robust donor acknowledgment emails for Donate form.
 * Sends after Stripe payment succeeds (one-time) or subscription is created (monthly).
 * Copies olivia@autismsanctuary.org.
 *
 * Run: wp eval-file wordpress-migration/setup-donor-ack-notification.php
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

$ack_body = <<<'HTML'
<p>Dear {Name:1},</p>

<p>Thank you for your generous gift to Autism Sanctuary. Your support helps us sustain our care farm, programs, and the people we serve in Western Albemarle.</p>

<p><strong>Gift summary</strong></p>
<ul>
<li><strong>Amount:</strong> ${payment_amount}</li>
<li><strong>Frequency:</strong> {Frequency:4}</li>
<li><strong>Designation:</strong> {Apply my gift to:7}</li>
<li><strong>Date:</strong> {payment_date}</li>
<li><strong>Transaction ID:</strong> {transaction_id}</li>
</ul>

{In honor / memory of:9 before="<p><strong>In honor / memory of:</strong> " after="</p>"}
{Message (optional):10 before="<p><strong>Your message:</strong> " after="</p>"}

<p>Autism Sanctuary is a Virginia 501(c)(3) nonprofit and DBHDS-licensed care farm. No goods or services were provided in exchange for this contribution. Please retain this email as your donation receipt for tax purposes.</p>

<p>If you have any questions about your gift, reply to this email or contact us at <a href="mailto:info@autismsanctuary.org">info@autismsanctuary.org</a> · (434) 207-2118.</p>

<p>With gratitude,<br>
Autism Sanctuary<br>
2860 Pea Ridge Road<br>
Charlottesville, VA 22901<br>
<a href="https://www.autismsanctuary.org/">autismsanctuary.org</a></p>
HTML;

$base = [
	'toType'             => 'field',
	'toField'            => '2',
	'to'                 => '2',
	'toEmail'            => '',
	'cc'                 => 'olivia@autismsanctuary.org',
	'bcc'                => '',
	'from'               => 'info@autismsanctuary.org',
	'fromName'           => 'Autism Sanctuary',
	'replyTo'            => 'info@autismsanctuary.org',
	'subject'            => 'Thank you for your gift to Autism Sanctuary — ${payment_amount}',
	'message'            => $ack_body,
	'disableAutoformat'  => false,
	'enableAttachments'  => false,
	'isActive'           => true,
	'service'            => 'wordpress',
	'conditionalLogic'   => null,
	'notification_conditional_logic' => '0',
	'notification_conditional_logic_object' => '',
];

$wanted = [
	'donor-ack-payment' => array_merge($base, [
		'name'  => 'Donor Acknowledgment (Payment Completed)',
		'event' => 'complete_payment',
	]),
	'donor-ack-subscription' => array_merge($base, [
		'name'  => 'Donor Acknowledgment (Monthly Gift Started)',
		'event' => 'create_subscription',
		'subject' => 'Thank you for your monthly gift to Autism Sanctuary — ${payment_amount}',
		'message' => str_replace(
			'Thank you for your generous gift to Autism Sanctuary.',
			'Thank you for starting a monthly gift to Autism Sanctuary.',
			$ack_body
		),
	]),
];

// Remove prior versions of these notifications (by name) so re-runs stay idempotent.
$notifications = is_array($form['notifications']) ? $form['notifications'] : [];
foreach ($notifications as $id => $n) {
	$name = $n['name'] ?? '';
	if (strpos($name, 'Donor Acknowledgment') === 0) {
		unset($notifications[$id]);
		echo "Removed old notification: {$name}\n";
	}
}

foreach ($wanted as $key => $notif) {
	$id = wp_generate_uuid4();
	$notif['id'] = $id;
	$notifications[$id] = $notif;
	echo "Added: {$notif['name']} [{$notif['event']}] id={$id}\n";
}

$form['notifications'] = $notifications;
$result = GFAPI::update_form($form);
if (is_wp_error($result)) {
	echo 'Update failed: ' . $result->get_error_message() . "\n";
	return;
}

$form = GFAPI::get_form($form_id);
echo "=== Donate form #{$form_id} notifications ===\n";
foreach ($form['notifications'] as $n) {
	$active = !empty($n['isActive']) ? 'on' : 'off';
	$cc = $n['cc'] ?? '';
	$to = ($n['toType'] ?? '') === 'field' ? ('field:' . ($n['toField'] ?? $n['to'] ?? '')) : ($n['to'] ?? '');
	echo "- {$n['name']} | event={$n['event']} | to={$to} | cc={$cc} | active={$active}\n";
}

echo "=== Done ===\n";
