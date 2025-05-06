<?php
use function Pest\Faker\faker;

test('database connection works', function() {
    $db = DB::conectar();
    expect($db)->not->toBeNull();
});

test('admin user exists', function() {
    $result = DB::select(
        "SELECT * FROM usuarios WHERE nombre_usuario = ?", 
        ['admin'], 
        "s"
    );
    
    expect($result)->toHaveCount(1)
        ->and($result[0]['nombre_usuario'])->toBe('admin');
});