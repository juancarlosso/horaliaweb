<?php

return [
    'itemsPorPagina' => 10,
    'precio_mensual_por_empresa_mxn' => 150,
    'video_como_funciona_youtube' => 'https://youtu.be/TdkJnSW34to',
    'aniosAsistencia' => [2026, 2027],
    'perfilesPersonal' => [
        'administrador' => ['id' => 2, 'nombre' => 'Administrador'],
        'empleado_regular' => ['id' => 3, 'nombre' => 'Empleado Regular'],
    ],
    'entidades_federativas' => [
        'Aguascalientes', 'Baja California', 'Baja California Sur', 'Campeche', 'Chiapas',
        'Chihuahua', 'Ciudad de México', 'Coahuila de Zaragoza', 'Colima', 'Durango',
        'Estado de México', 'Guanajuato', 'Guerrero', 'Hidalgo', 'Jalisco', 'Michoacán de Ocampo',
        'Morelos', 'Nayarit', 'Nuevo León', 'Oaxaca', 'Puebla', 'Querétaro', 'Quintana Roo',
        'San Luis Potosí', 'Sinaloa', 'Sonora', 'Tabasco', 'Tamaulipas', 'Tlaxcala',
        'Veracruz de Ignacio de la Llave', 'Yucatán', 'Zacatecas',
    ],
    // Catálogo c_RegimenFiscal del Anexo 20, CFDI 4.0.
    'regimenes_fiscales' => [
        '601' => ['descripcion' => 'General de Ley Personas Morales', 'tipo_persona' => 'moral'],
        '603' => ['descripcion' => 'Personas Morales con Fines no Lucrativos', 'tipo_persona' => 'moral'],
        '605' => ['descripcion' => 'Sueldos y Salarios e Ingresos Asimilados a Salarios', 'tipo_persona' => 'fisica'],
        '606' => ['descripcion' => 'Arrendamiento', 'tipo_persona' => 'fisica'],
        '607' => ['descripcion' => 'Régimen de Enajenación o Adquisición de Bienes', 'tipo_persona' => 'fisica'],
        '608' => ['descripcion' => 'Demás ingresos', 'tipo_persona' => 'fisica'],
        '610' => ['descripcion' => 'Residentes en el Extranjero sin Establecimiento Permanente en México', 'tipo_persona' => 'ambas'],
        '611' => ['descripcion' => 'Ingresos por Dividendos (socios y accionistas)', 'tipo_persona' => 'fisica'],
        '612' => ['descripcion' => 'Personas Físicas con Actividades Empresariales y Profesionales', 'tipo_persona' => 'fisica'],
        '614' => ['descripcion' => 'Ingresos por intereses', 'tipo_persona' => 'fisica'],
        '615' => ['descripcion' => 'Régimen de los ingresos por obtención de premios', 'tipo_persona' => 'fisica'],
        '616' => ['descripcion' => 'Sin obligaciones fiscales', 'tipo_persona' => 'fisica'],
        '620' => ['descripcion' => 'Sociedades Cooperativas de Producción que optan por diferir sus ingresos', 'tipo_persona' => 'moral'],
        '621' => ['descripcion' => 'Incorporación Fiscal', 'tipo_persona' => 'fisica'],
        '622' => ['descripcion' => 'Actividades Agrícolas, Ganaderas, Silvícolas y Pesqueras', 'tipo_persona' => 'moral'],
        '623' => ['descripcion' => 'Opcional para Grupos de Sociedades', 'tipo_persona' => 'moral'],
        '624' => ['descripcion' => 'Coordinados', 'tipo_persona' => 'moral'],
        '625' => ['descripcion' => 'Régimen de las Actividades Empresariales con ingresos a través de Plataformas Tecnológicas', 'tipo_persona' => 'fisica'],
        '626' => ['descripcion' => 'Régimen Simplificado de Confianza', 'tipo_persona' => 'ambas'],
    ],
];
