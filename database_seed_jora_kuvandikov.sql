-- SQL Script to add test documents for user jora_kuvandikov
-- This script creates an employee, admin user, and sample documents with signers

-- Step 1: Check if employee exists, if not create
DO $$
DECLARE
    v_employee_id INTEGER;
    v_admin_id INTEGER;
    v_employee_meta_id INTEGER;
    v_department_id INTEGER;
    v_document_id INTEGER;
    v_doc_hash VARCHAR;
BEGIN

    SELECT id INTO v_employee_id FROM e_employee WHERE employee_id_number = 'EMP-JK-2024';
    
    IF v_employee_id IS NULL THEN
        INSERT INTO e_employee (
            employee_id_number, 
            first_name, 
            second_name, 
            third_name,
            birth_date,
            _gender,
            passport_number,
            passport_pin,
            telephone,
            email,
            active,
            created_at,
            updated_at
        ) VALUES (
            'EMP-JK-2024',
            'Jora',
            'Kuvandikov',
            'Rashidovich',
            '1990-05-15',
            '11',
            'AB1234567',
            '12345678901234',
            '+998901234567',
            'jora.kuvandikov@univer.uz',
            true,
            NOW(),
            NOW()
        ) RETURNING id INTO v_employee_id;
        
        RAISE NOTICE 'Created employee with ID: %', v_employee_id;
    ELSE
        RAISE NOTICE 'Employee already exists with ID: %', v_employee_id;
    END IF;

    SELECT id INTO v_admin_id FROM e_admin WHERE login = 'jora_kuvandikov';
    
    IF v_admin_id IS NULL THEN
        INSERT INTO e_admin (
            login,
            _role,
            password,
            email,
            telephone,
            full_name,
            auth_key,
            language,
            status,
            _employee,
            created_at,
            updated_at
        ) VALUES (
            'jora_kuvandikov',
            (SELECT id FROM e_admin_role WHERE code = 'teacher' LIMIT 1),
            '$2y$13$ZqVvjXGvfVXGJv8p3wMvPuKJDjPzQfKJZqVvjXGvfVXGJv8p3wMvPu',
            'jora.kuvandikov@univer.uz',
            '+998901234567',
            'Kuvandikov Jora Rashidovich',
            md5(random()::text),
            'uz-UZ',
            'enable',
            v_employee_id,
            NOW(),
            NOW()
        ) RETURNING id INTO v_admin_id;
        
        RAISE NOTICE 'Created admin user with ID: %', v_admin_id;
    ELSE
        RAISE NOTICE 'Admin user already exists with ID: %', v_admin_id;
    END IF;

    SELECT id INTO v_department_id FROM e_department WHERE active = true LIMIT 1;
    
    IF v_department_id IS NULL THEN
        RAISE EXCEPTION 'No active department found. Please create a department first.';
    END IF;

    SELECT id INTO v_employee_meta_id FROM e_employee_meta WHERE _employee = v_employee_id AND active = true LIMIT 1;
    
    IF v_employee_meta_id IS NULL THEN
        INSERT INTO e_employee_meta (
            _employee,
            _department,
            _employee_type,
            _position,
            _employment_form,
            _employment_staff,
            _employee_status,
            contract_number,
            contract_date,
            decree_number,
            decree_date,
            active,
            created_at,
            updated_at
        ) VALUES (
            v_employee_id,
            v_department_id,
            '11',
            '11',
            '11',
            '11',
            '11',
            '123-к',
            '2020-09-01',
            '123-б',
            '2020-09-01',
            true,
            NOW(),
            NOW()
        ) RETURNING id INTO v_employee_meta_id;
        
        RAISE NOTICE 'Created employee meta with ID: %', v_employee_meta_id;
    ELSE
        RAISE NOTICE 'Employee meta already exists with ID: %', v_employee_meta_id;
    END IF;

    FOR i IN 1..5 LOOP
        v_doc_hash := md5(random()::text || clock_timestamp()::text);
        
        INSERT INTO e_document (
            hash,
            document_title,
            document_type,
            document_id,
            status,
            provider,
            _admin,
            created_at,
            updated_at
        ) VALUES (
            v_doc_hash,
            CASE 
                WHEN i = 1 THEN 'Akademik ma''lumotnoma - Talaba: Aliyev A.A.'
                WHEN i = 2 THEN 'Buyruq - Talabalarni stipendiyaga tayinlash'
                WHEN i = 3 THEN 'Akademik ma''lumotnoma - Talaba: Karimov K.K.'
                WHEN i = 4 THEN 'Buyruq - O''qituvchilarni rag''batlantirish'
                ELSE 'Akademik ma''lumotnoma - Talaba: Rahimov R.R.'
            END,
            CASE 
                WHEN i % 2 = 0 THEN 'common\models\academic\EDecreeInfo'
                ELSE 'common\models\archive\EAcademicInformation'
            END,
            i,
            CASE 
                WHEN i = 1 THEN 'pending'
                WHEN i = 2 THEN 'pending'
                WHEN i = 3 THEN 'signed'
                WHEN i = 4 THEN 'pending'
                ELSE 'signed'
            END,
            'eduimzo',
            v_admin_id,
            NOW() - (i || ' days')::INTERVAL,
            NOW() - (i || ' days')::INTERVAL
        ) RETURNING id INTO v_document_id;

        INSERT INTO e_document_signer (
            _document,
            _employee_meta,
            type,
            employee_name,
            employee_position,
            priority,
            status,
            signed_at,
            created_at,
            updated_at
        ) VALUES (
            v_document_id,
            v_employee_meta_id,
            CASE WHEN i % 2 = 0 THEN 'approver' ELSE 'reviewer' END,
            'Kuvandikov Jora Rashidovich',
            'O''qituvchi',
            1,
            CASE 
                WHEN i = 3 THEN 'signed'
                WHEN i = 5 THEN 'signed'
                ELSE 'pending'
            END,
            CASE 
                WHEN i = 3 THEN NOW() - (i - 1 || ' days')::INTERVAL
                WHEN i = 5 THEN NOW() - (i - 1 || ' days')::INTERVAL
                ELSE NULL
            END,
            NOW() - (i || ' days')::INTERVAL,
            NOW() - (i || ' days')::INTERVAL
        );
        
        RAISE NOTICE 'Created document % with hash: %', i, v_doc_hash;
    END LOOP;

    FOR i IN 6..10 LOOP
        v_doc_hash := md5(random()::text || clock_timestamp()::text);
        
        INSERT INTO e_document (
            hash,
            document_title,
            document_type,
            document_id,
            status,
            provider,
            _admin,
            created_at,
            updated_at
        ) VALUES (
            v_doc_hash,
            'Buyruq №' || (100 + i) || ' - ' || 
            CASE 
                WHEN i = 6 THEN 'Talabalarni ko''chirish'
                WHEN i = 7 THEN 'Xodimlarni tayinlash'
                WHEN i = 8 THEN 'Talabalarni chetlashtirish'
                WHEN i = 9 THEN 'Akademik ta''til berish'
                ELSE 'Talabalarni qayta tiklash'
            END,
            'common\models\academic\EDecreeInfo',
            i,
            'pending',
            'webimzo',
            v_admin_id,
            NOW() - (i - 5 || ' hours')::INTERVAL,
            NOW() - (i - 5 || ' hours')::INTERVAL
        ) RETURNING id INTO v_document_id;

        INSERT INTO e_document_signer (
            _document,
            _employee_meta,
            type,
            employee_name,
            employee_position,
            priority,
            status,
            signed_at,
            created_at,
            updated_at
        ) VALUES (
            v_document_id,
            v_employee_meta_id,
            'approver',
            'Kuvandikov Jora Rashidovich',
            'O''qituvchi',
            1,
            'pending',
            NULL,
            NOW() - (i - 5 || ' hours')::INTERVAL,
            NOW() - (i - 5 || ' hours')::INTERVAL
        );
        
        RAISE NOTICE 'Created recent document % with hash: %', i, v_doc_hash;
    END LOOP;

    RAISE NOTICE '===========================================';
    RAISE NOTICE 'Successfully created test data for jora_kuvandikov';
    RAISE NOTICE 'Employee ID: %', v_employee_id;
    RAISE NOTICE 'Admin ID: %', v_admin_id;
    RAISE NOTICE 'Employee Meta ID: %', v_employee_meta_id;
    RAISE NOTICE 'Created 10 sample documents with signers';
    RAISE NOTICE 'Login: jora_kuvandikov';
    RAISE NOTICE 'Password: password (hashed)';
    RAISE NOTICE '===========================================';

END $$;

-- Verify the data
SELECT 
    'Documents for jora_kuvandikov' as info,
    COUNT(*) as total_documents,
    COUNT(CASE WHEN ds.status = 'pending' THEN 1 END) as pending,
    COUNT(CASE WHEN ds.status = 'signed' THEN 1 END) as signed
FROM e_document_signer ds
JOIN e_employee_meta em ON ds._employee_meta = em.id
JOIN e_employee e ON em._employee = e.id
WHERE e.employee_id_number = 'EMP-JK-2024';

