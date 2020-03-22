/* ========== AJAX ========== */

var i = 0;

$(document).ready(function() {
	var results = [], length = 0;

	$("#mapread").click(function(event) {
		length = maplist.length;
		processItem();

	});

	function processItem() {
		// Check length, if 0 quit loop...
		if(maplist.length) {
			i++;
			// Make a call always referencing results[0] since we're shfiting the array results...
			$.ajax({
				url: "mapread.php",
				type: 'POST',
				data: { map: maplist[0], num: i },
				cache: false,
				success: function(html) {
					$("#mapreadstate").html(i + "/" + maplist.length + " : " + html);

					if(html.indexOf('**') == -1) {
						$("#maplist").html($("#maplist").html() + html);
					}

					// Remove the first item to prepare for next iteration...
					maplist.shift();

					processItem();
				}
			});
		}
	}
});
