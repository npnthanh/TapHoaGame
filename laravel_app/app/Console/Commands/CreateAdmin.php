<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\{DB, Validator};
use Illuminate\Validation\Rules\Password;

class CreateAdmin extends Command
{
    protected $signature = 'shop:admin {--email= : Email quản trị} {--name= : Tên hiển thị}';
    protected $description = 'Tạo tài khoản quản trị mới; mật khẩu được nhập ẩn, không có mật khẩu mặc định';

    public function handle(): int
    {
        $data = ['name' => $this->option('name') ?: $this->ask('Tên quản trị'), 'email' => $this->option('email') ?: $this->ask('Email'), 'password' => $this->secret('Mật khẩu (tối thiểu 12 ký tự, gồm chữ, số, ký tự đặc biệt)'), 'password_confirmation' => $this->secret('Nhập lại mật khẩu')];
        $validator = Validator::make($data, ['name' => 'required|string|max:100', 'email' => 'required|email|max:255|unique:users,email', 'password' => ['required', 'confirmed', Password::min(12)->letters()->numbers()->symbols()]]);
        if ($validator->fails()) { foreach ($validator->errors()->all() as $error) $this->error($error); return self::FAILURE; }
        DB::transaction(function () use ($data) {
            $user = User::create(collect($data)->only('name', 'email', 'password')->all());
            $user->forceFill(['role' => 'admin', 'email_verified_at' => now()])->save();
            $user->wallet()->create();
        });
        $this->info('Đã tạo quản trị viên. Đăng nhập tại /admin/login rồi mở /admin.');
        return self::SUCCESS;
    }
}
