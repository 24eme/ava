(function($)
{
    var _doc = $(document);

    $.initLots = function() {
        if ($('#form_conditionnement_lots').length == 0)
        {
            return;
        }

        $('div.checkboxlots input[type="checkbox"]').click(function(e){
          e.preventDefault();
        });

        var checkBlocsLot = function() {
            $('#form_conditionnement_lots .bloc-lot').each(function() {
                var saisi = false;
                $(this).find('input, select').each(function() {
                    if(($(this).val() && $(this).attr('data-default-value') != $(this).val()) || $(this).is(":focus")) {
                        saisi = true;
                    }
                });
                if(!saisi) {
                    $(this).addClass('transparence-sm');
                } else {
                    $(this).removeClass('transparence-sm');
                }
            });
        }

        var checkBlocsLotCepages = function() {
            $('#form_conditionnement_lots .ligne_lot_cepage').each(function() {
                var saisi = true;
                $(this).find('input, select').each(function() {
                    if(!$(this).val()) {
                        saisi = false;
                    }
                });
                $(this).find('input, select').each(function() {
                    if($(this).is(":focus")) {
                        saisi = true;
                    }
                });
                if(!saisi) {
                    $(this).addClass('transparence-sm');
                } else {
                    $(this).removeClass('transparence-sm');
                }
            });

            $('#form_conditionnement_lots .modal_lot_cepages').each(function() {

                var libelle = "";
                var volume = 0.0;
                var total = 0.0;
                $(this).find('.ligne_lot_cepage').each(function() {
                    total += ($(this).find('.input-float').val())? parseFloat($(this).find('.input-float').val()) : 0;
                });
                $(this).find('.ligne_lot_cepage').each(function() {
                    var ligne = $(this);
                    var cepage = $(this).find('.select2 option:selected').text();
                    var volume = parseFloat($(this).find('.input-float').val());
                    if(cepage && volume > 0) {
                        if(libelle) {
                            libelle = libelle + ", ";
                        }
                        var p = (total)? Math.round((volume/total) * 100) : 0;
                        libelle = libelle + cepage + "&nbsp;("+p+"%)";
                        $(this).removeClass('transparence-sm');
                    } else {
                        $(this).addClass('transparence-sm');
                    }

                    $(this).find('input, select').each(function() {
                        if($(this).is(":focus")) {
                            ligne.removeClass('transparence-sm');
                        }
                    });
                });
                if(!libelle) {
                    libelle = "Vin de cépage(s)";
                    $('#lien_'+$(this).attr('id')).removeAttr("checked");
                }else{
                  $('#lien_'+$(this).attr('id')).prop("checked","checked");
                }
                $('span.checkboxtext_'+$(this).attr('id')).html(libelle);
            });
        }

        var inputs_hl = document.querySelectorAll('.modal input.input-hl')

        inputs_hl.forEach(function (input, index) {
            input.addEventListener('change', function (event) {
                var total = 0.0

                var modal = event.target.parentElement
                while (! modal.classList.contains('modal')) {
                    modal = modal.parentElement
                }

                var lot = modal.dataset.lot

                inputs = modal.querySelectorAll('input.input-hl')
                inputs.forEach(function (input) {
                    if (! isNaN(parseFloat(input.value))) {
                        total += parseFloat(input.value)
                    }
                })

                var vol_total = document.getElementById('conditionnement_lots_lots_'+lot+'_volume')
                vol_total.value = parseFloat(total)

                $('#conditionnement_lots_lots_'+lot+'_volume').blur()

                vol_total.readOnly = (parseFloat(vol_total.value) > 0) ? true : false
            })
        })

        function precision(f) {
            if (!isFinite(f)) { return 2 }
            var e = 1, p = 0
            while (Math.round(f * e) / e !== f) { e *= 10; p++; }
            if (p > 4) { p = 4 }
            return p
        }

        checkBlocsLot();
        checkBlocsLotCepages();
        $('#form_conditionnement_lots input').on('keyup', function() { checkBlocsLot(); checkBlocsLotCepages(); });
        $('#form_conditionnement_lots select').on('change', function() { checkBlocsLot(); checkBlocsLotCepages(); });
        $('#form_conditionnement_lots input').on('focus', function() { checkBlocsLot(); checkBlocsLotCepages(); });
        $('#form_conditionnement_lots select').on('focus', function() { checkBlocsLot(); checkBlocsLotCepages(); });
        $('#form_conditionnement_lots input').on('blur', function() { checkBlocsLot(); checkBlocsLotCepages(); });
        $('#form_conditionnement_lots select').on('blur', function() { checkBlocsLot(); checkBlocsLotCepages(); });

        $('#form_conditionnement_lots input.input-float').on('click', function(e) {
            if (! e.target.readOnly) {
                return false
            }

            id = parseInt(e.target.id.replace(/[^0-9]/g, ''))
            $('#conditionnement_lots_lots_'+id+'_cepages').modal('toggle')
        })

        $('#form_conditionnement_lots .lot-delete').on('click', function() {
            if(!confirm("Étes vous sûr de vouloir supprimer ce lot ?")) {

                return;
            }

            $(this).parents('.bloc-lot').find('input, select').each(function() {
                $(this).val("");
            });
            $(this).parents('.bloc-lot').find('.select2autocomplete').select2('val', "");
            $(this).parents('.bloc-lot').hide();
        })

    }

    _doc.ready(function()
    {
        $.initLots();

    });

})(jQuery);
