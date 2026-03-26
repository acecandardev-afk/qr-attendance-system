@once
@push('scripts')
<script>
window.adminBulkToolbar = function (formId, itemLabel) {
    return {
        confirmOpen: false,
        itemLabel: itemLabel,
        selectedCount: 0,
        syncCount() {
            const form = document.getElementById(formId);
            this.selectedCount = form ? form.querySelectorAll('tbody input.bulk-cb:checked').length : 0;
        },
        toggleAll(checked) {
            const form = document.getElementById(formId);
            if (!form) {
                return;
            }
            form.querySelectorAll('tbody input.bulk-cb').forEach(function (cb) {
                cb.checked = checked;
            });
            this.syncCount();
        },
        openConfirm() {
            this.syncCount();
            if (this.selectedCount === 0) {
                return;
            }
            this.confirmOpen = true;
        },
        confirmSubmit() {
            this.confirmOpen = false;
            const form = document.getElementById(formId);
            if (form) {
                form.submit();
            }
        },
    };
};
</script>
@endpush
@endonce
