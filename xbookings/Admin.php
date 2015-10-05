<?php

class XBookingAdmin {

	/**
	 * Current page
	 *
	 * @var string
	 */
	var $_page = 'tm-settings';

	function __construct(){
		add_action('admin_enqueue_scripts', array($this,'scripts'));
		add_action('admin_init', array($this,'register_settings'));
		add_action('admin_init', array($this,'strip_wp_http_referer'));
		add_action('admin_menu', array($this,'menu'), 1);
		add_action('admin_init', array($this,'admin_notification'));
		add_filter( "option_page_capability_datemgmt", array($this,"datemgmt_capability") );
	}
	
	function scripts(){
		wp_register_script( 'tm_admin_js', plugins_url('/js/admin.js', TM_PLUGIN_FILE), array('jquery'), '1.0.0' );
		wp_enqueue_script( 'tm_admin_js' );

		wp_enqueue_script('jquery-ui-datepicker');
		
		wp_register_style( 'tm_admin_css', plugins_url('/css/admin.css', TM_PLUGIN_FILE), false, '1.0.0' );
		wp_enqueue_style( 'tm_admin_css' );
	}
	
	/** Add admin pages

	 */
	function menu(){

		$menus = array(

			'datemgmt' => array(
				__('Block Dates', 'tm'),
				__('Block Dates', 'tm'),
				'edit_pages'
			),
			'openinghrs' => array(
				__('Opening Hours', 'tm'),
				__('Opening Hours', 'tm'),
				'edit_pages'
			),
		);

		add_menu_page(__('TM', 'tm'), __('Bookings', 'tm'), 'edit_pages', 'tm-settings', array($this,'page'), '');

		$menus = apply_filters('tm_admin_menus', $menus);
		
		foreach ($menus as $slug => $titles) {
			add_submenu_page('tm-settings', $titles[0], $titles[1], $titles[2], $slug, array($this,'page'));
		}	
		
	}
	function datemgmt_capability(){
		return 'edit_posts';
	}
	
	/**
	 * Admin screen success message
	 */
	
	function admin_notification(){
		if ($_GET['settings-updated']) {
			echo '<div class="updated">
		       <p>Your changes have been saved.</p>
		    </div>';
		}
	}
	
	
	
	/** Register plugin settings
	 * 
	 * @param string $group
	 * @return array
	 */
	function settings($group = false){

		$settings = array(
				'datemgmt' => array(
						array(
								__('Block Dates', 'tm'),
								'tm_block_dates',
								'textarea',
								'List dates separated by spaces in D-MM-YYYY format, eg: 4-05-2014 21-05-2014'
						),
				),
				'openinghrs' => array(
						array(
								__('Monday', 'tm'),
								'open_mon',
								'text',
						),
						array(
								__('Tuesday', 'tm'),
								'open_tue',
								'text',
						),
						array(
								__('Wednesday', 'tm'),
								'open_wed',
								'text',
						),
						array(
								__('Thursday', 'tm'),
								'open_thu',
								'text',
						),
						array(
								__('Friday', 'tm'),
								'open_fri',
								'text',
						),
						array(
								__('Saturday', 'tm'),
								'open_sat',
								'text',
						),
						array(
								__('Sunday', 'tm'),
								'open_sun',
								'text',
								'Provide times in the format "HH:MM - HH:MM"'
						),
				),
		);

		$settings = apply_filters('tm_settings', $settings);
		return ($group !== false) ? $settings[$group] : $settings;
	}

	function register_settings(){
		$settings = $this->settings();

		foreach($settings as $group => $setting_fields){
			foreach($setting_fields as $setting){
				register_setting( $group, $setting[1] );
			}
		}
	}

	/**
	 * Remove referring page from URL to prevent exceeding the URI length limit
	 */
	
	public function strip_wp_http_referer () {
		$current_url = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
	
		if (strpos($current_url, '_wp_http_referer') !== false) {
			$new_url = remove_query_arg( array( '_wp_http_referer', '_wpnonce' ), stripslashes($current_url));
			wp_redirect ($new_url);
		}
	}

	/** Admin page markup
	 *
	 */
	function page(){
		$this->_page = $_REQUEST['page'];
		$setting_fields = $this->settings($this->_page);
		
		$admin_page = $_REQUEST['page'];
		
		if ($admin_page == 'tm-settings') {
			
			$BookingsListTable = new BookingsListTable();
			?>
					<div class="wrap">
					<form>
						<h2 class="logo">Review Bookings</h2>
						
						<label for="m">Filter by month:</label><?php $BookingsListTable->months_dropdown(); ?>
						<button type="submit" class="button-secondary">Filter</button>
						<input type="hidden" name="page" value="<?php echo $this->_page; ?>"/>
						
						<?php $BookingsListTable->prepare_items(); ?>
						<?php $BookingsListTable->display(); ?>
						
					</form>
			
					</div>
					<?php
			
		} else {
			?>
		<div class="wrap">
			<h2 class="logo"><?php echo get_admin_page_title(); ?></h2>
			<form action="options.php" method="post">
				<?php settings_fields( $this->_page ); ?>
				<table class="form-table">
				<?php foreach ($setting_fields as $setting) : ?>
					<tr valign="top">
						<th scope="row"><?php echo $setting[0]; ?></th>
						<td>
							<?php switch ($setting[2]) {
								case 'textarea':
									echo '<textarea type="text" name="'. $setting[1] .'" cols="80" rows="10" class="large-text">'. get_option($setting[1]) .'</textarea>';
									break;
								case 'number':
									echo '<input type="number" step="1" min="0" name="'. $setting[1] .'" value="'. get_option($setting[1]) .'" class="all-options"/>';
									break;
								case 'datepicker':
									echo '<input type="text" class="datepicker '.$setting[1].'">
									<textarea type="text" name="'. $setting[1] .'" cols="80" rows="10" class="large-text">'. get_option($setting[1]) .'</textarea>';
									break;
								default:
									echo '<input type="textarea" name="'. $setting[1] .'" value="'. get_option($setting[1]) .'" class="regular-text"/>';
									break;
							} ?>
							<p class="description"><?php echo $setting[3]; ?></p>
						</td>
					</tr>
				<?php endforeach; ?>
				</table>
				<?php
				submit_button(); ?>
			</form>	
		</div>
		<?php			
		}
	}
}