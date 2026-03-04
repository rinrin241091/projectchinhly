<x-filament-panels::page.simple>
    <style>
        /* Ẩn sidebar khi đang chọn phông dù session cũ có tồn tại */
        .fi-sidebar-nav,
        .fi-sidebar-ctn {
            display: none !important;
        }

        /* Hồ sơ trung tâm toàn bộ cụm tiêu đề + thẻ select */
        .select-organization-wrapper {
            display: flex;
            flex-direction: column;
            align-items: center;
            transform: translateX(-15%);
        }

        @media (max-width: 1024px) {
            .select-organization-wrapper {
                transform: translateX(-6%);
            }
        }
        @media (max-width: 768px) {
            .select-organization-wrapper {
                transform: none;
            }
        }

        .select-organization-ctn {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: calc(100vh - 96px);
            padding: 1rem;
            box-sizing: border-box;
            background: #f8fafc;
        }

        .select-organization-card {
            width: 100%;
            max-width: 540px;
        }
    </style>

    <div class="select-organization-ctn">
        <div class="select-organization-wrapper">
            <div style="width:100%; max-width:1100px;">
                <h2 class="text-3xl font-bold text-center mb-8">Chọn phòng làm việc</h2>
            </div>
            <div class="bg-white p-8 rounded-2xl shadow-lg select-organization-card">
                <h1 class="text-2xl font-bold text-center mb-6">Chọn loại phông làm việc</h1>

                <form wire:submit="save" class="space-y-6">
                    {{ $this->form }}

                    <x-filament::button type="submit" class="w-full">
                        Xác nhận
                    </x-filament::button>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            try {
                document.querySelectorAll('h1,h2,.fi-page-header,.filament-page-title').forEach(function (el) {
                    if (el.closest('.select-organization-wrapper')) return;
                    if (/Chọn phông|Chọn phòng/i.test(el.textContent.trim())) {
                        el.style.display = 'none';
                    }
                });
            } catch (e) {}
        });
    </script>
</x-filament-panels::page.simple>
