<?php
/*
Plugin Name: Reorder Multisite Sites Dropdown
Plugin URI: https://www.kodeala.com
Description: Reorder the "My Sites" dropdown menu in the Admin Bar.
Author: Kodeala
Version: 1.0.1
Network: true
Tags: multisite, multi site, network, my sites, dropdown, menu, reorder, customization, WordPress, plugin, network admin, administration, network sites, site list, site order, admin bar, network menu
Requires at least: 4.5
Tested up to: 6.3
Requires PHP: 7.0
Stable tag: 1.0.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/
if (is_multisite()) {
	function kodeala_msdd_scripts()
	{
		wp_enqueue_style('kodeala-style-css', plugins_url('/css/style.css', __FILE__));
		wp_enqueue_script('jquery-ui-sortable');
		wp_enqueue_script('kodeala-functions-js', plugins_url('/js/functions.js', __FILE__));
	}
	add_action('admin_enqueue_scripts', 'kodeala_msdd_scripts');


	class kodeala_msdd_Settings_Page
	{
		protected $kodeala_msdd_settings_slug = 'reorder-my-sites';
		public function __construct()
		{
			add_action('network_admin_menu', array(
				$this,
				'kodeala_msdd_menu_and_fields'
			));
			add_action('network_admin_edit_' . $this->kodeala_msdd_settings_slug . '-update', array(
				$this,
				'kodeala_msdd_update'
			));
		}


		public function kodeala_msdd_function()
		{
			global $wp_admin_bar;
			$sites          		= $wp_admin_bar->user->blogs;
			$subsites				= get_sites();
			$kodeala_msdd_options	= get_site_option('kodeala_msdd', '');
			$count 					= 0;

			$kodeala_msdd_additional = array();
			foreach( $subsites as $subsite ) {
				$subsite_id = get_object_vars($subsite)["blog_id"];
				$kodeala_msdd_additional[] = $subsite_id;
			}
			if(!empty($kodeala_msdd_options)){
				foreach ($kodeala_msdd_additional as $site_id) {
					if(!in_array($site_id, filter_var_array($kodeala_msdd_options, FILTER_SANITIZE_NUMBER_INT))){ //Compare the IDs of sites with IDs of saved sites. Add additional site to bottom of saved list.
						$kodeala_msdd_options[] = $site_id;
					}
				}
			}

			echo '<div class="mysites-sortable">';
			if ($kodeala_msdd_options) {
				foreach ($kodeala_msdd_options as $site) {
					switch_to_blog($kodeala_msdd_options[esc_html($count)]);
					echo '<div class="mysites-sortable-row">';
					echo '<div class="mysites-moverow"><span class="dashicons dashicons-move"></span></div>';
					echo '<div class="mysites-title">' . esc_html(get_bloginfo('blogname')) . '</div>';
					echo '<div class="mysites-url"><a href="'. esc_url(get_bloginfo('siteurl')) .'">' . esc_url(get_bloginfo('siteurl')) . '</a></div>';
					echo '<input type="hidden" readonly name="kodeala_msdd[' . esc_html($count) . ']" value="' . esc_html(get_current_blog_id()) . '" />';
					echo '</div>';
					restore_current_blog();
					esc_html($count++);
				}
			} else {
				foreach ($sites as $site) {
					echo '<div class="mysites-sortable-row">';
					echo '<div class="mysites-moverow"><span class="dashicons dashicons-move"></span></div>';
					echo '<div class="mysites-title">' . esc_html($site->blogname) . '</div>';
					echo '<div class="mysites-url"><a href="'. esc_url($site->siteurl) .'">' . esc_url($site->siteurl) . '</a></div>';
					echo '<input type="hidden" readonly name="kodeala_msdd[' . esc_html($count) . ']" value="' . esc_html($site->userblog_id) . '" />';
					echo '</div>';
					esc_html($count++);
				}
			}
			echo '</div>';
		}

		public function kodeala_msdd_menu_and_fields()
		{
			add_submenu_page(
				'settings.php',
				__('Reorder My Sites Dropdown Menu', 'multisite-settings'),
				__('Reorder My Sites', 'multisite-settings'),
				'manage_network_options',
				$this->kodeala_msdd_settings_slug . '-page',
				array(
					$this,
					'kodeala_msdd_create_page'
				)
			);

			// Register a new section on the page.
			add_settings_section(
				'default-section',
				'',
				'',
				$this->kodeala_msdd_settings_slug . '-page'
			);
			register_setting($this->kodeala_msdd_settings_slug . '-page', 'kodeala_msdd');
			add_settings_field(
				'kodeala_msdd', //ID
				'', //Title
				array(
					$this,
					'kodeala_msdd_function'
				), // callback.
				$this->kodeala_msdd_settings_slug . '-page', // page.
				'default-section' // section.
				);

		}

		//Create Settings Page
		public function kodeala_msdd_create_page()
		{
			if (isset($_GET['updated'])){
			?>
			<div id="message" class="updated notice is-dismissible">
				<p>
					<?php esc_html_e('Options Saved', 'multisite-settings'); ?>
				 </p>
			</div>
		<?php } ?>
		<div class="wrap mysites-settings">
			<h1><?php echo esc_attr(get_admin_page_title()); ?></h1>
			<form action="edit.php?action=<?php echo esc_attr($this->kodeala_msdd_settings_slug); ?>-update" method="POST">
				<?php
					settings_fields($this->kodeala_msdd_settings_slug . '-page');
					do_settings_sections($this->kodeala_msdd_settings_slug . '-page');
					submit_button();
				?>
			</form>
		</div>
		<?php
		}

		//Update Settings
		public function kodeala_msdd_update()
		{
			check_admin_referer($this->kodeala_msdd_settings_slug . '-page-options');
			global $new_whitelist_options;

			$options = $new_whitelist_options[$this->kodeala_msdd_settings_slug . '-page'];

			foreach ($options as $option) {
				$post_Options = filter_var_array($_POST[$option], FILTER_SANITIZE_NUMBER_INT);
				if (isset($_POST[$option])) {
					update_site_option($option, $post_Options);
				} else {
					delete_site_option($option);
				}
			}

			wp_safe_redirect(add_query_arg(array(
				'page' => $this->kodeala_msdd_settings_slug . '-page',
				'updated' => 'true'
			), network_admin_url('settings.php')));
			exit;
		}

	}

	new kodeala_msdd_Settings_Page();

	//Reorder My Sites Dropdown Menu
	class kodeala_msdd_reorder_mysite_dd
	{
		function __construct()
		{
			add_action('admin_bar_menu', array(
				$this,
				'kodeala_msdd_admin_bar_menu'
			));
		}

		function kodeala_msdd_admin_bar_menu()
        {
            global $wp_admin_bar;
            $sites = $wp_admin_bar->user->blogs;
            $subsites = get_sites();
            $kodeala_msdd_options = get_site_option('kodeala_msdd');
            if ($kodeala_msdd_options) {
                $wp_admin_bar->user->blogs = array();
                foreach ($kodeala_msdd_options as $site_id) {
                    $wp_admin_bar->user->blogs[$site_id] = $sites[$site_id];
                }

                $kodeala_msdd_additional = array();
                foreach ($subsites as $subsite) {
                    $subsite_id = get_object_vars($subsite)["blog_id"];
                    $kodeala_msdd_additional[] = $subsite_id;
                }
                if (!empty($kodeala_msdd_options)) {
                    foreach ($kodeala_msdd_additional as $site_id) {
                        if (!in_array($site_id, filter_var_array($kodeala_msdd_options, FILTER_SANITIZE_NUMBER_INT))) {
                            // Compare the IDs of sites with IDs of saved sites. Add additional site to the bottom of the saved list.
                            $wp_admin_bar->user->blogs[$site_id] = $sites[$site_id];
                        }
                    }
                }
            }
        }

	}
	$kodeala_msdd_reorder_mysite_dd = new kodeala_msdd_reorder_mysite_dd();
}
?>