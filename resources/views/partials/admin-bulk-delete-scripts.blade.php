@once

@push('scripts')

<script>

window.adminBulkToolbar = function (formId, itemLabel, opts) {

    opts = opts || {};

    var requirePassword = !!opts.requirePassword;

    var verifyUrl = opts.verifyUrl || '';



    return {

        confirmOpen: false,

        itemLabel: itemLabel,

        selectedCount: 0,

        requirePassword: requirePassword,

        verifyUrl: verifyUrl,

        pw: '',

        archiveOk: false,

        verifying: false,

        verifySeq: 0,

        syncCount: function () {

            var form = document.getElementById(formId);

            this.selectedCount = form ? form.querySelectorAll('tbody input.bulk-cb:checked').length : 0;

        },

        toggleAll: function (checked) {

            var form = document.getElementById(formId);

            if (!form) {

                return;

            }

            form.querySelectorAll('tbody input.bulk-cb').forEach(function (cb) {

                cb.checked = checked;

            });

            this.syncCount();

        },

        resetPasswordState: function () {

            this.pw = '';

            this.archiveOk = false;

            this.verifying = false;

            this.verifySeq++;

        },

        openConfirm: function () {

            this.syncCount();

            if (this.selectedCount === 0) {

                return;

            }

            if (this.requirePassword) {

                this.resetPasswordState();

            }

            this.confirmOpen = true;

        },

        closeConfirm: function () {

            this.confirmOpen = false;

            if (this.requirePassword) {

                this.resetPasswordState();

            }

        },

        verify: async function () {

            if (!this.requirePassword || !this.verifyUrl) {

                return;

            }

            if (!this.pw) {

                this.verifySeq++;

                this.archiveOk = false;

                this.verifying = false;

                return;

            }

            var seq = ++this.verifySeq;

            this.verifying = true;

            try {

                var token = document.querySelector('meta[name=csrf-token]')?.getAttribute('content') ?? '';

                var res = await fetch(this.verifyUrl, {

                    method: 'POST',

                    headers: {

                        'Content-Type': 'application/json',

                        Accept: 'application/json',

                        'X-CSRF-TOKEN': token,

                        'X-Requested-With': 'XMLHttpRequest',

                    },

                    body: JSON.stringify({ password: this.pw }),

                });

                var data = {};

                try {

                    data = await res.json();

                } catch (e) {

                    data = {};

                }

                if (seq !== this.verifySeq) {

                    return;

                }

                this.archiveOk = res.ok && data.valid === true;

            } catch (e) {

                if (seq === this.verifySeq) {

                    this.archiveOk = false;

                }

            } finally {

                if (seq === this.verifySeq) {

                    this.verifying = false;

                }

            }

        },

        confirmSubmit: function () {

            if (this.requirePassword && !this.archiveOk) {

                return;

            }

            this.confirmOpen = false;

            var form = document.getElementById(formId);

            if (!form) {

                return;

            }

            if (this.requirePassword) {

                var hidden = form.querySelector('input[data-bulk-archive-password]');

                if (!hidden) {

                    hidden = document.createElement('input');

                    hidden.type = 'hidden';

                    hidden.name = 'current_password';

                    hidden.setAttribute('data-bulk-archive-password', '1');

                    form.appendChild(hidden);

                }

                hidden.value = this.pw;

                this.resetPasswordState();

            }

            form.submit();

        },

    };

};

</script>

@endpush

@endonce

