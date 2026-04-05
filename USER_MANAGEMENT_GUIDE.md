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
- Bảng pivot `organization_user` dùng để gán người dùng với từng "phông" (phòng làm
  việc). Mỗi record có thêm cột `role` (`admin`, `data_entry` = Nhân viên nhập liệu, `viewer`) chỉ rõ vai trò
  của người dùng trong phông đó – nghĩa là một user có thể là "Nhân viên nhập liệu" ở
  Phông A nhưng chỉ là "Người xem" ở Phông B.

### UI phân quyền
- Trang Quản lý phân quyền (UserResource) được thiết kế theo layout mẫu:
  - Thanh tìm kiếm tên/email
  - Bộ lọc theo phông và theo vai trò trong phông
  - Cột "Phòng làm việc" hiển thị badge màu tương ứng với role, kèm icon dấu +
  - Cột "Số phòng" hiển thị tổng số phông mà user đã được gán (có thể sắp xếp)
  - Cột "Vai trò" nằm riêng (hiển thị pivot role khi có nhiều phông)
  - Nút "+" trên mỗi dòng để gán nhanh một phông/role mới
  - Relation manager "Phông" để xem/sửa/xóa từng gán cụ thể

### Filament Resources
- **UserResource**: Chỉ admin mới thấy và sử dụng. Nó bao gồm:
  - Relation manager `OrganizationsRelationManager` để quản lý các phông và vai trò
    cho mỗi user.
  - Các bộ lọc và cột mới như mô tả ở trên.
  - Trường multi-select trong form tạo để gán phông với vai trò mặc định "Người xem".
  - Nhãn điều hướng đổi thành "Quản lý phân quyền".
- Các Resource khác tiếp tục có thể dùng trait `AdminOnlyResource` hoặc `RoleBasedPermissions`
  để kiểm soát hành vi theo quyền.


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
4. Chọn role phù hợp (admin/user)4. Nếu cần gán các phông ban đầu, chuyển qua tab “Phòng” sau khi tạo user và thêm từng phông với vai trò tương ứng.
## Kiểm tra quyền
- User với role `user` sẽ không thấy menu "Quản lý người dùng" trong sidebar
- User với role `admin` sẽ thấy tất cả các menu và có thể quản lý user
