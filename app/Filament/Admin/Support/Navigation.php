<?php

namespace App\Filament\Admin\Support;

final class Navigation
{
    public const GROUP_DASHBOARD = 'Dashboard';
    public const GROUP_CONTENT = 'Content';
    public const GROUP_GROWTH = 'Growth';
    public const GROUP_SYSTEM = 'System';

    public const CONTENT_SORTS = [
        'Blogs' => 1,
        'Landing Pages' => 2,
        'Testimonials' => 3,
    ];

    public const GROWTH_SORTS = [
        'Enquiries' => 1,
        'Events' => 2,
        'Webinars' => 3,
    ];

    public const SYSTEM_SORTS = [
        'Users' => 1,
        'Roles' => 2,
    ];
}
