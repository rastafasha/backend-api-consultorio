<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait TenantScoped
{
    /**
     * El método boot del trait se ejecuta automáticamente en el modelo.
     */
    protected static function bootTenantScoped()
    {
        // Aplicamos el Scope Global a todas las consultas de forma automática
        static::addGlobalScope('tenant', function (Builder $builder) {
    
    // Verificamos si el contenedor de Laravel tiene inyectado el Slug de la clínica de MongoDB
    if (app()->has('current_clinica_slug')) {
        $clinicaSlug = app('current_clinica_slug');
        
        // El Scope filtra de forma automática usando la columna varchar de texto de tu BD en MAMP
        $builder->where($builder->getModel()->getTable() . '.clinica_slug', $clinicaSlug);
    }
});

static::creating(function (Model $model) {
    if (app()->has('current_clinica_slug') && !$model->clinica_slug) {
        $model->clinica_slug = app('current_clinica_slug');
    }
});
    }
}