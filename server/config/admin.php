<?php

return ['roles' => [
    'superadmin' => ['console', 'reports.view', 'reports.financial', 'users.manage', 'accounts.manage', 'tasks.inputs', 'tasks.review', 'users.view', 'tasks.view', 'audit.view', 'support.manage', 'wallet.manage', 'templates.manage', 'generation.manage', 'content.manage'],
    'reviewer' => ['console', 'tasks.view', 'tasks.inputs', 'tasks.review'],
    'finance' => ['console', 'reports.view', 'reports.financial', 'users.view', 'wallet.manage'],
    'operations' => ['console', 'reports.view', 'tasks.view', 'templates.manage', 'generation.manage', 'content.manage'],
    'support' => ['console', 'support.manage', 'users.view', 'tasks.view'],
]];
