<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class JoraKuvandikovSeeder extends Seeder
{
    public function run(): void
    {
        DB::beginTransaction();

        try {
            $employeeId = DB::table('e_employee')->where('employee_id_number', 'EMP-JK-2024')->value('id');
            
            if (!$employeeId) {
                $employeeId = DB::table('e_employee')->insertGetId([
                    'employee_id_number' => 'EMP-JK-2024',
                    'first_name' => 'Jora',
                    'second_name' => 'Kuvandikov',
                    'third_name' => 'Rashidovich',
                    'birth_date' => '1990-05-15',
                    '_gender' => '11',
                    'passport_number' => 'AB1234567',
                    'passport_pin' => '12345678901234',
                    'telephone' => '+998901234567',
                    'email' => 'jora.kuvandikov@univer.uz',
                    'active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                
                $this->command->info("Created employee with ID: {$employeeId}");
            } else {
                $this->command->info("Employee already exists with ID: {$employeeId}");
            }

            $adminId = DB::table('e_admin')->where('login', 'jora_kuvandikov')->value('id');
            
            if (!$adminId) {
                $roleId = DB::table('e_admin_role')->where('code', 'teacher')->value('id');
                if (!$roleId) {
                    $roleId = DB::table('e_admin_role')->first()->id ?? 1;
                }
                
                $adminId = DB::table('e_admin')->insertGetId([
                    'login' => 'jora_kuvandikov',
                    '_role' => $roleId,
                    'password' => Hash::make('password'),
                    'email' => 'jora.kuvandikov@univer.uz',
                    'telephone' => '+998901234567',
                    'full_name' => 'Kuvandikov Jora Rashidovich',
                    'auth_key' => md5(random_bytes(16)),
                    'language' => 'uz-UZ',
                    'status' => 'enable',
                    '_employee' => $employeeId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                
                $this->command->info("Created admin user with ID: {$adminId}");
            } else {
                $this->command->info("Admin user already exists with ID: {$adminId}");
            }

            $departmentId = DB::table('e_department')->where('active', true)->value('id');
            
            if (!$departmentId) {
                $this->command->error('No active department found. Please create a department first.');
                DB::rollBack();
                return;
            }

            $employeeMetaId = DB::table('e_employee_meta')
                ->where('_employee', $employeeId)
                ->where('active', true)
                ->value('id');
            
            if (!$employeeMetaId) {
                $employeeType = DB::table('h_employee_type')->where('active', true)->value('code');
                $employmentForm = DB::table('h_employment_form')->where('active', true)->value('code');
                $employeeStatus = DB::table('h_employee_status')->where('active', true)->value('code');
                
                $employeeMetaId = DB::table('e_employee_meta')->insertGetId([
                    '_employee' => $employeeId,
                    '_department' => $departmentId,
                    '_employee_type' => $employeeType ?? '11',
                    '_employment_form' => $employmentForm ?? '11',
                    '_employee_status' => $employeeStatus ?? '11',
                    'employee_name' => 'Kuvandikov Jora Rashidovich',
                    'employment_date' => '2020-09-01',
                    'decree_number' => '123-б',
                    'decree_date' => '2020-09-01',
                    'position_name' => "O'qituvchi",
                    'active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                
                $this->command->info("Created employee meta with ID: {$employeeMetaId}");
            } else {
                $this->command->info("Employee meta already exists with ID: {$employeeMetaId}");
            }

            $documents = [
                [
                    'title' => 'Akademik ma\'lumotnoma - Talaba: Aliyev A.A.',
                    'type' => 'common\models\archive\EAcademicInformation',
                    'status' => 'pending',
                    'provider' => 'eduimzo',
                    'signer_type' => 'reviewer',
                    'signer_status' => 'pending',
                    'days_ago' => 1,
                ],
                [
                    'title' => 'Buyruq - Talabalarni stipendiyaga tayinlash',
                    'type' => 'common\models\academic\EDecreeInfo',
                    'status' => 'pending',
                    'provider' => 'eduimzo',
                    'signer_type' => 'approver',
                    'signer_status' => 'pending',
                    'days_ago' => 2,
                ],
                [
                    'title' => 'Akademik ma\'lumotnoma - Talaba: Karimov K.K.',
                    'type' => 'common\models\archive\EAcademicInformation',
                    'status' => 'signed',
                    'provider' => 'eduimzo',
                    'signer_type' => 'reviewer',
                    'signer_status' => 'signed',
                    'days_ago' => 3,
                ],
                [
                    'title' => 'Buyruq - O\'qituvchilarni rag\'batlantirish',
                    'type' => 'common\models\academic\EDecreeInfo',
                    'status' => 'pending',
                    'provider' => 'eduimzo',
                    'signer_type' => 'approver',
                    'signer_status' => 'pending',
                    'days_ago' => 4,
                ],
                [
                    'title' => 'Akademik ma\'lumotnoma - Talaba: Rahimov R.R.',
                    'type' => 'common\models\archive\EAcademicInformation',
                    'status' => 'signed',
                    'provider' => 'eduimzo',
                    'signer_type' => 'reviewer',
                    'signer_status' => 'signed',
                    'days_ago' => 5,
                ],
                [
                    'title' => 'Buyruq №106 - Talabalarni ko\'chirish',
                    'type' => 'common\models\academic\EDecreeInfo',
                    'status' => 'pending',
                    'provider' => 'webimzo',
                    'signer_type' => 'approver',
                    'signer_status' => 'pending',
                    'hours_ago' => 1,
                ],
                [
                    'title' => 'Buyruq №107 - Xodimlarni tayinlash',
                    'type' => 'common\models\academic\EDecreeInfo',
                    'status' => 'pending',
                    'provider' => 'webimzo',
                    'signer_type' => 'approver',
                    'signer_status' => 'pending',
                    'hours_ago' => 2,
                ],
                [
                    'title' => 'Buyruq №108 - Talabalarni chetlashtirish',
                    'type' => 'common\models\academic\EDecreeInfo',
                    'status' => 'pending',
                    'provider' => 'webimzo',
                    'signer_type' => 'approver',
                    'signer_status' => 'pending',
                    'hours_ago' => 3,
                ],
                [
                    'title' => 'Buyruq №109 - Akademik ta\'til berish',
                    'type' => 'common\models\academic\EDecreeInfo',
                    'status' => 'pending',
                    'provider' => 'webimzo',
                    'signer_type' => 'approver',
                    'signer_status' => 'pending',
                    'hours_ago' => 4,
                ],
                [
                    'title' => 'Buyruq №110 - Talabalarni qayta tiklash',
                    'type' => 'common\models\academic\EDecreeInfo',
                    'status' => 'pending',
                    'provider' => 'webimzo',
                    'signer_type' => 'approver',
                    'signer_status' => 'pending',
                    'hours_ago' => 5,
                ],
            ];

            foreach ($documents as $index => $doc) {
                $docId = $index + 1;
                $docHash = md5(uniqid() . microtime());
                
                $createdAt = isset($doc['days_ago']) 
                    ? now()->subDays($doc['days_ago'])
                    : now()->subHours($doc['hours_ago']);
                
                $documentId = DB::table('e_document')->insertGetId([
                    'hash' => $docHash,
                    'document_title' => $doc['title'],
                    'document_type' => $doc['type'],
                    'document_id' => $docId,
                    'status' => $doc['status'],
                    'provider' => $doc['provider'],
                    '_admin' => $adminId,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);

                $signedAt = $doc['signer_status'] === 'signed' 
                    ? $createdAt->addDay() 
                    : null;

                DB::table('e_document_signer')->insert([
                    '_document' => $documentId,
                    '_employee_meta' => $employeeMetaId,
                    'type' => $doc['signer_type'],
                    'employee_name' => 'Kuvandikov Jora Rashidovich',
                    'employee_position' => "O'qituvchi",
                    'priority' => 1,
                    'status' => $doc['signer_status'],
                    'signed_at' => $signedAt,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);
                
                $this->command->info("Created document {$index}: {$doc['title']}");
            }

            DB::commit();
            
            $this->command->info('===========================================');
            $this->command->info('Successfully created test data for jora_kuvandikov');
            $this->command->info("Employee ID: {$employeeId}");
            $this->command->info("Admin ID: {$adminId}");
            $this->command->info("Employee Meta ID: {$employeeMetaId}");
            $this->command->info('Created 10 sample documents with signers');
            $this->command->info('Login: jora_kuvandikov');
            $this->command->info('Password: password');
            $this->command->info('===========================================');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('Error: ' . $e->getMessage());
            throw $e;
        }
    }
}

