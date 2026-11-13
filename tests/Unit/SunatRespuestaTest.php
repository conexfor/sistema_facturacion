<?php

namespace Tests\Unit;

use App\Services\Sunat\SunatRespuesta;
use Greenter\Model\Response\Error;
use PHPUnit\Framework\TestCase;

class SunatRespuestaTest extends TestCase
{
    public function test_estado_segun_codigo_del_cdr(): void
    {
        $this->assertSame('aceptado', SunatRespuesta::estadoCdr('0'));
        $this->assertSame('observado', SunatRespuesta::estadoCdr('4252'));
        $this->assertSame('rechazado', SunatRespuesta::estadoCdr('2992'));
        $this->assertSame('rechazado', SunatRespuesta::estadoCdr('3105'));
        $this->assertSame('rechazado', SunatRespuesta::estadoCdr('0109'));
        $this->assertSame('rechazado', SunatRespuesta::estadoCdr(null));
    }

    public function test_distingue_falla_de_conexion_de_rechazo(): void
    {
        $sinRespuesta = (new Error())->setCode('HTTP')->setMessage('Error Fetching http headers');
        $timeout = (new Error())->setCode('30')->setMessage('Maximum execution time of 30 seconds exceeded');
        $rechazo = (new Error())->setCode('2992')->setMessage('El XML no contiene el tag de la tasa del tributo');

        $this->assertTrue(SunatRespuesta::esFallaDeConexion($sinRespuesta));
        $this->assertTrue(SunatRespuesta::esFallaDeConexion($timeout));
        $this->assertFalse(SunatRespuesta::esFallaDeConexion($rechazo));
        $this->assertFalse(SunatRespuesta::esFallaDeConexion(null));

        $this->assertSame(SunatRespuesta::MENSAJE_SIN_RESPUESTA, SunatRespuesta::mensajeError($sinRespuesta, 'x'));
        $this->assertStringStartsWith('2992 - ', SunatRespuesta::mensajeError($rechazo, 'x'));
    }
}
