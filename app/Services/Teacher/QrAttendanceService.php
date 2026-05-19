<?php

namespace App\Services\Teacher;

use App\Models\EAttendance;
use App\Models\EAttendanceControl;
use App\Models\ESubjectSchedule;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * QR-based attendance flow. Port of Yii2 TeacherController::actionQrAttendance/Poll/Save.
 *
 * Session storage: Laravel Cache (Redis if configured, else file/array).
 * Anti-screenshot: rotating code = md5(token + secret + floor(now/ROTATION_SECONDS)).
 */
class QrAttendanceService
{
    private const SESSION_TTL_SECONDS = 86400;
    private const ROTATION_SECONDS = 8;
    private const TRAINING_TYPE_LECTURE = '11';
    private const DEFAULT_LOAD_HOURS = 2;

    /**
     * Open a new QR session for a today's lesson, or refresh an existing one.
     *
     * @return array<string, mixed>
     */
    public function start(int $employeeId, int $scheduleId): array
    {
        $schedule = $this->loadOwnedSchedule($employeeId, $scheduleId);

        $today = Carbon::today()->toDateString();
        $lessonDate = $schedule->lesson_date instanceof \DateTimeInterface
            ? $schedule->lesson_date->format('Y-m-d')
            : substr((string) $schedule->lesson_date, 0, 10);

        if ($lessonDate !== $today) {
            abort(403, "QR davomat faqat bugungi dars uchun ochiq");
        }

        if ($this->attendanceAlreadySaved($schedule)) {
            abort(409, 'Bu dars uchun davomat allaqachon saqlangan');
        }

        $relatedGroupIds = $this->relatedGroupIds($schedule, $lessonDate);

        $token = $this->buildToken($schedule, $lessonDate, $employeeId);
        $meta = $this->readMeta($token);

        $secret = $meta['secret'] ?? Str::random(32);

        $meta = [
            'schedule_id'    => $schedule->id,
            'lesson_date'    => $lessonDate,
            'employee_id'    => $employeeId,
            'education_year' => $schedule->_education_year,
            'semester'       => $schedule->_semester,
            'subject'        => $schedule->_subject,
            'curriculum'     => $schedule->_curriculum ?? null,
            'lesson_pair'    => $schedule->_lesson_pair,
            'auditorium'     => $schedule->_auditorium ?? null,
            'training_type'  => $schedule->_training_type,
            'group'          => $schedule->_group,
            'group_ids'      => $relatedGroupIds,
            'load'           => self::DEFAULT_LOAD_HOURS,
            'secret'         => $secret,
        ];

        $this->writeMeta($token, $meta);

        // Ensure students set exists with current TTL (preserves prior scans if any).
        $this->writeStudents($token, $this->readStudents($token));

        return [
            'token'      => $token,
            'qr_data'    => $this->buildQrPayload($token, $secret),
            'expires_in' => $this->secondsUntilNextRotation(),
            'lesson'     => [
                'schedule_id'   => $schedule->id,
                'subject_id'    => $schedule->_subject,
                'group_id'      => $schedule->_group,
                'group_ids'     => $relatedGroupIds,
                'lesson_pair'   => $schedule->_lesson_pair,
                'lesson_date'   => $lessonDate,
                'training_type' => $schedule->_training_type,
            ],
            'students'   => $this->readStudents($token),
        ];
    }

    /**
     * Polling endpoint — fresh rotating QR + current scanned students.
     *
     * @return array<string, mixed>
     */
    public function poll(int $employeeId, string $token): array
    {
        $meta = $this->readMeta($token);

        if (!$meta || (int) ($meta['employee_id'] ?? 0) !== $employeeId) {
            abort(404, 'QR sessiyasi topilmadi');
        }

        return [
            'students'   => $this->readStudents($token),
            'qr_data'    => $this->buildQrPayload($token, $meta['secret']),
            'expires_in' => $this->secondsUntilNextRotation(),
        ];
    }

    /**
     * Student-side check-in. Validates rotating code, then adds student id to the set.
     *
     * @return array{success: bool, message: string}
     */
    public function checkIn(int $studentId, string $token, string $code): array
    {
        $meta = $this->readMeta($token);

        if (!$meta) {
            abort(404, 'QR sessiyasi topilmadi yoki muddati o\'tgan');
        }

        if (!hash_equals($this->rotatingCode($token, (string) $meta['secret'], time()), $code)
            && !hash_equals($this->rotatingCode($token, (string) $meta['secret'], time() - self::ROTATION_SECONDS), $code)
        ) {
            abort(403, 'QR kod muddati o\'tgan, qaytadan urinib ko\'ring');
        }

        if (!$this->studentBelongsToLesson($studentId, $meta)) {
            abort(403, 'Bu dars sizning fan-guruhingizga tegishli emas');
        }

        $students = $this->readStudents($token);
        $alreadyIn = in_array($studentId, $students, true);

        if (!$alreadyIn) {
            $students[] = $studentId;
            $this->writeStudents($token, $students);
        }

        return [
            'success' => true,
            'message' => $alreadyIn ? 'Davomat avval belgilangan' : 'Davomat qabul qilindi',
        ];
    }

    /**
     * Finalize attendance: create EAttendanceControl + absences for non-scanners.
     * `absences` is an explicit map of {student_id: absent_hours}; QR-scanned students
     * are treated as present and skipped here unless explicitly overridden.
     *
     * @param array<int, int> $absences Map studentId => absent hours (1..load)
     * @return array<string, mixed>
     */
    public function save(int $employeeId, int $scheduleId, int $topicId, ?int $load, array $absences): array
    {
        $schedule = $this->loadOwnedSchedule($employeeId, $scheduleId);

        if ($this->attendanceAlreadySaved($schedule)) {
            abort(409, 'Bu dars uchun davomat allaqachon saqlangan');
        }

        $loadHours = $load !== null && $load > 0 ? min($load, 6) : self::DEFAULT_LOAD_HOURS;

        return DB::transaction(function () use ($schedule, $employeeId, $topicId, $loadHours, $absences) {
            $lessonDate = $schedule->lesson_date instanceof \DateTimeInterface
                ? $schedule->lesson_date->format('Y-m-d')
                : (string) $schedule->lesson_date;

            $control = EAttendanceControl::create([
                '_subject_schedule' => $schedule->id,
                '_group'            => $schedule->_group,
                '_education_year'   => $schedule->_education_year,
                '_semester'         => $schedule->_semester,
                '_subject'          => $schedule->_subject,
                '_training_type'    => $schedule->_training_type,
                '_employee'         => $employeeId,
                '_lesson_pair'      => $schedule->_lesson_pair,
                'lesson_date'       => $lessonDate,
                'load'              => $loadHours,
                'active'            => true,
            ]);

            // If lecture: propagate control row to peer schedules sharing date/pair/teacher.
            if ((string) $schedule->_training_type === self::TRAINING_TYPE_LECTURE) {
                $this->propagateLectureControls($schedule, $employeeId, $loadHours, $lessonDate);
            }

            $created = 0;
            foreach ($absences as $studentId => $hours) {
                $hours = (int) $hours;
                if ($hours < 1 || $hours > $loadHours) {
                    continue;
                }

                $studentId = (int) $studentId;

                $studentGroup = (string) $schedule->_training_type === self::TRAINING_TYPE_LECTURE
                    ? $this->lookupStudentGroup($studentId, $schedule) ?? $schedule->_group
                    : $schedule->_group;

                EAttendance::updateOrCreate(
                    [
                        '_student'       => $studentId,
                        '_semester'      => $schedule->_semester,
                        '_subject'       => $schedule->_subject,
                        '_training_type' => $schedule->_training_type,
                        '_lesson_pair'   => $schedule->_lesson_pair,
                        'lesson_date'    => $lessonDate,
                    ],
                    [
                        '_subject_schedule' => $schedule->id,
                        '_education_year'   => $schedule->_education_year,
                        '_employee'         => $employeeId,
                        '_group'            => $studentGroup,
                        'absent_off'        => $hours,
                        'absent_on'         => 0,
                        'active'            => true,
                    ]
                );
                $created++;
            }

            $token = $this->buildToken($schedule, $lessonDate, $employeeId);
            Cache::forget($this->metaKey($token));
            Cache::forget($this->studentsKey($token));

            DB::table('e_subject_schedule')
                ->where('id', $schedule->id)
                ->update(['_subject_topic' => $topicId, 'updated_at' => Carbon::now()]);

            return [
                'control_id'      => $control->id,
                'absences_marked' => $created,
                'topic_id'        => $topicId,
                'load'            => $loadHours,
            ];
        });
    }

    private function loadOwnedSchedule(int $employeeId, int $scheduleId): ESubjectSchedule
    {
        $schedule = ESubjectSchedule::query()
            ->where('id', $scheduleId)
            ->where('_employee', $employeeId)
            ->first();

        if ($schedule === null) {
            abort(404, 'Dars topilmadi yoki sizga tegishli emas');
        }

        return $schedule;
    }

    private function attendanceAlreadySaved(ESubjectSchedule $schedule): bool
    {
        return EAttendanceControl::query()
            ->where('_subject_schedule', $schedule->id)
            ->where('_employee', $schedule->_employee)
            ->exists();
    }

    /**
     * For lectures: include all schedules sharing same subject/date/pair/teacher (multi-group lecture).
     *
     * @return int[]
     */
    private function relatedGroupIds(ESubjectSchedule $schedule, string $lessonDate): array
    {
        if ((string) $schedule->_training_type !== self::TRAINING_TYPE_LECTURE) {
            return [$schedule->_group];
        }

        $ids = ESubjectSchedule::query()
            ->where('_education_year', $schedule->_education_year)
            ->where('_semester', $schedule->_semester)
            ->where('_subject', $schedule->_subject)
            ->where('_training_type', $schedule->_training_type)
            ->where('_employee', $schedule->_employee)
            ->where('_lesson_pair', $schedule->_lesson_pair)
            ->whereDate('lesson_date', $lessonDate)
            ->where('active', true)
            ->distinct()
            ->pluck('_group')
            ->map(fn($v) => (int) $v)
            ->all();

        return $ids ?: [$schedule->_group];
    }

    private function propagateLectureControls(ESubjectSchedule $schedule, int $employeeId, int $load, string $lessonDate): void
    {
        $peers = ESubjectSchedule::query()
            ->where('_subject', $schedule->_subject)
            ->where('_training_type', $schedule->_training_type)
            ->where('_employee', $employeeId)
            ->where('_lesson_pair', $schedule->_lesson_pair)
            ->whereDate('lesson_date', $lessonDate)
            ->where('id', '!=', $schedule->id)
            ->get();

        foreach ($peers as $peer) {
            $exists = EAttendanceControl::query()
                ->where('_subject_schedule', $peer->id)
                ->where('_employee', $employeeId)
                ->exists();

            if ($exists) {
                continue;
            }

            EAttendanceControl::create([
                '_subject_schedule' => $peer->id,
                '_group'            => $peer->_group,
                '_education_year'   => $peer->_education_year,
                '_semester'         => $peer->_semester,
                '_subject'          => $peer->_subject,
                '_training_type'    => $peer->_training_type,
                '_employee'         => $employeeId,
                '_lesson_pair'      => $peer->_lesson_pair,
                'lesson_date'       => $lessonDate,
                'load'              => $load,
                'active'            => true,
            ]);
        }
    }

    private function lookupStudentGroup(int $studentId, ESubjectSchedule $schedule): ?int
    {
        $group = DB::table('e_student_meta')
            ->where('_student', $studentId)
            ->where('_semestr', $schedule->_semester)
            ->where('_education_year', $schedule->_education_year)
            ->where('active', true)
            ->value('_group');

        return $group !== null ? (int) $group : null;
    }

    private function buildToken(ESubjectSchedule $schedule, string $lessonDate, int $employeeId): string
    {
        if ((string) $schedule->_training_type === self::TRAINING_TYPE_LECTURE) {
            return md5(implode('_', [
                $schedule->_subject,
                $schedule->_training_type,
                $lessonDate,
                $schedule->_lesson_pair,
                $schedule->_education_year,
                $schedule->_semester,
                $schedule->_auditorium ?? '0',
                $employeeId,
            ]));
        }

        return md5($schedule->id . '_' . $lessonDate . '_' . $employeeId);
    }

    private function buildQrPayload(string $token, string $secret): string
    {
        return json_encode([
            'action' => 'attendance',
            'token'  => $token,
            'code'   => $this->rotatingCode($token, $secret, time()),
        ], JSON_UNESCAPED_SLASHES);
    }

    private function rotatingCode(string $token, string $secret, int $now): string
    {
        $slot = intdiv($now, self::ROTATION_SECONDS);

        return md5($token . '_' . $secret . '_' . $slot);
    }

    private function secondsUntilNextRotation(): int
    {
        $remainder = time() % self::ROTATION_SECONDS;

        return self::ROTATION_SECONDS - $remainder;
    }

    /**
     * @param array<string, mixed> $meta
     */
    private function studentBelongsToLesson(int $studentId, array $meta): bool
    {
        $groupIds = $meta['group_ids'] ?? [$meta['group'] ?? null];
        $groupIds = array_filter(array_map('intval', (array) $groupIds));

        if (empty($groupIds)) {
            return false;
        }

        return DB::table('e_student_meta')
            ->where('_student', $studentId)
            ->whereIn('_group', $groupIds)
            ->where('_semestr', $meta['semester'])
            ->where('_education_year', $meta['education_year'])
            ->where('active', true)
            ->exists();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function readMeta(string $token): ?array
    {
        $raw = Cache::get($this->metaKey($token));

        return is_array($raw) ? $raw : null;
    }

    /**
     * @param array<string, mixed> $meta
     */
    private function writeMeta(string $token, array $meta): void
    {
        Cache::put($this->metaKey($token), $meta, self::SESSION_TTL_SECONDS);
    }

    /**
     * @return int[]
     */
    private function readStudents(string $token): array
    {
        $raw = Cache::get($this->studentsKey($token), []);

        return array_values(array_map('intval', is_array($raw) ? $raw : []));
    }

    /**
     * @param int[] $studentIds
     */
    private function writeStudents(string $token, array $studentIds): void
    {
        Cache::put($this->studentsKey($token), array_values(array_unique(array_map('intval', $studentIds))), self::SESSION_TTL_SECONDS);
    }

    private function metaKey(string $token): string
    {
        return 'qr_attendance:' . $token . ':meta';
    }

    private function studentsKey(string $token): string
    {
        return 'qr_attendance:' . $token . ':students';
    }
}
