

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();


// "Select all" checkboxes for bulk actions: <input type="checkbox" data-select-all="FORM_ID">
document.addEventListener('change', (event) => {
    const master = event.target.closest('[data-select-all]');
    if (!master) return;
    document
        .querySelectorAll('input.bulk-check[form="' + master.dataset.selectAll + '"]')
        .forEach((box) => { box.checked = master.checked; });
});


// Bulk bars: show "N selected" and keep the action buttons off until something is ticked.
function refreshBulkBars() {
    document.querySelectorAll('form.bulk-bar').forEach((form) => {
        const boxes = document.querySelectorAll('input.bulk-check[form="' + form.id + '"]');
        const picked = Array.from(boxes).filter((box) => box.checked).length;
        const counter = form.querySelector('.bulk-count');
        if (counter) counter.textContent = picked > 0 ? picked + ' selected' : '';
        form.querySelectorAll('.bulk-actions button').forEach((button) => { button.disabled = picked === 0; });
        const master = form.querySelector('[data-select-all]');
        if (master) master.checked = boxes.length > 0 && picked === boxes.length;
    });
}
document.addEventListener('change', (event) => {
    if (event.target.closest('.bulk-check') || event.target.closest('[data-select-all]')) refreshBulkBars();
});
document.addEventListener('DOMContentLoaded', refreshBulkBars);

// A live update swapped the page content: recount the bulk bars.
document.addEventListener('live:updated', refreshBulkBars);
