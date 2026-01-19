(function () {
  function normalize(s) {
    return (s || '').toString().toLowerCase().trim();
  }

  function init(container) {
    var search = container.querySelector('[data-mpe-search]');
    var area = container.querySelector('[data-mpe-area]');
    var items = Array.prototype.slice.call(container.querySelectorAll('[data-mpe-item]'));
    var empty = container.querySelector('[data-mpe-empty]');

    function apply() {
      var q = normalize(search && search.value);
      var a = normalize(area && area.value);

      var shown = 0;
      items.forEach(function (it) {
        var blob = normalize(it.getAttribute('data-search'));
        var areaBlob = normalize(it.getAttribute('data-area'));

        var ok = true;
        if (q && blob.indexOf(q) === -1) ok = false;
        if (a && areaBlob !== a) ok = false;

        it.style.display = ok ? '' : 'none';
        if (ok) shown++;
      });

      if (empty) empty.style.display = shown ? 'none' : '';
    }

    if (search) search.addEventListener('input', apply);
    if (area) area.addEventListener('change', apply);
  }

  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.mpe-wrap').forEach(init);
  });
})();
