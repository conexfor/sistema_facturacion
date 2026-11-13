<?php

namespace Tests\Feature;

use App\Support\DatabaseUnavailable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Route;
use PDOException;
use RuntimeException;
use Tests\TestCase;

class DatabaseUnavailableTest extends TestCase
{
    private function errorConexion(int $codigo = 1130): QueryException
    {
        return new QueryException(
            'mysql',
            'select * from `sessions` where `id` = ? limit 1',
            [],
            new PDOException("SQLSTATE[HY000] [{$codigo}] Host 'localhost' is not allowed to connect to this MariaDB server"),
        );
    }

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware('web')->match(['get', 'post'], '/_test/db-caida', fn () => throw $this->errorConexion());
        Route::middleware('web')->get('/_test/consulta-erronea', fn () => throw new QueryException(
            'mysql', 'select * from no_existe', [], new PDOException("SQLSTATE[42S02]: Base table or view not found: 1146 Table 'no_existe' doesn't exist"),
        ));
    }

    public function test_detecta_solo_errores_de_conexion(): void
    {
        $this->assertSame(1130, DatabaseUnavailable::codigo($this->errorConexion()));
        $this->assertSame(2002, DatabaseUnavailable::codigo($this->errorConexion(2002)));
        $this->assertNull(DatabaseUnavailable::codigo(new RuntimeException('otra cosa')));
        $this->assertNull(DatabaseUnavailable::codigo(new PDOException("SQLSTATE[42S02]: Base table or view not found: 1146")));
    }

    public function test_muestra_pagina_amigable_sin_detalles_sql(): void
    {
        $this->get('/_test/db-caida')
            ->assertStatus(503)
            ->assertSee('Sistema no disponible')
            ->assertSee(url('/login'))
            ->assertDontSee('SQLSTATE')
            ->assertDontSee('select * from');
    }

    public function test_peticion_inertia_no_sale_de_la_pantalla(): void
    {
        // POST: p. ej. cobrar en el POS mientras la base de datos cae. No se
        // redirige (se perdería el carrito): el frontend muestra un aviso.
        $this->post('/_test/db-caida', [], ['X-Inertia' => 'true'])
            ->assertStatus(503)
            ->assertHeader('X-Database-Unavailable', '1')
            ->assertHeaderMissing('X-Inertia-Location')
            ->assertJsonPath('message', fn ($m) => str_contains($m, 'Sin conexión con la base de datos'));
    }

    public function test_peticion_json_recibe_503(): void
    {
        $this->getJson('/_test/db-caida')
            ->assertStatus(503)
            ->assertJsonMissing(['exception' => QueryException::class]);
    }

    public function test_errores_de_consulta_no_se_ocultan(): void
    {
        $this->get('/_test/consulta-erronea')->assertStatus(500);
    }
}
