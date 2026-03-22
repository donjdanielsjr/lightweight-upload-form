<?php
/**
 * Main plugin bootstrap class.
 *
 * @package LightweightUploadForm
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LUF_Plugin {

	/**
	 * Database service.
	 *
	 * @var LUF_Database
	 */
	protected $database;

	/**
	 * Validator service.
	 *
	 * @var LUF_Validator
	 */
	protected $validator;

	/**
	 * Upload service.
	 *
	 * @var LUF_Uploader
	 */
	protected $uploader;

	/**
	 * Mail service.
	 *
	 * @var LUF_Mailer
	 */
	protected $mailer;

	/**
	 * Form handler.
	 *
	 * @var LUF_Form_Handler
	 */
	protected $form_handler;

	/**
	 * Shortcode renderer.
	 *
	 * @var LUF_Shortcode
	 */
	protected $shortcode;

	/**
	 * Admin controller.
	 *
	 * @var LUF_Admin
	 */
	protected $admin;

	/**
	 * Wire services together.
	 */
	public function __construct() {
		$this->database     = new LUF_Database();
		$this->validator    = new LUF_Validator();
		$this->uploader     = new LUF_Uploader();
		$this->mailer       = new LUF_Mailer();
		$this->form_handler = new LUF_Form_Handler( $this->validator, $this->uploader, $this->mailer, $this->database );
		$this->shortcode    = new LUF_Shortcode();
		$this->admin        = new LUF_Admin( $this->database );
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function run() {
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
		add_action( 'init', array( $this->form_handler, 'handle_submission' ) );
		add_action( 'init', array( $this->shortcode, 'register_shortcode' ) );
		add_action( 'admin_menu', array( $this->admin, 'register_menu' ) );
		add_action( 'admin_init', array( $this->admin, 'handle_csv_export' ) );
	}

	/**
	 * Load translation files.
	 *
	 * @return void
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'lightweight-upload-form', false, dirname( LUF_PLUGIN_BASENAME ) . '/languages' );
	}
}
