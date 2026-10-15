        </div>
    </div>
</div>

</div>

	<?php include("res/inc/footer.php"); ?>

	<link rel="stylesheet" href="https://cdn.datatables.net/1.12.1/css/jquery.dataTables.min.css" integrity="sha384-1acln9x8L/qLiaGGA71iWpzCKwZpGAPXErmVAFNPf2k/3GYOsLOZfQ3+0uxxFtM5" crossorigin="anonymous">

	<script type="text/javascript" src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha384-1H217gwSVyLSIfaLxHbE7dRb3v4mYCKbpQvzx0cegeju1MVsGrX5xXxAvs/HgeFs" crossorigin="anonymous"></script>
	<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-Fy6S3B9q64WdZWQUiU+q4/2Lc9npb8tCaSX9FK7E8HnRr0Jz8D6OP9dO5Vg3Q9ct" crossorigin="anonymous"></script>
	
	<!-- Summernote Editor JS (dopo jQuery e Bootstrap) -->
	<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-bs4.min.js" integrity="sha384-tG37zJAk+EcUTn0PLQkIE5Bbmkuna7/Spxvd1GU2kyY8kKGX5V2K+qGitOvd5Sm1" crossorigin="anonymous"></script>
	<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/lang/summernote-it-IT.min.js" integrity="sha384-mf14l0aXjwpQkacgRFF5K76/bUY0ar575CGL/cg6Pingz1ds8HyiV7KPTN2PVBKw" crossorigin="anonymous"></script>
	
	<script type="text/javascript" src="res/js/venobox.js"></script>
	<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/sweetalert2@10" integrity="sha384-v5sCAl8qZOHabzoh1+noJcE2yaDE9V/9+mBHgmxFdpiY5chhD/Qf1rQ7igh8ew0q" crossorigin="anonymous"></script>
	<script type="text/javascript" src="res/js/autocomplete.js"></script>
	<script type="text/javascript" src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js" integrity="sha384-ZuLbSl+Zt/ry1/xGxjZPkp9P5MEDotJcsuoHT0cM8oWr+e1Ide//SZLebdVrzb2X" crossorigin="anonymous"></script>
	<script type="text/javascript" src="assets/js/custom.js?v=<?php
        $customJsPath = __DIR__ . '/../assets/js/custom.js';
        echo is_file($customJsPath) ? (string) filemtime($customJsPath) : '1';
    ?>"></script>

	<?php echo $scripts ?? ''; ?>

	<!-- JavaScript per dettagli e dropdown -->
	<script>
	$(document).ready(function() {
		const $sidebar = $('#sidebarNavCollapse');
		const $sidebarBackdrop = $('#sidebarBackdrop');
		const $sidebarToggle = $('.sidebar-toggler');
		const openSidebar = function() {
			$('body').addClass('sidebar-open');
			$sidebar.addClass('show');
			$sidebarBackdrop.addClass('show');
			$sidebarToggle.attr('aria-expanded', 'true');
		};
		const closeSidebar = function() {
			$('body').removeClass('sidebar-open');
			$sidebar.removeClass('show');
			$sidebarBackdrop.removeClass('show');
			$sidebarToggle.attr('aria-expanded', 'false');
		};
		$sidebarToggle.on('click', function(e) {
			e.preventDefault();
			if ($('body').hasClass('sidebar-open')) {
				closeSidebar();
			} else {
				openSidebar();
			}
		});
		$sidebarBackdrop.on('click', function () {
			closeSidebar();
		});
		$(window).on('resize', function() {
			if (window.innerWidth >= 992) {
				closeSidebar();
			}
		});

		// Inizializza dropdown Bootstrap 4
		$('.dropdown-toggle').dropdown();
		
		// Gestione input pagina (legacy - mantenuto per compatibilità)
		$('#goToPageBtn').on('click', function() {
			const page = parseInt($('#goToPage').val());
			const maxPages = <?php echo isset($total_pages_approx) ? $total_pages_approx : 1; ?>;
			
			if (page && page > 0 && page <= maxPages) {
				// Calcola l'offset per la pagina richiesta
				const offset = (page - 1) * <?php echo isset($limit) ? $limit : 25; ?>;
				const url = '?pg=codici_fast<?php echo isset($search_term) && $search_term ? '&search='.urlencode($search_term) : ''; ?><?php echo isset($search_type) && $search_type !== 'codice' ? '&searchType='.urlencode($search_type) : ''; ?><?php echo isset($limit) && $limit !== 25 ? '&limit='.$limit : ''; ?>&offset=' + offset;
				window.location.href = url;
			} else if (page > maxPages) {
				alert('La pagina massima è ' + maxPages);
			} else {
				alert('Inserisci un numero di pagina valido');
			}
		});
		
		// Gestione Enter nell'input (legacy)
		$('#goToPage').on('keypress', function(e) {
			if (e.which === 13) { // Enter key
				$('#goToPageBtn').click();
			}
		});
		
		// Gestione nuovo input pagina
		$('#goToPageBtnNew').on('click', function() {
			const page = parseInt($('#pageInput').val());
			const maxPages = <?php echo isset($total_pages_approx) ? $total_pages_approx : 1; ?>;
			
			if (page && page > 0 && page <= maxPages) {
				// Calcola l'offset per la pagina richiesta
				const offset = (page - 1) * <?php echo isset($limit) ? $limit : 25; ?>;
				const url = '?pg=codici_fast<?php echo isset($search_term) && $search_term ? '&search='.urlencode($search_term) : ''; ?><?php echo isset($search_type) && $search_type !== 'codice' ? '&searchType='.urlencode($search_type) : ''; ?><?php echo isset($limit) && $limit !== 25 ? '&limit='.$limit : ''; ?>&offset=' + offset;
				window.location.href = url;
			} else if (page > maxPages) {
				alert('La pagina massima è ' + maxPages);
			} else {
				alert('Inserisci un numero di pagina valido');
			}
		});
		
		// Gestione Enter nel nuovo input
		$('#pageInput').on('keypress', function(e) {
			if (e.which === 13) { // Enter key
				$('#goToPageBtnNew').click();
			}
		});
		
		$(document).on('click', '.btn-details', function () {
			const codiceId = $(this).data('codice-id');
			const userToken = $(this).data('token');
			
			// Mostra loading
			$(this).prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Caricamento...');

			$.ajax({
				url: 'res/inc/get_user_details.php',
				type: 'GET',
				dataType: 'json',
				data: { codice_id: codiceId, token: userToken },
				success: function (response) {
					if (response && response.success) {
						// Crea HTML per SweetAlert2
						let html = '<div class="text-left">';
						html += '<h6><strong>👤 Informazioni Utente</strong></h6>';
						html += '<p><strong>Nome:</strong> ' + response.user.nome + '</p>';
						html += '<p><strong>Cognome:</strong> ' + response.user.cognome + '</p>';
						html += '<p><strong>Email:</strong> ' + response.user.email + '</p>';
						html += '<p><strong>Telefono:</strong> ' + response.user.telefono + '</p>';
						html += '<p><strong>Fedeltà Card:</strong> ' + response.user.fedelta_card + '</p>';
						html += '<hr>';
						html += '<h6><strong>🎫 Codici Utilizzati (' + response.total_codici + ')</strong></h6>';
						html += '<div style="max-height: 200px; overflow-y: auto;">';
						
						(response.codici || []).forEach(function (c, index) {
							html += '<div class="mb-2 p-2 border rounded">';
							html += '<span class="badge ' + (c.is_current ? 'badge-success' : 'badge-secondary') + ' mr-2">' + c.codice + '</span>';
							if (c.is_current) html += '<span class="badge badge-primary">ATTUALE</span>';
							html += '<br><small class="text-muted">' + c.data_inserimento + '</small>';
							html += '</div>';
						});
						
						html += '</div></div>';
						
						Swal.fire({
							title: 'Dettagli Utente',
							html: html,
							width: '600px',
							showCloseButton: true,
							showConfirmButton: false,
							allowOutsideClick: true
						});
					} else {
						Swal.fire({
							icon: 'error',
							title: 'Errore',
							text: response && response.error ? response.error : 'Risposta non valida'
						});
					}
				},
				error: function (xhr) {
					Swal.fire({
						icon: 'error',
						title: 'Errore',
						text: 'Errore di connessione'
					});
				},
				complete: function() {
					// Ripristina pulsante
					$('.btn-details').prop('disabled', false).html('<i class="fa fa-eye"></i> Dettagli');
				}
			});
		});
		
		// Gestione modal Dettagli Premio
		$(document).on('click', '.btnPremioDetails', function(e) {
			e.preventDefault();
			e.stopPropagation();
			var $btn = $(this);
			var codicePartecipazione = $btn.attr('data-codice-partecipazione') || '';
			var codicePremio = $btn.attr('data-codice-premio') || '';
			var pdfPath = $btn.attr('data-pdf-path') || '';
			
			// Aggiornare il contenuto del modal
			$('#modal-codice-partecipazione').html(
				codicePartecipazione !== '' 
					? '<code class="text-primary">' + $('<div>').text(codicePartecipazione).html() + '</code>' 
					: '<span class="text-muted">Non disponibile</span>'
			);
			
			$('#modal-codice-premio').html(
				codicePremio !== '' 
					? '<code class="text-success">' + $('<div>').text(codicePremio).html() + '</code>' 
					: '<span class="text-muted">Non disponibile</span>'
			);
			
			// Gestire il PDF
			var $pdfContainer = $('#modal-pdf-container');
			if (pdfPath !== '') {
				// Normalizzare i backslash Windows in slash normali
				var normalizedPath = pdfPath.replace(/\\/g, '/');
				// Rimuovere prefissi comuni se presenti
				normalizedPath = normalizedPath.replace(/^(public\/|assets\/)/, '');
				
				// Utilizzare lo script PHP per servire il PDF con i giusti headers
				var pdfViewUrl = 'res/inc/get_voucher_pdf.php?path=' + encodeURIComponent(normalizedPath);
				
				// Creare un bottone che apre il PDF in un popup SweetAlert2 (come per le foto scontrino)
				$pdfContainer.html(
					'<button type="button" class="btn btn-sm btn-primary btnViewPdfPremio" data-pdf-url="' + 
					$('<div>').text(pdfViewUrl).html() + '" data-pdf-name="PDF Premio">' +
					'<i class="fa fa-file-pdf mr-1"></i>Visualizza PDF</button>'
				);
			} else {
				$pdfContainer.html('<span class="text-muted">Nessun PDF disponibile</span>');
			}
			
			// Mostrare il modal
			$('#modalPremioDetails').modal('show');
		});
		
		// Gestione click sul bottone "Visualizza PDF" nel modal Dettagli Premio
		$(document).on('click', '.btnViewPdfPremio', function(e) {
			e.preventDefault();
			e.stopPropagation();
			const $btn = $(this);
			const pdfUrl = ($btn.data('pdf-url') || '').toString();
			const pdfName = ($btn.data('pdf-name') || 'PDF Premio').toString();
			
			if (!pdfUrl) {
				Swal.fire({
					icon: 'warning',
					title: 'PDF non disponibile',
					text: 'Il file PDF non è presente.'
				});
				return;
			}
			
			const escapeHtml = (value) => $('<div>').text(value).html();
			const safeUrl = escapeHtml(pdfUrl);
			const safeName = escapeHtml(pdfName);
			
			// Mostrare il PDF in un iframe dentro SweetAlert2 (come per le foto scontrino)
			Swal.fire({
				title: safeName,
				html: '<div class="text-center"><iframe src="' + safeUrl + '" style="width:100%;height:600px;" frameborder="0"></iframe></div>',
				width: 900,
				showCloseButton: true,
				focusConfirm: false,
				showConfirmButton: false,
				allowOutsideClick: true
			});
		});
	});
	</script>

</body>
</html>
