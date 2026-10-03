// Small extras. The site still works if JavaScript is off.

// Ask before doing something that can't be undone
document.querySelectorAll('form[data-confirm]').forEach(function (form) {
  form.addEventListener('submit', function (event) {
    if (!window.confirm(form.dataset.confirm)) {
      event.preventDefault();
    }
  });
});

// Packing checklist: count checked items
document.querySelectorAll('[data-checklist]').forEach(function (box) {
  var count = box.querySelector('[data-count]');
  var boxes = box.querySelectorAll('input[type=checkbox]');
  function update() {
    var done = 0;
    boxes.forEach(function (b) { if (b.checked) done++; });
    count.textContent = done;
    box.classList.toggle('all-done', done === boxes.length && boxes.length > 0);
  }
  boxes.forEach(function (b) { b.addEventListener('change', update); });
  update();
});

// Live total when changing quantity
document.querySelectorAll('form[data-price]').forEach(function (form) {
  var price = parseFloat(form.dataset.price);
  var qty = form.querySelector('[data-qty]');
  var total = form.querySelector('[data-total]');
  qty.addEventListener('input', function () {
    var n = Math.max(1, parseInt(qty.value, 10) || 1);
    total.textContent = '$' + (price * n).toFixed(2);
  });
});
