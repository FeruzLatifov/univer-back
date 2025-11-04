<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DocumentSeeder extends Seeder
{
    public function run(): void
    {
        $adminId = DB::table('e_admin')->first()->id ?? 1;
        $employees = DB::table('e_employee_meta')->limit(5)->pluck('id')->toArray();

        $documents = [
            ['hash' => Str::random(32), 'document_title' => 'O\'quv reja tasdiqlash', 'document_type' => 'decree', 'document_id' => 1001, 'status' => 'pending', '_admin' => $adminId, 'created_at' => now()->subDays(5), 'updated_at' => now()->subDays(5)],
            ['hash' => Str::random(32), 'document_title' => 'Xodimlar attestatsiyasi', 'document_type' => 'order', 'document_id' => 2012, 'status' => 'signed', '_admin' => $adminId, 'created_at' => now()->subDays(10), 'updated_at' => now()->subDays(2)],
            ['hash' => Str::random(32), 'document_title' => 'Talabalar stipendiyasi', 'document_type' => 'decree', 'document_id' => 1002, 'status' => 'pending', '_admin' => $adminId, 'created_at' => now()->subDays(3), 'updated_at' => now()->subDays(3)],
            ['hash' => Str::random(32), 'document_title' => 'O\'qituvchilar yuklamasi', 'document_type' => 'order', 'document_id' => 2013, 'status' => 'signed', '_admin' => $adminId, 'created_at' => now()->subDays(15), 'updated_at' => now()->subDays(1)],
            ['hash' => Str::random(32), 'document_title' => 'Magistratura qabul qilish', 'document_type' => 'decree', 'document_id' => 1003, 'status' => 'rejected', '_admin' => $adminId, 'created_at' => now()->subDays(20), 'updated_at' => now()->subDays(18)],
            ['hash' => Str::random(32), 'document_title' => 'Imtihon jadvali', 'document_type' => 'order', 'document_id' => 2014, 'status' => 'pending', '_admin' => $adminId, 'created_at' => now()->subDays(2), 'updated_at' => now()->subDays(2)],
            ['hash' => Str::random(32), 'document_title' => 'Kafedra mudiri tayinlash', 'document_type' => 'decree', 'document_id' => 1004, 'status' => 'signed', '_admin' => $adminId, 'created_at' => now()->subDays(30), 'updated_at' => now()->subDays(25)],
            ['hash' => Str::random(32), 'document_title' => 'Talabalarni ko\'chirish', 'document_type' => 'decree', 'document_id' => 1005, 'status' => 'pending', '_admin' => $adminId, 'created_at' => now()->subDays(1), 'updated_at' => now()->subDays(1)],
            ['hash' => Str::random(32), 'document_title' => 'Yangi laboratoriya ochish', 'document_type' => 'order', 'document_id' => 2015, 'status' => 'signed', '_admin' => $adminId, 'created_at' => now()->subDays(45), 'updated_at' => now()->subDays(40)],
            ['hash' => Str::random(32), 'document_title' => 'Xalqaro hamkorlik', 'document_type' => 'contract', 'document_id' => 3001, 'status' => 'pending', '_admin' => $adminId, 'created_at' => now(), 'updated_at' => now()],
            ['hash' => Str::random(32), 'document_title' => 'Ilmiy loyiha', 'document_type' => 'order', 'document_id' => 2016, 'status' => 'signed', '_admin' => $adminId, 'created_at' => now()->subDays(60), 'updated_at' => now()->subDays(55)],
            ['hash' => Str::random(32), 'document_title' => 'Talabalar taqdirlash', 'document_type' => 'decree', 'document_id' => 1006, 'status' => 'pending', '_admin' => $adminId, 'created_at' => now()->subHours(12), 'updated_at' => now()->subHours(12)],
            ['hash' => Str::random(32), 'document_title' => 'Ta\'til jadvali', 'document_type' => 'order', 'document_id' => 2017, 'status' => 'signed', '_admin' => $adminId, 'created_at' => now()->subDays(90), 'updated_at' => now()->subDays(85)],
            ['hash' => Str::random(32), 'document_title' => 'Yangi o\'quv dasturi', 'document_type' => 'decree', 'document_id' => 1007, 'status' => 'pending', '_admin' => $adminId, 'created_at' => now()->subHours(6), 'updated_at' => now()->subHours(6)],
            ['hash' => Str::random(32), 'document_title' => 'Universitet nizomi', 'document_type' => 'order', 'document_id' => 2018, 'status' => 'rejected', '_admin' => $adminId, 'created_at' => now()->subDays(7), 'updated_at' => now()->subDays(5)],
        ];

        foreach ($documents as $doc) {
            $documentId = DB::table('e_document')->insertGetId($doc);
            
            if (empty($employees)) continue;
            
            $signersCount = rand(2, min(4, count($employees)));
            for ($i = 0; $i < $signersCount; $i++) {
                $employeeId = $employees[$i % count($employees)];
                $signerStatus = $doc['status'] === 'signed' ? 'signed' : (rand(0, 1) ? 'signed' : 'pending');
                $signedAt = $signerStatus === 'signed' ? now()->subDays(rand(1, 10)) : null;
                
                DB::table('e_document_signer')->insert([
                    '_document' => $documentId,
                    '_employee_meta' => $employeeId,
                    'priority' => $i + 1,
                    'status' => $signerStatus,
                    'type' => $i === 0 ? 'reviewer' : 'approver',
                    'employee_name' => 'Xodim #' . $employeeId,
                    'employee_position' => $i === 0 ? 'Tekshiruvchi' : 'Tasdiqlovchi',
                    'signed_at' => $signedAt,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $this->command->info('✅ 15 ta hujjat va imzochilar muvaffaqiyatli yaratildi!');
    }
}
