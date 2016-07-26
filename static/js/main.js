jQuery('select').select2({
    placeholder: 'Wähle ein Pokemon'
}).on('select2:selecting', function(e) {
    getPKMN(e.params.args.data.id, e.params.args.data.text)
})

function getPKMN(id, text) {
    jQuery('.loader').fadeIn()
    jQuery.ajax({
        type: 'POST',
        url: '/get/pokemon',
        data: {
            id: id,
            name: text
        },
        success: function(markers) {
            deleteMarkers()
            addMarkers(markers, text)
            window.history.pushState({'pkmn_id': id, 'pkmn_name': text}, 'Pookémon Map Braunschweig - '+text, '/pokemon/'+text.toLowerCase())
        }
    })
}

jQuery('.menue-icon-open').on('click', function() {
    jQuery('.menue-container').addClass('wide')
    jQuery('.menue').addClass('ib')
    jQuery(this).hide().removeClass('ib')
    jQuery('.menue-icon-close').addClass('ib')
    // jQuery('select').select2('open')
})

jQuery('.menue-icon-close').on('click', function() {
    jQuery('.menue-container').removeClass('wide')
    jQuery('.menue').removeClass('ib')
    jQuery(this).hide().removeClass('ib')
    jQuery('.menue-icon-open').addClass('ib')
})

window.onpopstate = function(e) {
    if (e.state.pkmn_id != null) {
        getPKMN(e.state.pkmn_id, e.state.pkmn_name)
        jQuery('select').select2().val(e.state.pkmn_id).trigger('change')
    }
}
