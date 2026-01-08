<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    //its database model for connect table to laravel
    protected $table = 'larastudent';
    protected $fillable = ['name','email','mobile','age'];
}
