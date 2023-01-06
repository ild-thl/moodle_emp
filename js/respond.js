self.window.addEventListener('load', init);

function init() {
  if (document.forms['emrexresponse']) {
    document.forms['emrexresponse'].submit();
  }
}