<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankAccount extends Model
{
    use HasFactory;

    protected $fillable = ['bank_name', 'account_number', 'swift', 'show_on_documents'];

    protected $casts = ['show_on_documents' => 'boolean'];
}
