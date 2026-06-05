function initGridSearch(inputId, tableId, noResultsId, searchClassSelectors = []) {
    const searchInput = document.getElementById(inputId);
    const table = document.getElementById(tableId);
    const noResultsRow = document.getElementById(noResultsId);

    if (!searchInput || !table) return;

    searchInput.addEventListener("input", function () {
        const filter = searchInput.value.toLowerCase().trim();
        let hasMatches = false;
        const rows = table.querySelectorAll("tbody tr");

        rows.forEach(row => {
            if (row.id === noResultsId || row.classList.contains("no-data-row")) {
                return;
            }

            let matchFound = false;

            if (searchClassSelectors.length > 0) {
                matchFound = searchClassSelectors.some(selector => {
                    const cell = row.querySelector(selector);
                    const cellText = cell ? cell.innerText.toLowerCase() : "";
                    return cellText.includes(filter);
                });
            } else {
                matchFound = row.innerText.toLowerCase().includes(filter);
            }

            if (matchFound) {
                row.style.display = "";
                hasMatches = true;
            } else {
                row.style.display = "none";
            }
        });

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

            const rowText = row.innerText.toLowerCase();
            const matchesText = rowText.includes(textFilter);

            let matchesCategory = true;
            if (categoryFilter !== "" && filterClassSelector) {
                const categoryCell = row.querySelector(filterClassSelector);
                const categoryText = categoryCell ? categoryCell.innerText.toLowerCase() : "";
                matchesCategory = categoryText.includes(categoryFilter);
            }

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

document.addEventListener("DOMContentLoaded", function () {
    initGridSearch("customerSearchInput", "customersTable", "customerNoResultsRow");
    initGridSearch("agentSearchInput", "agentTable", "customerNoResultsRow");
    initGridSearchAndFilter(
        "planSearchInput",
        "plansTable",
        "planNoResultsRow",
        "planCategoryFilterInput",
        ".search-plan-category"
    );
    initGridSearch("messageSearchInput", "messagesTable", "messageNoResultsRow");

    const progressBars = document.querySelectorAll('.progress-bar-fill[data-width]');
    if (progressBars.length > 0) {
        requestAnimationFrame(function() {
            setTimeout(function() {
                progressBars.forEach(function(bar, index) {
                    setTimeout(function() {
                        bar.style.width = bar.getAttribute('data-width') + '%';
                    }, index * 120);
                });
            }, 200);
        });
    }

    const revenueData = window.AdminDashboardRevenueData || [31000, 40000, 28000, 51000, 42000, 65000, 58000];
    const revenueLabels = window.AdminDashboardRevenueLabels || ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

    const revenueChartOptions = {
        series: [{
            name: 'Total Premium (Revenue)',
            data: revenueData
        }],
        chart: {
            type: 'area',
            height: 250,
            toolbar: { show: false },
            fontFamily: 'inherit'
        },
        colors: ['#3B82F6'],
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
            categories: revenueLabels,
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

    const lossRatioOptions = {
        series: [70, 20, 10],
        chart: {
            type: 'donut',
            height: 230,
            fontFamily: 'inherit'
        },
        labels: ['Collected Premiums', 'Paid Claims (Losses)', 'Operational Costs'],
        colors: ['#3B82F6', '#EF4444', '#F59E0B'],
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
                                return '50%';
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

document.addEventListener('DOMContentLoaded', function() {
    const table = document.getElementById('messagesTable');
    const modal = document.getElementById('messageModal');
    const closeBtn = document.getElementById('closeModalBtn');

    if (!table || !modal) return;

    table.querySelector('tbody').addEventListener('click', function(e) {
        const row = e.target.closest('tr');
        
        if (!row || row.classList.contains('no-data-row') || row.id === 'messageNoResultsRow') return;

        const sender  = row.cells[0].innerText.trim();
        const email   = row.cells[1].innerText.trim();
        const message = row.cells[2].innerText.trim();
        const date    = row.cells[3].innerText.trim();

        document.getElementById('modalSender').innerText = sender;
        document.getElementById('modalEmail').innerText = email;
        document.getElementById('modalMessageText').innerText = message;
        document.getElementById('modalDate').innerText = date;
        
        const modalSubjectElement = document.getElementById('modalSubject');
        if (modalSubjectElement) {
            modalSubjectElement.innerText = "Inquiry from " + sender;
        }

        modal.classList.add('show');
    });

    if (closeBtn) {
        closeBtn.addEventListener('click', function() {
            modal.classList.remove('show');
        });
    }

    window.addEventListener('click', function(e) {
        if (e.target === modal) {
            modal.classList.remove('show');
        }
    });
});