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

$logo = 'https://www.autismsanctuary.org/wp-content/uploads/2026/08/autism-sanctuary-logo-300x284.png';

$ack_body = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"></head>
<body style="margin:0;padding:0;background:#f3efe4;font-family:'Source Sans 3',Helvetica,Arial,sans-serif;color:#1b1b1b;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f3efe4;padding:32px 12px;">
  <tr>
    <td align="center">
      <table role="presentation" width="560" cellpadding="0" cellspacing="0" border="0" style="max-width:560px;width:100%;background:#ffffff;border:1px solid #d9d2c4;border-radius:6px;overflow:hidden;">
        <tr>
          <td align="center" style="background:#ffffff;padding:28px 24px 16px;border-bottom:1px solid #d9d2c4;">
            <img src="{$logo}" width="120" height="114" alt="Autism Sanctuary" style="display:block;width:120px;height:auto;border:0;margin:0 auto 14px;">
            <p style="margin:0;font-family:Georgia,'Times New Roman',serif;font-size:22px;line-height:1.3;color:#2f5d43;font-weight:600;">Thank you for your gift</p>
          </td>
        </tr>
        <tr>
          <td style="padding:28px 28px 8px;font-size:16px;line-height:1.65;color:#1b1b1b;">
            <p style="margin:0 0 16px;">Dear {Name:1},</p>
            <p style="margin:0 0 16px;">Thank you for your generous gift to Autism Sanctuary. Your support helps us sustain our care farm, programs, and the people we serve in Western Albemarle.</p>
            <p style="margin:0 0 8px;font-family:Georgia,'Times New Roman',serif;font-size:18px;color:#2f5d43;font-weight:600;">Gift summary</p>
          </td>
        </tr>
        <tr>
          <td style="padding:0 28px 8px;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f7f4ec;border:1px solid #d9d2c4;border-radius:4px;">
              <tr>
                <td style="padding:14px 16px;font-size:15px;line-height:1.6;color:#1b1b1b;">
                  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                    <tr>
                      <td style="padding:4px 0;color:#4a534c;width:38%;">Amount</td>
                      <td style="padding:4px 0;font-weight:600;">{Total:12}</td>
                    </tr>
                    <tr>
                      <td style="padding:4px 0;color:#4a534c;">Frequency</td>
                      <td style="padding:4px 0;font-weight:600;">{Frequency:4}</td>
                    </tr>
                    <tr>
                      <td style="padding:4px 0;color:#4a534c;">Designation</td>
                      <td style="padding:4px 0;font-weight:600;">{Apply my gift to:7}</td>
                    </tr>
                    <tr>
                      <td style="padding:4px 0;color:#4a534c;">Date</td>
                      <td style="padding:4px 0;font-weight:600;">{payment_date}</td>
                    </tr>
                    <tr>
                      <td style="padding:4px 0;color:#4a534c;vertical-align:top;">Transaction ID</td>
                      <td style="padding:4px 0;font-weight:600;font-size:13px;word-break:break-all;">{donation_txn}</td>
                    </tr>
                  </table>
                </td>
              </tr>
            </table>
          </td>
        </tr>
        <tr>
          <td style="padding:8px 28px 0;font-size:15px;line-height:1.65;color:#1b1b1b;">
            <p style="margin:12px 0 0;">{In honor / memory of:9}</p>
            <p style="margin:8px 0 0;">{Message (optional):10}</p>
          </td>
        </tr>
        <tr>
          <td style="padding:20px 28px 8px;font-size:15px;line-height:1.65;color:#1b1b1b;">
            <p style="margin:0 0 16px;">Autism Sanctuary is a Virginia 501(c)(3) nonprofit and DBHDS-licensed care farm. No goods or services were provided in exchange for this contribution. Please retain this email as your donation receipt for tax purposes.</p>
            <p style="margin:0 0 16px;">Questions about your gift? Reply to this email or contact us at <a href="mailto:info@autismsanctuary.org" style="color:#2f5d43;">info@autismsanctuary.org</a> · <a href="tel:+14342072118" style="color:#2f5d43;">(434) 207-2118</a>.</p>
            <p style="margin:0 0 4px;">With gratitude,</p>
            <p style="margin:0;font-family:Georgia,'Times New Roman',serif;font-size:18px;color:#2f5d43;font-weight:600;">Autism Sanctuary</p>
            <p style="margin:8px 0 0;font-size:14px;line-height:1.5;color:#4a534c;">
              2860 Pea Ridge Road<br>
              Charlottesville, VA 22901<br>
              <a href="https://www.autismsanctuary.org/" style="color:#2f5d43;">autismsanctuary.org</a>
            </p>
          </td>
        </tr>
        <tr>
          <td style="padding:20px 28px 28px;font-size:12px;line-height:1.5;color:#4a534c;border-top:1px solid #d9d2c4;">
            This receipt confirms your gift to Autism Sanctuary. Thank you for supporting care farming and community.
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
</body>
</html>
HTML;

$monthly_body = str_replace(
	'Thank you for your generous gift to Autism Sanctuary.',
	'Thank you for starting a monthly gift to Autism Sanctuary.',
	$ack_body
);
$monthly_body = str_replace(
	'Thank you for your gift',
	'Thank you for your monthly gift',
	$monthly_body
);

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
	'subject'            => 'Thank you for your gift to Autism Sanctuary — {Total:12}',
	'message'            => $ack_body,
	'disableAutoformat'  => true,
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
		'name'    => 'Donor Acknowledgment (Monthly Gift Started)',
		'event'   => 'create_subscription',
		'subject' => 'Thank you for your monthly gift to Autism Sanctuary — {Total:12}',
		'message' => $monthly_body,
	]),
];

$notifications = is_array($form['notifications']) ? $form['notifications'] : [];
foreach ($notifications as $id => $n) {
	$name = $n['name'] ?? '';
	if (strpos($name, 'Donor Acknowledgment') === 0) {
		unset($notifications[$id]);
		echo "Removed old notification: {$name}\n";
	}
}

foreach ($wanted as $notif) {
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

echo "=== Done. Form #{$form_id} ===\n";
