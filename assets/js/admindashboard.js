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


function initGridSearchAndFilter(inputId, tableId, noResultsId, filterSelectId = null, filterClassSelector = null) {
    const searchInput = document.getElementById(inputId);
    const filterSelect = filterSelectId ? document.getElementById(filterSelectId) : null;
    const table = document.getElementById(tableId);
    const noResultsRow = document.getElementById(noResultsId);
    
    if (!table) return;

    function performFilter() {
        const textFilter = searchInput ? searchInput.value.toLowerCase().trim() : "";
        const categoryFilter = filterSelect ? filterSelect.value.toLowerCase().trim() : "";
        let hasMatches = false;
        const rows = table.querySelectorAll("tbody tr");

        rows.forEach(row => {
            if (row.id === noResultsId || row.classList.contains("no-data-row")) return;

            // شرط البحث النصي
            const rowText = row.innerText.toLowerCase();
            const matchesText = rowText.includes(textFilter);

            // شرط فئة التأمين
            let matchesCategory = true;
            if (categoryFilter !== "" && filterClassSelector) {
                const categoryCell = row.querySelector(filterClassSelector);
                const categoryText = categoryCell ? categoryCell.innerText.toLowerCase() : "";
                matchesCategory = categoryText.includes(categoryFilter);
            }

            // السطر يظهر لو الشرطين مع بعض تحققوا
            if (matchesText && matchesCategory) {
                row.style.display = "";
                hasMatches = true;
            } else {
                row.style.display = "none";
            }
        });

        if (noResultsRow) {
            const isFiltering = textFilter !== "" || categoryFilter !== "";
            noResultsRow.style.display = (isFiltering && !hasMatches) ? "" : "none";
        }
    }

    if (searchInput) searchInput.addEventListener("input", performFilter);
    if (filterSelect) filterSelect.addEventListener("change", performFilter);
}







// ── 🚀 تشغيل الربط الفعلي ──
document.addEventListener("DOMContentLoaded", function () {
    
    // أولاً: جدول العملاء (بحث نصي كامل)
    initGridSearch("customerSearchInput", "customersTable", "customerNoResultsRow");

    // ثانياً: جدول الوكلاء (بحث نصي كامل)
    initGridSearch("agentSearchInput", "agentTable", "customerNoResultsRow");

    // ثالثاً: جدول الخطط (البحث النصي + فلتر قائمة الفئات لايف)
    initGridSearchAndFilter(
        "planSearchInput",           // ID حقل البحث
        "plansTable",                // ID الجدول
        "planNoResultsRow",          // ID سطر لا توجد نتائج
        "planCategoryFilterInput",   // ID الـ Select بتاع الفئات
        ".search-plan-category"      // كلاس الـ <td> بتاعة الفئة جوه الجدول
    );

    initGridSearch("messageSearchInput", "messagesTable", "messageNoResultsRow");


//test charts
// ── 📊 تشغيل منظومة التقارير والرسومات البيانية (ApexCharts) ──

// 1) إعدادات الرسم البياني للأرباح الشهرية (Line/Area Chart)
const revenueChartOptions = {
    series: [{
        name: 'Total Premium (Revenue)',
        data: [31000, 40000, 28000, 51000, 42000, 65000, 58000, 72000, 60000, 85000, 78000, 95000] // الداتا الوهمية للشهور
    }],
    chart: {
        type: 'area',
        height: 250,
        toolbar: { show: false }, // إخفاء زراير التحكم المزعجة لشكل أنظف
        fontFamily: 'inherit'
    },
    colors: ['#3B82F6'], // الأزرق الصريح بتاع اللوحة
    dataLabels: { enabled: false },
    stroke: { curve: 'smooth', width: 3 },
    fill: {
        type: 'gradient',
        gradient: {
            shadeIntensity: 1,
            opacityFrom: 0.4,
            opacityTo: 0.05,
            stops: [0, 90, 100]
        }
    },
    grid: {
        borderColor: '#E5E7EB',
        strokeDashArray: 4
    },
    xaxis: {
        categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
        labels: { style: { colors: '#64748B', fontSize: '12px' } }
    },
    yaxis: {
        labels: { style: { colors: '#64748B', fontSize: '12px' } }
    }
};

const revenueChart = new ApexCharts(document.getElementById("revenueChart"), revenueChartOptions);
if (document.getElementById("revenueChart")) {
    revenueChart.render();
}


// 2) إعدادات رسمة نسبة الخسائر والتعويضات (Donut Chart)
const lossRatioOptions = {
    series: [70, 20, 10], // النسب المئوية (70% مكاسب وأقساط، 20% تعويضات مدفوعة، 10% مصاريف إدارية)
    chart: {
        type: 'donut',
        height: 230,
        fontFamily: 'inherit'
    },
    labels: ['Collected Premiums', 'Paid Claims (Losses)', 'Operational Costs'],
    colors: ['#3B82F6', '#EF4444', '#F59E0B'], // أزرق، أحمر، برتقالي متناسقين
    legend: {
        position: 'bottom',
        fontSize: '12px',
        labels: { colors: '#374151' }
    },
    dataLabels: { enabled: true },
    plotOptions: {
        pie: {
            donut: {
                size: '70%',
                labels: {
                    show: true,
                    total: {
                        show: true,
                        label: 'Net Profit',
                        formatter: function (w) {
                            return '50%' // صافي ربح شركة التأمين بناءً على الحسبة
                        },
                        style: { fontSize: '14px', fontWeight: '600', color: '#64748B' }
                    }
                }
            }
        }
    }
};

const lossRatioChart = new ApexCharts(document.getElementById("lossRatioChart"), lossRatioOptions);
if (document.getElementById("lossResultsChart") || document.getElementById("lossRatioChart")) {
    lossRatioChart.render();
}
    
});


//popup section//

/**
 /**
 * Messages Table Popup Handler
 */
document.addEventListener('DOMContentLoaded', function() {
    const table = document.getElementById('messagesTable');
    const modal = document.getElementById('messageModal');
    const closeBtn = document.getElementById('closeModalBtn');

    // التأكد من إن الجدول والـ Modal موجودين في الصفحة قبل التشغيل
    if (!table || !modal) return;

    // الاستماع للضغط على أي سطر داخل الـ tbody
    table.querySelector('tbody').addEventListener('click', function(e) {
        // تحديد السطر المضغوط عليه
        const row = e.target.closest('tr');
        
        // تجاهل الضغط لو كان سطر الفلتر أو سطر "لا يوجد بيانات"
        if (!row || row.classList.contains('no-data-row') || row.id === 'messageNoResultsRow') return;

        // ── 📌 إعادة ضبط الـ Indexes بناءً على الـ 4 أعمدة الحقيقية بالجدول ──
        const sender  = row.cells[0].innerText.trim();
        const email   = row.cells[1].innerText.trim();
        const message = row.cells[2].innerText.trim(); // الرسالة هي العمود الثالث (Index 2)
        const date    = row.cells[3].innerText.trim(); // التاريخ هو العمود الرابع (Index 3)

        // حقن البيانات داخل الـ Popup
        document.getElementById('modalSender').innerText = sender;
        document.getElementById('modalEmail').innerText = email;
        document.getElementById('modalMessageText').innerText = message;
        document.getElementById('modalDate').innerText = date;
        
        // تكة صايعة للـ UI: بما إن مفيش Subject، هنحط عنوان ثابت أو نكتب Message Details
        const modalSubjectElement = document.getElementById('modalSubject');
        if (modalSubjectElement) {
            modalSubjectElement.innerText = "Inquiry from " + sender;
        }

        // إظهار الـ Popup بإضافة كلاس الـ show
        modal.classList.add('show');
    });

    // قفل الـ Popup عند الضغط على زر الإغلاق (X)
    if (closeBtn) {
        closeBtn.addEventListener('click', function() {
            modal.classList.remove('show');
        });
    }

    // قفل الـ Popup عند الضغط في أي مكان خارج الصندوق الأبيض
    window.addEventListener('click', function(e) {
        if (e.target === modal) {
            modal.classList.remove('show');
        }
    });
});