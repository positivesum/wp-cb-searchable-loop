<?php
// Class
if (!class_exists('cfct_module_loop_searchable') && class_exists('cfct_build_module')) {
	class cfct_module_loop_searchable extends cfct_build_module {
		protected $_deprecated_id = 'cfct-loop-searchable';

# Constructor
		public function __construct() {
			// Plugin vars
			$this->pluginDir		= basename(dirname(__FILE__));
			$this->pluginPath		= WP_PLUGIN_DIR . '/' . $this->pluginDir;
			$this->pluginUrl 		= WP_PLUGIN_URL.'/'.$this->pluginDir;

			// Module options
			$opts = array(
				// Plugin Url
				'url' => $this->pluginUrl,
				// Default View
				'view' => $this->pluginPath.'/view.php',
				// Description
				'description' => __('Display a list of posts with category and keyword search.', 'carrington-build'),
				// Icon
				'icon' => $this->pluginUrl.'/icon.png'
			);

			// Register new query vars
			add_filter('query_vars', array($this, 'query_vars'));

			// use if this module is to have no user configurable options
			// Will suppress the module edit button in the admin module display
			# $this->editable = false

			parent::__construct('cfct-loop-searchable', __('Searchable Loop', 'carrington-build'), $opts);
		}

		/**
		 * Don't contribute to the post_content stored in the database
		 *
		 * @return null
		 */
		public function text() {
			return null;
		}
#Query Params
		public function query_vars($vars) {
			$vars[] = 'category';
			$vars[] = 'keywords';
            $vars[] = 'year';
            $vars[] = 'month';
            $vars[] = 'oby';

			return $vars;
		}

# Display
		public function display($data) {
			global $wp_query, $post, $wpdb, $wp_locale;

			// Module title
			$title = esc_html($data[$this->get_field_id('title')]);
			// Module description
			$description = $data[$this->get_field_id('description')];
            // Header form
            $header_form = empty($data[$this->get_field_name('show_header_form')]) ? 'on' : $data[$this->get_field_name('show_header_form')];

			// Page
			$paged = get_query_var('paged');

			// Category List
			$categories = $this->get_post_categories();

            // Order param
            $orderby = get_query_var('oby');
            $orderby = ($orderby == "") ? $orderby = 'date' : $orderby;
            // Year
            $year = get_query_var('year');
            // Month
            $month = get_query_var('month');

			// Current category (set in admin or choose from drop down menu in frontend)
            $default_category = $data[$this->get_field_name('post_category')];
			$category = get_query_var('category');
			$category = ($category !== "" ) ? get_query_var('category') : $data[$this->get_field_name('post_category')];

			// Keywords for search
			$keywords = get_query_var('keywords');

			// Make new Query
			$query_string = array(
                'posts_per_page=9',
				'order=DESC',
				'paged='.$paged
			);
            if ($orderby) {
                $query_string[] = 'orderby='.$orderby;
            }
            if ($year) {
                $query_string[] = 'year='.$year;
            }
            if ($month) {
                $query_string[] = 'monthnum='.$month;
            }

			if ($category) {
				$query_string[] = 'category__in='.$category;
			}
			if ($keywords) {
				$query_string[] = 's='.$keywords;
			}
			// Make query string
			$query_string = implode('&', $query_string);

            // Get available years
            $_years = $wpdb->get_results( "SELECT YEAR(post_date) as Y FROM $wpdb->posts GROUP BY YEAR(post_date)");
            $years = array();
            foreach ($_years as $_year) {
                $years[] = $_year->Y;
            }
            rsort($years);
            // Generate months array
            for ( $i = 1; $i < 13; $i = $i +1 ) {
                $months[] = $wp_locale->get_month_abbrev( $wp_locale->get_month( $i ) );
            }

			// Make new WP_Query object
			query_posts($query_string);
            $wp_query = $GLOBALS['wp_query'];

			// Output
			return $this->load_view(
                $data,
                compact(
                    'title',
                    'description',
                    'categories',
                    'category',
                    'months',
                    'month',
                    'years',
                    'year',
                    'order',
                    'wp_query',
                    'keywords',
                    'header_form',
                    'default_category'
                )
            );
		}

# Admin Form
		public function admin_form($data) {
			// Form wrapper
			$out = '<div id="'.$this->id_base.'-admin-form-wrapper">';

			// Title - the simple textfield (input type="text")
			$out .= $this->admin_form_title($data);

			// Description - textarea
			$out .= $this->admin_form_description($data);

            // Show header form?
            $out .= $this->admin_form_header_form($data);

			// Post settings (type, category)
			$out .= $this->admin_form_post_settings($data);

			// Close form wrapper
			$out .= '</div>';

			return $out;
		}
# Update
        /**
         * Update data, standard is to just return the new data
         *
         * @param array $new_data
         * @param array $old_data
         * @return array
         */
        function update($new_data, $old_data) {
            if (empty($new_data[$this->get_field_name('show_header_form')])) {
                $new_data[$this->get_field_name('show_header_form')] = 'off';
            }
            return $new_data;
        }
# Admin Helpers
		public function admin_text($data) {
			return strip_tags($data[$this->get_field_name('title')]);
		}

		public function admin_js() {
			return '';
		}
		public function admin_css() {
			return '';
		}

		private function admin_form_post_settings($data) {
			// Post type - drop down with all post types (posts/pages/etc.)
			// Category - categories for selected post type
			$out = '
				<fieldset class="cfct-form-section">
					<legend>'.__('Post Options', 'carrington-build').'</legend>

					<div class="'.$this->id_base.'-display-group-left">
						<!-- post type -->
						<div class="cfct-inline-els">
							'.$this->admin_form_post_types($data).'
						</div>
						<!-- / post type -->
						<!-- post type categories -->
						<div class="cfct-inline-els">
							'.$this->admin_form_post_categories($data).'
						</div>
						<!-- / post type categories -->

					</div>
				</fieldset>
			';

			return $out;
		}

		/**
		 * Return html of admin form drop down post types
		 * @param  $data
		 * @return string
		 */
		private function admin_form_post_types($data) {
			$out = '
				<label for="'.$this->id_base.'-post_type">Type: </label>
				<select class="post_type-dropdown" name="'.$this->get_field_id('post_type').'" id="'.$this->get_field_id('post_type').'">
			';

			$options = $this->get_post_types();
			$value = $data[$this->get_field_name('post_type')];

			foreach ($options as $post_type => $option) {
				$selected = '';
				if ($value == $post_type) {
					$selected = 'selected';
				}
				$out .= '<option value="'.$post_type.'" '.$selected.'>'.$option->label.'</option>';
			}

			$out .= '</select>';

			return $out;
		}
		/**
		 * Return html of drop downs (select's) with items for every post type
		 * @param  $data
		 * @return string
		 */
		private function admin_form_post_categories($data) {
			$value = $data[$this->get_field_name('post_category')];

			$out = '<label for="'.$this->id_base.'-post_category">Category: </label>';

			$out .= wp_dropdown_categories(
				array(
					 'id' => $this->get_field_id('post_category'),
					 'selected' => $value,
				     'hide_empty' => 0,
					 'echo' => false,
				     'hide_if_empty' => false,
				     'taxonomy' => 'category',
				     'name' => $this->get_field_id('post_category'),
				     'orderby' => 'name',
					 'class' => 'post_category-dropdown',
				     'hierarchical' => true,
				     'show_option_none' => __('Select Category')
				)
			);

			return $out;
		}

        // Settings for loop (posts per page, etc)
        private function admin_form_loop_settings($data) {}

        // Settings for header forms
        private function admin_form_header_form($data) {
            $checked = '';
            if (empty($data[$this->get_field_name('show_header_form')]) || $data[$this->get_field_name('show_header_form')] == 'on') {
                $checked = 'checked';
            }

            $out = '<label for="'.$this->id_base.'-show_header_form">Show Header Form: </label>';
            $out .= '<input '.$checked.' type="checkbox" name="'.$this->get_field_id('show_header_form').'" id="'.$this->get_field_id('show_header_form').'" />';

            return $out;
        }

		/**
		 * Return html of admin form title
		 * @param  $data
		 * @return string
		 */
		private function admin_form_title($data) {
			$out = '
				<fieldset>
					<!-- title -->
					<div class="'.$this->id_base.'-input-wrapper">
						<label for="'.$this->get_field_id('title').'">'.__('Title', 'carrington-build').'</label>
						<input type="text" name="'.$this->get_field_id('title').'" id="'.$this->get_field_id('title').'" value="'.$data[$this->get_field_name('title')].'" />
					</div>
					<div class="clear"></div>
					<!-- /title -->
				</fieldset>
			';
			return $out;
		}
		/**
		 * Return html of admin form description (textarea)
		 * @param  $data
		 * @return string
		 */
		private function admin_form_description($data) {
			$out = '
				<fieldset>
					<!-- description -->
					<div class="'.$this->id_base.'-textarea-wrapper">
						<label for="'.$this->get_field_id('description').'">'.__('Description', 'carrington-build').'</label>
						<textarea name="'.$this->get_field_id('description').'" id="'.$this->get_field_id('description').'">'.$data[$this->get_field_name('description')].'</textarea>
					</div>
					<div class="clear"></div>
					<!-- /description -->
				</fieldset>
			';
			return $out;
		}

		/**
		 * Get array of objects post types
		 * @return array
		 */
		private function get_post_types($args = '') {
			// Args for search post types
			$defaults = array(
				'public' => true,
				'show_in_nav_menus' => true,
				'capability_type' => 'post'
			);
			$args = wp_parse_args( $args, $defaults );

			// Get array of post types objects
			$post_types = get_post_types($args, 'objects');

			// Remove post types without taxonomy "Category"
			foreach ($post_types as $type => $obj) {
				// Get taxonomies list for post type
				$_taxonomies = get_object_taxonomies($type, 'object');

				// Need only post types with taxonomy - "Category"
				if (false == isset($_taxonomies['category'])) {
					unset($post_types[$type]);
				}
			}

			return $post_types;
		}

		/**
		 * Return array of categories objects
		 *
		 * @param string $args
		 * @return array
		 */
		private function get_post_categories($args = '') {
			$defaults = array( 'taxonomy' => 'category' );
			$args = wp_parse_args( $args, $defaults );

			// Get categories
			$categories = (array) get_terms( $args['taxonomy'], $args );

			return $categories;
		}
	}

	cfct_build_register_module('cfct_module_loop_searchable');
}

