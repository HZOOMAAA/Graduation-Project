// دالة إضافة حقل الزوجة ديناميكياً (مرة واحدة فقط)
function addSpouseField() {
    const container = document.getElementById('spouse-dynamic-area');
    const addSpouseBtn = document.getElementById('addSpouseBtn');

    // كارت حقول الزوجة بالكلاس الجديد
    const spouseCard = document.createElement('div');
    spouseCard.className = 'insurance-member-card';
    spouseCard.id = 'spouse-card-node';

    spouseCard.innerHTML = `
        <button type="button" class="insurance-remove-btn" onclick="removeSpouseField()">
            <i class="fa-solid fa-trash-can"></i> Remove
        </button>
        <h4 style="margin-top:0; margin-bottom:12px; color:#111827; font-weight:600;">Spouse Birthdate</h4>
        <div class="insurance-birthdate-grid">
            <input type="number" name="spouse_day" placeholder="DD" min="1" max="31" required>
            <input type="number" name="spouse_month" placeholder="MM" min="1" max="12" required>
            <input type="number" name="spouse_year" placeholder="YYYY" min="1920" max="2026" required>
        </div>
    `;

    container.appendChild(spouseCard);
    addSpouseBtn.style.display = 'none'; // إخفاء زر الإضافة
}

// حذف حقل الزوجة وإعادة إظهار زر الإضافة
function removeSpouseField() {
    const card = document.getElementById('spouse-card-node');
    if (card) {
        card.remove();
    }
    document.getElementById('addSpouseBtn').style.display = 'flex';
}

let childCounter = 0;

// دالة إضافة طفل ديناميكياً
function addChildField() {
    childCounter++;
    const container = document.getElementById('children-dynamic-area');

    const childCard = document.createElement('div');
    childCard.className = 'insurance-member-card';
    childCard.id = `child-card-${childCounter}`;

    childCard.innerHTML = `
        <button type="button" class="insurance-remove-btn" onclick="removeChildField(${childCounter})">
            <i class="fa-solid fa-trash-can"></i> Remove
        </button>
        <h4 style="margin-top:0; margin-bottom:12px; color:#111827; font-weight:600;">Child ${childCounter} Birthdate</h4>
        <div class="insurance-birthdate-grid">
            <input type="number" name="child_day[]" placeholder="DD" min="1" max="31" required>
            <input type="number" name="child_month[]" placeholder="MM" min="1" max="12" required>
            <input type="number" name="child_year[]" placeholder="YYYY" min="1920" max="2026" required>
        </div>
    `;

    container.appendChild(childCard);
}

// حذف طفل معين
function removeChildField(id) {
    const card = document.getElementById(`child-card-${id}`);
    if (card) {
        card.remove();
    }
}