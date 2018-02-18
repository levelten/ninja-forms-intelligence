(function( $, wp, wp_intel, settings ) {
  var $document = $(document);

  wp = wp || {};

  wp_intel = wp_intel || {};

  wp_intel.setup_plugins = {};

  /*
  wp_intel.setup_plugins.init = function() {
    // copy function by value
    wp.updates.installPluginSuccess0 = wp.updates.installPluginSuccess.bind({});

    wp.updates.installPluginSuccess = wp_intel.setup_plugins.installPluginSuccess;
  };

  wp_intel.setup_plugins.installPluginSuccess = function( response ) {

    if (response.slug) {
      var url0, matches, a = jQuery("a[data-slug='" + response.slug + "']");

      if (a.attr) {
        a = a.attr('data-activate-url');
        if (a.length) {
          url0 = response.activateUrl;
          console.log(response.activateUrl);
          response.activateUrl = a + '&_wpnonce=' + wp_intel.setup_plugins.getUrlParameter(response.activateUrl, '_wpnonce') + '&plugin=' + wp_intel.setup_plugins.getUrlParameter(response.activateUrl, 'plugin');
          console.log(a);
          console.log(response.activateUrl);
        }
      }

      wp.updates.installPluginSuccess0(response);
    }
  };

  wp_intel.setup_plugins.getUrlParameter = function (url, name) {
    var l = document.createElement('a');
    l.href = url;

    name = name.replace(/[\[]/, '\\[').replace(/[\]]/, '\\]');
    var regex = new RegExp('[\\?&]' + name + '=([^&#]*)');
    var results = regex.exec(l.search);
    return results === null ? '' : decodeURIComponent(results[1].replace(/\+/g, ' '));
  };

  setTimeout(function(){ if (wp.updates != undefined) { wp_intel.setup_plugins.init(); }  }, 1000);
  */

})( jQuery, window.wp, window.wp_intel, window._intel );