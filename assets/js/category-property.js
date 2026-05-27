document.addEventListener("DOMContentLoaded", function () {
    // تشغيل الـ Custom Select لكل القوائم في الصفحة بنعومة
    const wrappers = document.querySelectorAll(".custom-select-wrapper");
    
    wrappers.forEach(wrapper => {
        const trigger = wrapper.querySelector(".custom-select-trigger");
        const options = wrapper.querySelectorAll(".custom-option");
        const hiddenInput = wrapper.parentElement.querySelector("input[type='hidden']");
        
        trigger.addEventListener("click", function (e) {
            e.stopPropagation();
            // قفل أي قائمة تانية مفتوحة
            wrappers.forEach(w => { if(w !== wrapper) w.classList.remove("open"); });
            wrapper.classList.toggle("open");
        });
        
        options.forEach(option => {
            option.addEventListener("click", function () {
                const value = this.getAttribute("data-value");
                const text = this.innerText;
                
                trigger.querySelector("span").innerText = text;
                hiddenInput.value = value;
                wrapper.classList.remove("open");
            });
        });
    });
    
    // غلق القوائم عند الضغط في أي مكان خارجها
    document.addEventListener("click", function () {
        wrappers.forEach(w => w.classList.remove("open"));
    });
});

// دالت التحكم في الـ Modal المنبثق
function showAppModal(type, title, message) {
    const modal = document.getElementById('appModal');
    const iconDiv = document.getElementById('appModalIcon');
    
    document.getElementById('appModalTitle').innerText = title;
    document.getElementById('appModalMsg').innerText = message;
    
    iconDiv.className = 'app-modal-icon';
    if(type === 'success') {
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

// معالجة إرسال فورم الممتلكات بالـ AJAX
document.getElementById('propertyInsuranceForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const submitBtn = document.getElementById('submitBtn');
    const btnText = document.getElementById('submitBtnText');
    const btnLoader = document.getElementById('submitBtnLoader');
    
    submitBtn.disabled = true;
    btnText.style.display = 'none';
    btnLoader.style.display = 'inline-block';
    
    const formData = new FormData(this);
    
    fetch('submit_property_application.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        submitBtn.disabled = false;
        btnText.style.display = 'inline-block';
        btnLoader.style.display = 'none';
        
        if(data.status === 'success') {
            showAppModal('success', 'Application Received!', data.message || 'Property valuation submitted successfully.');
            this.reset();
            // ريسيت للتكست الافتراضي في الـ Custom Selects
            document.querySelectorAll(".custom-select-trigger span").forEach((span, index) => {
                if(index === 0) span.innerText = "Select Type";
                if(index === 1) span.innerText = "Select Year";
                if(index === 2) span.innerText = "Select Coverage Plan";
            });
        } else {
            showAppModal('error', 'Error Occurred', data.message || 'Failed to analyze property data.');
        }
    })
    .catch(error => {
        submitBtn.disabled = false;
        btnText.style.display = 'inline-block';
        btnLoader.style.display = 'none';
        showAppModal('error', 'Connection Error', 'Server is unreachable. Check your database connection.');
    });
});