<?php

return [
    'email' => 'El campo :attribute debe ser una dirección de correo electrónico válida.',
    'exists' => 'El valor seleccionado para :attribute no es válido.',
    'max' => [
        'array' => 'El campo :attribute no debe contener más de :max elementos.',
        'file' => 'El campo :attribute no debe ser mayor que :max kilobytes.',
        'numeric' => 'El campo :attribute no debe ser mayor que :max.',
        'string' => 'El campo :attribute no debe contener más de :max caracteres.',
    ],
    'min' => [
        'array' => 'El campo :attribute debe contener al menos :min elementos.',
        'file' => 'El campo :attribute debe ser de al menos :min kilobytes.',
        'numeric' => 'El campo :attribute debe ser al menos :min.',
        'string' => 'El campo :attribute debe contener al menos :min caracteres.',
    ],
    'regex' => 'El formato del campo :attribute no es válido.',
    'required' => 'El campo :attribute es obligatorio.',
    'size' => [
        'array' => 'El campo :attribute debe contener :size elementos.',
        'file' => 'El campo :attribute debe ser de :size kilobytes.',
        'numeric' => 'El campo :attribute debe ser :size.',
        'string' => 'El campo :attribute debe contener :size caracteres.',
    ],
    'string' => 'El campo :attribute debe ser una cadena de caracteres.',
    'uuid' => 'El campo :attribute debe ser un UUID válido.',

    'attributes' => [
        'name' => 'nombre',
        'address' => 'dirección',
        'phone' => 'teléfono',
        'nit' => 'NIT',
        'nrc' => 'NRC',
        'commercial_name' => 'nombre comercial',
        'economic_activity_code' => 'código de actividad económica',
        'establishment_type' => 'tipo de establecimiento',
        'departament_id' => 'departamento',
        'municipality_id' => 'municipio',
        'district_id' => 'distrito',
        'email' => 'correo electrónico',
        'password' => 'contraseña',
        'mh_establishment_code' => 'código de establecimiento MH',
        'mh_pos_code' => 'código de punto de venta MH',
        'own_establishment_code' => 'código propio de establecimiento',
        'own_pos_code' => 'código propio de punto de venta',
    ],
];
