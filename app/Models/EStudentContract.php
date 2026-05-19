<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Student Contract (shartnoma)
 *
 * Schema owned by Yii2 — common/models/finance/EStudentContract.php.
 * Read-only on the Laravel side for now.
 *
 * @property int $id
 * @property string|null $number
 * @property string $date
 * @property string $hash
 * @property float $summa
 * @property float $real_summa
 * @property string|null $_education_year
 * @property int $_student
 * @property int $_specialty
 * @property string $_education_type
 * @property string $_education_form
 * @property string|null $contract_status
 * @property bool $active
 */
class EStudentContract extends Model
{
    protected $table = 'e_student_contract';

    protected $casts = [
        'summa'      => 'float',
        'real_summa' => 'float',
        'active'     => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(EStudent::class, '_student', 'id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(EPaidContractFee::class, '_student_contract', 'id')
            ->where('active', true);
    }

    public function paidSum(): float
    {
        return (float) $this->payments->sum('summa');
    }
}
