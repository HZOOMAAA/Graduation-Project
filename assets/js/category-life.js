document.addEventListener("DOMContentLoaded", function () {
    const wrappers = document.querySelectorAll(".custom-select-wrapper");

    wrappers.forEach(wrapper => {
        const trigger = wrapper.querySelector(".custom-select-trigger");
        const options = wrapper.querySelectorAll(".custom-option");
        const hiddenInput = wrapper.closest(".input-group").querySelector("input[type='hidden']");

        trigger.addEventListener("click", function (e) {
            e.stopPropagation();
            wrappers.forEach(w => { if (w !== wrapper) w.classList.remove("open"); });
            wrapper.classList.toggle("open");
        });

        options.forEach(option => {
            option.addEventListener("click", function () {
                trigger.querySelector("span").innerText = this.innerText;
                if (hiddenInput) hiddenInput.value = this.getAttribute("data-value");
                wrapper.classList.remove("open");
            });
        });
    });

    document.addEventListener("click", function () {
        wrappers.forEach(w => w.classList.remove("open"));
    });

    const form = document.getElementById('lifeInsuranceForm');
    if (!form) return;

    form.addEventListener('submit', function (e) {
        e.preventDefault();

        const bDay        = document.getElementById('birth_day').value.trim();
        const bMonth      = document.getElementById('birth_month').value.trim();
        const bYear       = document.getElementById('birth_year').value.trim();
        const coverageAmt = document.getElementById('coverage_amount').value.trim();
        const policyTerm  = document.getElementById('policy-term-input').value.trim();
        const benefName   = document.getElementById('beneficiary_name').value.trim();
        const benefRel    = document.getElementById('relation-input').value.trim();

        if (!bDay || !bMonth || !bYear) { showModal('error', 'Missing Field', 'Please enter your complete birthdate.'); return; }
        if (parseInt(bDay) < 1 || parseInt(bDay) > 31) { showModal('error', 'Invalid Value', 'Day must be between 1 and 31.'); return; }
        if (parseInt(bMonth) < 1 || parseInt(bMonth) > 12) { showModal('error', 'Invalid Value', 'Month must be between 1 and 12.'); return; }
        if (parseInt(bYear) < 1920 || parseInt(bYear) > 2026) { showModal('error', 'Invalid Value', 'Please enter a valid birth year.'); return; }
        
        if (!coverageAmt || parseFloat(coverageAmt) <= 0) { showModal('error', 'Invalid Value', 'Please enter a valid insurance coverage amount.'); return; }
        if (!policyTerm) { showModal('error', 'Missing Field', 'Please select the policy duration term.'); return; }
        if (!benefName) { showModal('error', 'Missing Field', 'Please enter the primary beneficiary name.'); return; }
        if (!benefRel) { showModal('error', 'Missing Field', 'Please select the relationship status.'); return; }

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
            if (data.success) {
                window.location.href = '/Graduation-Project/plans.php';
            } else if (data.login_required) {
                window.location.href = data.redirect_url;
            } else {
                showModal('error', 'Error Occurred', data.message || 'Failed to analyze life risk parameters.');
            }
        })
        .catch(error => {
            showModal('error', 'Connection Error', 'Server error. Please verify your internet connection.');
        })
        .finally(() => {
            submitBtn.disabled = false;
            if (btnText) btnText.style.display = 'inline-block';
            if (btnLoader) btnLoader.style.display = 'none';
        });
    });
});

function showModal(type, title, message) {
    const modal = document.getElementById('appModal');
    const iconDiv = document.getElementById('appModalIcon');
    document.getElementById('appModalTitle').innerText = title;
    document.getElementById('appModalMsg').innerText = message;

    iconDiv.className = 'app-modal-icon';
    if (type === 'success') {
        iconDiv.innerHTML = "<i class='fa-solid fa-circle-check'></i>";
    } else {
        iconDiv.innerHTML = "<i class='fa-solid fa-circle-xmark'></i>";
    }
    modal.style.display = 'flex';
}

function closeAppModal() {
    document.getElementById('appModal').style.display = 'none';
}