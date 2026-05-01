document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('carInsuranceForm');
    if (!form) return;

    form.addEventListener('submit', async function (e) {
        e.preventDefault();

        // ── Validate hidden inputs filled by custom selects ────────────────
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

        // ── Show loader ────────────────────────────────────────────────────
        document.getElementById('submitBtnText').style.display   = 'none';
        document.getElementById('submitBtnLoader').style.display = 'inline';
        document.getElementById('submitBtn').disabled = true;

        // ── Build FormData & POST ──────────────────────────────────────────
        const formData = new FormData(form);

        try {
            const response = await fetch('/Graduation-Project/submit_car_application.php', {
                method: 'POST',
                body:   formData,
            });

            const result = await response.json();

            if (result.success) {
                // ── Redirect to plan-selection page ──────────────────────────
                window.location.href = '/Graduation-Project/plans.php?application_id=' + result.application_id;
            } else {
                showModal('error', 'Submission Failed', result.message || 'Something went wrong. Please try again.');
            }
        } catch (err) {
            showModal('error', 'Network Error', 'Could not connect to server. Please check your connection.');
        } finally {
            document.getElementById('submitBtnText').style.display   = 'inline';
            document.getElementById('submitBtnLoader').style.display = 'none';
            document.getElementById('submitBtn').disabled = false;
        }
    });
});

// ── Modal Helpers ──────────────────────────────────────────────────────────────
function showModal(type, title, message) {
    const overlay = document.getElementById('appModal');
    const icon    = document.getElementById('appModalIcon');
    const titleEl = document.getElementById('appModalTitle');
    const msgEl   = document.getElementById('appModalMsg');

    icon.className = 'app-modal-icon';

    if (type === 'success') {
        icon.classList.add('app-modal-icon--success');
        icon.innerHTML = '<i class="fas fa-check-circle"></i>';
    } else {
        icon.classList.add('app-modal-icon--error');
        icon.innerHTML = '<i class="fas fa-times-circle"></i>';
    }

    titleEl.textContent = title;
    msgEl.textContent   = message;
    overlay.style.display = 'flex';
}

function closeAppModal() {
    document.getElementById('appModal').style.display = 'none';
}
