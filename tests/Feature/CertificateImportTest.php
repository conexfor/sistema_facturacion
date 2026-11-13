<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Sunat\CertificateInspector;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CertificateImportTest extends TestCase
{
    use RefreshDatabase;

    private const RUC = '20601234565';

    private function admin(): User
    {
        $this->seed();

        return User::where('email', 'admin@erp.test')->firstOrFail();
    }

    /** Certificado autofirmado (clave + cert en PEM) con el RUC en el subject. */
    private function pem(array $dn): string
    {
        $cnf = tempnam(sys_get_temp_dir(), 'cnf');
        file_put_contents($cnf, "[req]\ndistinguished_name = dn\n[dn]\n");
        $cfg = ['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA, 'config' => $cnf];

        $key = openssl_pkey_new($cfg);
        $x509 = openssl_csr_sign(openssl_csr_new($dn, $key, $cfg), null, $key, 365, $cfg);
        openssl_x509_export($x509, $cert);
        openssl_pkey_export($key, $priv, null, $cfg);
        @unlink($cnf);

        return $priv . $cert;
    }

    private function subirCertificado(User $user, string $pem)
    {
        return $this->actingAs($user)->post(route('settings.sunat.update'), [
            'sunat_modo' => 'beta',
            'certificado' => UploadedFile::fake()->createWithContent('cert.pem', $pem),
        ]);
    }

    public function test_detecta_ruc_valido_en_cualquier_campo_del_subject(): void
    {
        $info = CertificateInspector::inspect($this->pem([
            'countryName' => 'PE',
            'organizationName' => 'MI EMPRESA S.A.C.',
            'organizationalUnitName' => 'RUC:' . self::RUC,
            'commonName' => 'Juan Perez',
        ]));

        $this->assertSame(self::RUC, $info['ruc']);
        $this->assertSame('MI EMPRESA S.A.C.', $info['titular']);
        $this->assertFalse($info['vencido']);
    }

    public function test_ignora_numeros_con_digito_verificador_invalido(): void
    {
        $this->assertFalse(CertificateInspector::rucValido('20123456789'));

        $info = CertificateInspector::inspect($this->pem(['commonName' => 'Ref 20123456789']));
        $this->assertNull($info['ruc']);
    }

    public function test_sin_consulta_ruc_no_conserva_datos_de_la_empresa_anterior(): void
    {
        Storage::fake();
        config(['services.peru_api.token' => null]);
        $user = $this->admin();

        $this->subirCertificado($user, $this->pem(['serialNumber' => self::RUC, 'organizationName' => 'MI EMPRESA S.A.C.']))
            ->assertSessionHas('warning');

        $company = $user->company->fresh();
        $this->assertSame(self::RUC, $company->ruc);
        $this->assertSame('MI EMPRESA S.A.C.', $company->razon_social);
        // Los datos demo de la empresa anterior no se mezclan con los reales.
        foreach (['nombre_comercial', 'direccion', 'ubigeo', 'departamento', 'provincia', 'distrito', 'telefono', 'email'] as $campo) {
            $this->assertNull($company->{$campo}, "{$campo} debería quedar vacío");
        }

        $this->actingAs($user)->get(route('settings.sunat.edit'))
            ->assertInertia(fn ($page) => $page
                ->where('certificado.ruc', self::RUC)
                ->where('certificado.coincide', true));
    }

    public function test_mismo_ruc_conserva_datos_ingresados_a_mano(): void
    {
        Storage::fake();
        config(['services.peru_api.token' => null]);
        $user = $this->admin();
        $pem = $this->pem(['serialNumber' => self::RUC, 'organizationName' => 'MI EMPRESA S.A.C.']);

        $this->subirCertificado($user, $pem);
        $user->company->fresh()->update(['direccion' => 'JR. REAL 456', 'telefono' => '999888777']);

        // Renovación del certificado del mismo RUC: no borra lo completado a mano.
        $this->subirCertificado($user, $pem);

        $company = $user->company->fresh();
        $this->assertSame('JR. REAL 456', $company->direccion);
        $this->assertSame('999888777', $company->telefono);
    }

    public function test_subir_certificado_aplica_datos_de_sunat_automaticamente(): void
    {
        Storage::fake();
        config(['services.peru_api.token' => 'test', 'services.peru_api.cache_ttl' => 0]);
        Http::fake(['*' => Http::response([
            'razon_social' => 'MI EMPRESA S.A.C.',
            'direccion' => 'AV. LARCO 123',
            'ubigeo' => '150122',
            'departamento' => 'LIMA',
            'provincia' => 'LIMA',
            'distrito' => 'MIRAFLORES',
            'estado' => 'ACTIVO',
            'condicion' => 'HABIDO',
        ])]);

        $user = $this->admin();
        $this->subirCertificado($user, $this->pem(['serialNumber' => self::RUC, 'commonName' => 'MI EMPRESA']))
            ->assertSessionHas('success');

        $company = $user->company->fresh();
        $this->assertSame(self::RUC, $company->ruc);
        $this->assertSame('MI EMPRESA S.A.C.', $company->razon_social);
        $this->assertSame('150122', $company->ubigeo);
        $this->assertSame('MIRAFLORES', $company->distrito);
        $this->assertSame('sunat/' . self::RUC . '/cert.pem', $company->certificate_path);
        Storage::assertExists($company->certificate_path);
    }

    public function test_rechaza_archivo_sin_clave_privada(): void
    {
        Storage::fake();
        $user = $this->admin();
        $soloCert = preg_replace('/-----BEGIN PRIVATE KEY-----.+?-----END PRIVATE KEY-----\s*/s', '', $this->pem(['commonName' => 'x']));

        $this->subirCertificado($user, $soloCert)->assertSessionHasErrors('certificado');
    }
}
