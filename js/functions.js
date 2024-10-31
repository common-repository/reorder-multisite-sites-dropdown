jQuery(function($){
	function kodeala_msdd_UpdateName() {
		var $mysitesCount = 0;
		var $mySiteContainer = $('.mysites-sortable > div');
		var $mysitesLength = $mySiteContainer.length;
		$mySiteContainer.each(function(){
			var $mySiteRowName = $(this).find('input[name*="kodeala_msdd"]').attr('name').replace(/\d+(?=\D*$)/,$mysitesCount);
			$(this).find('input[name*="kodeala_msdd"]').attr('name', $mySiteRowName);
			$mysitesCount++
		});
	}

	$(".mysites-sortable").sortable({
        items: '> div',
		handle: '.mysites-moverow',
		update: function() {
			kodeala_msdd_UpdateName();
		}
    });
});