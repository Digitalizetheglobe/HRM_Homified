@php
    $employeeStatus = request('employee_status') === 'inactive' ? 'inactive' : 'active';
@endphp
<div class="emp-status-switch-wrap">
    <span class="emp-status-switch-label">{{ __('Employees') }}</span>
    <div class="emp-status-switch" role="group" aria-label="{{ __('Employee status') }}">
        <input type="radio" name="employee_status" id="employee_status_active" value="active" {{ $employeeStatus === 'active' ? 'checked' : '' }}>
        <label for="employee_status_active" class="active-opt">{{ __('Active') }}</label>
        <input type="radio" name="employee_status" id="employee_status_inactive" value="inactive" {{ $employeeStatus === 'inactive' ? 'checked' : '' }}>
        <label for="employee_status_inactive" class="inactive-opt">{{ __('Inactive') }}</label>
    </div>
</div>
<style>
    .emp-status-switch-wrap {
        display: flex;
        flex-direction: column;
        gap: 5px;
        min-width: 180px;
    }
    .emp-status-switch-label {
        font-size: 12px;
        font-weight: 600;
        color: #7a8499;
        letter-spacing: .4px;
        text-transform: uppercase;
        margin: 0;
    }
    .emp-status-switch {
        display: inline-flex;
        align-items: center;
        padding: 3px;
        border-radius: 999px;
        background: #eef1f6;
        width: fit-content;
    }
    .emp-status-switch input {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }
    .emp-status-switch label {
        margin: 0;
        padding: 6px 16px;
        border-radius: 999px;
        font-size: 13px;
        font-weight: 600;
        line-height: 1.2;
        cursor: pointer;
        color: #64748b;
        text-transform: none;
        letter-spacing: 0;
    }
    .emp-status-switch input:checked + label.active-opt {
        background: #16a34a;
        color: #fff;
    }
    .emp-status-switch input:checked + label.inactive-opt {
        background: #64748b;
        color: #fff;
    }
</style>
<script>
    if (!window.__employeeStatusToggleBound) {
        window.__employeeStatusToggleBound = true;
        document.addEventListener('change', function (event) {
            if (!event.target || event.target.name !== 'employee_status') {
                return;
            }
            var form = event.target.closest('form');
            if (!form) {
                return;
            }
            ['employee', 'employee_id'].forEach(function (name) {
                var field = form.querySelector('[name="' + name + '"]');
                if (field) {
                    field.value = '';
                }
            });
            if (typeof form.requestSubmit === 'function') {
                form.requestSubmit();
            } else {
                form.submit();
            }
        });
    }
</script>
