<?php

namespace App\Filament\Pages;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Hash;
use Filament\Notifications\Notification;
use Filament\Actions\Action;

class ChangePassword extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-key';

    protected static string $view = 'filament.pages.change-password';

    protected static ?string $title = 'Đổi mật khẩu';

    protected static bool $shouldRegisterNavigation = false;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('current_password')
                    ->label('Mật khẩu hiện tại')
                    ->password()
                    ->required()
                    ->currentPassword(),
                TextInput::make('new_password')
                    ->label('Mật khẩu mới')
                    ->password()
                    ->required()
                    ->different('current_password')
                    ->minLength(8),
                TextInput::make('new_password_confirmation')
                    ->label('Xác nhận mật khẩu mới')
                    ->password()
                    ->required()
                    ->same('new_password'),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label(__('filament-panels::resources/pages/edit-record.form.actions.save.label'))
                ->submit('save'),
        ];
    }

    public function save(): void
    {
        $this->form->validate();

        auth()->user()->update([
            'password' => Hash::make($this->data['new_password']),
        ]);

        Notification::make()
            ->title('Mật khẩu đã được thay đổi thành công')
            ->success()
            ->send();
        
        $this->form->fill();
    }
}
