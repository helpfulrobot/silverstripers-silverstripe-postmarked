/**
 * Created by nivankafonseka on 9/25/15.
 */
(function($){

    $.entwine('ss', function($){

        var popup = $('.postmark_popup');
        if(popup.length == 0){
            $('body').append('<div class="postmark_popup"></div>');
            popup = $('.postmark_popup');
        }
        var dialog = popup.dialog({
            autoOpen: false,
            height: 550,
            width: 700,
            modal: true
        });


        var messages_selector = '.message-item';
        var message_header_selector = '.message-header';
        var message_popup_button_selector = '.open-message-popup';
        var message_form_selector = '#Form_MessageForm';


        $(message_popup_button_selector).entwine({

            onclick: function(e){

                var url = this.attr('href');

                $.ajax({
                    url         : url,
                    'success'   : function(data){
                        dialog.html(data);
                        dialog.dialog( "open" );
                    }
                });
                return false;
            }

        });

        $(message_header_selector).entwine({

            onclick : function(e){
                var message = $(this).closest(messages_selector);
                if(message.hasClass('ex')){
                    message.removeClass('ex').addClass('collapsed').find('.message-contents').hide();
                }
                else{
                    message.addClass('ex').removeClass('collapsed').find('.message-contents').show();
                    if(this.data('id') && this.data('readlink')){
                        $.ajax({
                            url         : this.data('readlink'),
                            data        : {
                                m       : this.data('id')
                            },
                            method      : 'GET',
                            type        : 'GET'
                        });
                    }
                }
                e.preventDefault();

            }

        });


        $(message_form_selector).entwine({

            onsubmit: function(e){
                e.preventDefault();
                var form = $(this);
                var action = form.attr('action');
                var data = new FormData(this[0]);

                form.parent().find('.js-message').remove();

                var emails = form.find('.option-selector-folder-holder div.item');
                if(emails.length > 0){

                    $.ajax({
                        url     : action,
                        data    : data,
                        async   : false,
                        cache   : false,
                        contentType: false,
                        processData: false,
                        method  : 'POST',
                        type    : 'POST',
                        success : function(){
                            form.hide();
                            form.before('<p class="message js-message">Email sent successfully</p>');
                        }
                    });
                }
                else{
                    form.before('<p class="message js-message">Please select email addresses</p>');
                }


                return false;
            }

        });


        $('.toggle-block').entwine({
            onmatch: function(){

                var dom = $(this);
                var contents = dom.find('.contents');
                var header = dom.find('h4');

                contents.hide();
                header.on('click', function(){
                    if(header.hasClass('ex')){
                        contents.hide();
                        header.removeClass('ex');
                    }
                    else{
                        contents.show();
                        header.addClass('ex');
                    }

                    return false;
                });

            }
        });

    });


})(jQuery);