(function( $ ) {

    $.fn.wpghToolBar = function() {

        var html =
        '<wpgh-toolbar class="action-icons">' +
            '<div>' +
                '<span class="dashicons dashicons-admin-page"></span>' +
                '<span class="dashicons dashicons-move handle"></span>' +
                '<span class="dashicons dashicons-trash"></span>' +
            '</div>' +
        '</wpgh-toolbar>';

        this.each(function() {

            var row = $( this );

            if ( row.find( 'wpgh-toolbar' ).length === 0 )
                row.prepend( html );

        });

        return this;

    };

}( jQuery ));

( function( $, editor ) {

    $.extend( editor, {

        editor:     null,
        actions:    null,
        settings:   null,
        active:     null,
        alignment:  null,
        sidebar:    null,
        htmlCode:   null,

        /**
         * Initialize the editor
         */
        init: function () {

            var self = this;

            self.editor  = $( '#email-body' );
            self.actions = $( '#editor-panel' );
            self.settings = $( '#settings-panel' );

            self.editor.on( 'click', function (e) {
                e.preventDefault();
                self.feed( e.target );
            } );

            self.editor.on( 'click', 'span.dashicons-admin-page', function ( e ) {
                e.preventDefault();
                self.duplicateBlock( e.target );
            });

            self.editor.on( 'click', 'span.dashicons-trash', function ( e ) {
                e.preventDefault();
                self.deleteBlock( e.target );
            });

            self.makeSortable();
            self.makeDraggable();

            /* Activate Spinner */
            $('form').on( 'submit', function( e ){
                self.save( e );
            });

            $( '.row' ).wpghToolBar();

            self.alignment = $( '#email-align' );
            self.alignment.on( 'change', function () {
                var email =  $( '#email-inside' );
                if ( $( this ).val() === 'left' ){
                    email.css( 'margin-left', '0' );
                    email.css( 'margin-right', 'auto' );
                } else {
                    email.css( 'margin-left', 'auto' );
                    email.css( 'margin-right', 'auto' );
                }
            } );

            // /* Size the editor to full screen if being views in an Iframe. */
            // // TODO
            // if ( self.inFrame() ){
            //     // $( 'body' ).html( $( '#wpbody' ) );
            //     // $( '#screen-meta-links' ).remove();
            //     $( 'html' ).css( 'padding-top', 0 );
            //     $( '#wpcontent' ).css( 'margin', 0 );
            //     $( '#wpadminbar' ).addClass( 'hidden' );
            //     $( '#adminmenuwrap' ).addClass( 'hidden' );
            //     $( '#adminmenuback' ).addClass( 'hidden' );
            //     $( '#wpfooter' ).addClass( 'hidden' );
            //     $( '.title-wrap' ).css( 'display', 'none' );
            //     $( '.funnel-editor-header' ).css( 'top', 0 );
            //
            //     $( document ).on( 'change keydown keyup', function ( e ) {
            //         parent.wpghEmailElement.changesSaved = false;
            //     } );
            //
            //     $(  parent.document ).on( 'click','.popup-save', function( e ){
            //         self.save( e );
            //     } );
            //
            //     parent.wpghEmailElement.ID = email.id;
            // }

            self.editorSizing();

            $( window ).resize(function() {
                self.editorSizing();
            });

            $('#editor-toggle').change(function(){
                if ($(this).is(':checked')) {

                    if ( ! self.htmlCode ){
                        self.initCodeMirror();
                    }

                    $('#email-content').hide();
                    $('#html-editor').show();

                    self.prepareEmailHTML();
                    self.htmlCode.doc.setValue( html_beautify( $('#email-inside').html() ) );
                } else {
                    $( '.row' ).wpghToolBar();
                    $('#html-editor').hide();
                    $('#email-content').show();
                }
            }).change();

            this.sidebar = new StickySidebar( '#postbox-container-1' , {
                topSpacing: 78,
                bottomSpacing: 0
            });

        },

        prepareEmailHTML : function()
        {
            var $email = $('#email-content');
            $('wpgh-toolbar').remove();
            $email.find('div').removeAttr( 'contenteditable' ).removeClass( 'active' );
            TextBlock.destroyEditor();
        },

        /**
         * Code Mirror
         */
        initCodeMirror: function()
        {
            var self = this;

            self.htmlCode = CodeMirror.fromTextArea( document.getElementById("html-code"), {
                lineNumbers: true,
                mode: "text/html",
                matchBrackets: true,
                indentUnit: 4,
                specialChars: /[\u0000-\u001f\u007f-\u009f\u00ad\u061c\u200b-\u200f\u2028\u2029\ufeff]/
            });

            self.htmlCode.on('change', function() {
                $('#email-inside').html(self.htmlCode.doc.getValue());
            });

            self.htmlCode.setSize( null, self.editor.height() );
        },

        inFrame: function () {
            try {
                return window.self !== window.top;
            } catch (e) {
                return true;
            }
        },

        save: function ( e ) {

            var self = this;

            e.preventDefault();
            showSpinner();

            self.prepareEmailHTML();

            $('#content').val( $('#email-inside').html() );
            var fd = $('form').serialize();
            fd = fd +  '&action=gh_update_email';

            adminAjaxRequest( fd, function ( response ) {

                handleNotices( response.data.notices );
                hideSpinner();

                $( '.row' ).wpghToolBar();

                console.log( response );
            } );
        },

        editorSizing: function (){
            $('.editor-header').width( $('#poststuff').width() );
        },

        /**
         * Make the blocks sortable
         */
        makeSortable: function(){
            $( ".email-sortable" ).sortable({
                placeholder: "sortable-placeholder",
                axis: 'y',
                start: function(e, ui){
                    ui.placeholder.height(ui.item.height());
                },
                handle: '.handle',
                stop: function (e, ui) {
                }
            });
        },

        /**
         * Make the blocks draggable
         */
        makeDraggable: function(){
            $( ".email-draggable" ).draggable({
                connectToSortable: ".email-sortable",
                helper: "clone",
                start: function ( e, ui ) {
                    var el = this;
                    var block_type = el.id.replace( '-block', '' );
                    var html = $( '.' + block_type + '-template' ).html();
                    $('#temp-html').html( html );
                },
                stop: function ( e, ui ) {
                    $('#email-content').find('.email-draggable').replaceWith( $('#temp-html').html() );
                }
            });
        },

        /**
         * Make the blocks draggable
         */
        makeClickable: function(){
            $( ".email-draggable" ).on( 'dblclick', function ( e ) {
                $('#email-content')
            });
        },

        /**
         * Show the block settings
         * Make the block active
         *
         * @param e
         */
        feed: function( e ) {

            // console.log( {e: e} );

            /* Make Current Block Active*/
            if ( e.parentNode === null ){
                return;
            }

            var block = $( e ).closest( '.row' );

            /* check if already active */
            if ( block.hasClass( 'active' ) ){
                return;
            }

            if ( ! block.hasClass( 'row' ) ){

                this.editor.find( '.row' ).removeClass( 'active' );
                this.actions.find( '.postbox' ).addClass( 'hidden' );

                // Show regular settings
                this.settings.show();

                $(document).trigger( 'madeInactive' );

                return;

            }

            this.active = block;

            /* Make all blocks inactive */
            this.editor.find( '.row' ).removeClass( 'active' );
            block.addClass( 'active' );
            var blockType = block.attr( 'data-block' );

            if ( typeof blockType === 'undefined' && typeof block !== 'undefined' ){

                /* backwards compat */
                var $content = block.find( '.content-wrapper' );
                var classes = $content.attr( 'class' );
                blockType = /\w+_block/.exec( classes )[0];
                blockType = blockType.replace( '_block', '' );

            }

            // Hide All Settings
            this.actions.find( '.postbox' ).addClass( 'hidden' );

            // Show block Settings
            this.actions.find( '#' + blockType + '-block-editor' ).removeClass( 'hidden' );

            // Hide Regular Settings Panel
            this.settings.hide();

            $(document).trigger( 'madeActive', [ block, blockType ] );
            // console.log( { block_type: blockType, block: block });

            this.sidebar.updateSticky();

        },

        /**
         * Delete a block
         *
         * @param e
         */
        deleteBlock: function( e ){
            $( e ).closest( '.row' ).remove();

        },

        /**
         * Duplicate a block
         *
         * @param e
         */
        duplicateBlock: function( e ){
            $(document).trigger( 'duplicateBlock' );
            $(e).closest('.row').removeClass('active');
            $(e).closest('.row').clone().insertAfter( $(e).closest('.row') );

        },

        getActive: function () {
            return this.active;
        }

    } );

    $(function () {
        editor.init();
    })

} )( jQuery, EmailEditor );