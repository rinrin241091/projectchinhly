# Hướng dẫn sử dụng hệ thống quản lý user với phân quyền

## Tổng quan
Hệ thống đã được cấu hình với 2 cấp độ quyền:
- **Admin**: Có toàn quyền quản lý, bao gồm cả trang quản lý user
- **User**: Không thấy trang quản lý user, chỉ có thể sử dụng các chức năng cơ bản

## Cài đặt

### 1. Chạy migration
```bash
php artisan migrate
```

### 2. Chạy seeder để tạo user mẫu
```bash
php artisan db:seed
```

### 3. Thông tin đăng nhập mẫu
- **Admin**: 
  - Email: admin@example.com
  - Password: admin123
- **User**: 
  - Email: user@example.com
  - Password: user123

## Cấu trúc quyền

### Database
- Bảng `users` đã được thêm trường `role` với các giá trị:
  - `admin`: Quyền cao nhất
  - `user`: Quyền hạn chế

### Filament Resources
- **UserResource**: Chỉ admin mới thấy và sử dụng
- Các Resource khác có thể áp dụng trait `AdminOnlyResource` để giới hạn quyền

## Sử dụng

### Để thêm quyền admin cho các Resource khác
Thêm trait `AdminOnlyResource` vào Resource:

```php
use App\Traits\AdminOnlyResource;

class YourResource extends Resource
{
    use AdminOnlyResource;
    
    // ... rest of your code
}
```

### Tạo user mới
1. Đăng nhập với tài khoản admin
2. Vào menu "Quản lý người dùng"
3. Click "Tạo mới" để thêm user
4. Chọn role phù hợp (admin/user)

## Kiểm tra quyền
- User với role `user` sẽ không thấy menu "Quản lý người dùng" trong sidebar
- User với role `admin` sẽ thấy tất cả các menu và có thể quản lý user
