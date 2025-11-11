<?php
class Menu extends Model
{
    protected $fillable = ['title','route','icon','parent_id','order','required_permissions'];
    protected $casts = ['required_permissions' => 'array'];

    public function children() { return $this->hasMany(Menu::class, 'parent_id')->orderBy('order'); }
    public function parent()   { return $this->belongsTo(Menu::class, 'parent_id'); }
}
