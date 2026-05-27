function initGridSearch(inputId, tableId, noResultsId, searchClassSelectors = []) {
    const searchInput = document.getElementById(inputId);
    const table = document.getElementById(tableId);
    const noResultsRow = document.getElementById(noResultsId);

    if (!searchInput || !table) return; // حماية لو التاب مش مفتوح

    searchInput.addEventListener("input", function () {
        const filter = searchInput.value.toLowerCase().trim();
        let hasMatches = false;

        // لقط السطور الحقيقية اللي جوه الـ tbody فقط عشان نبعد عن الـ thead تماماً
        const rows = table.querySelectorAll("tbody tr");

        rows.forEach(row => {
            // تجنب فحص سطر الـ No Results نفسه عشان ميتأثرش بالفلترة
            if (row.id === noResultsId || row.classList.contains("no-data-row")) {
                return;
            }

            let matchFound = false;

            // لو الأدمن حدد كلاسات معينة (زي الاسم والإيميل)
            if (searchClassSelectors.length > 0) {
                matchFound = searchClassSelectors.some(selector => {
                    const cell = row.querySelector(selector);
                    const cellText = cell ? cell.innerText.toLowerCase() : "";
                    return cellText.includes(filter);
                });
            } else {
                // لو محددش، يدور في السطر كله (كل الـ td) أوتوماتيك
                matchFound = row.innerText.toLowerCase().includes(filter);
            }

            // تنفيذ الإخفاء والإظهار
            if (matchFound) {
                row.style.display = "";
                hasMatches = true;
            } else {
                row.style.display = "none";
            }
        });

        // إظهار أو إخفاء رسالة "No matching found" (تعديل الـ style.style الدبل)
        if (noResultsRow) {
            noResultsRow.style.display = (filter !== "" && !hasMatches) ? "" : "none";
        }
    });
}

// ── 🚀 تشغيل الربط الفعلي ──
document.addEventListener("DOMContentLoaded", function () {
    // جرب تشغلها كدة (بدون تحديد كلاسات) عشان يدور في السطر كله أوتوماتيك ويضمن اللقطة
    initGridSearch("customerSearchInput", "customersTable", "customerNoResultsRow");
});
document.addEventListener("DOMContentLoaded", function () {
    // جرب تشغلها كدة (بدون تحديد كلاسات) عشان يدور في السطر كله أوتوماتيك ويضمن اللقطة
    initGridSearch("agentSearchInput", "agentTable", "customerNoResultsRow");
});