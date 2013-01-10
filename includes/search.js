jQuery(document).ready(function($) { 
 $.widget( "custom.catcomplete", $.ui.autocomplete, {
    _renderMenu: function( ul, items ) {
      var that = this,
        currentCategory = "";

      $.each( items, function( index, item ) {
		if ( item.category != currentCategory ) {
			ul.append( "<li class='ui-autocomplete-category'>" + item.category + "</li>" );
          		currentCategory = item.category;
        	}
        	that._renderItemData( ul, item );
      	});
    }
 });

    $( ".pcg-search" ).catcomplete({
      delay: 0,
      minLength: 2,
      source: function( request, response ) {
        $.ajax({
          url: plugincodexgen.ajax_url,
          dataType: "json",
          data: {
            action: "plugin_codex_search",
	    term: request.term
          },
	success: function( data ) {
            response(data);
          }
        });
      },
	 select: function (event, ui) {
            window.location = ui.item.url;
        }
     });

});
