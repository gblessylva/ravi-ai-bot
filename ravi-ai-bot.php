<?php

/**
 * Plugin Name: Igen Chat
 * Plugin URI: https://www.gblessylva.co
 * Description: Embed your Igen Chat chatbot on any WordPress site.
 * Version: 1.0.3
 * Author: gblessylva
 * Author URI: https://www.chatbase.co/
 * License: GPL2
 */

// Hook to add the options page to the admin menu
add_action( 'admin_menu', 'add_chatbot_options_page' );

// Function to add the options page to the admin menu
function add_chatbot_options_page() {
	$parent_slug = 'ravi-chatbot'; // Choose a unique slug for your menu
	add_menu_page(
		'Igen Chat', // Page title
		'Igen Chat', // Menu title
		'manage_options', // Capability
		$parent_slug, // Menu slug
		'chatbot_options_page', // Callback function
		'', // Icon URL (optional)
		99 // Position (optional)
	);
	add_action( 'admin_init', 'register_chatbot_options' );
}

// Function to register the options settings
function register_chatbot_options() {
	register_setting( 'chatbot_options', 'user_id', array(
		'type' => 'string',
		'sanitize_callback' => 'sanitize_text_field',
		'default' => NULL,
	) );
	register_setting( 'chatbot_options', 'chatbot_id', array(
		'type' => 'string',
		'sanitize_callback' => 'sanitize_text_field',
		'default' => NULL,
	) );
	add_action( 'pre_update_option_user_id', 'verify_chatbot_details', 10, 2 );
	add_action( 'pre_update_option_chatbot_id', 'verify_chatbot_details', 10, 2 );
}

// Function to verify chatbot details
function verify_chatbot_details( $value, $old_value ) {
	$user_id = isset( $_POST['user_id'] ) ? sanitize_text_field( $_POST['user_id'] ) : '';
	$chatbot_id = isset( $_POST['chatbot_id'] ) ? sanitize_text_field( $_POST['chatbot_id'] ) : '';

	if ( ! empty( $user_id ) && ! empty( $chatbot_id ) ) {
		$response = wp_remote_post( 'https://chatbotapi-dev.igenchat.co/v1/chatbot-api/verify-chatbot', array(
			'body' => json_encode( array(
				'user_id' => $user_id,
				'chatbot_id' => $chatbot_id
			) ),
			'headers' => array(
				'Content-Type' => 'application/json'
			)
		) );

		// if ( is_wp_error( $response ) ) {
		// 	add_settings_error( 'chatbot_options', 'chatbot_verification_error', 'Verification failed. Please try again.', 'error' );
		// 	return $old_value;
		// }

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		add_settings_error( 'chatbot_options', 'chatbot_verification_success', 'User and Chatbot ID verified and saved successfully.', 'updated' );
		return $value;

		// if ( isset( $data['success'] ) && $data['success'] ) {
		// 	add_settings_error( 'chatbot_options', 'chatbot_verification_success', 'User and Chatbot ID verified and saved successfully.', 'updated' );
		// 	return $value;
		// }
		// } else {
		// 	add_settings_error( 'chatbot_options', 'chatbot_verification_error', 'User cannot be verified.', 'error' );
		// 	return $old_value;
		// }

	}

	// add_settings_error( 'chatbot_options', 'chatbot_verification_error', 'User ID and Chatbot ID are required.', 'error' );
	// return $old_value;
}

// Function to define the content of the options page
function chatbot_options_page() {
	?>
	<div class="wrap">
		<style>
			body {
				font-family: Arial, sans-serif;
				margin: 0;
				padding: 0;
				box-sizing: border-box;
				background-color: #f8f9fa;
			}

			#form-container {
				display: flex;
				flex-direction: column;
				justify-content: center;
				align-items: center;
			}

			.logo-container a {
				text-decoration: none;
				color: #000;
			}

			.form-group {
				margin-bottom: 0.5rem;
			}

			.note-label {
				font-weight: 600;
			}

			label.text-secondary {
				color: #6c757d;
			}

			input.form-control {
				width: 100%;
				padding: 0.375rem 0.75rem;
				font-size: 1rem;
				line-height: 1.5;
				color: #495057;
				background-color: #fff;
				background-clip: padding-box;
				border: 1px solid #ced4da;
				border-radius: 0.25rem;
			}

			.submit-btn-container {
				display: flex;
				justify-content: flex-end;
			}

			.logo-container {
				display: flex;
				justify-content: center;
				margin-bottom: 1rem;
			}
		</style>
		<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
		<div id="form-container">
			<?php settings_errors(); // Display settings errors or success messages ?>
			<form method="post" action="options.php">
				<?php settings_fields( 'chatbot_options' ); ?>
				<?php do_settings_sections( 'chatbot_options' ); ?>
				<div class="logo-container">
					<a href="https://www.igenchat.co/" target="_blank">
						<img alt="Igen Chat" loading="lazy" width="200" style="color:transparent"
							src="https://mydev.igenchat.co/images/iGenChatLogo.png">
					</a>
				</div>
				<div class="form-group">
					<label for="user_id" class="text-secondary">User ID</label>
					<input type="text" class="form-control" placeholder="User ID" name="user_id" id="user_id"
						value="<?php echo esc_attr( get_option( 'user_id' ) ); ?>" required />
				</div>
				<div class="form-group">
					<label for="chatbot_id" class="text-secondary">Chatbot ID</label>
					<input type="text" class="form-control" placeholder="Chatbot ID" name="chatbot_id" id="chatbot_id"
						value="<?php echo esc_attr( get_option( 'chatbot_id' ) ); ?>" required />
				</div>
				<label class="note-label">*Note: You can find your Chatbot ID on <a href="https://mydev.igenchat.co/"
						target="_blank">Igen Chat</a> chatbot settings tab.</label>
				<div class="submit-btn-container">
					<?php submit_button(); ?>
				</div>
			</form>
		</div>
	</div>
	<?php
}

// Function to embed the script on the site using the ID entered in the options page
function chatbot_embed_chatbot() {
	$chatbot_id = get_option( 'chatbot_id' );

	if ( ! empty( $chatbot_id ) ) {
		$handle = 'chatbot-script';
		$script_url = 'https://mydev.igenchat.co/embed.min.js';

		// Enqueue the script
		wp_enqueue_script(
			$handle,
			$script_url,
			array(), // Dependencies (if any)
			null, // Version number (null for no version)
			true // Add script in the footer
		);

		// Pass data to the script
		wp_localize_script(
			$handle,
			'embeddedChatbotConfig',
			array(
				'chatbotId' => esc_attr( $chatbot_id ),
				'domain' => 'https://mydev.igenchat.co',
			)
		);
	}
}


// // Hook to enqueue the chatbot script on the front end
add_action( 'wp_enqueue_scripts', 'chatbot_embed_chatbot' );

