<?php
/**
 * Wedge
 *
 * Pluggable payment gateway for subscriptions paid through PayPal.
 *
 * @package wedge
 * @copyright 2010-2013 Wedgeward, wedge.org
 * @license http://wedge.org/license/
 *
 * @version 0.1
 */

// This won't be dedicated without this - this must exist in each gateway!
// Wedge Payment Gateway: paypal

if (!defined('WEDGE'))
	die('Hacking attempt...');

class paypal_display
{
	public $title = 'PayPal';

	public function getGatewaySettings()
	{
		global $txt;

		$setting_data = array(
			array('text', 'paypal_email', 'subtext' => $txt['paypal_email_desc']),
		);

		return $setting_data;
	}

	// Is this enabled for new payments?
	public function gatewayEnabled()
	{
		global $settings;

		return !empty($settings['paypal_email']);
	}

	// What do we want?
	public function fetchGatewayFields($unique_id, $sub_data, $value, $period, $return_url)
	{
		global $settings, $txt, $boardurl;

		$return_data = array(
			'form' => 'https://www.' . (!empty($settings['paidsubs_test']) ? 'sandbox.' : '') . 'paypal.com/cgi-bin/webscr',
			'id' => 'paypal',
			'hidden' => array(),
			'title' => $txt['paypal'],
			'desc' => $txt['paid_confirm_paypal'],
			'submit' => $txt['paid_paypal_order'],
			'javascript' => '',
		);

		// All the standard bits.
		$return_data['hidden']['business'] = $settings['paypal_email'];
		$return_data['hidden']['item_name'] = $sub_data['name'] . ' ' . $txt['subscription'];
		$return_data['hidden']['item_number'] = $unique_id;
		$return_data['hidden']['currency_code'] = strtoupper($settings['paid_currency_code']);
		$return_data['hidden']['no_shipping'] = 1;
		$return_data['hidden']['no_note'] = 1;
		$return_data['hidden']['amount'] = $value;
		$return_data['hidden']['cmd'] = !$sub_data['repeatable'] ? '_xclick' : '_xclick-subscriptions';
		$return_data['hidden']['return'] = $return_url;
		$return_data['hidden']['src'] = 1;
		$return_data['hidden']['notify_url'] = $boardurl . '/subscriptions.php';

		// Now then, what language should we use? These are best-match from language packs through to PayPal region codes (which are basically by country)
		$langs = array(
			'albanian' => 'AL',
			'arabic' => 'EG', // http://en.wikipedia.org/wiki/Arabic puts Egypt's dialect at 80m users and thus the most prevalent.
			'bangla' => 'BD',
			'bulgarian' => 'BG',
			'catalan' => 'AD',
			'chinese_simplified' => 'CN',
			'chinese_traditional' => 'CN',
			'croatian' => 'HR',
			'czech' => 'CZ',
			'danish' => 'DK',
			'dutch' => 'NL',
			'english' => 'US',
			'english_british' => 'GB',
			'finnish' => 'FI',
			'french' => 'FR',
			'galician' => 'ES', // Could just as easily have been PT though
			'german' => 'DE',
			'hebrew' => 'IL',
			'hindi' => 'IN',
			'hungarian' => 'HU',
			'indonesian' => 'ID',
			'italian' => 'IT',
			'japanese' => 'JP',
			'kurdish_kurmanji',
			'kurdish_sorani',
			'macedonian' => 'MK',
			'malay' => 'MY',
			'norwegian' => 'NO',
			'persian' => 'IR', // Best guess
			'polish' => 'PL',
			'portuguese_brazilian' => 'BR',
			'portuguese_pt' => 'PT',
			'romanian' => 'RO',
			'russian' => 'RU',
			'serbian_cyrillic' => 'CS',
			'serbian_latin' => 'CS',
			'slovak' => 'SK',
			'spanish_es' => 'ES',
			'spanish_latin' => 'MX', // There's a whole variety this could be, Mexico is the dominant however.
			'swedish' => 'SE',
			'thai' => 'TH',
			'turkish' => 'TR',
			'ukrainian' => 'UA',
			'urdu' => 'PK',
			'uzbek_latin' => 'UZ',
			'vietnamese' => 'VN',
		);
		$return_data['hidden']['lc'] = isset($langs[we::$user['language']]) ? $langs[we::$user['language']] : 'US';

		// Now stuff that depends on what we're doing.
		if ($sub_data['flexible'])
		{
			$return_data['hidden']['a3'] = $value;
			$return_data['hidden']['p3'] = 1;
			$return_data['hidden']['t3'] = strtoupper(substr($period, 0, 1));
		}
		elseif (!$sub_data['lifetime'])
		{
			preg_match('~(\d*)(\w)~', $sub_data['real_length'], $match);
			$unit = $match[1];
			$period = $match[2];

			$return_data['hidden']['a3'] = $value;
			$return_data['hidden']['p3'] = $unit;
			$return_data['hidden']['t3'] = $period;
		}

		// If it's repeatable add some JavaScript to respect this idea.
		if (!empty($sub_data['repeatable']))
			$return_data['javascript'] = '
				$(\'#gateway_desc\').append(\'<br><br><label><input type="checkbox" name="do_paypal_recur" id="do_paypal_recur" checked onclick="switchPaypalRecur();">' . $txt['paid_make_recurring'] . '</label>\');

				function switchPaypalRecur()
				{
					$(\'#paypal_cmd\').val( $(\'#do_paypal_recur\')[0].checked ? \'_xclick-subscriptions\' : \'_xclick\' );
				}';

		return $return_data;
	}
}

class paypal_payment
{
	private $return_data;

	// This function returns true/false for whether this gateway thinks the data is intended for it.
	public function isValid()
	{
		global $settings;

		// Has the user set up an email address?
		if (empty($settings['paypal_email']))
			return false;
		// Check the correct transaction types are even here.
		if ((!isset($_POST['txn_type']) && !isset($_POST['payment_status'])) || (!isset($_POST['business']) && !isset($_POST['receiver_email'])))
			return false;
		// Correct email address?
		if (!isset($_POST['business']))
			$_POST['business'] = $_POST['receiver_email'];
		if ($settings['paypal_email'] != $_POST['business'] && (empty($settings['paypal_additional_emails']) || !in_array($_POST['business'], explode(',', $settings['paypal_additional_emails']))))
			return false;
		return true;
	}

	// Validate all the data was valid.
	public function precheck()
	{
		global $settings, $txt;

		loadSource('Class-WebGet');

		// Put this to some default value.
		if (!isset($_POST['txn_type']))
			$_POST['txn_type'] = '';

		// Build the request string - starting with the minimum requirement.
		$weget = new weget('https://' . (!empty($settings['paidsubs_test']) ? 'www.sandbox.' : 'www.') . 'paypal.com/cgi-bin/webscr');
		$weget->setMethod('POST');
		$weget->addPostVar('cmd', '_notify_validate');

		// Now my dear, add all the posted bits.
		foreach ($_POST as $k => $v)
			$weget->addPostVar($k, $v);

		$this->return_data = $weget->get();
		if (!$this->return_data)
			generateSubscriptionError($txt['paypal_could_not_connect']);

		// If this isn't verified then give up...
		// !! This contained a comment "send an email", but we don't appear to send any?
		if (strcmp(trim($this->return_data), 'VERIFIED') != 0)
			exit;

		// Check that this is intended for us.
		if ($settings['paypal_email'] != $_POST['business'] && (empty($settings['paypal_additional_emails']) || !in_array($_POST['business'], explode(',', $settings['paypal_additional_emails']))))
			exit;

		// Is this a subscription - and if so it's it a secondary payment that we need to process?
		if ($this->isSubscription() && (empty($_POST['item_number']) || strpos($_POST['item_number'], '+') === false))
			// Calculate the subscription it relates to!
			$this->_findSubscription();

		// Verify the currency!
		if (strtolower($_POST['mc_currency']) != $settings['paid_currency_code'])
			exit;

		// Can't exist if it doesn't contain anything.
		if (empty($_POST['item_number']))
			exit;

		// Return the id_sub and id_member
		return explode('+', $_POST['item_number']);
	}

	// Is this a refund?
	public function isRefund()
	{
		if ($_POST['payment_status'] == 'Refunded' || $_POST['payment_status'] == 'Reversed' || $_POST['txn_type'] == 'Refunded' || ($_POST['txn_type'] == 'reversal' && $_POST['payment_status'] == 'Completed'))
			return true;
		else
			return false;
	}

	// Is this a subscription?
	public function isSubscription()
	{
		if (substr($_POST['txn_type'], 0, 14) == 'subscr_payment' && $_POST['payment_status'] == 'Completed')
			return true;
		else
			return false;
	}

	// Is this a normal payment?
	public function isPayment()
	{
		if ($_POST['payment_status'] == 'Completed' && $_POST['txn_type'] == 'web_accept')
			return true;
		else
			return false;
	}

	// How much was paid?
	public function getCost()
	{
		return (isset($_POST['tax']) ? $_POST['tax'] : 0) + $_POST['mc_gross'];
	}

	// exit.
	public function close()
	{
		global $subscription_id;

		// If it's a subscription record the reference.
		if ($_POST['txn_type'] == 'subscr_payment' && !empty($_POST['subscr_id']))
		{
			$_POST['subscr_id'] = $_POST['subscr_id'];
			wesql::query('
				UPDATE {db_prefix}log_subscribed
				SET vendor_ref = {string:vendor_ref}
				WHERE id_sublog = {int:current_subscription}',
				array(
					'current_subscription' => $subscription_id,
					'vendor_ref' => $_POST['subscr_id'],
				)
			);
		}

		exit;
	}

	// A private function to find out the subscription details.
	private function _findSubscription()
	{
		// Assume we have this?
		if (empty($_POST['subscr_id']))
			return false;

		// Do we have this in the database?
		$request = wesql::query('
			SELECT id_member, id_subscribe
			FROM {db_prefix}log_subscribed
			WHERE vendor_ref = {string:vendor_ref}
			LIMIT 1',
			array(
				'vendor_ref' => $_POST['subscr_id'],
			)
		);
		// No joy?
		if (wesql::num_rows($request) == 0)
		{
			// Can we identify them by email?
			if (!empty($_POST['payer_email']))
			{
				wesql::free_result($request);
				$request = wesql::query('
					SELECT ls.id_member, ls.id_subscribe
					FROM {db_prefix}log_subscribed AS ls
						INNER JOIN {db_prefix}members AS mem ON (mem.id_member = ls.id_member)
					WHERE mem.email_address = {string:payer_email}
					LIMIT 1',
					array(
						'payer_email' => $_POST['payer_email'],
					)
				);
				if (wesql::num_rows($request) == 0)
					return false;
			}
			else
				return false;
		}
		list ($member_id, $subscription_id) = wesql::fetch_row($request);
		$_POST['item_number'] = $member_id . '+' . $subscription_id;
		wesql::free_result($request);
	}
}
