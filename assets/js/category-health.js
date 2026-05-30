// ── Dynamic spouse & children fields ──────────────────────────────────────────

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


// ── AJAX Form Submission ──────────────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('healthInsuranceForm');
    if (!form) return;

    form.addEventListener('submit', async function (e) {
        e.preventDefault();

        // ── Validate required fields ────────────────────────────────────────
        const birthDay   = form.querySelector('input[name="birth_day"]').value.trim();
        const birthMonth = form.querySelector('input[name="birth_month"]').value.trim();
        const birthYear  = form.querySelector('input[name="birth_year"]').value.trim();
        const chronic    = form.querySelector('input[name="family_chronic"]:checked');

        if (!birthDay || !birthMonth || !birthYear) {
            showModal('error', 'Missing Field', 'Please enter your full birthdate.');
            return;
        }

        if (parseInt(birthDay) < 1 || parseInt(birthDay) > 31) {
            showModal('error', 'Invalid Date', 'Birth day must be between 1 and 31.');
            return;
        }
        if (parseInt(birthMonth) < 1 || parseInt(birthMonth) > 12) {
            showModal('error', 'Invalid Date', 'Birth month must be between 1 and 12.');
            return;
        }
        if (parseInt(birthYear) < 1920 || parseInt(birthYear) > new Date().getFullYear()) {
            showModal('error', 'Invalid Date', 'Please enter a valid birth year.');
            return;
        }

        if (!chronic) {
            showModal('error', 'Missing Field', 'Please select chronic disease status.');
            return;
        }

        // ── Validate spouse fields if added ─────────────────────────────────
        const spouseCard = document.getElementById('spouse-card-node');
        if (spouseCard) {
            const sd = spouseCard.querySelector('input[name="spouse_day"]').value.trim();
            const sm = spouseCard.querySelector('input[name="spouse_month"]').value.trim();
            const sy = spouseCard.querySelector('input[name="spouse_year"]').value.trim();
            if (!sd || !sm || !sy) {
                showModal('error', 'Missing Field', 'Please complete the spouse birthdate or remove the spouse.');
                return;
            }
        }

        // ── Redirect directly to plans page ────────────────────────────────
        window.location.href = '/Graduation-Project/plans.php';
    });
});

// ── Modal Helpers ─────────────────────────────────────────────────────────────
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