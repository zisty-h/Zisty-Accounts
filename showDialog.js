function showDialog(message) {
    const dialog = document.getElementById('dialog');
    dialog.textContent = message;
    setTimeout(() => {
      dialog.classList.add('show');
    }, 100);
    setTimeout(() => {
      dialog.classList.remove('show');
    }, 2000);
  }