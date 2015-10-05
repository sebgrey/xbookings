<?php 
//Note: This should be called using the shortcode [bookingform]

global $xbooking_errors; ?>			
<div class="bookingform">			 
	<form id="bookavisit" action="" method="post">
	
		<label for="date">Date</label><input type="text" name="date" id="booking-datepicker" class="booking-datepicker" value="<?php echo $_GET['setdate'];?>">
			<div class="errormessage"><?php echo $xbooking_errors->get_error_message('invalid_date');echo $xbooking_errors->get_error_message('no_availability'); ?></div>
			
		<label for="time">Time</label><input type="text" name="time" id="booking-timepicker" class="booking-timepicker" value="<?php echo $_GET['settime'];?>">
			<div class="errormessage"><?php echo $xbooking_errors->get_error_message('invalid_time');echo $xbooking_errors->get_error_message('no_availability'); ?></div>
			
		<label for="groupsize">Number of people</label><input type="text" name="groupsize" value="<?php echo $_GET['setgroupsize'];?>">
			<div class="errormessage"><?php echo $xbooking_errors->get_error_message('invalid_groupsize'); ?></div>
			
		<label for="fullname">Name</label><input type="text" name="fullname" value="<?php echo $_GET['setname'];?>">
			<div class="errormessage"><?php echo $xbooking_errors->get_error_message('invalid_fullname'); ?></div>
			
		<label for="phone">Contact number</label><input type="text" name="phone" value="<?php echo $_GET['setphone'];?>">
			<div class="errormessage"><?php echo $xbooking_errors->get_error_message('invalid_phone'); ?></div>
			
		<label for="email">Email</label><input type="text" name="email" value="<?php echo $_GET['setemail'];?>">
			<div class="errormessage"><?php echo $xbooking_errors->get_error_message('invalid_email'); ?></div>
		
		<button type="submit" name="submit">Submit</button>
		<?php wp_nonce_field("booking-form"); ?>
	</form>
</div>