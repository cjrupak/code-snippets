<?php

use function GuzzleHttp\Psr7\parse_query;

if ( ! class_exists('cjaddons_global_ajax_support')) {
    class cjaddons_global_ajax_support
    {

        public $helpers;

        private static $instance;

        public static function getInstance()
        {
            if ( ! isset(self::$instance)) {
                self::$instance = new self();
            }

            return self::$instance;
        }

        public function __construct()
        {
            $this->helpers = cjaddons_helpers::getInstance();
            add_action('wp_ajax_nopriv_get_google_addresses', array($this, 'getGoogleAddresses'));
            add_action('wp_ajax_get_google_addresses', array($this, 'getGoogleAddresses'));
            add_action('wp_ajax_nopriv_get_google_addresses_by_lat_lng', array($this, 'getGoogleAddressesByLatLng'));
            add_action('wp_ajax_get_google_addresses_by_lat_lng', array($this, 'getGoogleAddressesByLatLng'));
            add_action('wp_ajax_nopriv_vue_upload_user_avatar', array($this, 'vueUploadUserAvatar'));
            add_action('wp_ajax_vue_upload_user_avatar', array($this, 'vueUploadUserAvatar'));
            add_action('wp_ajax_nopriv_vue_upload_file', array($this, 'vueUploadFiles'));
            add_action('wp_ajax_vue_upload_file', array($this, 'vueUploadFiles'));
            add_action('wp_ajax_vue_upload_addon', array($this, 'vueUploadAddon'));
            add_action('wp_ajax_nopriv_cjaddons_query_posts', array($this, 'queryPostsCallback'));
            add_action('wp_ajax_cjaddons_query_posts', array($this, 'queryPostsCallback'));
            add_action('wp_ajax_nopriv_cjaddons_query_users', array($this, 'queryUsersCallback'));
            add_action('wp_ajax_cjaddons_query_users', array($this, 'queryUsersCallback'));
            add_action('wp_ajax_cjaddons_dismiss_admin_notice', array($this, 'dismissAdminNotice'));
            add_action('wp_ajax_cjaddons_download_products', array($this, 'downloadProducts'));
            add_action('wp_ajax_cjaddons_clone_ui_block', array($this, 'cloneUiBlock'));
        }

        public function downloadProducts()
        {
            $return = array();
            parse_str(parse_url($_POST['url'], PHP_URL_QUERY), $data);
            $product_name = $data['p'];
            $license_key = $data['license_key'];
            $download_key = $data['k'];
            $file_name = $product_name;
            $path = '';
            if (strpos($file_name, 'addon-') === 0) {
                $path = WP_PLUGIN_DIR . '/';
            }
            if (strpos($file_name, 'cjuib-') === 0) {
                $upload_dir = wp_upload_dir();
                $path = $upload_dir['basedir'] . '/cssjockey-add-ons/ui-blocks/';
                if ( ! is_dir($path)) {
                    mkdir($path);
                }
            }
            $url = $this->helpers->itemInfo('author_url') . '/download/?p=' . $file_name . '&k=' . $download_key;
            $zipFile = $path . $file_name . '.zip'; // Local Zip File Path

            $zipResource = fopen($zipFile, "w");
            // Get The Zip File From Server
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_FAILONERROR, true);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_AUTOREFERER, true);
            curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_FILE, $zipResource);
            $result = curl_exec($ch);
            curl_close($ch);
            if ( ! $result) {
                $return['errors'] = __('Something went wrong! Please contact us at support@cssjockey.com.', 'cssjockey-add-ons');
                echo json_encode($return);
                die();
            }
            $unzip_file_path = $path . $file_name . '.zip';
            $unzip_destination_path = dirname($unzip_file_path) . '/' . $file_name;
            if (strpos($file_name, 'addon-') === 0) {
                $unzip_destination_path = dirname($unzip_file_path);
            }
            $this->helpers->unzipFile($unzip_file_path, $unzip_destination_path);
            unlink($unzip_file_path);

            // create ui block if not exists
            if (strpos($file_name, 'cjuib-') === 0) {

                if ( ! file_exists($unzip_destination_path . '/info.json')) {
                    $return['error'] = __('There\'s some issue with this ui block. Please contact us at support@cssjockey.com.', 'cssjockey-add-ons');
                    echo json_encode($return);
                    die();
                }

                ob_start();
                require_once $unzip_destination_path . '/info.json';
                $info = ob_get_clean();
                $info = json_decode($info, 1);

                if ( ! class_exists($info['class_name'])) {
                    require_once $unzip_destination_path . '/init.php';
                }

                $class_instance = $info['class_name']::getInstance();
                $class_info = $class_instance->info;
                $class_info['name'] = $class_info['name'] . ' (New)';
                $block_id = $this->helpers->insertUiBlock($class_info);
                $block_info = $this->helpers->postInfo($block_id);
                $return['block_data']['ID'] = $block_info['ID'];
                $return['block_data']['title'] = $block_info['post_title'];
                $return['block_data']['class_name'] = $block_info['_component_class_name'];
                $return['block_data']['description'] = $block_info['post_excerpt'];
                $return['block_data']['screenshot'] = $class_instance->info['screenshot'];
                $slug = basename($class_instance->info['path']);
                $return['block_data']['slug'] = $slug;
                $return['block_data']['license_key'] = $license_key;
                update_option('cjaddons_license_' . $slug, $license_key);
            }

            // activate plugin
            if (strpos($file_name, 'addon-') === 0) {
                update_option('cjaddons_license_' . $file_name, $license_key);
                $plugin_file = sprintf('%s/plugins/%s/index.php', WP_CONTENT_DIR, $file_name);
                if ( ! is_plugin_active($plugin_file)) {
                    activate_plugin($plugin_file);
                }
            }
            $return['success'] = __("Product is installed and activated!\nYou can refresh this page to see changes.", 'cssjockey-add-ons');

            echo json_encode($return);
            wp_die();
        }

        public function cloneUiBlock()
        {
            $existing_block_id = $_POST['block_id'];
            $post_info = $this->helpers->postInfo($existing_block_id);
            unset($post_info['ID']);
            unset($post_info['post_name']);
            unset($post_info['post_date']);
            unset($post_info['post_date_gmt']);
            unset($post_info['post_modified']);
            unset($post_info['post_modified_gmt']);
            $cloned_title = $post_info['post_title'] . ' - ' . __('Cloned', 'cssjockey-add-ons');
            $post_title = (isset($_POST['new_title'])) ? $_POST['new_title'] : $cloned_title;
            $post_info['post_title'] = $post_title;
            $new_block_id = wp_insert_post($post_info);
            $this->helpers->updatePostInfo($new_block_id, $post_info);
            $new_block_info = $this->helpers->postInfo($new_block_id);
            $class_instance = $new_block_info['_component_class_name']::getInstance();
            $new_block_info['screenshot'] = $class_instance->info['screenshot'];
            wp_set_object_terms($new_block_id, $class_instance->info['group'], 'cj-ui-blocks-cat');
            echo json_encode($new_block_info);
            die();
        }


        public function dismissAdminNotice()
        {
            $id = $_POST['id'];
            $current_user = wp_get_current_user();
            $dismissed_notices = get_user_meta($current_user->ID, 'cjaddons_dismissed_notices', true);
            if ( ! is_array($dismissed_notices)) {
                $dismissed_notices = array();
            }
            $dismissed_notices[$id] = $id;
            update_user_meta($current_user->ID, 'cjaddons_dismissed_notices', $dismissed_notices);
            die();
        }

        public function getGoogleAddresses()
        {
            $string = $_REQUEST['term'];
            $addresses = $this->helpers->getAddressFromGoogle($string, $_REQUEST['filter']);
            $return[] = array('address' => __('No address found.', 'cssjockey-add-ons'));
            if ( ! empty($addresses)) {
                $return = array();
                foreach ($addresses as $key => $value) {
                    $return[] = $value;
                }
            }
            echo json_encode($return);
            die();
        }

        public function getGoogleAddressesByLatLng()
        {
            $addresses = $this->helpers->getAddressByCoords($_REQUEST['lat'], $_REQUEST['lng']);
            $return[] = __('No address found.', 'cssjockey-add-ons');
            if ( ! empty($addresses)) {
                $return = array();
                foreach ($addresses as $key => $value) {
                    $return[] = $value;
                }
            }
            echo json_encode($return);
            die();
        }

        public function getAddressByPostalCode()
        {
            $address = $this->helpers->getAddressesByPostcode($_POST['zip']);
            $return = array();
            if (is_array($address)) {
                $return = $address;
            } else {
                $return = array();
            }
            echo json_encode($return);
            die();
        }

        public function vueUploadUserAvatar()
        {
            $return = array();

            // check if square
            list($width, $height) = getimagesize($_FILES['file']['tmp_name']);

            if ($width !== $height) {
                $return['errors'] = __('You must upload an image with same width and height. e.g. 125x125 pixels', 'cssjockey-add-ons');
                echo json_encode($return);
                die();
            }

            $fileParts = @pathinfo($_FILES['file']['name']);
            if ( ! in_array($fileParts['extension'], array('jpg', 'jpeg', 'png', 'gif'))) {
                $return['errors'] = __('File format is not allowed.', 'cssjockey-add-ons');
                echo json_encode($return);
                die();
            }

            /*if( is_user_logged_in() && ! wp_verify_nonce( $_POST['_wp_nonce'], 'vue-upload-avatar' ) ) {
                $return['error'] = __( 'You are not allowed to perform this action.', 'cssjockey-add-ons' );
                echo json_encode( $return );
                die();
            }*/
            $upload_dir = $this->helpers->upload_path . '/cssjockey-add-ons/user-avatars';

            add_filter('upload_dir', function ($upload) {
                $upload['subdir'] = '/user-avatars/';
                $upload['path'] = $upload['basedir'] . '/cssjockey-add-ons/user-avatars';
                $upload['url'] = $upload['baseurl'] . '/cssjockey-add-ons/user-avatars';

                return $upload;
            });

            $allowed_file_types = null;
            $mime_types = array();
            $allowed_file_mime_types = (isset($_POST['allowed_file_types'])) ? explode(',', $_POST['allowed_file_types']) : null;
            if (is_array($allowed_file_mime_types)) {
                foreach ($allowed_file_mime_types as $key => $mime_type) {
                    $mime_types = explode('/', $mime_type)[0];
                }
            }
            if (is_array($mime_types)) {
                $allowed_file_types = implode(',', $mime_types);
            }
            $upload_data = $this->helpers->uploadFile($_FILES['file'], null, null, $allowed_file_types, null, 'guid', $upload_dir);
            if (is_array($upload_data)) {
                $return['errors'] = $upload_data['errors'];
            } else {
                $return['success'] = $upload_data;
            }
            echo json_encode($return);

            die();
        }

        public function vueUploadFiles()
        {
            if ( ! isset($_POST['delete_file'])) {
                $return = array();
                if ( ! wp_verify_nonce($_POST['_wp_nonce'], 'vue-upload-avatar')) {
                    $return['error'] = __('You are not allowed to perform this action.', 'cssjockey-add-ons');
                    die();
                }
                $upload_dir = $this->helpers->upload_path . '/cssjockey-add-ons/files';
                add_filter('upload_dir', function ($upload) {
                    $upload['subdir'] = '/files/';
                    $upload['path'] = $upload['basedir'] . '/cssjockey-add-ons/files';
                    $upload['url'] = $upload['baseurl'] . '/cssjockey-add-ons/files';

                    return $upload;
                });

                $allowed_file_types = null;
                $allowed_file_mime_types = (isset($_POST['allowed_file_types'])) ? explode(',', $_POST['allowed_file_types']) : null;
                if (is_array($allowed_file_mime_types)) {
                    foreach ($allowed_file_mime_types as $key => $mime_type) {
                        $allowed_file_types = explode('/', $mime_type)[0];
                    }
                }

                $upload_data = $this->helpers->uploadFile($_FILES['file'], null, null, $allowed_file_types, null, 'guid', $upload_dir);
                if (is_array($upload_data)) {
                    $return['errors'] = $upload_data['errors'];
                } else {
                    $return['success'] = $upload_data;
                }
                echo json_encode($return);

                die();
            }
            if (isset($_POST['delete_file'])) {
                $file_url = $_POST['delete_file'];
                $file_path = str_replace($this->helpers->root_url, $this->helpers->root_dir, $file_url);
                $this->helpers->deleteFile($file_path);
                $return['success'] = $_POST['delete_file'];
                echo json_encode($return);
                die();
            }
        }

        public function vueUploadAddon()
        {
            global $wpdb;
            $upload_dir = WP_PLUGIN_DIR;
            $source = $_FILES['file'];
            $destination = trailingslashit($upload_dir);
            $file_name = str_replace('.zip', '', $source['name']);
            $file_type = substr($file_name, 0, 6);
            if ($file_type != 'addon-' && $file_type != 'cjuib-') {
                echo json_encode(array('errors' => __('You must upload a CSSJockey Add-on or a UI Block.', 'cssjockey-add-ons')));
                die();
            }

            if ($file_type == 'addon-') {
                $extension = pathinfo($source['name'], PATHINFO_EXTENSION);
                if ($extension !== 'zip') {
                    echo json_encode(array('errors' => __('Invalid file type.', 'cssjockey-add-ons')));
                    die();
                }
                if (is_dir($destination . $file_name)) {
                    $this->helpers->deleteDirectory($destination . $file_name);
                }
                $response = $this->helpers->unzipFile($source['tmp_name'], $destination);
                if (is_array($response) && ! empty($response)) {
                    echo json_encode(array('errors' => implode('<br>', $response)));
                } else {
                    echo json_encode(array('success' => __('Addon uploaded successfully!', 'cssjockey-add-ons')));
                }
            }

            if ($file_type == 'cjuib-') {
                $upload_dir = $this->helpers->upload_path . '/cssjockey-add-ons/ui-blocks';
                $source = $_FILES['file'];
                $destination = trailingslashit($upload_dir);
                $file_name = str_replace('.zip', '', $source['name']);

                $blocks = $wpdb->get_results("SELECT * FROM $wpdb->postmeta WHERE meta_key = '_component_info' AND meta_value LIKE '%{$file_name}%'");
                if (is_array($blocks)) {
                    foreach ($blocks as $key => $block) {
                        update_post_meta($block->post_id, '_cjaddons_compile_sass', 'yes');
                    }
                }

                $extension = pathinfo($source['name'], PATHINFO_EXTENSION);
                if ($extension !== 'zip') {
                    echo json_encode(array('errors' => __('Invalid file type.', 'cssjockey-add-ons')));
                    die();
                }
                if (is_dir($destination . $file_name)) {
                    $this->helpers->deleteDirectory($destination . $file_name);
                }
                $response = $this->helpers->unzipFile($source['tmp_name'], $destination . $file_name);
                if (is_array($response) && ! empty($response)) {
                    echo json_encode(array('errors' => implode('<br>', $response)));
                } else {
                    $upload_dir_msg = explode('wp-content', $upload_dir);
                    echo json_encode(array('success' => sprintf(__('UI BLock Uploaded to <br>%s.', 'cssjockey-add-ons'), 'wp-content' . $upload_dir_msg[1] . '/' . $file_name)));
                }
            }

            die();
        }

        public function queryPostsCallback()
        {
            $query_args = (isset($_REQUEST['args']) && is_array($_REQUEST['args'])) ? $_REQUEST['args'] : array();
            $return = array();
            $query_args['paged'] = (isset($query_args['paged'])) ? $query_args['paged'] : 1;
            $the_query = new WP_Query($query_args);
            $count = -1;
            $posts = array();
            if ($the_query->have_posts()) {
                $count = -1;
                while ($the_query->have_posts()) {
                    $count++;
                    $the_query->the_post();
                    global $post;
                    $post_info = $this->helpers->postInfo($post->ID);
                    $post_info['sort_order'] = strtotime($post_info['post_date']);
                    $posts[$count] = $post_info;
                }
                if (is_array($posts) && ! empty($posts)) {
                    $return['posts'] = $posts;
                    $return['post_count'] = $the_query->post_count;
                    $return['total_posts'] = (int)$the_query->found_posts;

                    $return['pagination'] = array();
                    // pagination data
                    $total_pages = $the_query->max_num_pages;
                    $current_page = (int)$the_query->query['paged'];
                    $next_page = $the_query->query['paged'] + 1;
                    $previous_page = $the_query->query['paged'] - 1;
                    if ($next_page > $total_pages) {
                        $next_page = null;
                    }
                    if ($previous_page <= 0) {
                        $previous_page = null;
                    }
                    $return['pagination']['total_pages'] = $total_pages;
                    $return['pagination']['current_page'] = $current_page;
                    $return['pagination']['next_page'] = $next_page;
                    $return['pagination']['previous_page'] = $previous_page;
                    $return = apply_filters('modify_cjaddons_query_posts', $return);
                } else {
                    $return = array();
                }
                wp_reset_postdata();
            } else {
                $return = array();
            }
            echo json_encode($return);
            die();
        }

        public function queryUsersCallback()
        {
            $users = get_users($_POST);
            $return = array();
            if ( ! empty($users)) {
                foreach ($users as $key => $user) {
                    $user_info = $this->helpers->userInfo($user->ID);
                    $return[] = $user_info;
                }
            }
            echo json_encode($return);
            die();
        }

    }

    cjaddons_global_ajax_support::getInstance();
}