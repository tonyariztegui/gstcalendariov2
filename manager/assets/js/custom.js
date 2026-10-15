function customEscapeHtml(value) {
	return $('<div>').text(value == null ? '' : value).html();
}

function customJoinActionButtonsHtml(actionButtons) {
	let buttonsHtml = '';
	actionButtons.forEach(function(btnHtml, index) {
		const wrapperClass = index > 0 ? 'ms-2' : '';
		buttonsHtml += '<div class="' + wrapperClass + '">' + btnHtml + '</div>';
	});
	return '<div class="mt-2 d-flex justify-content-end flex-wrap">' + buttonsHtml + '</div>';
}

function customFormatBadgeBool(flag) {
	const cls = flag ? 'bg-success text-white' : 'bg-danger text-white';
	const label = flag ? 'Si' : 'No';
	return '<span class="badge ' + cls + '">' + label + '</span>';
}

function customFormatFidelityTextValue(value) {
	if (value === null || value === undefined) {
		return '<span class="text-muted">-</span>';
	}
	const trimmed = String(value).trim();
	if (!trimmed) {
		return '<span class="text-muted">-</span>';
	}
	return customEscapeHtml(trimmed);
}

function customResolveFidelityFieldFlags(response) {
	const fieldsFlags = response.fields_flags || {};
	const showNome = fieldsFlags.nome !== false;
	const showCognome = fieldsFlags.cognome !== false;
	const scontrinoEnabled = fieldsFlags.scontrino === true;
	return {
		showNome: showNome,
		showCognome: showCognome,
		showName: showNome || showCognome,
		showEmail: fieldsFlags.email !== false,
		showPhone: (fieldsFlags.prefisso !== false) || (fieldsFlags.cellulare !== false),
		showPrivacy: fieldsFlags.privacy !== false,
		showMarketing: fieldsFlags.marketing !== false,
		showProfilazione: fieldsFlags.profilazione !== false,
		showImporto: fieldsFlags.importo !== false,
		showDataScontrino: scontrinoEnabled && fieldsFlags.data_scontrino !== false,
		showReceipt: scontrinoEnabled,
		showContactColumn: fieldsFlags.email !== false,
		showCodicePartecipazione: fieldsFlags.codice_partecipazione === true,
		showQrCode: response.qr_access_enabled === true
	};
}

function customBuildFidelityProfilesTableHtml(profiles, flags) {
	if (!profiles.length) {
		return '<p class="text-muted">Nessun profilo associato a questo indirizzo email.</p>';
	}
	let html = '<div class="table-responsive mb-4">';
	html += '<table class="table table-sm table-bordered align-middle mb-0">';
	html += '<thead><tr><th style="width:60px;">#</th>';
	if (flags.showName) html += '<th>Nome</th>';
	if (flags.showContactColumn) html += '<th>Email</th>';
	if (flags.showPhone) html += '<th>Telefono</th>';
	if (flags.showPrivacy) html += '<th>Privacy</th>';
	if (flags.showMarketing) html += '<th>Marketing</th>';
	if (flags.showProfilazione) html += '<th>Profilazione</th>';
	html += '</tr></thead><tbody>';
	profiles.forEach(function(profile, idx) {
		const profileNumber = profile.index != null ? parseInt(profile.index, 10) : (idx + 1);
		const nameParts = [];
		if (flags.showNome && profile.nome) nameParts.push(profile.nome);
		if (flags.showCognome && profile.cognome) nameParts.push(profile.cognome);
		const fullName = nameParts.join(' ').trim() || '-';
		html += '<tr>';
		html += '<td>' + customEscapeHtml(String(profileNumber)) + '</td>';
		if (flags.showName) html += '<td>' + customFormatFidelityTextValue(fullName) + '</td>';
		if (flags.showContactColumn) html += '<td>' + customFormatFidelityTextValue(profile.email) + '</td>';
		if (flags.showPhone) html += '<td>' + customFormatFidelityTextValue(profile.telefono) + '</td>';
		if (flags.showPrivacy) html += '<td>' + customFormatBadgeBool(parseInt(profile.privacy || 0, 10) === 1) + '</td>';
		if (flags.showMarketing) html += '<td>' + customFormatBadgeBool(parseInt(profile.marketing || 0, 10) === 1) + '</td>';
		if (flags.showProfilazione) html += '<td>' + customFormatBadgeBool(parseInt(profile.profilazione || 0, 10) === 1) + '</td>';
		html += '</tr>';
	});
	html += '</tbody></table></div>';
	return html;
}

function customBuildFidelityHistoryRowHtml(item, index, flags) {
	const nameParts = [];
	if (flags.showNome && item.nome) nameParts.push(item.nome);
	if (flags.showCognome && item.cognome) nameParts.push(item.cognome);
	const participantName = nameParts.length ? nameParts.join(' ') : (item.partecipante || '-');
	const importo = item.importo_label ? customEscapeHtml(item.importo_label) : '<span class="text-muted">-</span>';
	const dataScontrino = item.data_scontrino ? customEscapeHtml(item.data_scontrino) : '<span class="text-muted">-</span>';
	let fotoCell = '<span class="text-muted">Non disponibile</span>';
	if (item.file_url) {
		const safeUrl = customEscapeHtml(item.file_url);
		const safeName = customEscapeHtml(item.file_name || 'Scontrino');
		const safeExt = customEscapeHtml(item.file_ext || '');
		fotoCell = '<button type="button" class="btn btn-sm btn-outline-primary btnViewReceipt" data-file-url="' + safeUrl + '" data-file-name="' + safeName + '" data-file-ext="' + safeExt + '"><i class="fa fa-image me-1"></i></button>';
	}
	const codicePartecipazione = item.codice_partecipazione ? customEscapeHtml(item.codice_partecipazione) : '<span class="text-muted">-</span>';
	let html = '<tr>';
	html += '<td>' + customEscapeHtml(String(index + 1)) + '</td>';
	if (flags.showName) html += '<td>' + customFormatFidelityTextValue(participantName) + '</td>';
	if (flags.showImporto) html += '<td>' + importo + '</td>';
	html += '<td>' + (item.data_partecipazione ? customEscapeHtml(item.data_partecipazione) : '<span class="text-muted">-</span>') + '</td>';
	if (flags.showCodicePartecipazione) html += '<td>' + codicePartecipazione + '</td>';
	if (flags.showQrCode) {
		const qrCodeValue = item.qr_code ? '<code style="word-break:break-all;font-size:0.8rem;">' + customEscapeHtml(item.qr_code) + '</code>' : '<span class="text-muted">-</span>';
		html += '<td>' + qrCodeValue + '</td>';
	}
	if (flags.showDataScontrino) html += '<td>' + dataScontrino + '</td>';
	if (flags.showReceipt) html += '<td>' + fotoCell + '</td>';
	html += '</tr>';
	return html;
}

function customBuildFidelityHistoryTableHtml(history, flags) {
	if (!history.length) {
		return '<p class="text-muted mb-0">Nessuno storico di scontrini disponibile per questo indirizzo email.</p>';
	}
	let html = '<div class="table-responsive">';
	html += '<table class="table table-sm table-striped align-middle mb-0">';
	html += '<thead><tr><th style="width:60px;">#</th>';
	if (flags.showName) html += '<th>Partecipante</th>';
	if (flags.showImporto) html += '<th>Importo</th>';
	html += '<th>Data partecipazione</th>';
	if (flags.showCodicePartecipazione) html += '<th>Codice partecipazione</th>';
	if (flags.showQrCode) html += '<th>Codice QR</th>';
	if (flags.showDataScontrino) html += '<th>Data scontrino</th>';
	if (flags.showReceipt) html += '<th>Foto scontrino</th>';
	html += '</tr></thead><tbody>';
	history.forEach(function(item, index) {
		html += customBuildFidelityHistoryRowHtml(item, index, flags);
	});
	html += '</tbody></table></div>';
	return html;
}

function customBuildFidelityHistoryHtml(response, emailToken, mainId) {
	const emailAddress = response.display_token || response.email || '';
	const labelType = (response.label || '').toString().toUpperCase() === 'FIDELITY' ? 'FIDELITY' : 'EMAIL';
	const profiles = Array.isArray(response.profiles) ? response.profiles : [];
	const history = Array.isArray(response.history) ? response.history : [];
	const flags = customResolveFidelityFieldFlags(response);

	let html = '<div class="text-start" style="max-height:70vh; overflow-y:auto;" data-email="' + customEscapeHtml(emailAddress) + '" data-main-id="' + (mainId || '') + '" data-email-token="' + customEscapeHtml(emailToken) + '">';
	html += '<div class="mb-3 p-3 border rounded bg-light">';
	html += '<div class="fw-bold text-uppercase text-muted mb-2">' + labelType + '</div>';
	html += '<div class="h5 mb-0">' + customFormatFidelityTextValue(emailAddress) + '</div>';
	html += '</div>';
	html += customBuildFidelityProfilesTableHtml(profiles, flags);
	html += customBuildFidelityHistoryTableHtml(history, flags);
	html += '</div>';
	return html;
}

function customResetCopyLinkButton($btn) {
	$btn.html('<i class="fa fa-copy"></i> Copia');
	$btn.removeClass('btn-success').addClass('btn-outline-secondary');
}

function customHandleCopyValidationLink() {
	const linkInput = document.getElementById('validationLink');
	linkInput.select();
	linkInput.setSelectionRange(0, 99999);
	document.execCommand('copy');
	const $btn = $(this);
	$btn.html('<i class="fa fa-check"></i> Copiato!');
	$btn.removeClass('btn-outline-secondary').addClass('btn-success');
	setTimeout(function() {
		customResetCopyLinkButton($btn);
	}, 2000);
}

$(document).ready(function(){
    
	$('.vbox').venobox(); 

	const MOBILE_BREAKPOINT = 1250;
	const $appContainer = $('.app-container');
	const $mobileNavToggle = $('.mobile-toggle-nav');
	const $mobileHeaderToggle = $('.mobile-toggle-header-nav');
	const $headerContent = $('.app-header__content');

	const closeMobileSidebar = () => {
		$appContainer.removeClass('sidebar-mobile-open');
		$mobileNavToggle.removeClass('is-active');

		if ($(window).width() < MOBILE_BREAKPOINT) {
			$appContainer.addClass('closed-sidebar closed-sidebar-mobile');
		} else {
			$appContainer.removeClass('closed-sidebar closed-sidebar-mobile');
		}
	};

	const openMobileSidebar = () => {
		$appContainer
			.addClass('sidebar-mobile-open')
			.removeClass('closed-sidebar closed-sidebar-mobile');
		$mobileNavToggle.addClass('is-active');
	};

	const syncSidebarState = () => {
		if ($(window).width() < MOBILE_BREAKPOINT) {
			if (!$appContainer.hasClass('sidebar-mobile-open')) {
				$appContainer.addClass('closed-sidebar closed-sidebar-mobile');
			}
		} else {
			$appContainer.removeClass('closed-sidebar closed-sidebar-mobile sidebar-mobile-open');
			$mobileNavToggle.removeClass('is-active');
			$mobileHeaderToggle.removeClass('active');
			$headerContent.removeClass('header-mobile-open');
		}
	};

	$mobileNavToggle.on('click', function(event) {
		event.preventDefault();

		if ($appContainer.hasClass('sidebar-mobile-open')) {
			closeMobileSidebar();
		} else {
			openMobileSidebar();
		}
	});

	$mobileHeaderToggle.on('click', function(event) {
		event.preventDefault();

		const $toggle = $(this);
		$toggle.toggleClass('active');
		$headerContent.toggleClass('header-mobile-open');
	});

	$(window).on('resize', syncSidebarState);
	syncSidebarState();

	//Tables
    const $table = $(".tbl");
    if ($table.length > 0) {
        const tableContext = $table.data("context") || "utenti";
        const dataTableConfig = {
            lengthChange: false,
            lengthMenu: [
                [10, 50, 100, -1]
            ],
            language: {
                url: '//cdn.datatables.net/plug-ins/1.12.1/i18n/it-IT.json'
            },
            paging: true,
            pageLength: 50,
            orderCellsTop: true,
            initComplete: function () {
                const api = this.api();
                api.columns().eq(0).each(function () { });
            },
            processing: true,
            serverSide: true,
            searching: true,
            ajax: {
                url: '',
                type: 'POST',
                data: function (d) {
                    d.custom = $("#custom_filter").data("val");
                    d.tipo = $("#custom_filter").data("tp");
                    d.filterStatus = $('#filterStatus').val() || '';
                }
            }
        };

        if (tableContext === "partecipazioni") {
            dataTableConfig.order = [[2, 'desc']];
            dataTableConfig.ajax.url = 'res/inc/partecipazioni_table.php';
            dataTableConfig.columns = [
                {
                    data: null,
                    className: 'text-muted text-end',
                    orderable: false,
                    searchable: false,
                    render: function (data, type, row, meta) {
                        const start = meta && meta.settings && typeof meta.settings._iDisplayStart === 'number'
                            ? meta.settings._iDisplayStart
                            : 0;
                        const index = start + meta.row + 1;
                        return index;
                    },
                    width: '60px'
                },
                { data: 'partecipante' },
                { data: 'data_partecipazione' },
                { data: 'importo_scontrino' },
                { data: 'data_scontrino' },
                { data: 'punto_vendita' },
                { data: 'vincitore' },
                { data: 'confermato' },
                { data: 'annullato' },
                { data: 'win_date' },
                { data: 'email_inviato' },
                { data: 'azioni', orderable: false, searchable: false }
            ];
            dataTableConfig.columnDefs = [
                {
                    targets: [0, 6, 12],
                    orderable: false
                },
                {
                    targets: [0, 12],
                    searchable: false
                }
            ];
        } else {
            dataTableConfig.order = [[0, 'desc']];
            dataTableConfig.ajax.url = 'res/inc/_table.php';
            dataTableConfig.columns = [
                { data: "id_cliente" },
                { data: "nome" },
                { data: "cognome" },
                { data: "email" },
                { data: "cellulare" },
                { data: "email" },
                { data: "privacy" },
                { data: "marketing" },
                { data: "profilazione" },
                { data: "partecipazioni", orderable: false, searchable: false },
                { data: "storico", orderable: false, searchable: false }
            ];
            dataTableConfig.columnDefs = [
                {
                    targets: [6, 7, 8, 9, 10],
                    orderable: false
                },
                {
                    targets: [9, 10],
                    searchable: false
                }
            ];
        }

        window.table = $table.DataTable(dataTableConfig);
    }

    if (typeof table !== 'undefined') {
        table.on("click", "tr", (e) => { //Get Row index
            const index = table.row(e.currentTarget).index()
            const row = table.row(index).data()
            table.row(index).data(row).invalidate().draw()
        })
    }

    $(document).on('click', '.btnPartecipazioni', function(e) {
        e.preventDefault();
        const $btn = $(this);
        const emailToken = ($btn.data('email') || '').toString();
        const mainId = parseInt($btn.data('mainId') || 0, 10);
        const count = parseInt($btn.data('count') || 0, 10);

        if (!emailToken && !mainId) {
            return;
        }

        if (count <= 0) {
            Swal.fire({
                icon: 'info',
                title: 'Partecipazioni',
                text: 'Nessuna partecipazione registrata per questo indirizzo email.'
            });
            return;
        }

        const escapeHtml = (value) => $('<div>').text(value == null ? '' : value).html();

        Swal.fire({
            title: 'Caricamento...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        const params = {};
        let endpoint = '';
        if (emailToken) {
            endpoint = 'res/inc/get_fidelity_partecipazioni.php';
            params.email = emailToken;
        } else if (mainId) {
            endpoint = 'res/inc/get_user_partecipazioni.php';
            params.id_cliente = mainId;
        } else {
            Swal.close();
            return;
        }

        $.get(endpoint, params, function(response) {
            if (response && response.success) {
                const partecipazioni = response.partecipazioni || [];

                if (!partecipazioni.length) {
                    Swal.fire({
                        icon: 'info',
                        title: 'Partecipazioni',
                        text: 'Nessuna partecipazione registrata per questo indirizzo email.'
                    });
                    return;
                }

                let html = '<div class="text-left" style="max-height: 420px; overflow-y: auto;">';
                partecipazioni.forEach(function(item, idx) {
                    html += '<div class="mb-3 p-2 border rounded">';
                    html += '<div><strong>#' + (idx + 1) + '</strong> - ' + escapeHtml(item.data_partecipazione || '-') + '</div>';
                    if (item.partecipante) {
                        html += '<div><strong>Utente:</strong> ' + escapeHtml(item.partecipante) + '</div>';
                    }
                    if (item.data_scontrino) {
                        html += '<div><strong>Data scontrino:</strong> ' + escapeHtml(item.data_scontrino) + '</div>';
                    }
                    html += '<div><strong>Importo:</strong> &euro; ' + escapeHtml(item.importo_scontrino || '0,00') + '</div>';

                    if (item.qr_code) {
                        html += '<div class="mt-1"><strong>Codice QR:</strong> <code style="word-break:break-all;">' + escapeHtml(item.qr_code) + '</code></div>';
                    }

                    if (parseInt(item.has_won || 0, 10) === 1) {
                        html += '<div class="mt-2"><span class="badge bg-success"><i class="fa fa-trophy"></i> Vincita</span>';
                        if (item.prize_amount) {
                            html += ' <small class="text-muted">(' + escapeHtml(item.prize_amount) + ' &euro;)</small>';
                        }
                        if (item.win_date) {
                            html += '<div><small class="text-muted">Vinto il ' + escapeHtml(item.win_date) + '</small></div>';
                        }
                        html += '</div>';
                    }

                    const actionButtons = [];

                    if (item.file_url) {
                        actionButtons.push(
                            '<a class="btn btn-sm btn-outline-primary" href="' + item.file_url + '" target="_blank" rel="noopener">' +
                            '<i class="fa fa-external-link"></i> Visualizza file' +
                            '</a>'
                        );
                    }

                    if (parseInt(item.has_won || 0, 10) === 1) {
                        const redeemUrl = item.redeem_url ? item.redeem_url : ('res/inc/redeem_prize.php?participation_id=' + encodeURIComponent(item.id));
                        actionButtons.push(
                            '<a class="btn btn-sm btn-success" href="' + redeemUrl + '" target="_blank" rel="noopener">' +
                            '<i class="fa fa-gift"></i> Riscatta premio' +
                            '</a>'
                        );
                    }

                    if (actionButtons.length > 0) {
                        html += customJoinActionButtonsHtml(actionButtons);
                    } else {
                        html += '<div class="mt-2 text-muted">File non disponibile</div>';
                    }

                    html += '</div>';
                });
                html += '</div>';

                Swal.fire({
                    title: 'Partecipazioni',
                    html: html,
                    width: 700,
                    showCloseButton: true,
                    showConfirmButton: false,
                    allowOutsideClick: true
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Errore',
                    text: (response && response.error) ? response.error : 'Impossibile recuperare le partecipazioni.'
                });
            }
        }, 'json').fail(function() {
            Swal.fire({
                icon: 'error',
                title: 'Errore',
                text: 'Errore di connessione durante il recupero delle partecipazioni.'
            });
        });
    });

    let currentFidelityHistoryContext = null;
    let reopenHistoryAfterReceipt = false;

    function openFidelityHistory(emailToken, mainId) {
        if (!emailToken && !mainId) {
            return;
        }

        Swal.fire({
            title: 'Caricamento...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        const params = {};
        if (emailToken) {
            params.email = emailToken;
        } else if (mainId) {
            params.main_id = mainId;
        }

        $.get('res/inc/get_fidelity_history.php', params, function(response) {
            if (!response || !response.success) {
                const message = response && response.error ? response.error : 'Impossibile recuperare lo storico.';
                Swal.fire({
                    icon: 'error',
                    title: 'Errore',
                    text: message
                });
                return;
            }

            const html = customBuildFidelityHistoryHtml(response, emailToken, mainId);

            Swal.fire({
                title: 'Storico',
                html: html,
                width: 820,
                showCloseButton: true,
                focusConfirm: false,
                showConfirmButton: false,
                didClose: () => {
                    if (reopenHistoryAfterReceipt) {
                        reopenHistoryAfterReceipt = false;
                    } else {
                        currentFidelityHistoryContext = null;
                    }
                }
            });

            currentFidelityHistoryContext = { emailToken, mainId };
        }, 'json').fail(function() {
            Swal.fire({
                icon: 'error',
                title: 'Errore',
                text: 'Errore di connessione durante il recupero dello storico.'
            });
        });
    }

    $(document).on('click', '.btnFidelityHistory', function(e) {
        e.preventDefault();
        const $btn = $(this);
        const emailToken = ($btn.data('email') || '').toString();
        const mainId = parseInt($btn.data('mainId') || 0, 10);
        openFidelityHistory(emailToken, mainId);
    });

    $(document).on('click', '.btnViewReceipt', function(e) {
        e.preventDefault();
        const $btn = $(this);
        const wrapper = $btn.closest('[data-email]');
        if (wrapper && wrapper.length) {
            const tokenAttr = (wrapper.data('email-token') || '').toString();
            const mainIdAttr = parseInt(wrapper.data('main-id') || 0, 10);
            if (tokenAttr || mainIdAttr) {
                currentFidelityHistoryContext = { emailToken: tokenAttr, mainId: mainIdAttr };
            }
        }
        const url = ($btn.data('file-url') || '').toString();
        const name = ($btn.data('file-name') || 'Scontrino').toString();
        const ext = ($btn.data('file-ext') || '').toString().toLowerCase();

        if (currentFidelityHistoryContext) {
            reopenHistoryAfterReceipt = true;
        }

        if (!url) {
            Swal.fire({
                icon: 'warning',
                title: 'File non disponibile',
                text: 'Il file dello scontrino non è presente.'
            });
            return;
        }

        const escapeHtml = (value) => $('<div>').text(value).html();
        const safeUrl = escapeHtml(url);
        const safeName = escapeHtml(name);
        let content = '';

        const imageExt = ['png', 'jpg', 'jpeg', 'gif', 'webp', 'bmp', 'svg'];
        if (imageExt.includes(ext)) {
            content = '<img src="' + safeUrl + '" alt="' + safeName + '" style="max-width:100%;height:auto;" />';
        } else if (ext === 'pdf') {
            content = '<iframe src="' + safeUrl + '" style="width:100%;height:520px;" frameborder="0"></iframe>';
        } else {
            content = '<p class="mb-3">Il file non può essere visualizzato all\'interno del popup.</p>' +
                '<a class="btn btn-primary" href="' + safeUrl + '" target="_blank" rel="noopener">Apri documento</a>';
        }

        Swal.fire({
            title: safeName,
            html: '<div class="text-center">' + content + '</div>',
            width: 900,
            showCloseButton: true,
            focusConfirm: false,
            showConfirmButton: false
        }).then(() => {
            if (currentFidelityHistoryContext) {
                const ctx = currentFidelityHistoryContext;
                setTimeout(() => openFidelityHistory(ctx.emailToken, ctx.mainId), 0);
            }
        });
    });

    $(".codici_vincenti").DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.12.1/i18n/it-IT.json'
        }
    })

	$(document).on('click','.btnMod', function(e) {

		const index = table.row(e.currentTarget).index()
		const id = $(this).data("id")
		$("#saveContent").data("id", id)

		$.each($(this).data(), function(i, v) {
			$("#"+i).val(v)
		});

	})

	$(document).on('click','#saveContent', function() {

		const id = $(this).data("id")
		let err = 0
		let error = ''
        const frm = []
		
		$($(".form-req").get().reverse()).each(function() {
            const val = $(this).val()
			const field = $(this).attr("id")
            if (val == "") {
                err = 1
                error = "Attenzione! Compila il campo "+field
            }
        })

		if (err == 1) {

            swal.fire("", error)

        } else {

			$(".form-req").each(function() {
				const name = $(this).attr("id")
				frm.push({name: name, val: $(this).val()})
			})

            $.post("res/inc/op.php", { 
                op: 'save_rec', data: JSON.stringify(frm), id: id
                }).done(function(data) {

                    swal.fire("", "Record modificato con successo.")
					$("#close_modal").trigger("click")

					table.draw()

                })
		}

	})
    
    $(document).on('click','.btnUsed', function(e) {

		const id = $(this).data("id")

        swal.fire({
            title: "",
            text: "Vuoi davvero impostare questo buono come utilizzato?",
            showCancelButton: true,
            confirmButtonColor: '#48b461',
            confirmButtonText: 'Conferma',
            cancelButtonText: 'Annulla'
        }).then((result) => {
            if (result.isConfirmed) {
            
                $.post("res/inc/op.php", { 
                    op: 'save_used', id: id
                    }).done(function(data) {
    
                        swal.fire("", "Utilizzo impostato con successo.")
    
                        table.draw()
    
                    })

            }
        })

	})
    
    $(document).on('click','.sendEmail', function(e) {

		const tk = $(this).data("token")

        swal.fire({
            title: "",
            text: "Vuoi re-inviare la mail per questa vincita?",
            showCancelButton: true,
            confirmButtonColor: '#48b461',
            confirmButtonText: 'Invia Email',
            cancelButtonText: 'Annulla'
        }).then((result) => {
            if (result.isConfirmed) {
            
                $.post("res/inc/op.php", { 
                    op: 'send_email', tk: tk
                    }).done(function(data) {

                        swal.fire("", "Email inviata con successo.")
    
                    })

            }
        })

	})

	$(document).on('click', '.btnResetPw', function() {
    const email = $(this).data('email');
    swal.fire({
        title: "Reset Password",
        text: "Vuoi inviare una mail di reset password a questo utente?",
        showCancelButton: true,
        confirmButtonColor: '#48b461',
        confirmButtonText: 'Invia',
        cancelButtonText: 'Annulla'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post("res/inc/op.php", { 
                op: 'reset_password', email: email
            }).done(function(data) {
                swal.fire("", "Email di reset inviata (se l'indirizzo è corretto).");
            }).fail(function(xhr, status, error) {
                console.error('Errore AJAX Reset Password:', error);
                console.error('Response:', xhr.responseText);
                swal.fire("Errore", "Errore nell'invio dell'email di reset. Controlla i log del server.", "error");
            });
        }
    });
});

$(document).on('click', '.btnResendConfirm', function(e) {
    e.preventDefault();
    const email = $(this).data('email');
    Swal.fire({
        title: 'Invia nuovamente email di conferma',
        html: "<div><small class='text-danger' style='font-size:1rem;'>Nota: se il cliente si è registrato con un altro indirizzo email, la validazione non funzionerà.</small></div>",
        icon: null,
        showCancelButton: true,
        confirmButtonText: 'Invia',
        cancelButtonText: 'Annulla',
        width: '32em'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post('res/inc/resend_confirmation.php', { email: email }, function(response) {
                Swal.fire('Fatto!', response.message, response.success ? 'success' : 'error');
            }, 'json').fail(function(xhr, status, error) {
                console.error('Errore AJAX Resend Confirmation:', error);
                console.error('Response:', xhr.responseText);
                Swal.fire('Errore', 'Errore nell\'invio dell\'email di conferma. Controlla i log del server.', 'error');
            });
        }
    });
});

$('#filterValidation').on('change', function() {
    table.ajax.reload();
});

// Gestion du bouton View Link
$(document).on('click', '.btnViewLink', function(e) {
    e.preventDefault();
    const email = $(this).data('email');
    
    // Afficher loading
    Swal.fire({
        title: 'Caricamento...',
        text: 'Recupero del link di validazione...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    // Rà©cupà©rer le token de validation
    $.get('res/inc/get_validation_token.php', { email: email }, function(response) {
        if (response.success) {
            // Afficher le popup avec le lien
            Swal.fire({
                title: 'Link di Validazione',
                html: `
                    <div class="text-left">
                        <p><strong>Email:</strong> ${email}</p>
                        <p><strong>Link di validazione:</strong></p>
                        <div class="input-group mb-3">
                            <input type="text" class="form-control" id="validationLink" value="${response.link}" readonly>
                            <div class="input-group-append">
                                <button class="btn btn-outline-secondary" type="button" id="copyLink">
                                    <i class="fa fa-copy"></i> Copia
                                </button>
                            </div>
                        </div>
                        <small class="text-muted">
                            <i class="fa fa-info-circle"></i> 
                            Copia questo link e invialo al cliente per confermare la registrazione.
                        </small>
                    </div>
                `,
                showCloseButton: true,
                showConfirmButton: false,
                width: '600px',
                allowOutsideClick: true
            });
            
            // Gestion du bouton copier
            $('#copyLink').on('click', customHandleCopyValidationLink);
            
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Errore',
                text: response.error || 'Errore nel recupero del link'
            });
        }
    }, 'json').fail(function(xhr, status, error) {
        console.error('Errore AJAX View Link:', error);
        Swal.fire({
            icon: 'error',
            title: 'Errore',
            text: 'Errore di connessione'
        });
    });
});

});	
	
function numonly(el) {
	$(el).keydown(function (event) {

		if (event.shiftKey == true) {
			event.preventDefault();
		}
		
		const vKey = 86,
			cKey = 67;
		
		if ( (event.keyCode >= 48 && event.keyCode <= 57) || (event.keyCode >= 96 && event.keyCode <= 105) || event.keyCode == 8 || event.keyCode == 9 || 
			event.keyCode == 37 || event.keyCode == 39 || event.keyCode == 46 || event.keyCode == 188 || 
			( event.keyCode == vKey || event.keyCode == cKey ) ) {

		} else {
			event.preventDefault();
		}
		
		if($(this).val().indexOf('.') !== -1 && event.keyCode == 188) event.preventDefault();

	   });
}

function ValidateEmail(mail) {
  if (typeof mail !== 'string' || mail.length > 254) {
    return false;
  }
  const at = mail.indexOf('@');
  if (at <= 0 || at !== mail.lastIndexOf('@')) {
    return false;
  }
  const domain = mail.slice(at + 1);
  const dot = domain.lastIndexOf('.');
  return dot > 0 && dot < domain.length - 1;
}
    $(document).on('click', '.btnConfirmWin', function(e) {
        e.preventDefault();
        const participationId = $(this).data('participation-id');
        if (!participationId) {
            return;
        }

        Swal.fire({
            title: 'Confermi la vittoria?',
            text: 'Sei sicuro di voler confermare la vittoria?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Si, conferma',
            cancelButtonText: 'Annulla',
            confirmButtonColor: '#198754',
            cancelButtonColor: '#6c757d',
            reverseButtons: true
        }).then((result) => {
            if (!result.isConfirmed) {
                return;
            }

            Swal.fire({
                title: 'Conferma in corso...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            $.ajax({
                url: 'res/inc/confirm_win.php',
                method: 'POST',
                dataType: 'json',
                data: { participation_id: participationId }
            })
                .done((response) => {
                    const success = response && response.success;
                    Swal.fire({
                        icon: success ? 'success' : 'error',
                        title: success ? 'Vittoria confermata' : 'Errore',
                        text: response && response.message ? response.message : (success ? 'Operazione completata.' : 'Non è stato possibile confermare la vittoria.')
                    }).then(() => {
                        if (!success) {
                            return;
                        }
                        if (typeof table !== 'undefined') {
                            table.ajax.reload(null, false);
                        } else {
                            window.location.reload();
                        }
                    });
                })
                .fail((xhr) => {
                    const message = xhr && xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Errore imprevisto durante la conferma.';
                    Swal.fire({
                        icon: 'error',
                        title: 'Errore',
                        text: message
                    });
                });
        });
    });

    $(document).on('click', '.btnCancelWin', function(e) {
        e.preventDefault();
        const participationId = $(this).data('participation-id');
        if (!participationId) {
            return;
        }

        Swal.fire({
            title: 'Annullare la vittoria?',
            text: 'Questa azione rimetterà il premio in disponibilità e segnerà la partecipazione come non vincente.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Si, annulla',
            cancelButtonText: 'Annulla',
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            reverseButtons: true
        }).then((result) => {
            if (!result.isConfirmed) {
                return;
            }

            Swal.fire({
                title: 'Annullamento in corso...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            $.ajax({
                url: 'res/inc/cancel_win.php',
                method: 'POST',
                dataType: 'json',
                data: { participation_id: participationId }
            })
                .done((response) => {
                    const success = response && response.success;
                    Swal.fire({
                        icon: success ? 'success' : 'error',
                        title: success ? 'Vittoria annullata' : 'Errore',
                        text: response && response.message ? response.message : (success ? 'Operazione completata.' : 'Non è stato possibile annullare la vittoria.')
                    }).then(() => {
                        if (!success) {
                            return;
                        }
                        if (typeof table !== 'undefined') {
                            table.ajax.reload(null, false);
                        } else {
                            window.location.reload();
                        }
                    });
                })
                .fail((xhr) => {
                    const message = xhr && xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Errore imprevisto durante l\'annullamento.';
                    Swal.fire({
                        icon: 'error',
                        title: 'Errore',
                        text: message
                    });
                });
        });
    });

    function applyScontrinoValidationUi($row, status, lockAfter) {
        if (!$row || !$row.length) {
            return;
        }
        $row.removeClass('row-scontrino-valid row-scontrino-invalid');
        if (status === 1) {
            $row.addClass('row-scontrino-valid');
            $row.attr('data-scontrino-status', '1');
        } else if (status === 0) {
            $row.addClass('row-scontrino-invalid');
            $row.attr('data-scontrino-status', '0');
        } else {
            $row.attr('data-scontrino-status', '');
        }

        const $validBtn = $row.find('.btnValidateScontrino[data-status="1"]');
        const $invalidBtn = $row.find('.btnValidateScontrino[data-status="0"]');
        $validBtn.removeClass('btn-success btn-outline-success').addClass(status === 1 ? 'btn-success' : 'btn-outline-success');
        $invalidBtn.removeClass('btn-danger btn-outline-danger').addClass(status === 0 ? 'btn-danger' : 'btn-outline-danger');
        if (lockAfter && (status === 0 || status === 1)) {
            $row.find('.btnValidateScontrino').prop('disabled', true);
        }
    }

    function submitScontrinoValidation($row, participationId, nextStatus, lockEmails) {
        $row.find('.btnValidateScontrino').prop('disabled', true);

        $.ajax({
            url: 'res/inc/validate_scontrino.php',
            method: 'POST',
            dataType: 'json',
            data: {
                participation_id: participationId,
                status: nextStatus
            }
        })
            .done((response) => {
                if (!response || !response.success) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Errore',
                        text: (response && response.message) ? response.message : 'Non è stato possibile aggiornare la validazione.'
                    });
                    if (!lockEmails || String($row.attr('data-scontrino-status') || '') === '') {
                        $row.find('.btnValidateScontrino').prop('disabled', false);
                    }
                    return;
                }

                const status = response.status === null || typeof response.status === 'undefined'
                    ? null
                    : parseInt(response.status, 10);
                const shouldLock = !!(response.locked || lockEmails);
                applyScontrinoValidationUi($row, status, shouldLock);

                if (shouldLock && (status === 0 || status === 1)) {
                    // Con Conferma manager: dopo la scelta non si torna indietro
                    $row.find('.btnValidateScontrino').prop('disabled', true);
                } else {
                    $row.find('.btnValidateScontrino').prop('disabled', false);
                }

                const finishUi = () => {
                    const filterScontrino = (new URLSearchParams(window.location.search)).get('filterScontrino') || '';
                    if (filterScontrino === 'valid' || filterScontrino === 'invalid' || filterScontrino === 'pending') {
                        const matchesFilter =
                            (filterScontrino === 'valid' && status === 1) ||
                            (filterScontrino === 'invalid' && status === 0) ||
                            (filterScontrino === 'pending' && (status === null || Number.isNaN(status)));
                        if (!matchesFilter) {
                            $row.fadeOut(180, function () {
                                $(this).remove();
                            });
                        }
                    }
                };

                if (lockEmails) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Operazione completata',
                        text: response.message || 'Validazione registrata.'
                    }).then(() => {
                        if (typeof table !== 'undefined' && table && table.ajax && typeof table.ajax.reload === 'function') {
                            table.ajax.reload(null, false);
                        } else {
                            window.location.reload();
                        }
                    });
                    return;
                }

                finishUi();
            })
            .fail((xhr) => {
                const message = xhr && xhr.responseJSON && xhr.responseJSON.message
                    ? xhr.responseJSON.message
                    : 'Errore imprevisto durante la validazione scontrino.';
                Swal.fire({
                    icon: 'error',
                    title: 'Errore',
                    text: message
                });
                if (!lockEmails || String($row.attr('data-scontrino-status') || '') === '') {
                    $row.find('.btnValidateScontrino').prop('disabled', false);
                }
            });
    }

    $(document).on('click', '.btnValidateScontrino', function (e) {
        e.preventDefault();
        const $btn = $(this);
        if ($btn.prop('disabled')) {
            return;
        }
        const participationId = parseInt($btn.attr('data-participation-id') || '0', 10);
        const requestedStatus = String($btn.attr('data-status') || '');
        if (!participationId || (requestedStatus !== '0' && requestedStatus !== '1')) {
            return;
        }

        const $row = $btn.closest('tr');
        const currentStatus = String($row.attr('data-scontrino-status') || '');
        const lockEmails = String($btn.attr('data-lock-emails') || '') === '1'
            || $row.find('.btnValidateScontrino[data-lock-emails="1"]').length > 0;

        // Con email Conferma manager: niente reset, né cambio dopo la scelta
        if (lockEmails) {
            if (currentStatus === '0' || currentStatus === '1') {
                Swal.fire({
                    icon: 'info',
                    title: 'Scelta già registrata',
                    text: 'Con le email Conferma manager attive non è possibile modificare la validazione.'
                });
                return;
            }
            const confirmTitle = requestedStatus === '1' ? 'Confermare lo scontrino?' : 'Rifiutare lo scontrino?';
            const confirmText = requestedStatus === '1'
                ? 'Questa azione è definitiva e può inviare l\'email di conferma vincita.'
                : 'Questa azione è definitiva e può annullare la vincita e inviare l\'email di annullamento.';
            const confirmBtn = requestedStatus === '1' ? 'Si, conferma' : 'Si, rifiuta';
            Swal.fire({
                title: confirmTitle,
                text: confirmText,
                icon: requestedStatus === '1' ? 'question' : 'warning',
                showCancelButton: true,
                confirmButtonText: confirmBtn,
                cancelButtonText: 'Annulla',
                confirmButtonColor: requestedStatus === '1' ? '#198754' : '#d33',
                cancelButtonColor: '#6c757d',
                reverseButtons: true
            }).then((result) => {
                if (!result.isConfirmed) {
                    return;
                }
                Swal.fire({
                    title: 'Operazione in corso...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                submitScontrinoValidation($row, participationId, requestedStatus, true);
            });
            return;
        }

        // Senza email: secondo click sullo stesso stato = reset a "da validare"
        const nextStatus = currentStatus === requestedStatus ? 'pending' : requestedStatus;
        submitScontrinoValidation($row, participationId, nextStatus, false);
    });

    $(document).on('click', '.btnLastEmailPreview', function (e) {
        e.preventDefault();
        const $btn = $(this);
        const participationId = parseInt($btn.attr('data-participation-id') || 0, 10);
        if (!participationId) {
            return;
        }

        Swal.fire({
            title: 'Caricamento email...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        $.get('res/inc/get_last_email_preview.php', { participation_id: participationId }, function (response) {
            Swal.close();

            if (response && response.success) {
                const escapeHtml = (value) => {
                    return $('<div>').text(value == null ? '' : value).html();
                };
                const subject = escapeHtml(response.subject || 'Oggetto non disponibile');
                const typeLabel = escapeHtml(response.email_type_label || '');

                const previewHtml = response.html || '<p style="padding:1rem;">Nessun contenuto disponibile.</p>';

                Swal.fire({
                    title: 'Anteprima email',
                    html:
                        '<div class="text-start email-preview-meta">' +
                        '<p><strong>Oggetto:</strong> ' + subject + '</p>' +
                        (typeLabel ? '<p><strong>Tipo email:</strong> ' + typeLabel + '</p>' : '') +
                        '</div>' +
                        '<div id="emailPreviewContainer" class="mt-3"></div>',
                    width: '95vw',
                    showCloseButton: true,
                    showConfirmButton: false,
                    didOpen: () => {
                        const popup = Swal.getPopup();
                        if (popup) {
                            popup.style.maxWidth = '1100px';
                            popup.style.maxHeight = 'none';
                            popup.style.width = '95vw';
                        }
                        const swalContainer = Swal.getContainer();
                        if (swalContainer) {
                            swalContainer.style.overflowY = 'auto';
                        }
                        const htmlContainer = Swal.getHtmlContainer();
                        if (!htmlContainer) {
                            return;
                        }
                        htmlContainer.style.maxHeight = 'none';
                        const container = htmlContainer.querySelector('#emailPreviewContainer');
                        if (!container) {
                            return;
                        }
                        const iframe = document.createElement('iframe');
                        iframe.className = 'email-preview-frame';
                        iframe.style.width = '100%';
                        iframe.style.border = '1px solid #dcdcdc';
                        iframe.style.borderRadius = '4px';
                        iframe.style.background = '#ffffff';
                        iframe.style.minHeight = '400px';
                        iframe.style.height = '400px';

                        iframe.setAttribute('srcdoc', previewHtml);

                        iframe.addEventListener('load', () => {
                            try {
                                const doc = iframe.contentDocument || iframe.contentWindow.document;
                                if (!doc) {
                                    return;
                                }
                                const body = doc.body;
                                const documentElement = doc.documentElement;
                                if (body) {
                                    body.style.margin = '0';
                                }
                                const contentHeight = Math.max(
                                    body ? body.scrollHeight : 0,
                                    documentElement ? documentElement.scrollHeight : 0
                                );
                                if (contentHeight > 0) {
                                    iframe.style.height = contentHeight + 16 + 'px';
                                }
                            } catch (err) {
                                iframe.style.height = '80vh';
                            }
                        });

                        container.appendChild(iframe);
                    }
                });
            } else {
                Swal.fire({
                    icon: 'info',
                    title: 'Nessuna email disponibile',
                    text: response && response.message ? response.message : 'Nessuna email trovata per questa partecipazione.'
                });
            }
        }, 'json').fail(function (xhr, status, error) {
            Swal.close();
            let errorMessage = 'Impossibile recuperare l\'anteprima dell\'email.';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMessage = xhr.responseJSON.message;
            } else if (xhr.responseText) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.message) {
                        errorMessage = response.message;
                    }
                } catch (e) {
                    // Ignore JSON parse errors
                }
            }
            Swal.fire({
                icon: 'error',
                title: 'Errore',
                text: errorMessage
            });
        });
    });

    $(document).on('click', '.btnResendEmail', function (e) {
        e.preventDefault();
        const participationId = $(this).data('participation-id');
        const emailType = $(this).data('email-type');
        const emailLabel = $(this).data('email-label') || 'selezionata';

        if (!participationId || !emailType) {
            return;
        }

        Swal.fire({
            title: 'Reinviare l\'email?',
            text: 'Vuoi reinviare l\'email ' + emailLabel + ' al partecipante?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Si, reinvia',
            cancelButtonText: 'Annulla',
            confirmButtonColor: '#0d6efd',
            cancelButtonColor: '#6c757d',
            reverseButtons: true
        }).then((result) => {
            if (!result.isConfirmed) {
                return;
            }

            Swal.fire({
                title: 'Invio in corso...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            $.ajax({
                url: 'res/inc/resend_prize_email.php',
                method: 'POST',
                dataType: 'json',
                data: {
                    participation_id: participationId,
                    email_type: emailType
                }
            })
                .done((response) => {
                    const success = response && response.success;
                    Swal.fire({
                        icon: success ? 'success' : 'error',
                        title: success ? 'Email inviata' : 'Errore',
                        text: response && response.message ? response.message : (success ? 'Operazione completata.' : 'Invio non riuscito.')
                    });
                })
                .fail((xhr) => {
                    const message = xhr && xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Errore imprevisto durante il reinvio.';
                    Swal.fire({
                        icon: 'error',
                        title: 'Errore',
                        text: message
                    });
                });
        });
    });