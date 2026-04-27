document.addEventListener('DOMContentLoaded', function() {
    // 1. تعريف عناصر الماركة والموديل
    const brandWrapper = document.getElementById('brand-wrapper');
    const brandOptionsContainer = document.getElementById('brand-options');
    const brandInput = document.getElementById('brand-input');
    const brandTrigger = brandWrapper ? brandWrapper.querySelector('.custom-select-trigger span') : null;

    const modelWrapper = document.getElementById('model-wrapper');
    const modelOptionsContainer = document.getElementById('model-options');
    const modelInput = document.getElementById('model-input');
    const modelTrigger = modelWrapper ? modelWrapper.querySelector('.custom-select-trigger span') : null;

    // 2. دالة جلب الماركات (Brands)
    async function fetchBrands() {
        if (!brandTrigger) return;
        brandTrigger.textContent = "Loading Brands...";
        try {
            const response = await fetch('https://vpic.nhtsa.dot.gov/api/vehicles/GetMakesForVehicleType/car?format=json');
            const data = await response.json();
            const brands = data.Results.slice(0, 50); 
            
            brandOptionsContainer.innerHTML = ''; 
            
            brands.forEach(brand => {
                const span = document.createElement('span');
                span.classList.add('custom-option');
                span.setAttribute('data-value', brand.MakeName.toLowerCase());
                span.textContent = brand.MakeName;
                
                span.addEventListener('click', function() {
                    brandTrigger.textContent = this.textContent;
                    brandInput.value = this.getAttribute('data-value');
                    brandWrapper.classList.remove('open');
                    
                    fetchModels(brandInput.value);
                });
                
                brandOptionsContainer.appendChild(span);
            });
            brandTrigger.textContent = "Select Brand";
        } catch (error) {
            brandTrigger.textContent = "Error loading";
        }
    }

    // 3. دالة جلب الموديلات (Models)
    async function fetchModels(brandName) {
        if (!modelTrigger) return;
        modelTrigger.textContent = "Loading Models...";
        modelOptionsContainer.innerHTML = ''; 
        modelInput.value = ''; 
        
        try {
            const response = await fetch(`https://vpic.nhtsa.dot.gov/api/vehicles/GetModelsForMake/${brandName}?format=json`);
            const data = await response.json();
            const models = data.Results;
            
            models.forEach(model => {
                const span = document.createElement('span');
                span.classList.add('custom-option');
                span.setAttribute('data-value', model.Model_Name.toLowerCase());
                span.textContent = model.Model_Name;
                
                span.addEventListener('click', function() {
                    modelTrigger.textContent = this.textContent;
                    modelInput.value = this.getAttribute('data-value');
                    modelWrapper.classList.remove('open');
                });
                
                modelOptionsContainer.appendChild(span);
            });
            modelTrigger.textContent = "Select Model";
        } catch (error) {
            modelTrigger.textContent = "Error loading";
        }
    }

    // 4. السحر هنا: كود بيشغل أي قائمة حتى السنين من غير ما نغير الـ HTML
    document.querySelectorAll('.custom-select-wrapper').forEach(wrapper => {
        const trigger = wrapper.querySelector('.custom-select-trigger');
        
        // عشان يفتح ويقفل
        trigger.addEventListener('click', function() {
            document.querySelectorAll('.custom-select-wrapper').forEach(w => {
                if (w !== wrapper) w.classList.remove('open');
            });
            wrapper.classList.toggle('open');
        });

        // عشان يشغل اختيارات السنين الثابتة
        const staticOptions = wrapper.querySelectorAll('.custom-option');
        const hiddenInput = wrapper.parentElement.querySelector('input[type="hidden"]');
        const triggerSpan = wrapper.querySelector('.custom-select-trigger span');

        staticOptions.forEach(option => {
            option.addEventListener('click', function() {
                triggerSpan.textContent = this.textContent;
                if (hiddenInput) {
                    hiddenInput.value = this.getAttribute('data-value');
                }
                wrapper.classList.remove('open');
            });
        });
    });

    // 5. قفل القوائم لو ضغطت بره
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.custom-select-wrapper')) {
            document.querySelectorAll('.custom-select-wrapper').forEach(w => w.classList.remove('open'));
        }
    });

    // 6. تشغيل الـ API
    fetchBrands();
});