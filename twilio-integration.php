<?php
/*
Plugin Name: Twilio Calling
Plugin URI: http://localhost.com
Description: Twilio Calling System.
Version: 1.0.0
License: GPL2
Text Domain: twilio-integration
*/

// Twilio Libraries
require __DIR__ . '/vendor/autoload.php';

use Twilio\Rest\Client;


// Admin Menu
add_action( 'admin_menu', 'tw_admin_menu' );

register_uninstall_hook(__FILE__, 'tw_uninstall_plugin');

// AJAX Permission
add_action( 'wp_ajax_tw_change_options', 'tw_change_options' ); // admins
add_action( 'wp_ajax_nopriv_tw_change_options', 'tw_change_options' ); // edit needed
add_action( 'wp_ajax_tw_handle_twilio', 'tw_handle_twilio' ); // admins
add_action( 'wp_ajax_nopriv_tw_handle_twilio', 'tw_handle_twilio' ); // edit needed
add_action( 'wp_ajax_tw_handle_twilio_callback', 'tw_handle_twilio_callback' ); // admins
add_action( 'wp_ajax_nopriv_tw_handle_twilio_callback', 'tw_handle_twilio_callback' ); // edit needed

// Load JS and CSS
add_action( 'admin_enqueue_scripts', 'tw_public_scripts' );
add_action( 'wp_enqueue_scripts', 'tw_public_scripts' );


// Load js scripts and CSS
function tw_public_scripts() {
	wp_register_script( 'tw-js-public', plugins_url( '/js/twilio-integration.js', __FILE__ ), [ 'jquery', ], '', true );
	wp_register_style( 'tw-css-public', plugins_url( '/css/twilio-integration.css', __FILE__ ) );

	wp_enqueue_script( 'tw-js-public' );
	wp_enqueue_style( 'tw-css-public' );
}


// Add Twilio menu and Logs submenu to admin page
function tw_admin_menu() {
	$top_menu_item = 'tw_dashboard_admin_page';
	add_menu_page( '', __('Twilio','twilio-integration'), 'manage_options', 'tw_dashboard_admin_page', 'tw_dashboard_admin_page', 'dashicons-phone' );
	add_submenu_page( $top_menu_item, '', __('Logs','twilio-integration'), 'manage_options', 'tw_dashboard_log_page', 'tw_dashboard_log_page' );
}

// get default options
function tw_get_admin_options() {
	$current_options                  = [];
	$current_options['phone']         = get_option( 'tw-admin-phone' );
	$current_options['sid']           = get_option( 'tw-admin-sid' );
	$current_options['auth']          = get_option( 'tw-admin-auth' );
	$current_options['twilio-number'] = get_option( 'tw-admin-twilio-number' );

	return $current_options;
}

// Plugin settings page to add phone,sid,auth,twilio-number
function tw_dashboard_admin_page() {
	$current_options = tw_get_admin_options();
	echo( '<div class="wrap">
					<h2>'.__('Plugin Preferences','twilio-integration').'</h2>
					<p>
					<table>
						<tbody>
						    <form method="post" class="tw-change-options" action="' . ABSPATH . '/wp-admin/admin-ajax.php?action=tw_change_options">
							<div id="tw_manage_subscription_page_id"></div>
							<tr><th>'.__('Phone Number','twilio-integration').'</th>
							<td><input type="text" name="tw_phone_field" id="tw_phone_field" value="' . $current_options['phone'] . '" placeholder="Phone Number" required></td>
 							<td><p class="description" id="tw_manage_subscription_page_id-description">'.__('This is your personal number.', 'twilio-integration').'</p></td></tr>
 							<tr><th>'.__('Twilio Number','twilio-integration').'</th>
							<td><input type="text" name="tw_twphone_field" id="tw_twphone_field" value="' . $current_options['twilio-number'] . '" placeholder="Twillio Number" required></td>
							<td><p class="description" id="tw_manage_subscription_page_id-description">'.__('This is the number that twilio provided you.','twilio-integration').'</p></td></tr>
							<tr><th>'.__('Twilio SID','twilio-integration').'</th>
							<td><input type="text" name="tw_sid_field" id="tw_sid_field" value="' . $current_options['sid'] . '" placeholder="Twilio SID" required></td>
							<td><p class="description" id="tw_manage_subscription_page_id-description">'.__('This is the SID key provided to you by Twilio.','twilio-integration').'</p></td></tr>
							<tr><th>'.__('Twilio Auth','twilio-integration').'</th>
							<td><input type="text" name="tw_auth_field" id="tw_auth_field" value="' . $current_options['auth'] . '" placeholder="Twilio Auth" required></td>
							<td><p class="description" id="tw_manage_subscription_page_id-description">'.__('This is the secret AUTH key provided to you by Twilio.','twilio-integration').'</p></td></tr>
						</tbody>
					</table>' );
	wp_nonce_field( 'ajax-options-nonce', 'security-options' );
	@submit_button();
	echo( '</form>
					</p>
				</div>' );

}

// Log page html
function tw_dashboard_log_page() {

	$list = tw_get_call_log();

	$html = ( '<div class="wrap">
					<h2>'.__('Call Logs','twilio-integration').'</h2>' );
	if ( count( $list ) ) {

		$html .= ( '<p>
					<table class="table">
                        <thead>
                          <tr>
                            <th>'.__('Caller','twilio-integration').'</th>
                            <th>'.__('Time','twilio-integration').'</th>
                            <th>'.__('Status','twilio-integration').'</th>
                          </tr>
                        </thead>
                        <tbody>' );
		foreach ( $list as $record ) {
			// table content
			$html .= ( '<tr >
                            <td >' . $record['caller'] . '</td >
                            <td >' . $record['time'] . '</td >
                            <td > ' . __($record['status'],'twilio-integration') . '</td >
                          </tr >' );
		}
	} else {
		// Table empty
		$html .= '<p>'.__('No calls yet.','twilio-integration').'</p>';
	}
	$html .= ( '</tbody>
                     </table>
					</p>
				</div>' );

	echo $html;
}

// Returns call log from db table
function tw_get_call_log() {
	$table_name = get_option( 'plugin-table' );
	$result     = [];
	global $wpdb;
	if ( isset( $table_name ) ) {
		$query = $wpdb->get_results( '
            SELECT *
            FROM  ' . $table_name . ' order by call_time DESC;
        ' );
		foreach ( $query as $page ) {
			$result[] = [ 'caller' => $page->caller, 'time' => $page->call_time, 'status' => $page->call_status ];
		}
	}

	return $result;
}

// Adds call log to db table
function tw_add_log( $client_phone, $status ) {
	global $wpdb;

	$table_name = get_option( 'plugin-table' );

	if ( $wpdb->get_var( 'SHOW TABLES LIKE "' . $table_name . '"' ) != $table_name ) {
		tw_create_log_table();
	}

	return $wpdb->insert( $table_name, [ 'caller' => $client_phone, 'call_status' => $status ] );
}

// Plugin preferences form handler
function tw_change_options() {

	check_ajax_referer( 'ajax-options-nonce', 'security-options' );
	$result = [ 'status' => 0, 'message' => 'Fail', 'number' => '', 'error' => '' ];
	try {
		// prepare subscriber data
		$phone        = esc_attr( $_POST['tw_phone_field'] );
		$twilio_phone = esc_attr( $_POST['tw_twphone_field'] );
		$sid          = esc_attr( $_POST['tw_sid_field'] );
		$auth         = esc_attr( $_POST['tw_auth_field'] );

		if ( ! strlen( $phone ) || ! strlen( $twilio_phone ) || ! strlen( $sid ) || ! strlen( $auth ) ) {
			$result['error'] .= __('Fields are required','twilio-integration').'<br>';
		}
		if ( ! ( tw_phone_validator( $phone )) ) {
			$result['error'] .= __('Phone number is invalid','twilio-integration').'<br>';
		}
		if ( ! ( tw_phone_validator( $twilio_phone )) ) {
			$result['error'] .= __('Twilio number is invalid').'<br>';
		}

		if ( strlen($result['error'])  ) {
			$result['status'] = -1;
		} else {
			$result['message'] = 'Success';
			update_option( 'tw-admin-phone', $phone );
			update_option( 'tw-admin-auth', $auth );
			update_option( 'tw-admin-sid', $sid );
			update_option( 'tw-admin-twilio-number', $twilio_phone );
			$result['status'] = 1;
			$result['message'] = __('Options updated.', 'twilio-integration');
		}

	} catch ( Exception $e ) {
		$result['message'] = 'Caught exception: ' . $e->getMessage();
	}
	tw_return_json( $result );
}

// return php array to json
function tw_return_json( $php_array ) {
	$json_result = json_encode( $php_array );
	die( $json_result );
	exit;
}

// creates a log table in db

function tw_create_log_table() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'twilio_log';
	$sql        = '';
	try {
		$sql = 'CREATE TABLE ' . $table_name . '(
        id INT(10) AUTO_INCREMENT,
        caller VARCHAR(15) NOT NULL,
        call_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        call_status VARCHAR(15) NOT NULL,
        primary key(id)
        )';
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		$result = dbDelta( $sql );

		update_option( 'plugin-table', $table_name );
	} catch ( Exception $e ) {
		var_dump( $e->getMessage() );
	}

}
function tw_get_action() {
	if ( in_array( $_SERVER["REMOTE_ADDR"], array( "127.0.0.1", "::1" ) ) ) {
		$action = "https://0f2b26fe.ngrok.io/wordpress/wp-admin/admin-ajax.php";
	} else {
		$action = ABSPATH . "/wp-admin/admin-ajax.php";
	}

	return $action;
}

// Twilio Create Call
function tw_call( $client, $admin_phone, $client_phone ) {
	$options      = tw_get_admin_options();
	$twilio_phone = $options['twilio-number'];

	$client_phone = str_replace( "+", "", $client_phone );

	$action = tw_get_action() . '?action=tw_handle_twilio_callback';


	$call = $client->account->calls->create( $admin_phone, $twilio_phone,
		array(
			"url"                  => "https://handler.twilio.com/twiml/EH338711d4b5c0f1577cf4bc9b5e13c335?action=" . $action . "&client=" . $client_phone,
			'statusCallbackMethod' => 'GET',
			'statusCallback'       => $action . '&client=' . $client_phone,
			'statusCallbackEvent'  => [ 'completed' ],
		) );

	return [ 'id' => $call->sid, 'status' => 1, 'message' => $call->status ];
}

// Twilio send message
function tw_msg( $client, $from, $to, $content ) {
	$sms = $client->messages->create( $to, array(
			'from' => $from,
			'body' => $content
		)
	);

	return [ 'id' => $sms->sid, 'status' => $sms->status ];
}

// Returns the twilio client
function tw_get_twilio_client() {
	$options = tw_get_admin_options();
	$sid     = $options['sid'];
	$token   = $options['auth'];

	return new Client( $sid, $token );
}

function tw_phone_validator( $phone ) {
	return preg_match( "/\(?([0-9]{3})\)?([ .-]?)([0-9]{3})\\2([0-9]{4})/", $phone, $match );
}

// Twilio Click to Call handler
function tw_handle_twilio() {
// Your Account SID and Auth Token from twilio.com/console
	$client = tw_get_twilio_client();
	$result = [ 'status' => '', 'message' => 'Fail', ];

	try {
		// prepare subscriber data
		$phone       = esc_attr( $_POST['tw-client-number'] );
		$admin_phone = get_option( 'tw-admin-phone' );

		if ( isset( $phone ) && count( $phone ) && tw_phone_validator( $phone ) ) {
			$result = tw_call( $client, $admin_phone, $phone );
		} else {
			$result['status']  = - 1;
			$result['message'] = 'Enter Valid phone number';
		}
	} catch
	( Exception $e ) {
		$result['status']  = - 1;
		$result['message'] = 'Caught exception: ' . $e->getMessage();
	}
	tw_return_json( $result );
}

// Twilio call status tracking callback
function tw_handle_twilio_callback() {

//	$req_dump = print_r( $_REQUEST, true );
//	$fp       = fopen( '../../../request.log', 'a' );
//	fwrite( $fp, $req_dump );

	$req = $_REQUEST;

	// requires Wordpress timezone to be set
	date_default_timezone_set( get_option( 'timezone_string' ) );

	$dial_status    = $req['DialCallStatus'];
	$call_status    = $req['CallStatus'];
	$twilio_phone   = $req['Caller'];
	$client_phone   = $req['client'];
	$admin_phone    = get_option( 'tw-admin-phone' );
	$client_content = __( 'We are sorry but all our agents are busy right now. We’ll give you a call as soon as possible. Thank you. ' . get_bloginfo( 'name' ), 'twilio-integration' );
	$admin_content  = __( 'User with number ' . $client_phone . ' tried to request a call from you at ' . date( "h:i:sa" ) . ' but you were not available. Please call user as soon as possible', 'twilio-integration' );
	$client         = tw_get_twilio_client();

	if ( $call_status == 'busy' || $call_status == 'failed' || $call_status == 'canceled' || $call_status == 'no-answer' ) {
		// send sms to client
		$client_sms = tw_msg( $client, $twilio_phone, '+' . $client_phone, $client_content );
		// send sms to admin
		$admin_sms = tw_msg( $client, $twilio_phone, $admin_phone, $admin_content );

		tw_add_log( $client_phone, 'UNSUCCESSFUL' );

	} else {
		if ( $dial_status == 'busy' || $dial_status == 'failed' || $dial_status == 'canceled' || $dial_status == 'no-answer' ) {
			// send sms to client
			$client_sms = tw_msg( $client, $twilio_phone, $client_phone, $client_content );
			// send sms to admin
			$admin_sms = tw_msg( $client, $twilio_phone, $admin_phone, $admin_content );
			tw_add_log( $client_phone, 'UNSUCCESSFUL' );
			echo '<?xml version="1.0" encoding="UTF-8"?><Response>
        	<Say>Client is currently busy.</Say></Response>';
			die();
		} else if ( $dial_status == 'completed' ) {
			tw_add_log( $client_phone, 'SUCCESSFUL' );
			echo '<?xml version="1.0" encoding="UTF-8"?><Response>
        	<Say>Your call was complete. Thank you.</Say></Response>';
			die();
		}
	}
	echo '<?xml version="1.0" encoding="UTF-8"?><Response>
        	<Say></Say></Response>';
	die();
}


// At UNINSTALL
function tw_uninstall_plugin(){
	tw_remove_plugin_tables();
	tw_remove_options();
}
function tw_remove_plugin_tables(){
    $table_name = get_option('plugin-table');
	global $wpdb;
	$sql        = '';
	try {
		$sql = 'DROP TABLE ' . $table_name .';';
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		$result = dbDelta( $sql );

		delete_option( 'plugin-table' );
	} catch ( Exception $e ) {
		var_dump( $e->getMessage() );
	}
}

function tw_remove_options(){
	delete_option( 'tw-admin-phone');
	delete_option( 'tw-admin-auth');
	delete_option( 'tw-admin-sid');
	delete_option( 'tw-admin-twilio-number');
}
// WIDGET AREA

// Register and load the widget

function tw_load_widget() {
	$options = tw_get_admin_options();
	if(strlen($options['phone']) && strlen($options['auth']) && strlen($options['sid']) && strlen($options['twilio-number'])) {
		register_widget( 'tw_widget' );
	}
}

add_action( 'widgets_init', 'tw_load_widget' );

// Creating the widget
class tw_widget extends WP_Widget {

	function __construct() {
		parent::__construct(

// Base ID of your widget
			'tw_widget',

// Widget name will appear in UI
			__( 'Click to Call Widget', 'twilio-integration' ),

// Widget description
			array( 'description' => __( 'Enables the visitors to call you using Twilio.', 'tw_widget_domain' ), )
		);
	}

// Creating widget front-end

	public function widget( $args, $instance ) {
		$title = apply_filters( 'widget_title', $instance['title'] );

// before and after widget arguments are defined by themes
		echo $args['before_widget'];
		if ( ! empty( $title ) ) {
			echo $args['before_title'] . $title . $args['after_title'];
		}

// This is where you run the code and display the output
		echo( '<div id="getCallDiv">' );
		echo __( 'Your number', 'twilio-integration' );
		echo( '
			<form class="tw_handle_twilio" action="' . tw_get_action() . '?action=tw_handle_twilio" method="post">
			<input type="text" name="tw-client-number" id="tw-client-number" required/>
			<input type="submit"  id="getCallButton" value="' . __( 'Get a Call', 'twilio-integration' ) . '">
            </form></div><div id="msgCallDiv" style="display: none"><p>'.__('We got your details and we’ll contact you as soon as possible', 'twilio-integration').'</p></div>
		' );
		echo $args['after_widget'];
	}

// Widget Backend

	public function form( $instance ) {
		if ( isset( $instance['title'] ) ) {
			$title = $instance['title'];
		} else {
			$title = __( 'Click to Call', 'twilio-integration' );
		}
// Widget admin form
		?>
        <p>
            <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>"
                   name="<?php echo $this->get_field_name( 'title' ); ?>" type="text"
                   value="<?php echo esc_attr( $title ); ?>"/>
        </p>
		<?php
	}

// Updating widget replacing old instances with new
	public function update( $new_instance, $old_instance ) {
		$instance          = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';

		return $instance;
	}
} // Class wpb_widget ends here

