

<?php $__env->startSection('page-title'); ?>
    <?php echo e(__('Role & Permissions Assignment')); ?>

<?php $__env->stopSection(); ?>

<?php $__env->startSection('breadcrumb'); ?>
    <li class="breadcrumb-item"><a href="<?php echo e(route('dashboard')); ?>"><?php echo e(__('Home')); ?></a></li>
    <li class="breadcrumb-item"><?php echo e(__('Employee Permissions')); ?></li>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="row" x-data="permissionManager()">
    <div class="col-12 mb-4">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <form id="permissionForm" method="POST" action="<?php echo e(route('employee-permissions.sync')); ?>">
                    <?php echo csrf_field(); ?>
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <label class="form-label text-muted fw-bold"><?php echo e(__('Select Employee to Configure')); ?></label>
                            <select name="user_id" class="form-control" x-model="selectedEmployee" @change="fetchPermissions()" x-init="new Choices($el, { searchEnabled: true, removeItemButton: true, itemSelectText: '' })">
                                <option value=""><?php echo e(__('Select Employee')); ?></option>
                                <?php $__currentLoopData = $employees; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $employee): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($employee->id); ?>" <?php echo e(session('selected_user_id') == $employee->id ? 'selected' : ''); ?>><?php echo e($employee->name); ?> (<?php echo e($employee->type); ?>)</option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>
                        <div class="col-md-6 text-end mt-4">
                            <button type="submit" class="btn btn-primary px-4 shadow-sm" :disabled="!selectedEmployee">
                                <i class="ti ti-device-floppy me-2"></i><?php echo e(__('Save Permissions')); ?>

                            </button>
                        </div>
                    </div>

                    <!-- Hidden inputs for selected permissions -->
                    <template x-for="(hasPerm, permName) in activePermissions" :key="permName">
                        <input type="hidden" name="permissions[]" :value="permName" x-if="hasPerm">
                    </template>
                </form>
            </div>
        </div>
    </div>

    <!-- Main Configuration Area -->
    <div class="col-12" x-show="selectedEmployee" style="display: none;">
        <div class="row">
            <!-- Left Column: Module Sidebar -->
            <div class="col-md-3">
                <div class="card shadow-sm border-0" style="min-height: 600px; position: sticky; top: 100px; z-index: 10;">
                    <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                        <h6 class="text-uppercase text-muted fw-bold mb-0" style="font-size: 12px; letter-spacing: 1px;"><?php echo e(__('Modules')); ?></h6>
                    </div>
                    <div class="card-body p-3">
                        <div class="list-group list-group-flush rounded-3">
                            <?php $__currentLoopData = $menuPermissions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $moduleName => $features): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <a href="#" data-module-name="<?php echo e($moduleName); ?>"
                                   class="list-group-item list-group-item-action border-0 mb-1 rounded d-flex justify-content-between align-items-center transition-all"
                                   :class="{ 'bg-primary text-white shadow-sm': activeModule === '<?php echo e($moduleName); ?>', 'text-dark bg-light': activeModule !== '<?php echo e($moduleName); ?>' }"
                                   @click.prevent="setActiveModule('<?php echo e($moduleName); ?>', <?php echo e(json_encode($features)); ?>)">
                                    <span class="fw-medium">
                                        <i class="ti ti-box me-2" :class="{ 'text-white': activeModule === '<?php echo e($moduleName); ?>', 'text-primary': activeModule !== '<?php echo e($moduleName); ?>' }"></i> 
                                        <?php echo e($moduleName); ?>

                                    </span>
                                    <i class="ti ti-chevron-right" style="font-size: 14px;"></i>
                                </a>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Features and Scopes -->
            <div class="col-md-9">
                <div class="card shadow-sm border-0" style="min-height: 600px;">
                    <div class="card-header bg-white border-bottom pt-4 pb-3">
                        <h5 class="mb-0 text-primary fw-bold" x-text="activeModuleName ? activeModuleName + ' Configuration' : '<?php echo e(__('Select a module from the left')); ?>'"></h5>
                        <p class="text-muted small mb-0 mt-1" x-show="activeModuleName"><?php echo e(__('Configure Own vs Employees permissions for each feature.')); ?></p>
                    </div>
                    
                    <div class="card-body p-4 bg-light">
                        <div x-show="!activeModuleName" class="text-center text-muted mt-5">
                            <div class="mb-3">
                                <i class="ti ti-settings text-primary opacity-50" style="font-size: 60px;"></i>
                            </div>
                            <h5 class="fw-normal"><?php echo e(__('No Module Selected')); ?></h5>
                            <p class="mt-2"><?php echo e(__('Please select a module from the sidebar to configure its permissions.')); ?></p>
                        </div>

                        <div x-show="activeModuleName">
                            <template x-for="(scopes, featureName) in activeModuleData" :key="featureName">
                                <div class="mb-5">
                                    <h6 class="text-uppercase text-muted fw-bold mb-3 d-flex align-items-center" style="font-size: 13px; letter-spacing: 0.5px;">
                                        <i class="ti ti-layout-grid me-2 text-primary"></i> <span x-text="featureName"></span>
                                    </h6>
                                    
                                    <div class="row g-4">
                                        <template x-for="(config, scopeName) in scopes" :key="scopeName">
                                            <div class="col-md-6">
                                                <div class="card border border-light-subtle shadow-none h-100 transition-all hover-shadow">
                                                    <div class="card-header border-bottom py-3 d-flex justify-content-between align-items-center" 
                                                         :class="{ 'bg-primary-subtle': scopeName.includes('Own'), 'bg-info-subtle': scopeName.includes('Employees') || scopeName.includes('All') }">
                                                        <span class="fw-bold text-dark" x-text="scopeName"></span>
                                                        <span class="badge" 
                                                              :class="{ 'bg-primary': scopeName.includes('Own'), 'bg-info': scopeName.includes('Employees') || scopeName.includes('All') }" 
                                                              x-text="scopeName.includes('Own') ? 'Self Service' : 'Management'"></span>
                                                    </div>
                                                    <div class="card-body p-0">
                                                        <ul class="list-group list-group-flush">
                                                            <template x-for="(permString, actionName) in config.actions" :key="actionName">
                                                                <li class="list-group-item d-flex justify-content-between align-items-center py-3 border-light">
                                                                    <div class="d-flex align-items-center">
                                                                        <i class="ti ti-point text-muted me-2"></i>
                                                                        <span class="fw-medium text-secondary" x-text="actionName" :class="{ 'text-dark': isActionEnabled(permString) }"></span>
                                                                    </div>
                                                                    <div class="form-check form-switch m-0 p-0">
                                                                        <input class="form-check-input ms-0 mt-0 float-end cursor-pointer" type="checkbox" 
                                                                               style="width: 2.5em; height: 1.25em;"
                                                                               :checked="isActionEnabled(permString)"
                                                                               @change="toggleAction(permString, $event.target.checked)">
                                                                    </div>
                                                                </li>
                                                            </template>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .transition-all { transition: all 0.2s ease-in-out; }
    .hover-shadow:hover { box-shadow: 0 .5rem 1rem rgba(0,0,0,.08)!important; transform: translateY(-2px); }
    .bg-primary-subtle { background-color: rgba(13, 110, 253, 0.05)!important; }
    .bg-info-subtle { background-color: rgba(13, 202, 240, 0.05)!important; }
    .cursor-pointer { cursor: pointer; }
</style>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('script-page'); ?>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('permissionManager', () => ({
            selectedEmployee: '<?php echo e(session('selected_user_id', '')); ?>',
            activeModule: '',
            activeModuleName: '',
            activeModuleData: {},
            
            // This holds the exact permissions the user currently has checked
            activePermissions: {},

            init() {
                if (this.selectedEmployee) {
                    this.fetchPermissions();
                }
                
                let savedModule = sessionStorage.getItem('activePermissionsModule');
                if (savedModule) {
                    setTimeout(() => {
                        let link = document.querySelector(`a[data-module-name="${savedModule}"]`);
                        if (link) {
                            link.click();
                        }
                    }, 100);
                }
            },

            setActiveModule(moduleName, data) {
                this.activeModule = moduleName;
                this.activeModuleName = moduleName;
                this.activeModuleData = data;
                sessionStorage.setItem('activePermissionsModule', moduleName);
            },

            fetchPermissions() {
                if (!this.selectedEmployee) {
                    this.activePermissions = {};
                    return;
                }
                
                // Fetch direct permissions via AJAX
                fetch('<?php echo e(route('employee-permissions.fetch')); ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '<?php echo e(csrf_token()); ?>'
                    },
                    body: JSON.stringify({ user_id: this.selectedEmployee })
                })
                .then(res => res.json())
                .then(data => {
                    let perms = {};
                    if(data.permissions) {
                        data.permissions.forEach(p => perms[p] = true);
                    }
                    this.activePermissions = perms;
                });
            },

            // Check if the permission string is enabled
            isActionEnabled(permString) {
                return !!this.activePermissions[permString];
            },

            // Toggle an action ON or OFF
            toggleAction(permString, isEnabled) {
                if (isEnabled) {
                    this.activePermissions[permString] = true;
                } else {
                    delete this.activePermissions[permString];
                }
            }
        }));
    });
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\hrm_realestate\resources\views/employee-permissions/index.blade.php ENDPATH**/ ?>