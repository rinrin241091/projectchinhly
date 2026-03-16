<?php

namespace App\Filament\Pages\Auth;

use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Facades\Filament;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Filament\Notifications\Notification;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\ValidationException;

class Login extends \Filament\Pages\Auth\Login
{
    private const CAPTCHA_SESSION_KEY = 'login_captcha_text';

    public function mount(): void
    {
        if (Filament::auth()->check()) {
            redirect()->intended(Filament::getUrl());
        }

        $this->generateCaptcha();
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                $this->getEmailFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getCaptchaImageComponent(),
                $this->getCaptchaFormComponent(),
                $this->getRememberFormComponent(),
            ])
            ->statePath('data');
    }

    public function authenticate(): ?LoginResponse
    {
        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            Notification::make()
                ->title(__('filament-panels::pages/auth/login.notifications.throttled.title', [
                    'seconds' => $exception->secondsUntilAvailable,
                    'minutes' => ceil($exception->secondsUntilAvailable / 60),
                ]))
                ->body(array_key_exists('body', __('filament-panels::pages/auth/login.notifications.throttled') ?: []) ? __('filament-panels::pages/auth/login.notifications.throttled.body', [
                    'seconds' => $exception->secondsUntilAvailable,
                    'minutes' => ceil($exception->secondsUntilAvailable / 60),
                ]) : null)
                ->danger()
                ->send();

            return null;
        }

        $data = $this->form->getState();
        $captchaInput = trim((string) ($data['captcha'] ?? ''));
        $captchaExpected = (string) session(self::CAPTCHA_SESSION_KEY);

        if (($captchaInput === '') || ($captchaExpected === '') || ($captchaInput !== $captchaExpected)) {
            $this->generateCaptcha();

            throw ValidationException::withMessages([
                'data.captcha' => 'Mã captcha không đúng. Vui lòng nhập lại theo hình mới.',
            ]);
        }

        $credentials = $this->getCredentialsFromFormData($data);

        if (! Filament::auth()->validate($credentials)) {
            $this->generateCaptcha();

            throw ValidationException::withMessages([
                'data.email' => __('filament-panels::pages/auth/login.messages.failed'),
            ]);
        }

        if (! Filament::auth()->attempt($credentials, $data['remember'] ?? false)) {
            $this->generateCaptcha();

            throw ValidationException::withMessages([
                'data.email' => __('filament-panels::pages/auth/login.messages.failed'),
            ]);
        }

        session()->regenerate();

        return app(LoginResponse::class);
    }

    public function refreshCaptcha(): void
    {
        $this->generateCaptcha();
    }

    protected function getCaptchaImageComponent(): Placeholder
    {
        return Placeholder::make('captcha_image')
            ->label('Captcha')
            ->content(fn (): HtmlString => new HtmlString($this->getCaptchaImageHtml()))
            ->columnSpanFull();
    }

    protected function getCaptchaFormComponent(): TextInput
    {
        return TextInput::make('captcha')
            ->label('Nhập mã captcha (phân biệt hoa/thường)')
            ->required()
            ->autocomplete(false)
            ->helperText('Nhập đúng ký tự theo ảnh captcha, có phân biệt chữ hoa/thường.')
            ->dehydrateStateUsing(fn (?string $state): string => trim((string) $state))
            ->suffixAction(
                Action::make('refreshCaptcha')
                    ->icon('heroicon-m-arrow-path')
                    ->label('Đổi mã')
                    ->action(fn () => $this->refreshCaptcha())
            );
    }

    private function generateCaptcha(): void
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789';
        $captcha = '';

        for ($i = 0; $i < 5; $i++) {
            $captcha .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }

        session([self::CAPTCHA_SESSION_KEY => $captcha]);
    }

    private function getCaptchaImageHtml(): string
    {
        $text = (string) session(self::CAPTCHA_SESSION_KEY, 'ERROR');
        $chars = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $x = 18;
        $textSvg = '';

        foreach ($chars as $char) {
            $y = random_int(24, 34);
            $rotation = random_int(-18, 18);
            $safeChar = htmlspecialchars($char, ENT_QUOTES, 'UTF-8');
            $textSvg .= "<text x=\"{$x}\" y=\"{$y}\" transform=\"rotate({$rotation} {$x} {$y})\" fill=\"#1f2937\" font-size=\"24\" font-family=\"monospace\" font-weight=\"700\">{$safeChar}</text>";
            $x += 34;
        }

        $svg = "<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"240\" height=\"64\" viewBox=\"0 0 240 64\"><rect width=\"240\" height=\"64\" rx=\"8\" fill=\"#f8fafc\"/><path d=\"M0 40 C30 20, 60 50, 90 30 S150 20, 180 36 S220 50, 240 28\" stroke=\"#f59e0b\" stroke-width=\"2\" fill=\"none\" opacity=\"0.65\"/>{$textSvg}</svg>";
        $dataUri = 'data:image/svg+xml;base64,' . base64_encode($svg);

        return '<div style="display:flex;align-items:center;gap:.75rem;"><img src="' . $dataUri . '" alt="Captcha" style="height:64px;width:240px;border:1px solid #e5e7eb;border-radius:8px;" /><button type="button" wire:click="refreshCaptcha" style="padding:.55rem .8rem;border:1px solid #e5e7eb;border-radius:8px;background:#fff;cursor:pointer;">Đổi mã</button></div>';
    }

}
