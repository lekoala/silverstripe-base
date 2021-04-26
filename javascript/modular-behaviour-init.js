ModularBehaviour.init();
jQuery(document).ajaxStop(function (event, xhr, settings) {
  ModularBehaviour.run();
});
