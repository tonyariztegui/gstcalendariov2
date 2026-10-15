$(document).ready(function() {
	
//Popover	
	$('[data-toggle="popover"]').popover({ container: 'body', placement: 'top', trigger: 'hover', html: true });
	
//Tooltip
	$('[data-toggle="tooltip"]').tooltip();

//Notice    
    if ($(".notice").length > 0) { 
		$('.notice').css({ position:"fixed", top:0, left:0, width: "100%", opacity: ".85"}); $('.notice').css("text-align","center"); $('.notice').css("z-index","9999999");
		$('.notice').slideDown().delay(5000).slideUp();/**/ 
	} 
    
//toggle input value
  	if ($(".togglekey").length > 0) { 
		let swapValue = [];
		$(".togglekey").each(function(i){
		   swapValue[i] = $(this).val();
		   $(this).focus(function(){
			  if ($(this).val() == swapValue[i]) {
				 $(this).val("");
			  }
			  $(this).addClass("focus");
		   }).blur(function(){
			  if ($.trim($(this).val()) == "") {
				 $(this).val(swapValue[i]);
			 $(this).removeClass("focus");
			  }
		   });
		});
	}
	
//Fadeimg
	if ($(".pimg").length > 0) { 
		$(".pimg").fadeTo("slow", 1.0);
		$(".pimg").hover(
			function(){ $(this).fadeTo("fast", 0.5); }, 
			function(){ $(this).fadeTo("fast", 1.0);
		});

		$(".dimg").fadeTo("slow", 0.5);
	}
	
//Chckboxes & Radio Btn
	 $('input[type="checkbox"], input[type="radio"]').iCheck({
		checkboxClass: 'icheckbox_square',
		radioClass: 'iradio_square'
	 });
	
//CF
	if ($("#generacf").length > 0) {
		$("#generacf").click(function(e) {
			e.preventDefault();

			var error = 0;
			if ($("#nome").val() == '') { error = 1; swal("","Inserisci il nome","warning"); return false; }
			if ($("#cognome").val() == '') { error = 1; swal("","Inserisci il cognome","warning"); return false; }
			if ($("#dnascita").val() == '') { error = 1; swal("","Inserisci la data di nascita","warning"); return false; }
			if ($("#sex").val() == '') { error = 1; swal("","Seleziona il sesso","warning"); return false; }
			if ($("#pnascita").val() == '') { error = 1; swal("","Inserisci la provincia di nascita","warning"); return false; }
			if ($("#cnascita").val() == '') { error = 1; swal("","Inserisci la città di nascita","warning"); return false; }
			
			if ( error == 0 ) {
			
				$.post("/res/inc/codfisc.php", { 
					nome: $("#nome").val(), cognome: $("#cognome").val(), dn: $("#dnascita").val(), sesso: $("#sex").val(), luogo_pr: $("#pnascita").val(), luogo_ct: $("#cnascita").val() 
				}).done(function(data) {
					
					$("#codfisc").val(data);

				});
				
			}

		});
	}
	
//GoTo
	$(".goto").click(function(e) {
		e.preventDefault();
		var dest = $(this).attr("href");
		$('html, body').animate({
			scrollTop: $("#"+dest).offset().top - 90
		}, 1000);
	});
	
//Pagination JS
	if ($(".page-link").length > 0) {
		$(document).on('click','.page-link', function() {
				
			var id = $(this).attr("id");
			var sp = id.split("_");
			$("#page").val(sp[1]);
			document.change_page.submit();
				
		});
	}
	
});

function sortTable(tbl,n) {
  var table, rows, switching, i, x, y, shouldSwitch, dir, switchcount = 0;
  table = document.getElementById(tbl);
  switching = true;
  // Set the sorting direction to ascending:
  dir = "asc"; 
  /* Make a loop that will continue until
  no switching has been done: */
  while (switching) {
    // Start by saying: no switching is done:
    switching = false;
    rows = table.getElementsByTagName("TR");
    /* Loop through all table rows (except the
    first, which contains table headers): */
    for (i = 1; i < (rows.length - 1); i++) {
      // Start by saying there should be no switching:
      shouldSwitch = false;
      /* Get the two elements you want to compare,
      one from current row and one from the next: */
      x = rows[i].getElementsByTagName("TD")[n];
      y = rows[i + 1].getElementsByTagName("TD")[n];
      /* Check if the two rows should switch place,
      based on the direction, asc or desc: */
      if (dir == "asc") {
        if (x.innerHTML.toLowerCase() > y.innerHTML.toLowerCase()) {
          // If so, mark as a switch and break the loop:
          shouldSwitch = true;
          break;
        }
      } else if (dir == "desc") {
        if (x.innerHTML.toLowerCase() < y.innerHTML.toLowerCase()) {
          // If so, mark as a switch and break the loop:
          shouldSwitch = true;
          break;
        }
      }
    }
    if (shouldSwitch) {
      /* If a switch has been marked, make the switch
      and mark that a switch has been done: */
      rows[i].parentNode.insertBefore(rows[i + 1], rows[i]);
      switching = true;
      // Each time a switch is done, increase this count by 1:
      switchcount ++; 
    } else {
      /* If no switching has been done AND the direction is "asc",
      set the direction to "desc" and run the while loop again. */
      if (switchcount == 0 && dir == "asc") {
        dir = "desc";
        switching = true;
      }
    }
  }
}