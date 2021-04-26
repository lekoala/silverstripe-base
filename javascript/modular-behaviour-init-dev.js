ModularBehaviour.init({ debug: true });
jQuery(document).ajaxStop(function (event, xhr, settings) {
  ModularBehaviour.run();
});
