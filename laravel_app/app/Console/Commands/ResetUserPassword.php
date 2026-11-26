<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\{DB, Validator};
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class ResetUserPassword extends Command
{
    protected $signature='shop:password {email}';
    protected $description='Đặt lại mật khẩu người dùng qua SSH với nhập ẩn và hủy các phiên hiện tại';
    public function handle(): int
    {
        $user=User::where('email',$this->argument('email'))->first();
        if(!$user){$this->error('Không tìm thấy tài khoản.');return self::FAILURE;}
        $data=['password'=>$this->secret('Mật khẩu mới (12+ ký tự, có chữ, số, ký tự đặc biệt)'),'password_confirmation'=>$this->secret('Nhập lại mật khẩu')];
        $validator=Validator::make($data,['password'=>['required','confirmed',Password::min(12)->letters()->numbers()->symbols()]]);
        if($validator->fails()){foreach($validator->errors()->all() as $error)$this->error($error);return self::FAILURE;}
        DB::transaction(function()use($user,$data){$user->forceFill(['password'=>$data['password'],'remember_token'=>Str::random(60)])->save();DB::table('sessions')->where('user_id',$user->id)->delete();});
        $this->info('Đã đổi mật khẩu.'); return self::SUCCESS;
    }
}
