jQuery(document).ready(function($){

	$("#booking-timepicker").timepicker({
		timeFormat: "H:i"
	});
	
	$("#booking-datepicker").on('click', function(){
		var chosendate = $(this).val();
		
		// Disable blocked dates
		
		$.ajax({
			type: 'POST',
			dataType: 'json',
			url: wp_ajax.url,
			data: {
				action: 'get_blocked_dates',
			},
			success: function(response) {
				array = response.data.blocked;
				
				$("#booking-datepicker").datepicker({
					dateFormat: 'dd-mm-yy',
					beforeShowDay: function(date){
						
						var string = $.datepicker.formatDate('dd-mm-yy', date);
						return [$.inArray(string, array) == -1];
					}
				});
				
				$("#booking-datepicker").datepicker('show');
			}
		});
		
		
		// Get opening hours for the selected day
		$.ajax({
			type: 'POST',
			dataType: 'json',
			url: wp_ajax.url,
			data: {
				action: 'get_opening_times',
				chosendate: chosendate
			},
			success: function(response) {
				console.log(response.data.open);
				console.log(response.data.close);
				
				var open = response.data.open;
				var close = response.data.close;
				
				$("#booking-timepicker").timepicker(
					'option', {'minTime':open, 'maxTime':close} 
				);
			}
			
		});
	
	});
	
});
