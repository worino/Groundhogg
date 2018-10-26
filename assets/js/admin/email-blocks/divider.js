wpghEmailEditor = wpghEmailEditor || {};

var wpghDividerBlock;
( function( $, editor ) {

    wpghDividerBlock = {

        blockType: 'divider',

        height: null,

        init : function () {

            this.height  = $( '#divider-width' );
            this.height.on( 'change', function ( e ) {
                editor.getActive().find('hr').css('width', $(this).val() + '%' );
            });

            $(document).on( 'madeActive', function (e, block, blockType ) {

                if ( wpghDividerBlock.blockType === blockType ){

                    // wpghDividerBlock.createEditor();
                    // console.log( {in:'text', blockType: blockType} );
                    wpghDividerBlock.parse( block );
                }

            });

        },

        /**
         * A jquery implement block.
         *
         * @param block $
         */
        parse: function ( block ) {

            this.height.val( Math.ceil( ( editor.getActive().find('hr').width() / editor.getActive().find('hr').closest('div').width() ) * 100 ) );

        }

    };

    $(function(){
        wpghDividerBlock.init();
    })

})( jQuery, wpghEmailEditor );