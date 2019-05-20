<?php
	if ( ! class_exists( 'cjaddons_rest_cjsupport_form' ) ) {
		class cjaddons_rest_cjsupport_form {

			public $helpers, $module_id, $module_dir, $routes, $api_url, $module_info;

			private static $instance;

			public static function getInstance() {
				if ( ! isset( self::$instance ) ) {
					self::$instance = new self();
				}

				return self::$instance;
			}

			public function __construct() {
				$this->helpers     = cjaddons_supportezzy_helpers::getInstance();
				$this->module_info = $this->helpers->moduleInfo( basename( $this->helpers->module_dir ) );
				$this->module_id   = basename( $this->helpers->module_dir );
				$this->api_url     = '';
				if ( function_exists( 'using_index_permalinks' ) ) {
					$this->api_url = rest_url( 'cjaddons' ) . '/';
				}
				$this->routes = array(
					$this->module_id . '-get-form' => array(
						'endpoint'    => $this->module_info['module_id'] . '/get-form',
						'name'        => sprintf( __( '%s Login', 'addon-supportezzy' ), $this->module_info['module_name'] ),
						'description' => __( 'Accept user_login and user_pass to authenticate a user.', 'addon-supportezzy' ),
						'methods'     => array(
							'post' => array( $this, 'getForm' ), // callback function
						),
						'permissions' => function () {
							return true;
							// return current_user_can( 'manage_options' );
						},
					),
				);
				add_filter( 'cjaddons_register_api_route', array( $this, 'registerRoute' ) );
			}

			public function registerRoute( $routes ) {
				$routes = array_merge( $routes, $this->routes );

				return $routes;
			}

			public function getForm( $request ) {
				$form_type     = $request['form'];
				$form_fields   = array();
				$api_user_info = $this->helpers->getApiUserInfo( $request );
				$file_path     = $this->helpers->module_dir . '/autoload/forms/' . $form_type . '.php';
				ob_start();
				require_once $file_path;
				echo $this->helpers->renderFrontendForm( $form_fields );
				$data = ob_get_clean();

				return $this->helpers->apiResponse( $request, $data );
			}

		}

		cjaddons_rest_cjsupport_form::getInstance();
	}