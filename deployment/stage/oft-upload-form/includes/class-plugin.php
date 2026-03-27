<?php
/**
 * Main plugin bootstrap class.
 *
 * @package OFTUploadForm
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class OFTUF_Plugin {

	/**
	 * Database service.
	 *
	 * @var OFTUF_Database
	 */
	protected $database;

	/**
	 * Validator service.
	 *
	 * @var OFTUF_Validator
	 */
	protected $validator;

	/**
	 * Upload service.
	 *
	 * @var OFTUF_Uploader
	 */
	protected $uploader;

	/**
	 * Mail service.
	 *
	 * @var OFTUF_Mailer
	 */
	protected $mailer;

	/**
	 * Form handler.
	 *
	 * @var OFTUF_Form_Handler
	 */
	protected $form_handler;

	/**
	 * Shortcode renderer.
	 *
	 * @var OFTUF_Shortcode
	 */
	protected $shortcode;

	/**
	 * Admin controller.
	 *
	 * @var OFTUF_Admin
	 */
	protected $admin;

	/**
	 * Wire services together.
	 */
	public function __construct() {
		$this->database     = new OFTUF_Database();
		$this->validator    = new OFTUF_Validator();
		$this->uploader     = new OFTUF_Uploader();
		$this->mailer       = new OFTUF_Mailer();
		$this->form_handler = new OFTUF_Form_Handler( $this->validator, $this->uploader, $this->mailer, $this->database );
		$this->shortcode    = new OFTUF_Shortcode();
		$this->admin        = new OFTUF_Admin( $this->database, $this->mailer );
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
		add_action( 'admin_init', array( $this->admin, 'handle_test_email' ) );
		add_action( 'admin_init', array( $this->admin, 'handle_bulk_actions' ) );
	}

	/**
	 * Load translation files.
	 *
	 * @return void
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'oft-upload-form', false, dirname( OFTUF_PLUGIN_BASENAME ) . '/languages' );
	}
}

