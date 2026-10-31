import DataTable from 'datatables.net-dt';

const table = document.querySelector('#stores-table');
if (table) {
    const form = document.querySelector('#store-filters');
    const search = document.querySelector('#store-search');
    const status = document.querySelector('#store-status');
    const feedback = document.querySelector('#table-feedback');
    DataTable.ext.errMode = 'none';
    const dt = new DataTable(table, {
        serverSide: true,
        processing: true,
        pageLength: 10,
        lengthMenu: [10, 25, 50],
        searchDelay: 700,
        orderMulti: false,
        order: [[2, 'desc']],
        search: { search: search.value, regex: false },
        layout: { topStart: 'pageLength', topEnd: null, bottomStart: 'info', bottomEnd: 'paging' },
        ajax: { url: table.dataset.url, data: data => { data.status = status.value; } },
        columns: [
            { data: 'name', name: 'name', render: DataTable.render.text() },
            { data: 'status_label', name: 'status', render: DataTable.render.text() },
            { data: 'created_at', name: 'created_at', render: DataTable.render.text() },
            { data: 'details_url', searchable: false, orderable: false, render: () => '', createdCell: (cell, value) => {
                const link = document.createElement('a');
                const url = new URL(value, window.location.origin);
                if (url.origin === window.location.origin) link.href = url.href;
                link.className = 'text-link';
                link.textContent = 'فتح المتجر ←';
                cell.append(link);
            } },
        ],
        language: {
            emptyTable: 'لا توجد متاجر ضمن هذه التصفية.',
            zeroRecords: 'لا توجد نتائج مطابقة.',
            processing: 'جارٍ تحميل المتاجر…',
            loadingRecords: 'جارٍ التحميل…',
            lengthMenu: 'عرض _MENU_ متجر',
            info: '_START_–_END_ من _TOTAL_ متجر',
            infoEmpty: 'لا توجد نتائج',
            infoFiltered: '(من أصل _MAX_ متجر)',
            paginate: { first: 'الأولى', last: 'الأخيرة', next: 'التالي', previous: 'السابق' },
            aria: { orderable: 'رتّب حسب هذا العمود', orderableReverse: 'اعكس الترتيب' },
        },
    });
    document.querySelector('#fallback-pagination').hidden = true;
    dt.on('xhr', (event, settings, data) => {
        if (data && typeof data.recordsFiltered === 'number') {
            document.querySelector('.count-label').textContent = `${data.recordsFiltered} متجر ضمن التصفية`;
        }
    });
    form.addEventListener('submit', event => {
        event.preventDefault();
        feedback.textContent = '';
        dt.search(search.value).draw();
    });
    dt.on('dt-error', () => {
        feedback.textContent = 'تعذّر تحديث الجدول. تحقق من الاتصال، أو أعد تحميل الصفحة لتسجيل الدخول مجددًا.';
        document.querySelector('#fallback-pagination').hidden = false;
    });
}
