<?php

namespace App\Services;

use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\{Crypt, DB, File, Hash};
use Illuminate\Support\Str;

class DemoExportService
{
    public function export(string $directory): array
    {
        if (! app()->environment('local', 'testing') || DB::getDriverName() !== 'mysql') {
            throw new \RuntimeException('Chỉ xuất demo từ môi trường local/testing dùng MySQL.');
        }
        if (is_dir($directory)) throw new \RuntimeException('Thư mục đích đã tồn tại. Chọn thư mục mới để tránh ghi đè.');
        File::ensureDirectoryExists($directory);
        $key = random_bytes(32);
        $encrypter = new Encrypter($key, config('app.cipher'));
        $sqlFile = fopen($directory.'/taphoagame-demo.sql', 'xb');
        $schemaFile = fopen($directory.'/taphoagame-schema.sql', 'xb');
        if (! $sqlFile || ! $schemaFile) throw new \RuntimeException('Không mở được file xuất SQL.');
        $header = "-- TapHoaGame demo export: ".now()->toIso8601String()."\n-- Import into a NEW empty database. Demo funds/keys are not real.\nSET NAMES utf8mb4;\nSET SESSION SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\nSET FOREIGN_KEY_CHECKS=0;\n";
        fwrite($sqlFile,$header); fwrite($schemaFile,$header);
        $credentials = []; $counts = [];
        $skipData = ['sessions','cache','cache_locks','jobs','failed_jobs','job_batches','password_reset_tokens','audit_logs'];

        try {
            DB::transaction(function () use ($sqlFile,$schemaFile,$skipData,$encrypter,&$credentials,&$counts) {
                foreach (DB::select("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'") as $tableRow) {
                    $table = array_values((array)$tableRow)[0];
                    if (! preg_match('/^[a-zA-Z0-9_]+$/D',$table)) throw new \RuntimeException('Tên bảng không được hỗ trợ.');
                    $ddl = array_values((array)DB::selectOne('SHOW CREATE TABLE `'.$table.'`'))[1];
                    $ddl = preg_replace('/ AUTO_INCREMENT=\d+/', '', $ddl);
                    $statement = "\nDROP TABLE IF EXISTS `$table`;\n$ddl;\n";
                    fwrite($sqlFile,$statement); fwrite($schemaFile,$statement);
                    $count = 0;
                    if (! in_array($table,$skipData,true)) {
                        foreach (DB::table($table)->cursor() as $record) {
                            $row=(array)$record;
                            if ($table === 'users') {
                                $password=Str::password(24);
                                $credentials[]=['email'=>$row['email'],'role'=>$row['role'],'password'=>$password];
                                $row['password']=Hash::make($password); $row['remember_token']=null; $row['last_login_at']=null;
                            }
                            if ($table === 'product_keys') $row['secret']=$encrypter->encryptString(Crypt::decryptString($row['secret']));
                            $columns=implode(',',array_map(fn($column)=>'`'.$column.'`',array_keys($row)));
                            $values=implode(',',array_map(fn($value)=>$value===null ? 'NULL' : DB::getPdo()->quote((string)$value),array_values($row)));
                            $insert="INSERT INTO `$table` ($columns) VALUES ($values);\n";
                            fwrite($sqlFile,$insert);
                            // The clean schema includes only migration bookkeeping.
                            if ($table==='migrations') fwrite($schemaFile,$insert);
                            $count++;
                        }
                    }
                    $counts[$table]=$count;
                }
            });
            fwrite($sqlFile,"\nSET FOREIGN_KEY_CHECKS=1;\n"); fwrite($schemaFile,"\nSET FOREIGN_KEY_CHECKS=1;\n");
        } finally { fclose($sqlFile); fclose($schemaFile); }

        $environment=implode("\n",[
            'APP_NAME="Tạp Hóa Game"','APP_ENV=production','APP_KEY=base64:'.base64_encode($key),
            'APP_DEBUG=false','APP_URL=https://taphoagame.kivon.tech','APP_LOCALE=vi','APP_FALLBACK_LOCALE=en',
            'DB_CONNECTION=mysql','DB_HOST=localhost','DB_PORT=3306','DB_DATABASE=HOSTINGER_DB_NAME',
            'DB_USERNAME=HOSTINGER_DB_USER','DB_PASSWORD="CHANGE_DATABASE_PASSWORD"',
            'SESSION_DRIVER=database','SESSION_LIFETIME=120','SESSION_ENCRYPT=true','SESSION_SECURE_COOKIE=true','SESSION_SAME_SITE=lax','SESSION_DOMAIN=null',
            'CACHE_STORE=database','QUEUE_CONNECTION=sync','FILESYSTEM_DISK=local',
            'LOG_CHANNEL=single','LOG_LEVEL=warning','MAIL_MAILER=log','MAIL_FROM_ADDRESS="noreply@taphoagame.kivon.tech"',
            'MAIL_FROM_NAME="Tạp Hóa Game"','SHOP_DEMO_MODE=true','PAYMENT_ENABLED=false',
            'PAYMENT_WEBHOOK_SECRET=','PAYMENT_ACCOUNT_NUMBER=','PAYMENT_PROVIDER=bank-adapter','',
        ]);
        File::put($directory.'/.env.hostinger',$environment);
        $access="THÔNG TIN TRUY CẬP BẢN DEMO HOSTINGER\nKhông upload file này vào public_html.\nCác mật khẩu local cũ không còn đúng trên bản xuất.\n\n";
        foreach($credentials as $item) $access.=$item['role'].' | '.$item['email'].' | '.$item['password']."\n";
        File::put($directory.'/ACCESS.txt',$access);
        File::put($directory.'/manifest.json',json_encode(['created_at'=>now()->toIso8601String(),'domain'=>'taphoagame.kivon.tech','mode'=>'demo','tables'=>$counts,'passwords_rotated'=>true,'inventory_reencrypted'=>true,'sql_sha256'=>hash_file('sha256',$directory.'/taphoagame-demo.sql')],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
        @chmod($directory.'/.env.hostinger',0600); @chmod($directory.'/ACCESS.txt',0600);
        return $counts;
    }
}
