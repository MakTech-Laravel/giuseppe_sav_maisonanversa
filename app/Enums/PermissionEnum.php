<?php

namespace App\Enums;

enum PermissionEnum: string
{
    // DASHBOARD
    case DASHBOARD_VIEW = 'dashboard.view';
    case REPOSITORY_VIEW = 'repository.view';
    case DOCUMENTATION_VIEW = 'documentation.view';

    // FILE UPLOAD
    case FILE_UPLOAD_INDEX = 'file-upload.index';
    case FILE_UPLOAD_STORE = 'file-upload.store';

    // POSTS
    case POSTS_INDEX = 'posts.index';
    case POSTS_VIEW = 'posts.view';
    case POSTS_CREATE = 'posts.create';
    case POSTS_EDIT = 'posts.edit';
    case POSTS_DELETE = 'posts.delete';
    case POSTS_PUBLISH = 'posts.publish';

    // USERS
    case USERS_INDEX = 'users.index';
    case USERS_VIEW = 'users.view';
    case USERS_CREATE = 'users.create';
    case USERS_EDIT = 'users.edit';
    case USERS_DELETE = 'users.delete';

    // ROLES
    case ROLES_INDEX = 'roles.index';
    case ROLES_VIEW = 'roles.view';
    case ROLES_CREATE = 'roles.create';
    case ROLES_EDIT = 'roles.edit';
    case ROLES_DELETE = 'roles.delete';

    // PERMISSIONS (developer-defined, read-only — listing & export only)
    case PERMISSIONS_INDEX = 'permissions.index';
    case PERMISSIONS_EXPORT = 'permissions.export';

    // SETTINGS
    case SETTINGS_INDEX = 'settings.index';
    case SETTINGS_VIEW = 'settings.view';
    case SETTINGS_CREATE = 'settings.create';
    case SETTINGS_EDIT = 'settings.edit';
    case SETTINGS_DELETE = 'settings.delete';
    case SETTINGS_IMPORT = 'settings.import';
    case SETTINGS_EXPORT = 'settings.export';
    case SETTINGS_PRINT = 'settings.print';

    // -------------------------------------------------------------------------
    // group() — returns the display group for the `group` DB column.
    // Used by PermissionSeeder and the role management UI (grouped checkboxes).
    // -------------------------------------------------------------------------
    public function group(): string
    {
        return match ($this) {
            self::DASHBOARD_VIEW,
            self::REPOSITORY_VIEW,
            self::DOCUMENTATION_VIEW => 'Dashboard',

            self::FILE_UPLOAD_INDEX,
            self::FILE_UPLOAD_STORE => 'File Upload',

            self::POSTS_INDEX,
            self::POSTS_VIEW,
            self::POSTS_CREATE,
            self::POSTS_EDIT,
            self::POSTS_DELETE,
            self::POSTS_PUBLISH => 'Posts',

            self::USERS_INDEX,
            self::USERS_VIEW,
            self::USERS_CREATE,
            self::USERS_EDIT,
            self::USERS_DELETE => 'Users',

            self::ROLES_INDEX,
            self::ROLES_VIEW,
            self::ROLES_CREATE,
            self::ROLES_EDIT,
            self::ROLES_DELETE => 'Roles',

            self::PERMISSIONS_INDEX,
            self::PERMISSIONS_EXPORT => 'Permissions',

            self::SETTINGS_INDEX,
            self::SETTINGS_VIEW,
            self::SETTINGS_CREATE,
            self::SETTINGS_EDIT,
            self::SETTINGS_DELETE,
            self::SETTINGS_IMPORT,
            self::SETTINGS_EXPORT,
            self::SETTINGS_PRINT => 'Settings',
        };
    }

    // -------------------------------------------------------------------------
    // guard() — returns the guard name for this permission.
    // Change to 'api' for Passport API-only apps.
    // -------------------------------------------------------------------------
    public function guard(): string
    {
        return GuardEnum::WEB->value; // change to 'api' for Passport
    }
}
