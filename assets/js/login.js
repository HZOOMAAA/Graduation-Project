function closePopup() {
    const popup = document.getElementById('errorPopup');
    if (popup) {
        popup.classList.add('fade-out');
        setTimeout(() => popup.remove(), 500);
    }
}

setTimeout(closePopup, 5000);