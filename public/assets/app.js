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

// Live total: item price x quantity, or package price + extras, plus setup fee
document.querySelectorAll('form[data-price], form[data-package-price]').forEach(function (form) {
  var total = form.querySelector('[data-total]');
  var qty = form.querySelector('[data-qty]');
  var extras = form.querySelectorAll('[data-extra-price]');
  var setup = form.querySelector('input[name=service][value=setup]');
  var address = form.querySelector('[data-address]');
  var addressInput = address ? address.querySelector('input') : null;

  function update() {
    var sum = 0;
    if (form.dataset.price) {
      sum = parseFloat(form.dataset.price) * Math.max(1, parseInt(qty.value, 10) || 1);
    } else {
      sum = parseFloat(form.dataset.packagePrice);
    }
    extras.forEach(function (input) {
      sum += parseFloat(input.dataset.extraPrice) * Math.max(0, parseInt(input.value, 10) || 0);
    });
    var wantsSetup = setup && setup.checked;
    if (wantsSetup) {
      sum += parseFloat(setup.dataset.fee);
    }
    if (address) {
      address.hidden = !wantsSetup;
      addressInput.required = wantsSetup;
    }
    total.textContent = '$' + sum.toFixed(2);
  }

  form.addEventListener('input', update);
  form.addEventListener('change', update);
  update();
});
