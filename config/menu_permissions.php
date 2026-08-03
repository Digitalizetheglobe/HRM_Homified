<?php

return [
    'Employee' => [
        'Employees' => [
            'Own Employees' => [
                'actions' => [
                    'Page Access' => 'employee.profile.view.own',
                    'Edit'        => 'employee.profile.edit.own',
                ],
                'supports_data_scope' => false,
            ],
            'All Employees' => [
                'actions' => [
                    'Page Access' => 'employee.view.all',
                    'Show'        => 'employee.show.all',
                    'Create'      => 'employee.create.all',
                    'Edit'        => 'employee.edit.all',
                    'Send Mail'   => 'employee.send_mail.all',
                    'Delete'      => 'employee.delete.all',
                    'Export'      => 'employee.export.all',
                ],
                'supports_data_scope' => false,
            ]
        ]
    ],
    'Attendance' => [
        'Attendance Calendar' => [
            'Own Attendance Calendar' => [
                'actions' => [
                    'Page Access' => 'attendance.calendar.view.own',
                ],
                'supports_data_scope' => false,
            ],
            'Employees Attendance Calendar' => [
                'actions' => [
                    'Page Access'              => 'attendance.calendar.view.all',
                    'Update Attendance Status' => 'attendance.calendar.update.all',
                ],
                'supports_data_scope' => false,
            ],
        ],
        'Attendance Regularisation' => [
            'Own Attendance Regularisation' => [
                'actions' => [
                    'Page Access' => 'attendance.regularisation.view.own',
                    'Create'      => 'attendance.regularisation.create.own',
                ],
                'supports_data_scope' => false,
            ],
            'Employees Attendance Regularisation' => [
                'actions' => [
                    'Page Access' => 'attendance.regularisation.view.all',
                    'Create'      => 'attendance.regularisation.create.all',
                    'Edit'        => 'attendance.regularisation.edit.all',
                    'Delete'      => 'attendance.regularisation.delete.all',
                    'Action'      => 'attendance.regularisation.action.all',
                ],
                'supports_data_scope' => false,
            ],
        ],
        'Marked Attendance' => [
            'Own Marked Attendance' => [
                'actions' => [
                    'Page Access' => 'attendance.marked.view.own',
                ],
                'supports_data_scope' => false,
            ],
            'Employees Marked Attendance' => [
                'actions' => [
                    'Page Access' => 'attendance.marked.view.all',
                    'Edit'        => 'attendance.marked.edit.all',
                    'Delete'      => 'attendance.marked.delete.all',
                    'Import'      => 'attendance.marked.import.all',
                    'Export'      => 'attendance.marked.export.all',
                ],
                'supports_data_scope' => false,
            ],
        ],
        'Bulk Attendance' => [
            'Own Bulk Attendance' => [
                'actions' => [
                    'Page Access' => 'attendance.bulk.view.own',
                ],
                'supports_data_scope' => false,
            ],
            'Employees Bulk Attendance' => [
                'actions' => [
                    'Page Access' => 'attendance.bulk.view.all',
                    'Create'      => 'attendance.bulk.create.all',
                ],
                'supports_data_scope' => false,
            ],
        ],
        'Biometric Attendance' => [
            'Own Biometric Attendance' => [
                'actions' => [
                    'Page Access' => 'attendance.biometric.view.own',
                ],
                'supports_data_scope' => false,
            ],
            'Employees Biometric Attendance' => [
                'actions' => [
                    'Page Access' => 'attendance.biometric.view.all',
                    'Sync'        => 'attendance.biometric.sync.all',
                ],
                'supports_data_scope' => false,
            ],
        ],
        'Employee Tracking' => [
            'Employees Tracking' => [
                'actions' => [
                    'Page Access' => 'attendance.employee_tracking.view.all',
                ],
                'supports_data_scope' => false,
            ],
        ],
    ],
    'Leave' => [
        'Manage Leave' => [
            'Own Leave' => [
                'actions' => [
                    'Page Access' => 'leave.manage.view.own',
                    'Create'      => 'leave.manage.create.own',
                    'Edit'        => 'leave.manage.edit.own',
                    'Delete'      => 'leave.manage.delete.own',
                ],
                'supports_data_scope' => false,
            ],
            'Employees Leave' => [
                'actions' => [
                    'Page Access' => 'leave.manage.view.all',
                    'Create'      => 'leave.manage.create.all',
                    'Edit'        => 'leave.manage.edit.all',
                    'Delete'      => 'leave.manage.delete.all',
                    'Export'      => 'leave.manage.export.all',
                    'Action'      => 'leave.manage.action.all',
                ],
                'supports_data_scope' => false,
            ],
        ],
        'Leave Details' => [
            'Own Leave Details' => [
                'actions' => [
                    'Page Access' => 'leave.details.view.own',
                ],
                'supports_data_scope' => false,
            ],
            'Employees Leave Details' => [
                'actions' => [
                    'Page Access' => 'leave.details.view.all',
                    'Action'      => 'leave.details.action.all',
                ],
                'supports_data_scope' => false,
            ],
        ],
        'Carryforward Leaves' => [
            'Own Carryforward Leaves' => [
                'actions' => [
                    'Page Access' => 'leave.carryforward.view.own',
                ],
                'supports_data_scope' => false,
            ],
            'Employees Carryforward Leaves' => [
                'actions' => [
                    'Page Access' => 'leave.carryforward.view.all',
                ],
                'supports_data_scope' => false,
            ],
        ],
        'Comp-Off Leaves' => [
            'Own Comp-Off Leaves' => [
                'actions' => [
                    'Page Access' => 'leave.compoff.view.own',
                ],
                'supports_data_scope' => false,
            ],
            'Employees Comp-Off Leaves' => [
                'actions' => [
                    'Page Access' => 'leave.compoff.view.all',
                ],
                'supports_data_scope' => false,
            ],
        ],
    ],
    'Payroll' => [
        'Set Salary' => [
            'Own Salary' => [
                'actions' => [
                    'Page Access' => 'payroll.salary.view.own',
                ],
                'supports_data_scope' => false,
            ],
            'Employees Salary' => [
                'actions' => [
                    'Page Access' => 'payroll.salary.view.all',
                    'Edit'        => 'payroll.salary.edit.all',
                    'Salary Increment' => 'payroll.salary.increment.all',
                    'Download Increment Letter' => 'payroll.salary.download_increment.all',
                ],
                'supports_data_scope' => false,
            ],
        ],
        'Payslip' => [
            'Own Payslip' => [
                'actions' => [
                    'Page Access' => 'payroll.payslip.view.own',
                    'Export'      => 'payroll.payslip.export.own',
                ],
                'supports_data_scope' => false,
            ],
            'Employees Payslip' => [
                'actions' => [
                    'Page Access' => 'payroll.payslip.view.all',
                    'Generate'    => 'payroll.payslip.generate.all',
                    'Export'      => 'payroll.payslip.export.all',
                    'Send Email'  => 'payroll.payslip.send.all',
                ],
                'supports_data_scope' => false,
            ],
        ],
        'Payable Days' => [
            'Own Payable Days' => [
                'actions' => [
                    'Page Access' => 'payroll.payable_days.view.own',
                ],
                'supports_data_scope' => false,
            ],
            'Employees Payable Days' => [
                'actions' => [
                    'Page Access' => 'payroll.payable_days.view.all',
                ],
                'supports_data_scope' => false,
            ],
        ],
        'Salary Arrears' => [
            'Employees Salary Arrears' => [
                'actions' => [
                    'Page Access' => 'payroll.salary_arrears.view.all',
                    'Create'      => 'payroll.salary_arrears.create.all',
                    'Edit'        => 'payroll.salary_arrears.edit.all',
                    'Delete'      => 'payroll.salary_arrears.delete.all',
                ],
                'supports_data_scope' => false,
            ],
        ],
        'Other Deduction' => [
            'Employees Other Deduction' => [
                'actions' => [
                    'Page Access' => 'payroll.other_deduction.view.all',
                    'Create'      => 'payroll.other_deduction.create.all',
                    'Edit'        => 'payroll.other_deduction.edit.all',
                    'Delete'      => 'payroll.other_deduction.delete.all',
                ],
                'supports_data_scope' => false,
            ],
        ],
        'Petrol Allowance' => [
            'Employees Petrol Allowance' => [
                'actions' => [
                    'Page Access' => 'payroll.petrol_allowance.view.all',
                    'Create'      => 'payroll.petrol_allowance.create.all',
                    'Delete'      => 'payroll.petrol_allowance.delete.all',
                ],
                'supports_data_scope' => false,
            ],
        ],
        'Salary Processing' => [
            'Employees Salary Processing' => [
                'actions' => [
                    'Page Access' => 'payroll.salary_processing.view.all',
                    'Export'      => 'payroll.salary_processing.export.all',
                    'Update Status' => 'payroll.salary_processing.update_status.all',
                ],
                'supports_data_scope' => false,
            ],
        ],
    ],
    'Ticket' => [
        'Manage Ticket' => [
            'Own Ticket' => [
                'actions' => [
                    'Page Access' => 'ticket.manage.view.own',
                    'Create'      => 'ticket.manage.create.own',
                    'Edit'        => 'ticket.manage.edit.own',
                    'Delete'      => 'ticket.manage.delete.own',
                    'Reply'       => 'ticket.manage.reply.own',
                ],
                'supports_data_scope' => false,
            ],
            'Employees Ticket' => [
                'actions' => [
                    'Page Access' => 'ticket.manage.view.all',
                    'Create'      => 'ticket.manage.create.all',
                    'Edit'        => 'ticket.manage.edit.all',
                    'Delete'      => 'ticket.manage.delete.all',
                    'Reply'       => 'ticket.manage.reply.all',
                ],
                'supports_data_scope' => false,
            ],
        ],
    ],
    'Company Policy' => [
        'Manage Company Policy' => [
            'Own Company Policy' => [
                'actions' => [
                    'Page Access' => 'company_policy.manage.view.own',
                ],
                'supports_data_scope' => false,
            ],
            'Employees Company Policy' => [
                'actions' => [
                    'Page Access'    => 'company_policy.manage.view.all',
                    'Create'         => 'company_policy.manage.create.all',
                    'Edit'           => 'company_policy.manage.edit.all',
                    'Delete'         => 'company_policy.manage.delete.all',
                    'Acknowledgements' => 'company_policy.manage.acknowledgements.all',
                ],
                'supports_data_scope' => false,
            ],
        ],
    ],
    'Exit' => [
        'Resignation' => [
            'Own Resignation' => [
                'actions' => [
                    'Page Access' => 'exit.resignation.view.own',
                    'Show'        => 'exit.resignation.show.own',
                    'Create'      => 'exit.resignation.create.own',
                    'Edit'        => 'exit.resignation.edit.own',
                    'Delete'      => 'exit.resignation.delete.own',
                ],
                'supports_data_scope' => false,
            ],
            'Employees Resignation' => [
                'actions' => [
                    'Page Access' => 'exit.resignation.view.all',
                    'Show'        => 'exit.resignation.show.all',
                    'Create'      => 'exit.resignation.create.all',
                    'Edit'        => 'exit.resignation.edit.all',
                    'Delete'      => 'exit.resignation.delete.all',
                    'Approve'     => 'exit.resignation.approve.all',
                ],
                'supports_data_scope' => false,
            ],
        ],
        'Termination' => [
            'Own Termination' => [
                'actions' => [
                    'Page Access' => 'exit.termination.view.own',
                ],
                'supports_data_scope' => false,
            ],
            'Employees Termination' => [
                'actions' => [
                    'Page Access' => 'exit.termination.view.all',
                    'Create'      => 'exit.termination.create.all',
                    'Edit'        => 'exit.termination.edit.all',
                    'Delete'      => 'exit.termination.delete.all',
                ],
                'supports_data_scope' => false,
            ],
        ],
    ],
    'Holiday' => [
        'Manage Holiday' => [
            'Own Holiday' => [
                'actions' => [
                    'Page Access' => 'holiday.manage.view.own',
                ],
                'supports_data_scope' => false,
            ],
            'Employees Holiday' => [
                'actions' => [
                    'Page Access' => 'holiday.manage.view.all',
                    'Create'      => 'holiday.manage.create.all',
                    'Edit'        => 'holiday.manage.edit.all',
                    'Delete'      => 'holiday.manage.delete.all',
                ],
                'supports_data_scope' => false,
            ],
        ],
    ],
    'To-Do' => [
        'To-Do List' => [
            'Own To-Do' => [
                'actions' => [
                    'Page Access' => 'todo.manage.view.own',
                    'Create'      => 'todo.manage.create.own',
                    'Edit'        => 'todo.manage.edit.own',
                    'Delete'      => 'todo.manage.delete.own',
                ],
                'supports_data_scope' => false,
            ],
            'Employees To-Do' => [
                'actions' => [
                    'Page Access' => 'todo.manage.view.all',
                    'Create'      => 'todo.manage.create.all',
                    'Edit'        => 'todo.manage.edit.all',
                    'Delete'      => 'todo.manage.delete.all',
                ],
                'supports_data_scope' => false,
            ],
        ],
    ],
    'Notice' => [
        'Manage Notice' => [
            'Own Notice' => [
                'actions' => [
                    'Page Access' => 'notice.manage.view.own',
                ],
                'supports_data_scope' => false,
            ],
            'Employees Notice' => [
                'actions' => [
                    'Page Access' => 'notice.manage.view.all',
                    'Create'      => 'notice.manage.create.all',
                    'Edit'        => 'notice.manage.edit.all',
                    'Delete'      => 'notice.manage.delete.all',
                ],
                'supports_data_scope' => false,
            ],
        ],
    ],
    'HRM System Setup' => [
        'HRM System Setup' => [
            'Setup' => [
                'actions' => [
                    'Page Access' => 'setup.hrm.view.all',
                    'Create'      => 'setup.hrm.create.all',
                    'Edit'        => 'setup.hrm.edit.all',
                    'Delete'      => 'setup.hrm.delete.all',
                ],
                'supports_data_scope' => false,
            ],
        ],
    ],
];
