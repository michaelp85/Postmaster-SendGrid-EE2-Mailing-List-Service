<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * SendGrid + EE2 Mailing List
 *
 * Allows you to push email using SendGrid's email service to subscribers of native 
 * 	ExpressionEngine Mailing Lists.
 *
 * Service created by Michael Pasqualone (https://www.mpasqualone.com) by extending
 *	original Sendgrid.php service by Justin Kimbrell
 *
 */

class SendGridEEMailingList_postmaster_service extends Base_service {

	public $name = 'SendGridEEMailingList';
	public $title = 'SendGrid + EE Mailing List';
	public $url  = 'http://sendgrid.com/api/mail.send.json';

	public $default_settings = array(
		'api_user'        => '',
		'api_key'         => '',
		'plain_text_only' => 'false',
		'mailinglist_id'  => '1'
	);

	public $fields = array(
		'api_user' => array(
			'label' => 'Username',
			'id'	=> 'sendgrid_api_user'
		),
		'api_key' => array(
			'label' => 'Password',
			'id'	=> 'sendgrid_api_key',
			'type'  => 'password'
		),
		'plain_text_only' => array(
			'label' => 'Plain Text Only',
			'id'	=> 'plain_text_only',
			'description' => 'Whether or not to force the email to be only plain text',
			'type'  => 'radio',
			'settings' => array(
				'options' => array(
					'true'   => 'True',
					'false'  => 'False',
				)
			)
		)
	);

	public $description = '
	<p>Send e-mails to native ExpressionEngine Mailing List subscribers via SendGrid.</p>
	';

	public function __construct()
	{
		parent::__construct();
	}

	public function send($parsed_object, $parcel)
	{
		$settings = $this->get_settings();

		$plain_message = strip_tags($parsed_object->message);
		$html_message  = $parsed_object->message;

		if(isset($parsed_object->html_message) && !empty($parsed_object->html_message))
		{
			$html_message = $parsed_object->html_message;
		}

		if(isset($parsed_object->plain_message) && !empty($parsed_object->plain_message))
		{
			$plain_message = $parsed_object->plain_message;
		}

		$query = ee()->db->get_where('mailing_list', array('list_id' => $settings->mailinglist_id));
		
		$emails = array();
		foreach($query->result() as $row)
		{
			$emails[] = $row->email;
		}
		$xsmtpapi = array(
			'to' => $emails,
			'unique_args' => array(
				'subject' => $parsed_object->subject
			)
		);

		$post = array(
			'api_user' => $settings->api_user,
			'api_key'  => $settings->api_key,
			'x-smtpapi' => json_encode( $xsmtpapi ),
			'to'       => 'dummy@example.com',
			'toname'   => $parsed_object->to_name,
			'from'     => $parsed_object->from_email,
			'fromname' => $parsed_object->from_name,
			'subject'  => $parsed_object->subject,
			'text'     => $plain_message,
			'html'     => $html_message,
			'date'     => date('r', $this->now)
		);
				
		if(isset($settings->plain_text_only) && $settings->plain_text_only == 'true')
		{
			$post['html'] = $plain_message;	
		}
		
		$this->curl->create($this->url);		
		$this->curl->option(CURLOPT_HEADER, FALSE);
		$this->curl->option(CURLOPT_RETURNTRANSFER, TRUE);
		$this->curl->post($post);

		$response = $this->curl->execute();

		if(!$response)
		{
			$this->show_error('Error: '.$this->curl->error_string.'<p>Consult with SendGrid\'s documentation for more information regarding this error. <a href="http://docs.sendgrid.com/documentation/api/web-api/">http://docs.sendgrid.com/documentation/api/web-api/</a></p>');
		}
		else
		{
			$response = json_decode($response);
		}

		return new Postmaster_Service_Response(array(
			'status'     => $response->message == 'success' ? POSTMASTER_SUCCESS : POSTMASTER_FAILED,
			'parcel_id'  => $parcel->id,
			'channel_id' => isset($parcel->channel_id) ? $parcel->channel_id : FALSE,
			'author_id'  => isset($parcel->entry->author_id) ? $parcel->entry->author_id : FALSE,
			'entry_id'   => isset($parcel->entry->entry_id) ? $parcel->entry->entry_id : FALSE,
			'gmt_date'   => $this->now,
			'service'    => $parcel->service,
			'to_name'    => $parsed_object->to_name,
			'to_email'   => implode(', ', $emails),
			'from_name'  => $parsed_object->from_name,
			'from_email' => $parsed_object->from_email,
			'cc'         => $parsed_object->cc,
			'bcc'        => $parsed_object->bcc,
			'subject'    => $parsed_object->subject,
			'message'    => $parsed_object->message,
			'parcel'     => $parcel
		));
	}

	public function display_settings($settings, $parcel)
	{
		// Get Mailing List names/id's from db
		$results = ee()->db->select('list_id, list_title')->get('mailing_lists');
		
		if ($results->num_rows() > 0)
		{
			$lists = array();
			foreach($results->result_array() as $row)
			{
				$id = $row['list_id'];
				$lists[$id] = $row['list_title'];
			}
		}
		
		
		$this->fields['mailinglist_id'] = array(
			'type'  => 'select',
			'id'	=> 'sendgrid_ee_mailinglist_id',
			'label' => 'ExpressionEngine Mailing List ID',
			'settings' => array(
				'options' => $lists		
			)
		);	
		
		return $this->build_table($settings);
	}
}