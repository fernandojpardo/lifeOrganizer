<?php

namespace App\Services;

use App\Models\Category;

class CategorySuggester
{
    private static array $map = [
        'food'          => ['mercado', 'supermercado', 'walmart', 'costco', 'restaurant', 'mc', 'burger', 'pizza', 'sushi', 'comida', 'cafe', 'starbucks', 'delivery', 'uber eats', 'rappi', 'didi food', 'grocery', 'tienda', 'panaderia', 'taqueria', 'tacos', 'lunch', 'dinner', 'breakfast', 'dominos', 'kfc', 'subway'],
        'transport'     => ['uber', 'didi', 'lyft', 'cabify', 'taxi', 'gasolina', 'gas station', 'pemex', 'shell', 'metro', 'autobus', 'bus', 'parking', 'peaje', 'toll', 'estacionamiento', 'gasolinera', 'boleto', 'transporte', 'vuelo', 'aerolinea', 'flight', 'airline'],
        'utilities'     => ['electric', 'electricity', 'agua', 'water', 'internet', 'telefono', 'phone', 'telmex', 'telcel', 'at&t', 'totalplay', 'izzi', 'megacable', 'cable', 'gas natural', 'cfe', 'luz', 'factura', 'servicio'],
        'health'        => ['farmacia', 'hospital', 'doctor', 'dentist', 'medico', 'clinica', 'pharmacy', 'cvs', 'walgreens', 'laboratorio', 'consulta', 'medicina', 'salud', 'gym', 'gimnasio'],
        'education'     => ['universidad', 'colegio', 'escuela', 'school', 'curso', 'udemy', 'coursera', 'libro', 'book', 'amazon books', 'libreria', 'maestria', 'colegiatura', 'inscripcion', 'tuition'],
        'entertainment' => ['netflix', 'spotify', 'cine', 'cinema', 'disney', 'steam', 'playstation', 'xbox', 'amazon prime', 'hbo', 'apple tv', 'youtube', 'twitch', 'juego', 'game', 'concierto', 'teatro', 'evento'],
        'salary'        => ['nomina', 'salario', 'salary', 'payroll', 'sueldo', 'quincena', 'pago de nomina', 'deposito nomina'],
        'freelance'     => ['freelance', 'proyecto', 'honorarios', 'cliente', 'invoice', 'factura cobrada', 'pago proyecto', 'consultoria'],
        'investment'    => ['bolsa', 'gbm', 'cetes', 'inversion', 'dividendo', 'crypto', 'bitcoin', 'ethereum', 'fondo', 'acciones', 'rendimiento', 'interest', 'interes', 'bursatil'],
    ];

    public static function suggest(string $description): ?array
    {
        if (empty(trim($description))) {
            return null;
        }

        $lower = mb_strtolower($description);

        foreach (self::$map as $slug => $keywords) {
            foreach ($keywords as $kw) {
                if (str_contains($lower, $kw)) {
                    $category = Category::where('slug', $slug)->first();
                    if ($category) {
                        return ['category_id' => $category->id, 'slug' => $slug, 'name' => $category->name];
                    }
                }
            }
        }

        return null;
    }
}
