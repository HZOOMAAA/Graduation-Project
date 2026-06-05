document.addEventListener("DOMContentLoaded", function () {
    const wrappers = document.querySelectorAll(".custom-select-wrapper");
    
    wrappers.forEach(wrapper => {
        const trigger = wrapper.querySelector(".custom-select-trigger");
        const options = wrapper.querySelectorAll(".custom-option");
        const hiddenInput = wrapper.closest(".input-group").querySelector("input[type='hidden']");
        
        trigger.addEventListener("click", function (e) {
            e.stopPropagation();
            wrappers.forEach(w => { if(w !== wrapper) w.classList.remove("open"); });
            wrapper.classList.toggle("open");
        });
        
        options.forEach(option => {
            option.addEventListener("click", function () {
                const value = this.getAttribute("data-value");
                const text = this.innerText;
                
                trigger.querySelector("span").innerText = text;
                if (hiddenInput) hiddenInput.value = value;
                wrapper.classList.remove("open");
            });
        });
    });
    
    document.addEventListener("click", function () {
        wrappers.forEach(w => w.classList.remove("open"));
    });

    const form = document.getElementById('propertyInsuranceForm');
    if (!form) return;

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const propertyType     = document.getElementById('property-type-input').value.trim();
        const constructionYear = document.getElementById('construction-year-input').value.trim();
        const propertyValue    = document.getElementById('property_value').value.trim();
        const contentsValue    = document.getElementById('contents_value').value.trim();
        const coverageType     = document.getElementById('coverage-input').value.trim();
        const propertyAddress  = document.getElementById('property_address').value.trim();

        if (!propertyType) { showModal('error', 'Missing Field', 'Please select the property type.'); return; }
        if (!constructionYear) { showModal('error', 'Missing Field', 'Please select the construction year.'); return; }
        
        if (!propertyValue || parseFloat(propertyValue) <= 0) { 
            showModal('error', 'Invalid Value', 'Please enter a valid estimated building value.'); 
            return; 
        }
        if (!contentsValue || parseFloat(contentsValue) < 0) { 
            showModal('error', 'Invalid Value', 'Please enter a valid contents & furniture value.'); 
            return; 
        }
        
        if (!coverageType) { showModal('error', 'Missing Field', 'Please select a required coverage plan.'); return; }
        if (!propertyAddress) { showModal('error', 'Missing Field', 'Please enter the full property address.'); return; }

        const submitBtn = document.getElementById('submitBtn');
        const btnText = document.getElementById('submitBtnText');
        const btnLoader = document.getElementById('submitBtnLoader');
        
        submitBtn.disabled = true;
        if (btnText) btnText.style.display = 'none';
        if (btnLoader) btnLoader.style.display = 'inline-block';
        
        const formData = new FormData(this);
        
        fetch('/Graduation-Project/category-property.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                window.location.href = '/Graduation-Project/plans.php';
            } else if (data.login_required) {
                window.location.href = data.redirect_url;
            } else {
                showModal('error', 'Error Occurred', data.message || 'Failed to analyze property data.');
            }
        })
        .catch(error => {
            showModal('error', 'Connection Error', 'Server is unreachable. Check your internet connection.');
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
    if(type === 'success') {
        iconDiv.innerHTML = "<i class='fa-solid fa-circle-check'></i>";
    } else {
        iconDiv.innerHTML = "<i class='fa-solid fa-circle-xmark'></i>";
    }
    modal.style.display = 'flex';
}

function closeAppModal() {
    document.getElementById('appModal').style.display = 'none';
}