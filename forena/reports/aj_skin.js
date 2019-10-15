(function ($) {
  Drupal.behaviors.ajSkin = {
    attach: function (context, settings) {
    	console.log("DataTable section");
		if ($('#ajPageTableDefaults').length) {
			$('#ajPageTableDefaults').dataTable({
				"bRetrieve": true,
				"sScrollX": "100%"
			});
		} else if ($('#ajPageTableNoPagingOrSorting').length){
			$('#ajPageTableNoPagingOrSorting').dataTable({
				"bRetrieve": true,
				"bPaginate": false,
				"bSort": false,
				"bFilter": false
			});
		} else if ($('#ajOverdue').length){
			$('#ajOverdue').dataTable({
				"bRetrieve": true,
				"bPaginate": true,
				"bSort": true,
				"bFilter": true
			});
		} else if ($('#ajRiskScreen').length){
			$('#ajRiskScreen').dataTable({
				"bRetrieve": true,
				"bPaginate": true,	
				"bFilter": true,
				"bSort": true 
			});
		}
		
			$('#ajRiskScreenTotal').dataTable({
				"bRetrieve": true,
				"bPaginate": true,	
				"bFilter": true,
				"bSort": true
			    
			});
			$('#ajActiveParticipants').dataTable({
				"bRetrieve": true,
				"bPaginate": true,
				"bSort": true,
				"bFilter": true
			});

			if ($('#ajActiveParticipants2').length){
			$('#ajActiveParticipants2').dataTable({
				"bRetrieve": true,
				"bPaginate": true,
				"bSort": true,
				"bFilter": true
			});
		}else if ($('#ajProgramParticipantOverview').length){
			$('#ajProgramParticipantOverview').dataTable({
				"bRetrieve": true,
				"bPaginate": true,
				"bSort": true,
				"bFilter": true
			});
		}else if ($('#ajParticipantActivitiesReceived').length){
			$('#ajParticipantActivitiesReceived').dataTable({
				"bRetrieve": true,
				"bPaginate": true,
				"bSort": true,
				"bFilter": true
			});
		}
    }
  };
})(jQuery);


