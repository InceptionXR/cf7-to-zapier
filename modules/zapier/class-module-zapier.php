<?php
/**
 * CFTZ_Module_Zapier
 *
 * @package         Cf7_To_Zapier
 * @subpackage      CFTZ_Module_Zapier
 * @since           1.0.0
 *
 */

// If this file is called directly, call the cops.
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

if ( ! class_exists( 'CFTZ_Module_Zapier' ) ) {
    class CFTZ_Module_Zapier {

        /**
         * The Core object
         *
         * @since    1.0.0
         * @var      Cf7_To_Zapier    $core   The core class
         */
        private $core;

        /**
         * The Module Indentify
         *
         * @since    1.0.0
         */
        const MODULE_SLUG = 'zapier';

        /**
         * Define the core functionalities into plugin.
         *
         * @since    1.0.0
         * @param    Cf7_To_Zapier      $core   The Core object
         */
        public function __construct( Cf7_To_Zapier $core ) {
            $this->core = $core;
        }

        /**
         * Register all the hooks for this module
         *
         * @since    1.0.0
         * @access   private
         */
        private function define_hooks() {
            $this->core->add_action( 'ctz_trigger_webhook', array( $this, 'pull_the_trigger' ), 10, 2 );
        }

        /**
         * Send data to Zapier
         *
         * @since    1.0.0
         * @access   private
         */
        public function pull_the_trigger( array $data, $hook_url ) {
            $content_type = 'application/json';

            $blog_charset = get_option( 'blog_charset' );
            if ( ! empty( $blog_charset ) ) {
                $content_type .= '; charset=' . get_option( 'blog_charset' );
            }

            $args = array(
                'method'    => 'POST',
                'body'      => json_encode( $data ),
                'headers'   => array(
                    'Content-Type'  => $content_type,
                ),
            );

            /**
             * Filter: ctz_post_request_args
             *
             * The 'ctz_post_request_args' filter POST args so developers
             * can modify the request args if any service demands a particular header or body.
             *
             * @since    1.1.0
             */
            $response = wp_remote_post( $hook_url, apply_filters( 'ctz_post_request_args', $args ) );
			$body = wp_remote_retrieve_body( $response );
			$data = json_decode( $body, true );
			
			$statusCode = $data['status'];
			$errorCode = $data['error_code'];
			$errorMessage = $data['message'];

			if ($statusCode >= 400) {
				$errorMsg = 'This submission is not valid';
				
				if ($statusCode == 400) {
					if (strpos($errorMessage, 'and owns goods') !== false || strpos($errorMessage, 'Email already signed up') !== false || $errorCode === 7003) {
						$errorMsg = "It seems like you are already a Bookful subscriber.<br>Please contact support@inceptionxr.com to learn how you can enjoy this exclusive offer.";
					} 
// 					elseif ($errorCode == 7002) {
// 						$errorMsg = "This coupon has already been redeemed.";
// 					} 
				}
// 				elseif ( $statusCode == 404 ) {
// 					$errorMsg = 'Error loading coupon';
// 				}			
				
				throw new Exception( $errorMsg );
			}
			
            /**
             * Action: ctz_post_request_result
             *
             * You can perform a action with the result of the request.
             * By default we do nothing but you can throw a Exception in webhook errors.
             *
             * @since    1.4.0
             */
            do_action( 'ctz_post_request_result', $response, $hook_url );
        }

        /**
         * Run the module.
         *
         * @since    1.0.0
         */
        public function run() {
            $this->define_hooks();
        }
    }
}