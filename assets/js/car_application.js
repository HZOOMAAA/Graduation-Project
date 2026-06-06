document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('carInsuranceForm');
    if (!form) return;

    form.addEventListener('submit', async function (e) {
        e.preventDefault();

        const brand     = document.getElementById('brand-input').value.trim();
        const model     = document.getElementById('model-input').value.trim();
        const year      = document.getElementById('year-input').value.trim();
        const price     = document.getElementById('price').value.trim();
        const condition = form.querySelector('input[name="condition"]:checked');

        if (!brand) {
            showModal('error', 'Missing Field', 'Please select a car brand.');
            return;
        }
        if (!model) {
            showModal('error', 'Missing Field', 'Please select a car model.');
            return;
        }
        if (!year) {
            showModal('error', 'Missing Field', 'Please select the manufacture year.');
            return;
        }
        if (!price || parseFloat(price) <= 0) {
            showModal('error', 'Missing Field', 'Please enter a valid estimated price.');
            return;
        }
        if (!condition) {
            showModal('error', 'Missing Field', 'Please select the car condition.');
            return;
        }

        document.getElementById('submitBtnText').style.display   = 'none';
        document.getElementById('submitBtnLoader').style.display = 'inline';
        document.getElementById('submitBtn').disabled = true;

        const formData = new FormData(form);

        try {
            const response = await fetch('/Graduation-Project/category-car.php', {
                method: 'POST',
                body:   formData,
            });

            const result = await response.json();

            if (result.success) {
                window.location.href = '/Graduation-Project/plans.php';
            } else if (result.login_required) {
                window.location.href = result.redirect_url;
            } else {
                showModal('error', 'Submission Failed', result.message || 'Something went wrong.');
            }
        } catch (error) {
            showModal('error', 'Connection Error', 'Unable to connect to the server. Please try again.');
        } finally {
            document.getElementById('submitBtnText').style.display   = 'inline';
            document.getElementById('submitBtnLoader').style.display = 'none';
            document.getElementById('submitBtn').disabled = false;
        }
    });
});

function showModal(type, title, message) {
    const overlay = document.getElementById('appModal');
    const icon    = document.getElementById('appModalIcon');
    const titleEl = document.getElementById('appModalTitle');
    const msgEl   = document.getElementById('appModalMsg');

    icon.className = 'app-modal-icon';

    if (type === 'success') {
        icon.innerHTML = '<i class="fa-solid fa-circle-check"></i>';
    } else {
        icon.innerHTML = '<i class="fa-solid fa-circle-xmark"></i>';
    }

    titleEl.textContent = title;
    msgEl.textContent   = message;
    overlay.style.display = 'flex';
}

function closeAppModal() {
    document.getElementById('appModal').style.display = 'none';
}