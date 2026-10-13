<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_shows_required_messages(): void
    {
        $this->post('/register', [])
            ->assertSessionHasErrors([
                'name' => 'お名前を入力してください',
                'email' => 'メールアドレスを入力してください',
                'password' => 'パスワードを入力してください',
            ]);
    }

    public function test_register_shows_format_messages(): void
    {
        $this->post('/register', [
            'name' => 'テスト',
            'email' => 'invalid',
            'password' => 'short',
            'password_confirmation' => 'different',
        ])->assertSessionHasErrors([
            'email' => 'メールアドレスはメール形式で入力してください',
            'password' => 'パスワードは8文字以上で入力してください',
            'password_confirmation' => 'パスワードと一致しません',
        ]);
    }

    public function test_register_redirects_to_attendance(): void
    {
        $this->post('/register', [
            'name' => 'テスト',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect('/attendance');

        $this->assertAuthenticated();
        $this->get('/attendance')->assertOk();
    }

    public function test_login_validation_and_failure_messages(): void
    {
        $this->post('/login', [])
            ->assertSessionHasErrors([
                'email' => 'メールアドレスを入力してください',
                'password' => 'パスワードを入力してください',
            ]);

        $this->post('/login', ['email' => 'nobody@example.com', 'password' => 'password'])
            ->assertSessionHasErrors(['email' => 'ログイン情報が登録されていません']);
    }

    public function test_user_can_login_and_logout(): void
    {
        $user = User::factory()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect('/attendance');
        $this->assertAuthenticatedAs($user);

        $this->post('/logout');
        $this->assertGuest();
    }

    public function test_admin_cannot_login_from_user_login(): void
    {
        $admin = User::factory()->admin()->create();

        $this->post('/login', ['email' => $admin->email, 'password' => 'password'])
            ->assertSessionHasErrors(['email' => 'ログイン情報が登録されていません']);
    }

    public function test_admin_login_validation_and_success(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();

        $this->post('/admin/login', [])
            ->assertSessionHasErrors([
                'email' => 'メールアドレスを入力してください',
                'password' => 'パスワードを入力してください',
            ]);

        $this->post('/admin/login', ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors(['email' => 'ログイン情報が登録されていません']);

        $this->post('/admin/login', ['email' => $admin->email, 'password' => 'password'])
            ->assertRedirect('/admin/attendance/list');
        $this->assertAuthenticatedAs($admin);

        $this->post('/admin/logout')->assertRedirect('/admin/login');
        $this->assertGuest();
    }
}
