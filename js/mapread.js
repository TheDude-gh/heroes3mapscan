/* ========== AJAX ========== */


	$(function(){
		$('#mapread').click(function() {
			
			for(var i = 0; i < maplist.length; i++) {
				$.ajax({
					url: "mapread.php",
					type: 'POST',
					data: { map: maplist[i] },
					cache: false
				}).done(function(html){
					$("#maplist").html($("#maplist").html() + html);
				});
			}

		});
	});
