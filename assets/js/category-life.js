document.addEventListener("DOMContentLoaded", function () {
    const wrappers = document.querySelectorAll(".custom-select-wrapper");

    wrappers.forEach(wrapper => {
        const trigger = wrapper.querySelector(".custom-select-trigger");
        const options = wrapper.querySelectorAll(".custom-option");
        const hiddenInput = wrapper.parentElement.querySelector("input[type='hidden']");

        trigger.addEventListener("click", function (e) {
            e.stopPropagation();
            wrappers.forEach(w => { if (w !== wrapper) w.classList.remove("open"); });
            wrapper.classList.toggle("open");
        });

        options.forEach(option => {
            option.addEventListener("click", function () {
                trigger.querySelector("span").innerText = this.innerText;
                hiddenInput.value = this.getAttribute("data-value");
                wrapper.classList.remove("open");
            });
        });
    });

    document.addEventListener("click", function () {
        wrappers.forEach(w => w.classList.remove("open"));
    });
});

function showAppModal(type, title, message) {
    const modal = document.getElementById('appModal');
    const iconDiv = document.getElementById('appModalIcon');
    document.getElementById('appModalTitle').innerText = title;
    document.getElementById('appModalMsg').innerText = message;

    iconDiv.className = 'app-modal-icon';
    if (type === 'success') {
        iconDiv.classList.add('app-modal-icon--success');
        iconDiv.innerHTML = "<i class='bx bx-check-circle'></i>";
    } else {
        iconDiv.classList.add('app-modal-icon--error');
        iconDiv.innerHTML = "<i class='bx bx-x-circle'></i>";
    }
    modal.style.display = 'flex';
}

function closeAppModal() {
    document.getElementById('appModal').style.display = 'none';
}

document.getElementById('lifeInsuranceForm').addEventListener('submit', function (e) {
    e.preventDefault();
    const submitBtn = document.getElementById('submitBtn');
    const btnText = document.getElementById('submitBtnText');
    const btnLoader = document.getElementById('submitBtnLoader');

    submitBtn.disabled = true;
    if (btnText) btnText.style.display = 'none';
    if (btnLoader) btnLoader.style.display = 'inline-block';

    const formData = new FormData(this);

    fetch('/Graduation-Project/submit_life_application.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            submitBtn.disabled = false;
            if (btnText) btnText.style.display = 'inline-block';
            if (btnLoader) btnLoader.style.display = 'none';

            if (data.success) {
                // Redirect directly to plans page without pop up
                window.location.href = '/Graduation-Project/plans.php';
            } else {
                showAppModal('error', 'Error Occurred', data.message || 'Failed to analyze life risk parameters.');
            }
        })
        .catch(error => {
            submitBtn.disabled = false;
            if (btnText) btnText.style.display = 'inline-block';
            if (btnLoader) btnLoader.style.display = 'none';
            showAppModal('error', 'Connection Error', 'Server error. Please verify your connection.');
        });
});