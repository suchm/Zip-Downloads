(function ($) {

  var ZipDownloads = function () {

    var zipdownloads = this;

    var sizes = [];

    $.extend(zipdownloads, {

      init: function () {

        var self = zipdownloads;

        self.allBanners(); 
        $('#media-all-banners input').on('click', self.selectBanners);     
        $('#media-create-zip').on('click', self.createZipFile);
      },

      /**
       * Select all files on load
       * @param  {[type]} e [description]
       * @return {[type]}   [description]
       */
      allBanners: function (e) {

        if( $('#media-all-banners input').is(':checked') ){
          $('#media-sizes span input').prop( 'checked', true );
        }

      },

      /**
       * Select files to download
       * @param  {[type]} e [description]
       * @return {[type]}   [description]
       */
      selectBanners: function (e) {

        if( $( this ).is(':checked') ){
            $('#media-sizes span input').prop('checked', true );
        } else {
            $('#media-sizes span input').prop('checked', false );
        }

      },

      /**
       * Create the zip file
       * @param  {[type]} e [description]
       * @return {[type]} striing  [description]
       */
      createZipFile: function (e) {
        
        if( !$('#media-download-zip').hasClass( 'hide' ) ){ $('#media-download-zip').addClass('hide'); }
        if( !$('#media-none-selected').hasClass( 'hide' ) ){ $('#media-none-selected').addClass('hide'); }
        if( !$('#media-progress-bar').hasClass( 'hide' ) ){ $('#media-progress-bar').addClass('hide'); }
        $('#media-progress-bar').css('width', '2%');

        sizes = [];

        $('#media-sizes span input').each( function(index, val) {

            if( $( this ).is(':checked') ){

              banner = $(this).val();
              sizes.push( banner );
            }
        });

        if( sizes.length === 0 ){

            $('#media-none-selected').removeClass('hide');

        } else {

            if( $('#media-progress-bar').hasClass( 'hide' ) ){ $('#media-progress-bar').removeClass('hide'); }

            var ajax_url = localizedscript.ajax_url;

            var data = {
                'action': 'zip_files',
                'file_uploads': sizes
            };

            // Send ajax request to create zip file and bring back download link
            $.ajax({
                url: ajax_url, 
                type: "POST",
                data: data,
                datatype: "text",
                success: function(data){

                    $('#media-download-zip').attr( 'href', data );
                    $('#media-progress-bar').animate({ width: '100%' }, 500, function(){

                        $('#media-download-zip').removeClass('hide');
                    });
                }
            });
        }

        return false;

      }

    });

    zipdownloads.init();

  };

  $(document).on('ready', ZipDownloads);

})(jQuery);
