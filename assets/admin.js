(function () {
  'use strict';
  var choices = document.querySelectorAll('input[name="flakflak_wp_settings[visibility]"]');
  var container = document.getElementById('flakflak-specific-pages');
  var input = document.getElementById('flakflak-post-ids');
  if (!container || !input) return;
  function update() {
    var selected = document.querySelector('input[name="flakflak_wp_settings[visibility]"]:checked');
    var specific = selected && selected.value === 'specific';
    container.hidden = !specific;
    input.disabled = !specific;
    input.required = !!specific;
  }
  choices.forEach(function (choice) { choice.addEventListener('change', update); });
  update();
})();
