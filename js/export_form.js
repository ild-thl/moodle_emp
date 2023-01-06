let check_all = null;
let checkboxs = null;

self.window.addEventListener('load', init);

function init() {

  check_all = document.querySelector('#m-element-select-all');
  checkboxs = document.querySelectorAll('.m-element-select-achievement');

  if (check_all) {
    check_all.addEventListener('change', function () {
      if (this.checked) {
        selectAll();
      } else {
        unselectAll();
      }
    });
  }
}

function selectAll() {
  checkboxs.forEach((checkbox) => {
    if(checkbox.disabled === false) {
      checkbox.checked = true;
    }
  });
  check_all.checked = true;
}

function unselectAll() {
  checkboxs.forEach((checkbox) => {
    checkbox.checked = false;
  });
  check_all.checked = false;
}