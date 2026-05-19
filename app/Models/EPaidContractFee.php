<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Paid Contract Fee (kontrakt to'lovi)
 *
 * Schema owned by Yii2 — common/models/finance/EPaidContractFee.php.
 *
 * @property int $id
 * @property int|null $_student_contract
 * @property int $_student
 * @property float $summa
 * @property bool $active
 */
class EPaidContractFee extends Model
{
    protected $table = 'e_paid_contract_fee';

    protected $casts = [
        'summa'      => 'float',
        'active'     => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(EStudentContract::class, '_student_contract', 'id');
    }
}
