# Document Manager - Permission System Overhaul

## Overview
This document describes the comprehensive permission system overhaul implemented for the Document Manager application. The system now enforces strict role-based access control at three levels: global roles, organization roles, and action-level restrictions.

## Permission Model

### Global User Roles
- **admin**: System administrator with full access to everything
- **user**: Regular user (default role)
- **data_entry**: Data input specialist role

### Organization-Level Roles (per organization/phông)
When a user is assigned to an organization, they get one of these roles:
- **admin**: Organization administrator - full control over data and member management
- **data_entry**: Nhân viên nhập liệu - can create/edit/delete data, but cannot manage members
- **viewer**: Organization viewer - read-only access, cannot create/edit/delete/import/export

## Changes Made

### 1. RoleBasedPermissions Trait
**File**: `app/Traits/RoleBasedPermissions.php`

Added four new static methods for granular permission checks:

#### `canImport(): bool`
- Returns `true` for:
  - Global admin users
  - Users with `admin` or Nhân viên nhập liệu (`data_entry`) role in the selected organization
  - Users with `data_entry` global role
- Returns `false` for:
  - Viewers (cannot import)
  - Users without organization access

#### `canExport(): bool`
- Returns `true` for:
  - Global admin users
  - Users with `admin` or Nhân viên nhập liệu (`data_entry`) role in the selected organization
  - Users with `data_entry` global role
- Returns `false` for:
  - Viewers (cannot export)
  - Users without organization access

#### `canManageMembers(): bool`
- Returns `true` for:
  - Global admin users
  - Users with `admin` role in the selected organization
- Returns `false` for:
  - Nhân viên nhập liệu (cannot add/edit/delete members)
  - Viewers (cannot manage members)

#### `canCreateStorage(): bool`
- Returns `true` for:
  - Global admin users
  - Users with `admin` role in the selected organization
- Returns `false` for:
  - Nhân viên nhập liệu (cannot create storage units)
  - Viewers (cannot create storage units)

### 2. DocumentResource
**File**: `app/Filament/Resources/DocumentResource.php`

#### Changes:
- **Import Action**: Added `->visible(fn() => static::canImport())`
  - Only admins and data-entry staff can see the import button
  - Viewers cannot import documents
  
- **Create Action**: Added `->visible(fn() => static::canCreate())`
  - Viewers cannot create documents
  
- **Edit/Delete Actions**: Added `->visible(fn() => static::canEdit(null))` and `->visible(fn() => static::canDelete(null))`
  - Viewers cannot edit or delete documents
  
- **Bulk Delete Action**: Added `->visible(fn() => static::canDelete(null))`

### 3. DocumentResource Import Page
**File**: `app/Filament/Resources/DocumentResource/Pages/ImportDocuments.php`

#### Changes:
- Added `RoleBasedPermissions` trait
- Added permission check in `mount()` method to restrict access to import page
- Added permission check in `import()` method as a safety measure
  - Shows error notification if user lacks import permission
  - Redirects to documents list if unauthorized

### 4. ArchiveRecordResource
**File**: `app/Filament/Resources/ArchiveRecordResource.php`

#### Changes:
- **Create Action**: Added `->visible(fn () => ... && static::canCreate())`
  - Only admins and data-entry staff can create archive records
  
- **Export Action**: Added `->visible(fn () => ... && static::canExport())`
  - Only admins and data-entry staff can export to Excel
  - Viewers cannot export
  
- **Edit/Delete Actions**: Added visibility checks
  - Viewers cannot edit or delete archive records
  
- **Bulk Delete Action**: Added visibility check

### 5. StorageResource
**File**: `app/Filament/Resources/StorageResource.php`

#### Changes:
- Added `RoleBasedPermissions` trait
- **Create Action**: Added `->visible(fn() => static::canCreateStorage())`
  - Only organization admins can create storage units
  - Data-entry staff and viewers cannot create storage
  
- **Edit Action**: Added `->visible(fn() => static::canEdit(null))`
- **Bulk Delete Action**: Added `->visible(fn() => static::canDelete(null))`

### 6. OrganizationsRelationManager (User Member Management)
**File**: `app/Filament/Resources/UserResource/RelationManagers/OrganizationsRelationManager.php`

#### Changes:
- **Attach Action** (Add Member): Added `->visible(fn() => RoleBasedPermissions::canManageMembers())`
  - Only organization admins can add members
  
- **Edit Action** (Change Role): Added `->visible(fn() => RoleBasedPermissions::canManageMembers())`
  - Only organization admins can change member roles
  
- **Detach Action** (Remove Member): Added `->visible(fn() => RoleBasedPermissions::canManageMembers())`
  - Only organization admins can remove members
  
- **Detach Bulk Action**: Added visibility check

### 7. Other Resources
Updated the following resources with permission checks for consistency:

#### ArchiveRecordItemResource
- Added `RoleBasedPermissions` trait
- Added `->visible()` checks to Create, Edit, Delete, and BulkDelete actions

#### BoxResource
- Added `RoleBasedPermissions` trait
- Added `->visible()` checks to Create, Edit, Delete, and BulkDelete actions

#### ShelveResource
- Added `RoleBasedPermissions` trait
- Added `->visible()` checks to Create, Edit, Delete, and BulkDelete actions

## Permission Matrix

| Action | Viewer | Nhân viên nhập liệu (data_entry) | Admin (Org) | Admin (Global) |
|--------|--------|--------|------------|---------|
| View Records | ✅ | ✅ | ✅ | ✅ |
| Create Records | ❌ | ✅ | ✅ | ✅ |
| Edit Records | ❌ | ✅ | ✅ | ✅ |
| Delete Records | ❌ | ✅ | ✅ | ✅ |
| Import Documents | ❌ | ✅ | ✅ | ✅ |
| Export Excel | ❌ | ✅ | ✅ | ✅ |
| Create Storage Units | ❌ | ❌ | ✅ | ✅ |
| Create/Edit Archive Records | ❌ | ✅ | ✅ | ✅ |
| Add Members | ❌ | ❌ | ✅ | ✅ |
| Change Member Roles | ❌ | ❌ | ✅ | ✅ |
| Remove Members | ❌ | ❌ | ✅ | ✅ |

## Functionality Summary

### For Viewers (Người xem)
- Can only **view** data across all resources
- Cannot perform any create, edit, delete, import, or export operations
- Cannot manage organization members
- Cannot create or modify storage infrastructure
- **Read-only** access mode

### For Data Entry Staff (Nhân viên nhập liệu)
- Can **create, edit, delete** documents and archive records
- Can **import** documents from CSV files
- Can **export** archive records to Excel
- **Cannot** add, edit, or remove organization members
- **Cannot** create or modify storage infrastructure (Storage, Shelves, Boxes)
- Focused on **data entry and management** only

### For Organization Admins (Quản trị viên phông)
- **Full access** to all data operations (create, edit, delete)
- Can **import** documents and **export** data
- Can **add, edit, and remove** organization members
- Can **manage storage infrastructure** (create/edit storage units, shelves, boxes)
- Can assign roles to other members within the organization
- Complete organizational control

### For Global Admins
- **System-wide access** to everything
- Can manage all organizations
- Can create and manage users
- Bypass all permission checks across the entire application

## Implementation Details

### Permission Checks Flow
1. **Action Visibility**: Before showing buttons/actions, `->visible()` checks are applied
2. **Page Access**: When accessing specific pages (e.g., import), mounted hooks check permissions
3. **Action Execution**: When actions are executed, permission checks occur as a safety measure
4. **Session Context**: All checks consider the currently selected organization (`session('selected_archival_id')`)

### Session-Based Organization Context
All permission checks use the `session('selected_archival_id')` to determine which organization context the user is operating in. This allows the same user to have different roles in different organizations.

## Testing Recommendations

Test the following scenarios:
1. **Viewer user**:
   - Verify import button is hidden
   - Verify export button is hidden
   - Verify create/edit/delete buttons are hidden
   - Verify member management is hidden

2. **Data-entry user**:
   - Verify import/export buttons are visible and functional
   - Verify create/edit/delete are visible and functional
   - Verify member management is hidden

3. **Organization Admin**:
   - Verify all data operations work
   - Verify member management buttons are visible
   - Verify storage creation is available

4. **Cross-organization**:
   - Verify user with different roles in different organizations
   - Verify permissions are correctly applied per organization

## Files Modified

```
app/Traits/RoleBasedPermissions.php
app/Filament/Resources/DocumentResource.php
app/Filament/Resources/DocumentResource/Pages/ImportDocuments.php
app/Filament/Resources/ArchiveRecordResource.php
app/Filament/Resources/StorageResource.php
app/Filament/Resources/UserResource/RelationManagers/OrganizationsRelationManager.php
app/Filament/Resources/ArchiveRecordItemResource.php
app/Filament/Resources/BoxResource.php
app/Filament/Resources/ShelveResource.php
```

## Notes

- All permission checks are non-blocking for global admins
- Permission checks leverage existing `hasOrganization()` method on User model
- Global role naming has been normalized to `data_entry`
- Multiple visibility check patterns ensure defense-in-depth approach to security
