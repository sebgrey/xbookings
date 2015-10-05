<?php
if( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class BookingsListTable extends WP_List_Table {
	
	function __construct(){
		global $status, $page;

		parent::__construct( array(
				'singular'  => __( 'booking', 'mylisttable' ),     //singular name of the listed records
				'plural'    => __( 'bookings', 'mylisttable' ),   //plural name of the listed records
				'ajax'      => false        //does this table support ajax?
		) );

	}

	function no_items() {
		_e( 'No bookings found.' );
	}

	function column_default( $item, $column_name ) {
		switch( $column_name ) {
			case 'date':
				return date(get_option('date_format'),strtotime($item[ $column_name ]));
			case 'time':
				return date('H:i',strtotime($item[$column_name]));
			case 'created':
			case 'id':
			case 'groupsize':
			case 'fullname':
			case 'phone':
			case 'email':
			case 'status':
				return $item[ $column_name ];
			case 'active':
				return ($item[ $column_name ] == '1') ? 'Active':'Not Active' ;
				
			default:
				return print_r( $item, true ) ; //Show the whole array for troubleshooting purposes
		}
	}

	function get_columns(){
		$columns = array(
				'cb'      => __( '<input type="checkbox" />'),
				'date'      => __( 'Date', 'wp_xbookings' ),
				'time'      => __( 'Time', 'wp_xbookings' ),
				'groupsize' => __('Group Size', 'wp_xbookings'),
				'fullname'    => __( 'Name', 'wp_xbookings' ),
				'phone'      => __( 'Phone', 'wp_xbookings' ),
				'email'      => __( 'Email', 'wp_xbookings' ),
				'status'	=> __('Confirmation', 'wp_xbookings')	
		);
		return $columns;
	}

	function usort_reorder( $a, $b ) {
		// If no sort, default to date
		$orderby = ( ! empty( $_GET['orderby'] ) ) ? $_GET['orderby'] : 'date';
		// If no order, default to desc
		$order = ( ! empty($_GET['order'] ) ) ? $_GET['order'] : 'desc';
		// Determine sort order
		$result = strcmp( $a[$orderby], $b[$orderby] );
		// Send final sort direction to usort
		return ( $order === 'asc' ) ? $result : -$result;
	}

	function column_booktitle($item){
		$actions = array(
				'edit'      => sprintf('<a href="?page=%s&action=%s&book=%s">Edit</a>',$_REQUEST['page'],'edit',$item['ID']),
				'delete'    => sprintf('<a href="?page=%s&action=%s&book=%s">Delete</a>',$_REQUEST['page'],'delete',$item['ID']),
		);

		return sprintf('%1$s %2$s', $item['booktitle'], $this->row_actions($actions) );
	}
 
	function get_bulk_actions() {
		$actions = array(
				'confirm'	=> 'Confirm',
				'deny'		=> 'Deny',
				'pend'		=> 'Mark as Pending',
				'delete'    => 'Delete'
		);
		return $actions;
	}

	function process_bulk_actions() {
		
		if( 'confirm'===$this->current_action() ) {
			echo "<div id='message' class='updated below-h2'><p>Bookings confirmed.</p></div>";
				
			foreach($_GET["item"] as $booking_id) {
				$booking = XBooking::get("id={$booking_id}",ARRAY_A);
				$booking['status'] = 'confirmed';
				XBooking::update($booking);
			}
		}
		
		if( 'deny'===$this->current_action() ) {
			echo "<div id='message' class='updated below-h2'><p>Bookings denied.</p></div>";
				
			foreach($_GET["item"] as $booking_id) {
				$booking = XBooking::get("id={$booking_id}",ARRAY_A);
				$booking['status'] = 'denied';
				XBooking::update($booking);
			}
		}
		
		if( 'pend'===$this->current_action() ) {
			echo "<div id='message' class='updated below-h2'><p>Bookings marked as pending.</p></div>";
		
			foreach($_GET["item"] as $booking_id) {
				$booking = XBooking::get("id={$booking_id}",ARRAY_A);
				$booking['status'] = 'pending';
				XBooking::update($booking);
			}
		}
		
		if( 'delete'===$this->current_action() ) {
			echo "<div id='message' class='updated below-h2'><p>Bookings deleted.</p></div>";
			
			foreach($_GET["item"] as $booking_id) {
				$booking = XBooking::get("id={$booking_id}",ARRAY_A);
				$booking['active'] = false;
				XBooking::update($booking);
			}
		}
		
		$action = $this->current_action();
		XBooking::email_update($booking, $action);
	}
	
	
	function column_cb($item) {
		return sprintf(
				'<input type="checkbox" name="item[]" value="%s" />', $item['id']
		);
	}

	function prepare_items() {
		
		$this->process_bulk_actions();

		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );
		
		$args = array(
			'active' => '1'
		);
		
		if(isset($_GET["m"]) && !empty($_GET["m"])){
			$date = DateTime::createFromFormat("Ym", $_GET["m"]);
			$date = $date->modify('first day of this month');
			$date = $date->format('U');
			
			$args['date_query'] = array(
				"after" =>  date("Y-m-d",$date),
				"before" =>	date("Y-m-d",strtotime('+1 month',$date))
			);
		}
		
		$data = XBooking::get($args, ARRAY_A);
		
 		usort( $data, array( &$this, 'usort_reorder' ) );

		$per_page = 30;
		$current_page = $this->get_pagenum();
		$total_items = count( $data );

		// only necessary because we have sample data
		$this->found_data = array_slice( $data,( ( $current_page-1 )* $per_page ), $per_page );

		$this->set_pagination_args( array(
				'total_items' => $total_items,                  //WE have to calculate the total number of items
				'per_page'    => $per_page                     //WE have to determine how many items to show on a page
		) );
		$this->items = $this->found_data;
	
	}


	// Sortable columns 
	function get_sortable_columns() {
		$sortable_columns = array(
				'date'  => array('date',false),
				'status'  => array('status',false),
		);
		return $sortable_columns;
	}
	
	// Select by month
	
	function months_dropdown(  ) {
		global $wpdb, $wp_locale;
	
		$months = XBooking::unique_months();

		/**
		 * Filter the 'Months' drop-down results.
		 *
		 * @since 3.7.0
		 *
		 * @param object $months    The months drop-down query results.
		 * @param string $post_type The post type.
		*/
		$months = apply_filters( 'months_dropdown_results', $months, $post_type );
	
		$month_count = count( $months );
	
		if ( !$month_count || ( 1 == $month_count && 0 == $months[0]->month ) )
			return;
	
		$m = isset( $_GET['m'] ) ? (int) $_GET['m'] : 0;
		?>
			<select name='m'>
				<option<?php selected( $m, 0 ); ?> value='0'><?php _e( 'All dates' ); ?></option>
	<?php
			foreach ( $months as $arc_row ) {
				if ( 0 == $arc_row->year )
					continue;
	
				$month = zeroise( $arc_row->month, 2 );
				$year = $arc_row->year;
	
				printf( "<option %s value='%s'>%s</option>\n",
					selected( $m, $year . $month, false ),
					esc_attr( $arc_row->year . $month ),
					/* translators: 1: month name, 2: 4-digit year */
					sprintf( __( '%1$s %2$d' ), $wp_locale->get_month( $month ), $year )
				);
			}
	?>
			</select>
	<?php
		}
	
}

