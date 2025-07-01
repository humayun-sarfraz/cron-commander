jQuery(function($){
  $('.cc-toggle').on('click', function(){
    var btn    = $(this);
    var hook   = btn.data('hook');
    var time   = btn.data('timestamp');

    $.post(CCVars.ajaxUrl, {
      action: 'cc_toggle_cron',
      nonce:  CCVars.nonce,
      hook:   hook,
      timestamp: time
    }, function(res){
      if ( res.success ) {
        // res.data is either 'Stopped' or 'Started'
        var nextLabel = ( res.data === 'Stopped' )
          ? CCVars.toggleText.start
          : CCVars.toggleText.stop;
        btn.text( nextLabel );
      } else {
        alert( res.data );
      }
    });
  });
});
